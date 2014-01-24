var nagadminApp = angular.module('nagadminApp', ['nagadmin.environment']);

nagadminApp.factory('HostInfo', function ($http, apiUrlRegistry) {
	return {
		"find": function (id) {
			return $http.get(apiUrlRegistry.host.info.replace('__ID__', id));
		},
		"findAll": function () {
			return $http.get(apiUrlRegistry.host.infoAll);
		}
	};
});

nagadminApp.factory('ServiceCheckCommand', function ($http, apiUrlRegistry) {
	return {
		"findAll": function () {
			var url = apiUrlRegistry.command.list.replace('__TYPE__', 'serviceCheck');
			return $http.get(url, {"cache": true});
		}
	};
});

nagadminApp.factory('LogRepository', function ($http, apiUrlRegistry) {
	return {
		"findAll": function () {
			return $http.get(apiUrlRegistry.log.listAll);
		},
		"findAllIfNewerThanId": function (lastSeenId) {
			return $http.get(apiUrlRegistry.log.listAllIfNewer.replace('__LAST_SEEN_ID__', lastSeenId));
		}
	};
});

nagadminApp.factory('LogEntriesUpdaterFactory', function ($timeout, LogRepository) {
	var LogEntriesUpdater = function (entities, callback) {
		this.entities = entities;
		this.callback = callback;
		this.running = false;
		this.timeoutId;
	};
	LogEntriesUpdater.prototype = {
		start: function () {
			var self = this;

			this.running = true;

			var scheduleUpdate = function () {
				self.timeoutId = $timeout(function () {
					var lastId = (self.entities.length !== 0 ? self.entities[0].id : 'null');

					LogRepository.findAllIfNewerThanId(lastId).success(function (entities) {
						if (entities.length !== 0) {
							self.entities= entities;
							self.callback(entities);
						}
					}).finally(function () {
						if (self.running) {
							scheduleUpdate();
						}
					});
				}, 10000, false);
			};

			scheduleUpdate();
		},

		stop: function () {
			this.running = false;
			$timeout.cancel(this.timeout);
		}
	};

	return {
		"create": function (entities, callback) {
			return new LogEntriesUpdater(entities, callback);
		}
	};
});

nagadminApp.factory('ServiceCheckScheduler', function ($http, apiUrlRegistry, csrfToken) {
	return {
		"scheduleOnHost": function (host, recheckType) {
			var url = apiUrlRegistry.host.recheckServices;
			url = url.replace('__ID__', host.id);
			url = url.replace('__RECHECK_TYPE__', recheckType);
			url = url.replace('__TOKEN__', csrfToken);
			return $http.post(url);
		}
	};
});

nagadminApp.factory('HostInfoUpdaterFactory', function ($timeout, HostInfo) {
	var HostInfoUpdater = function (hostInfo, onUpdateStart, onUpdateEnd) {
		this.hostInfo = hostInfo;
		this.onUpdateStart = onUpdateStart;
		this.onUpdateEnd = onUpdateEnd;
		this.updatingNow = false;
		this.timeoutId;
	};
	HostInfoUpdater.prototype = {
		start: function () {
			var self = this;

			var scheduleUpdate = function () {
				self.timeoutId = $timeout(function () {
					if (self.needsUpdate()) {
						self.update();
					}
					scheduleUpdate();
				}, 10000, false);
			};

			scheduleUpdate();
		},
		stop: function () {
			if (this.timeoutId) {
				$timeout.cancel(this.timeoutId);
			}
		},
		update: function () {
			var self = this;

			if (this.updatingNow) {
				return;
			}
			this.updatingNow = true;

			this.onUpdateStart();

			HostInfo.find(this.hostInfo.host.id).success(function (hostInfo) {
				self._sync(hostInfo);
			}).finally(function () {
				self.updatingNow = false;
				self.onUpdateEnd();
			});
		},
		_sync: function (hostInfo) {
			//Replacing 'within' the object, so that the changes would propagate to the original
			//(wherever it's coming from).
			//Never replace the object reference itself - that would detach it from the original.
			this.hostInfo.host = hostInfo.host;
			this.hostInfo.servicesInfo = hostInfo.servicesInfo;
		},
		needsUpdate: function () {
			var needsUpdate = false,
				timeNow = (new Date().getTime() / 1000);

			this.hostInfo.servicesInfo.forEach(function (serviceInfo) {
				var status = serviceInfo.status;

				if (status === null) {
					//Service not known to Nagios at all.
					return;
				}

				if (status.next_check < timeNow) {
					needsUpdate = true;
				}
			});

			return needsUpdate;
		}
	}

	return {
		"create": function (hostInfo, onUpdateStart, onUpdateEnd) {
			return new HostInfoUpdater(hostInfo, onUpdateStart, onUpdateEnd);
		}
	};
});

