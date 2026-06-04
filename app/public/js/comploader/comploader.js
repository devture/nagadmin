/** comploader 1.3 - BSD licensed - https://github.com/spantaleev/comploader **/

(function () {
	var registeredComponents = {},

		loadedComponents = {},
		loadedResources = {},

		componentWaiters = {},

		addToHead = function (child) {
			var head = document.head || document.getElementsByTagName('head')[0];
			head.appendChild(child);
		},

		loadStylesheets = function (stylesheets) {
			for (var idx in stylesheets) {
				var url = stylesheets[idx];

				if (url in loadedResources) {
					return;
				}

				var link = document.createElement('link');
				link.rel = "stylesheet";
				link.type = "text/css";
				link.href = url;
				addToHead(link);

				loadedResources[url] = true;
			}
		},

		loadScripts = function (scripts, callback) {
			if (scripts.length === 0) {
				callback();
				return;
			}

			var currentIdx = 0;

			var onScriptLoad = function () {
				loadedResources[scripts[currentIdx - 1]] = true;

				if (currentIdx === scripts.length) {
					callback();
				} else {
					loadNext();
				}
			};

			var loadNext = function () {
				var url = scripts[currentIdx];

				currentIdx += 1;

				if (url in loadedResources) {
					onScriptLoad();
				} else {
					var script = document.createElement('script');
					script.src = url;

					if (script.readyState) {
						//IE
						var onReadyStateChange = function () {
							//The same IE version may randomly fire "complete" or "loaded".
							if (script.readyState === 'complete' || script.readyState === 'loaded') {
								onScriptLoad();
							}
						};
						script.attachEvent('onreadystatechange', onReadyStateChange);
					} else {
						//Real browsers
						script.onload = onScriptLoad;
					}

					addToHead(script);
				}
			};

			loadNext();
		},

		objectsMerge = function (objects) {
			var result = {};

			for (var idx in objects) {
				var obj = objects[idx];
				for (var key in obj) {
					result[key] = obj[key];
				}
			}

			return result;
		},

		loadComponent = function (name, callback) {
			if (! (name in registeredComponents)) {
				throw new Error('Unknown component: ' + name);
			}

			if (name in loadedComponents) {
				callback();
				return;
			}

			if (name in componentWaiters) {
				//Some other section is already loading this.
				//Just register an additional waiter.
				componentWaiters[name].push(callback);
				return;
			}

			componentWaiters[name] = [callback];

			var configuration = registeredComponents[name];

			var onLoaded = function () {
				loadedComponents[name] = true;

				configuration.init();

				//Execute all waiter callbacks.
				//Some more may have been queued since we started.
				for (var idx in componentWaiters[name]) {
					componentWaiters[name][idx]();
				}
				delete componentWaiters[name];
			};

			comploader.load(configuration.requires, function () {
				loadStylesheets(configuration.stylesheets);
				loadScripts(configuration.scripts, onLoaded);
			});
		};

	window.comploader = {};

	comploader.register = function (name, configuration) {
		var baseConfig = {
			"requires": [],
			"scripts": [],
			"stylesheets": [],
			"init": function () { }
		};
		registeredComponents[name] = objectsMerge([baseConfig, configuration, {"name": name}]);
	};

	comploader.getConfiguration = function (name) {
		return (name in registeredComponents ? registeredComponents[name] : null);
	};

	comploader.load = function (components, callback) {
		if (typeof(components) === 'string') {
			components = [components];
		}

		if (typeof(callback) !== 'function') {
			callback = function () { };
		}

		if (components.length === 0) {
			callback();
			return;
		}

		var loadedCount = 0;

		var onComponentLoad = function () {
			loadedCount += 1;
			if (components.length === loadedCount) {
				callback();
			} else {
				loadNext();
			}
		};

		var loadNext = function () {
			loadComponent(components[loadedCount], onComponentLoad);
		};

		loadNext();
	};
})();
