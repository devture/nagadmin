interface NagadminGlobal {
	registerReactComponent<P>(name: string, component: React.ComponentType<P>): void;
}

interface Comploader {
	load(componentName: string, callback: () => void): void;
}

interface BootstrapTooltip {
	show(): void;
}

interface Window {
	Nagadmin: NagadminGlobal;
	comploader: Comploader;
	bootstrap: {
		Tooltip: {
			getOrCreateInstance(element: Element): BootstrapTooltip;
		};
	};
}
