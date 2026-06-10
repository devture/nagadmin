import * as React from 'react';

type HelloWorldAppProps = {
	name: string,
};

class HelloWorldApp extends React.Component<HelloWorldAppProps> {

	render() {
		return <div className="alert alert-success">Hello from React, {this.props.name}!</div>;
	}

}

window.Nagadmin.registerReactComponent('HelloWorldApp', HelloWorldApp);
