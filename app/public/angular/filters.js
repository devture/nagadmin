nagadminApp.filter('avatar_url', function () {
	return function (url, size) {
		return url.replace('__SIZE__', size);
	};
});

nagadminApp.filter('humanize_state', function () {
	var map = {'0': 'ok', '1': 'warning', '2': 'critical', '3': 'unknown'};
	return function (state) {
		return (typeof(map[state]) === 'undefined' ? 'unknown' : map[state]);
	};
});

nagadminApp.filter('state_label_class', function () {
	var map = {'ok': 'text-bg-success', 'warning': 'text-bg-warning', 'critical': 'text-bg-danger', 'unknown': 'text-bg-secondary'};
	return function (state) {
		return map[state];
	};
});

nagadminApp.filter('url_host_view', function (apiUrlRegistry) {
	return function (entity) {
		return apiUrlRegistry.host.view.replace('__ID__', entity.id);
	};
});

nagadminApp.filter('url_service_add', function (apiUrlRegistry) {
	return function (command, host) {
		return apiUrlRegistry.service.add.replace('__COMMAND_ID__', command.id).replace('__HOST_ID__', host.id);
	};
});

nagadminApp.filter('url_service_view', function (apiUrlRegistry) {
	return function (entity) {
		return apiUrlRegistry.service.view.replace('__ID__', entity.id);
	};
});
