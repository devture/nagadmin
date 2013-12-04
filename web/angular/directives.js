nagadminApp.directive('hostInfo', function ($timeout, HostInfoUpdaterFactory, ServiceCheckScheduler, templatePathRegistry) {
	return {
		"restrict": "E",
		"scope": {
			"entity": "=entity"
		},
		"templateUrl": templatePathRegistry.host.info,
		"link": function ($scope, $element) {
			var isBeingRechecked = false;
			var isRecheckAllRunning = false;

			var onUpdateStart = function () {
				isBeingRechecked = true;
			};

			var onUpdateEnd = function () {
				isBeingRechecked = false;
			};

			var updater = HostInfoUpdaterFactory.create($scope.entity, onUpdateStart, onUpdateEnd);

			$scope.recheckAll = function () {
				isRecheckAllRunning = true;

				ServiceCheckScheduler.scheduleAllOnHost($scope.entity.host).success(function () {
					//It could take up to `status_update_interval` seconds for the scheduled checks to propagate
					//to the status file. We should do a few updates at intervals.
					jQuery.each([6, 14, 30], function (_idx, seconds) {
						$timeout(function () {
							updater.update();
						}, seconds * 1000);
					});

					$timeout(function () {
						isRecheckAllRunning = false;
					}, 10 * 1000);
				});
			};

			$scope.isRechecking = function () {
				return (isBeingRechecked || isRecheckAllRunning);
			};

			$element.on('$destroy', function () {
				updater.stop();
			});

			updater.start();
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

nagadminApp.directive('serviceStatusBadge', function (templatePathRegistry) {
	return {
		"restrict": "E",
		"scope": {
			"entity": "=entity"
		},
		"templateUrl": templatePathRegistry.service.statusBadge
	};
});

nagadminApp.directive('serviceAddNewButton', function (ServiceCheckCommand, templatePathRegistry) {
	return {
		"restrict": "E",
		"scope": {
			"host": "=host"
		},
		"templateUrl": templatePathRegistry.service.addNewButton,
		"controller": function ($scope) {
			$scope.commands = [];

			ServiceCheckCommand.findAll().success(function (commands) {
				$scope.commands = commands;
			});
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
		"link": function (scope, $element) {
			var timeoutId = null;

			$timeout(function () {
				comploader.load("relative-time", function () {
					var $timeElement = $element.find('time');

					$timeElement.relativeTime().tooltip();

					var scheduleUpdate = function () {
						timeoutId = $timeout(function () {
							$timeElement.relativeTime();
							scheduleUpdate();
						}, 15000);
					};

					scheduleUpdate();
				});
			});

			$element.on('$destroy', function () {
				$timeout.cancel(timeoutId);
			});
		}
	};
});

nagadminApp.directive('contact', function ($timeout, templatePathRegistry) {
	return {
		"restrict": "E",
		"scope": {
			"entity": "=entity",
			"size": "=size"
		},
		"templateUrl": templatePathRegistry.contact.badge,
		"link": function ($scope, $element) {
			$timeout(function () {
				$element.find('img').tooltip();
			});
		}
	};
});
