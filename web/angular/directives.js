nagadminApp.directive('hostInfo', function (HostInfoUpdaterFactory, templatePathRegistry, url_host_viewFilter) {
	return {
		"restrict": "E",
		"scope": {
			"entity": "=entity"
		},
		"templateUrl": templatePathRegistry.host.info,
		"link": function ($scope, $element) {
			var onUpdateStart = function () {
				$scope.isBeingRechecked = true;
			};

			var onUpdateEnd = function () {
				$scope.isBeingRechecked = false;
			};

			var updater = HostInfoUpdaterFactory.create($scope.entity, onUpdateStart, onUpdateEnd);

			$scope.isBeingRechecked = false;
			$scope.hostViewUrl = url_host_viewFilter($scope.entity.host);

			$scope.updateHostInfo = function () {
				updater.update();
			};

			$element.on('$destroy', function () {
				updater.stop();
			});

			updater.start();
		}
	};
});

nagadminApp.directive('hostsInfoSummary', function () {
	var templateHtml = '';
	templateHtml += '<span class="label label-success label-nagadmin-status label-nagadmin-success-unobstrusive">{{ ok }} ok</span>';
	templateHtml += ' ';
	templateHtml += '<span class="label label-danger label-nagadmin-status label-nagadmin-important-unobstrusive">{{ failing }} failing</span>';

	return {
		"restrict": "E",
		"scope": {
			"hostsInfo": "=hostsInfo"
		},
		"template": templateHtml,
		"link": function ($scope) {
			//We either need to deep-watch 'hostsInfo' and do the for-loops counting then,
			//or just do the counting on every $digest and have a simpler listener.
			//The latter was picked, as deep-watching 'hostsInfo' was *considered* slower (not benchmarked!).
			//We only deep-watch the much simple status object now.

			var watcher = function ($scope) {
				var hostsInfo = (angular.isArray($scope.hostsInfo) ? $scope.hostsInfo : []);
				var totalCount = 0, okCount = 0;

				for (var hostIdx in hostsInfo) {
					var hostInfo = hostsInfo[hostIdx];
					for (var serviceIdx in hostInfo.servicesInfo) {
						var status = hostInfo.servicesInfo[serviceIdx].status;

						if (status === null) {
							//Don't count unknown services at all
							continue;
						}

						totalCount += 1;

						if (status.current_state == 0) {
							okCount += 1;
						}
					}
				}

				return {"ok": okCount, "failing": (totalCount - okCount)};
			};

			$scope.$watch(watcher, function (status) {
				if (status) {
					$scope.ok = status.ok;
					$scope.failing = status.failing;
				}
			}, true);
		}
	};
});

nagadminApp.directive('hostRecheckButton', function ($timeout, $window, ServiceCheckScheduler, templatePathRegistry) {
	return {
		"restrict": "E",
		"scope": {
			"host": "=host",
			"rechecking": "=rechecking",
			"onDirty": "=onDirty"
		},
		"templateUrl": templatePathRegistry.host.recheckButton,
		"link": function ($scope) {
			var isRecheckRunning = false;

			$scope.recheck = function (recheckType) {
				isRecheckRunning = true;

				ServiceCheckScheduler.scheduleOnHost($scope.host, recheckType).success(function (data) {
					if (data.unauthorized) {
						$window.alert('Unauthorized. Reload the page and try again.');
						isRecheckRunning = false;
						return;
					}

					if (data.scheduledCount === 0) {
						//Intentional delay, to make sure we indicate that rechecking takes place
						//(in case the API returns too fast and makes the button appear to not do anything).
						$timeout(function () {
							isRecheckRunning = false;
						}, 1000);
						return;
					}

					//It could take up to `status_update_interval` seconds for the scheduled checks to propagate
					//to the status file. We should do a few updates at intervals.
					jQuery.each([6, 14, 30], function (idx, seconds) {
						$timeout(function () {
							$scope.onDirty();

							if (idx === 1) {
								//Stop the recheck indication after 14 seconds (in the middle).
								isRecheckRunning = false;
							}
						}, seconds * 1000);
					});
				}).error(function () {
					isRecheckRunning = false;
				});
			};

			$scope.isRechecking = function () {
				return ($scope.rechecking || isRecheckRunning);
			};
		}
	};
});

nagadminApp.directive('serviceListTable', function (templatePathRegistry) {
	return {
		"restrict": "E",
		"scope": {
			"host": "=host",
			"servicesInfo": "=servicesInfo"
		},
		"templateUrl": templatePathRegistry.service.listTable
	};
});

