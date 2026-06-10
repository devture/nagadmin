import * as React from 'react';

import { BounceSpinner } from './generic/widgets/BounceSpinner';
import { ApiCommunicator } from './logs/ApiCommunicator';
import { LogEntriesPoller } from './logs/LogEntriesPoller';
import { LogListTable, LogViewUrlTemplates } from './logs/LogListTable';
import { LogEntry } from './logs/types';

type DashboardLogsAppProps = {
	urls: {
		logsList: string,
		logsListIfNewerThan: string,
		logManage: string,
	} & LogViewUrlTemplates,
};

type DashboardLogsAppState = {
	logs: LogEntry[]|null,
};

class DashboardLogsApp extends React.Component<DashboardLogsAppProps, DashboardLogsAppState> {

	private communicator: ApiCommunicator;
	private poller: LogEntriesPoller;

	constructor(props: DashboardLogsAppProps) {
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
		const logs = this.state.logs;

		return <>
			{logs !== null && logs.length > 0 &&
				<div className="float-end">
					<a className="btn btn-primary btn-sm" href={this.props.urls.logManage}>
						<i className="fa-solid fa-book"></i>
						{' '}See all {logs.length} log entries
					</a>
				</div>}

			<h3>Logs</h3>

			{logs === null
				? <BounceSpinner />
				: <LogListTable
					entities={logs}
					limit={3}
					urlTemplates={{hostView: this.props.urls.hostView, serviceView: this.props.urls.serviceView}}
				/>}
		</>;
	}

}

window.Nagadmin.registerReactComponent('DashboardLogsApp', DashboardLogsApp);
