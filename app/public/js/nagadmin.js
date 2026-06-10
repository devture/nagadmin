var Nagadmin = {
	reactComponentDefinitions: {}
};

Nagadmin.registerReactComponent = function (componentName, componentCallable) {
	Nagadmin.reactComponentDefinitions[componentName] = componentCallable;
};

Nagadmin.loadReactComponent = function (componentName, callback) {
	var comploaderDependencyName = 'react.component.' + componentName;

	comploader.load(comploaderDependencyName, function () {
		if (typeof(Nagadmin.reactComponentDefinitions[componentName]) === 'undefined') {
			throw new Error('Loaded React component via comploader (' + comploaderDependencyName + '), but did not find it registered. Does the component code call Nagadmin.registerReactComponent()?');
		}

		callback(Nagadmin.reactComponentDefinitions[componentName]);
	});
};

Nagadmin._initReact = function () {
	var initializeComponent = function () {
		var $container = $(this);

		$container.removeClass('js-react-component');

		var componentName = $container.data('component-name');
		var componentProps = $container.data('component-props');

		Nagadmin.loadReactComponent(componentName, function (componentCallable) {
			var root = ReactDOM.createRoot($container[0]);
			root.render(React.createElement(componentCallable, componentProps));
		});
	};

	$('.js-react-component').each(initializeComponent);

	// Detect new elements and instantly react-ify them.
	if (window.MutationObserver) {
		var observer = new MutationObserver(function (mutations) {
			mutations.forEach(function (mutation) {
				$(mutation.addedNodes).find('.js-react-component').each(initializeComponent);
			});
		});
		observer.observe(document, {childList: true, subtree: true});
	}
};

$(Nagadmin._initReact);

// esbuild bundles compiled with `--external:'react'` turn `import * as React from 'react';`
// into a `require('react')` call, which must return the globally-loaded React.
var require = function (pkg) {
	if (pkg === 'react') {
		return React;
	}

	throw new Error('require called with unexpected argument: ' + pkg);
};