nagadminApp.controller('HostsInfoCtrl', function ($scope, HostInfo, progressiveListGrower) {
	$scope.hostsInfo = [];
	$scope.filteredHostsInfo = [];
	$scope.selectedHostId = '';

	var getFilteredHostsInfo = function () {
		return jQuery.grep($scope.hostsInfo, function (hostInfo, _idx) {
			if (!$scope.selectedHostId) {
				return true;
			}
			return (hostInfo.id == $scope.selectedHostId);
		});
	};

	HostInfo.findAll().success(function (infoObjectsList) {
		$scope.$watch('selectedHostId', function () {
			$scope.filteredHostsInfo = getFilteredHostsInfo();
		});
		$scope.$watch('hostsInfo.length', function () {
			$scope.filteredHostsInfo = getFilteredHostsInfo();
		});

		progressiveListGrower(infoObjectsList, function (partialList) {
			$scope.hostsInfo = partialList;
		});
	});
});

nagadminApp.controller('HostInfoCtrl', function ($scope, HostInfo) {
	$scope.hostInfo = null;

	HostInfo.find($scope.hostId).success(function (hostInfo) {
		$scope.hostInfo = hostInfo;
	});
});

nagadminApp.controller('LogsCtrl', function ($scope, LogRepository, LogEntriesUpdaterFactory, progressiveListGrower) {
	$scope.logs = [];

	LogRepository.findAll().success(function (logs) {
		var batchSizer = function (currentLength) {
			return (currentLength < 50 ? 10 : 50);
		};

		progressiveListGrower(logs, function (partialList) {
			$scope.logs = partialList;

			if (partialList.length === logs.length) {
				var updater = LogEntriesUpdaterFactory.create($scope.logs, function (logs) {
					$scope.logs = logs;
				});
				updater.start();
			}
		}, batchSizer);
	});
});

nagadminApp.controller('DashboardCtrl', function ($scope, LogRepository, HostInfo, LogEntriesUpdaterFactory, progressiveListGrower) {
	$scope.logs = [];
	$scope.hostsInfo = [];

	LogRepository.findAll().success(function (logs) {
		$scope.logs = logs;

		var updater = LogEntriesUpdaterFactory.create($scope.logs, function (logs) {
			$scope.logs = logs;
		});
		updater.start();
	});

	HostInfo.findAll().success(function (infoObjectsList) {
		progressiveListGrower(infoObjectsList, function (partialList) {
			$scope.hostsInfo = partialList;
		});

		$scope.hostsInfo = infoObjectsList.slice(0, 1);
	});
});

/**
 * Takes a "big" list and calls the given callback periodically
 * giving it a larger and larger part of the original list.
 *
 * The goal is to have the consumer work with a small list initially,
 * so it could render something fast, and then continue expanding that
 * until the full list length is reached.
 */
nagadminApp.factory('progressiveListGrower', function ($timeout) {
	return function (list, callback, batchSizer) {
		var totalLength = list.length,
			lastLength = null;

		if (!batchSizer) {
			batchSizer = function (currentLength) {
				//Use smaller batches initially, larger after that.
				return (currentLength < 5 ? 1 : 6);
			};
		}

		var pushList = function (currentList) {
			callback(currentList);
			lastLength = currentList.length;
		};

		//Start by pushing just one item.
		pushList(list.slice(0, 1));

		var pushIteration = function () {
			var newItemsToPush = batchSizer(lastLength);

			pushList(list.slice(0, lastLength + newItemsToPush));

			if (lastLength < totalLength) {
				$timeout(pushIteration);
			}
		};
		$timeout(pushIteration);
	};
});