nagadminApp.directive('serviceStatusBadge', function (templatePathRegistry, humanize_stateFilter, state_label_classFilter) {
	return {
		"restrict": "E",
		"scope": {
			"entity": "=entity"
		},
		"templateUrl": templatePathRegistry.service.statusBadge,
		"link": function ($scope) {
			var createDependenciesStamp = function (entity) {
				if (entity === null) {
					return '';
				}
				return (entity.has_been_checked ? '1' : '0') + (entity.current_state) + entity.last_hard_state;
			};

			var lastDependenciesStamp = null;

			var rebuildScope = function () {
				var entity = $scope.entity;

				lastDependenciesStamp = createDependenciesStamp(entity);

				$scope.classes = '';

				if (entity === null) {
					$scope.text = 'missing';
					$scope.classes = 'label-default';
				} else {
					if (entity.has_been_checked == 1) {
						if (entity.current_state !== null) {
							var currentStateHuman = humanize_stateFilter(entity.current_state);
							$scope.text = currentStateHuman;
							$scope.classes += (' ' + state_label_classFilter(currentStateHuman));
						}

						if (entity.last_hard_state !== entity.current_state) {
							$scope.text += ' >';
						}
					} else {
						$scope.text = 'pending';
						$scope.classes = 'label-default';
					}
				}
			};

			$scope.$watch(function ($scope) {
				return createDependenciesStamp($scope.entity);
			}, rebuildScope);
		}
	};
});

nagadminApp.directive('serviceAddNewButton', function (ServiceCheckCommand, templatePathRegistry) {
	return {
		"restrict": "E",
		"scope": {
			"host": "=host"
		},
		"templateUrl": templatePathRegistry.service.addNewButton,
		"link": function ($scope) {
			$scope.commands = [];
			$scope.expanded = false;

			$scope.expand = function () {
				$scope.expanded = true;
				ServiceCheckCommand.findAll().success(function (commands) {
					$scope.commands = commands;
				});
			};
		}
	};
});

nagadminApp.directive('relativeTime', function ($timeout, $window, templatePathRegistry, comploader) {
	return {
		"restrict": "E",
		"scope": {
			"timestamp": "=timestamp"
		},
		"template": '<time data-time="{{ timestamp }}">&laquo; calculating time &raquo;</time>',
		"link": function ($scope, $element) {
			var timeoutId = null;
			var $time = $element.find('time');

			//Handles initialization & change transformations
			$scope.$watch('timestamp', function (timestampNew, timestampOld) {
				if (timestampNew) {
					$time.text($window.relativizeTime(new Date(timestampNew), new Date()));
				}
			});

			$time.on('mouseover', function () {
				$time.relativeTime().tooltip().tooltip('show');
			});

			//Periodically update relative time text
			var scheduleUpdate = function () {
				timeoutId = $timeout(function () {
					$time.relativeTime();
					scheduleUpdate();
				}, 15000, false);
			};

			scheduleUpdate();

			$element.on('$destroy', function () {
				$timeout.cancel(timeoutId);
			});
		}
	};
});

nagadminApp.directive('contact', function ($timeout, templatePathRegistry, avatar_urlFilter) {
	return {
		"restrict": "E",
		"scope": {
			"entity": "=entity",
			"size": "=size"
		},
		"template": '<img ng-src="{{ avatarUrl }}" class="img-rounded" style="border: 3px solid {{ entity.color }};" title="{{ entity.name }}" />',
		"link": function ($scope, $element) {
			$scope.avatarUrl = avatar_urlFilter($scope.entity.avatar_url, $scope.size);

			$element.on('mouseover', function () {
				//Create the tooltip on initial interaction
				$(this).find('img').tooltip().tooltip('show');
			});
		}
	};
});

nagadminApp.directive('hrefTo', function ($filter) {
	return {
		"restrict": "A",
		"scope": {
			"hrefTo": "=hrefTo",
			"hrefVia": "=hrefVia"
		},
		"link": function ($scope, $element) {
			var filter = $filter($scope.hrefVia);
			$element.attr('href', filter($scope.hrefTo));
		}
	};
});

nagadminApp.directive('logListTable', function (templatePathRegistry, url_service_viewFilter, url_host_viewFilter) {
	var generateInfoLink = function (entity) {
		if (entity.service.id) {
			return url_service_viewFilter(entity.service);
		}

		if (entity.host.id) {
			return url_host_viewFilter(entity.host);
		}

		return null;
	};

	var processEntities = function (entities, limit) {
		var entities = (limit ? entities.slice(0, limit) : entities);

		entities.forEach(function (entity) {
			entity.infoLink = generateInfoLink(entity);
		});

		return entities;
	};

	return {
		"restrict": "E",
		"scope": {
			"entities": "=entities",
			"limit": "=limit"
		},
		"templateUrl": templatePathRegistry.log.listTable,
		"link": function ($scope) {
			$scope.filteredEntities = [];

			$scope.$watch('entities', function (newVal, oldVal) {
				$scope.filteredEntities = processEntities(newVal, $scope.limit);
			});
		}
	};
});
