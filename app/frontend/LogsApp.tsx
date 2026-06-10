import * as React from 'react';

import { ApiCommunicator } from './logs/ApiCommunicator';
import { LogEntriesPoller } from './logs/LogEntriesPoller';
import { LogListTable, LogViewUrlTemplates } from './logs/LogListTable';
import { LogEntry } from './logs/types';

type LogsAppProps = {
	urls: {
		logsList: string,
		logsListIfNewerThan: string,
	} & LogViewUrlTemplates,
};

type LogsAppState = {
	logs: LogEntry[]|null,
};

class LogsApp extends React.Component<LogsAppProps, LogsAppState> {

	private communicator: ApiCommunicator;
	private poller: LogEntriesPoller;

	constructor(props: LogsAppProps) {
		super(props);

		this.state = {logs: null};

		this.communicator = new ApiCommunicator(props.urls.logsList, props.urls.logsListIfNewerThan);

		this.poller = new LogEntriesPoller(
			this.communicator,
			() => (this.state.logs !== null && this.state.logs.length !== 0 ? this.state.logs[0].id : null),
			(entities) => this.setState({logs: entities}),
		);
	}

	componentDidMount() {
		this.communicator.fetchAll((err, entities) => {
			if (err === null && entities !== null) {
				this.setState({logs: entities});
			}
		});

		this.poller.start();
	}

	componentWillUnmount() {
		this.poller.stop();
	}

	render() {
		if (this.state.logs === null) {
			return <div className="spinner text-center">
				<div className="bounce1"></div>
				<div className="bounce2"></div>
				<div className="bounce3"></div>
			</div>;
		}

		return <LogListTable
			entities={this.state.logs}
			urlTemplates={{hostView: this.props.urls.hostView, serviceView: this.props.urls.serviceView}}
		/>;
	}

}

window.Nagadmin.registerReactComponent('LogsApp', LogsApp);
