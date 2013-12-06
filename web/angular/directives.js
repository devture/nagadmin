nagadminApp.directive('hostInfo', function (HostInfoUpdaterFactory, templatePathRegistry, url_host_editFilter) {
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
			$scope.hostEditUrl = url_host_editFilter($scope.entity.host);

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
			if ($scope.entity !== null) {
				$scope.currentStateHuman = humanize_stateFilter($scope.entity.current_state);
				$scope.currentStateLabelClass = state_label_classFilter($scope.currentStateHuman);
			}
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

nagadminApp.directive('relativeTime', function ($timeout, templatePathRegistry, comploader) {
	return {
		"restrict": "E",
		"scope": {
			"timestamp": "=timestamp"
		},
		"templateUrl": templatePathRegistry.common.relativeTime,
		"link": function ($scope, $element) {
			$scope.jsTimestamp = ($scope.timestamp * 1000);

			var timeoutId = null;

			$timeout(function () {
				comploader.load("relative-time", function () {
					var $timeElement = $element.find('time');

					$timeElement.relativeTime().tooltip();

					var scheduleUpdate = function () {
						timeoutId = $timeout(function () {
							$timeElement.relativeTime();
							scheduleUpdate();
						}, 15000, false);
					};

					scheduleUpdate();
				});
			}, 0, false);

			$element.on('$destroy', function () {
				$timeout.cancel(timeoutId);
			});
		}
	};
});

nagadminApp.directive('chosenSelect', function ($timeout, comploader) {
	return {
		"restrict": "A",
		"scope": {
			"chosenDataSource": "=chosenDataSource"
		},
		"link": function ($scope, $element, attrs) {
			var initialized = false;

			var initialize = function () {
				initialize = function () { };

				$element.css('display', 'inline-block');

				comploader.load("chosen", function () {
					initialized = true;
					$timeout(function () {
						$element.chosen();
					}, 0, false);
				});
			};

			var rebuild = function () {
				$timeout(function () {
					$element.trigger('liszt:updated');
				}, 0, false);
			};

			$scope.$watch('chosenDataSource', function (newVal, oldVal) {
				if (!newVal || newVal.length == 0) {
					if (initialized) {
						rebuild();
					}
					return;
				}

				if (initialized) {
					rebuild();
				} else {
					initialize();
				}
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
		"templateUrl": templatePathRegistry.contact.badge,
		"link": function ($scope, $element) {
			$scope.avatarUrl = avatar_urlFilter($scope.entity.avatar_url, $scope.size);
			$timeout(function () {
				$element.find('img').tooltip();
			}, 0, false);
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
