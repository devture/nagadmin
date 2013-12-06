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
				}, 10000);
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

nagadminApp.controller('HostsInfoCtrl', function ($scope, HostInfo) {
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
		$scope.hostsInfo = infoObjectsList;
		$scope.filteredHostsInfo = getFilteredHostsInfo();

		$scope.$watch('selectedHostId', function (newVal, oldVal) {
			if (newVal !== oldVal) {
				$scope.filteredHostsInfo = getFilteredHostsInfo();
			}
		});
	});
});

nagadminApp.controller('HostInfoCtrl', function ($scope, HostInfo) {
	$scope.hostInfo = null;

	HostInfo.find($scope.hostId).success(function (hostInfo) {
		$scope.hostInfo = hostInfo;
	});
});
