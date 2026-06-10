import * as React from 'react';

import { ApiCommunicator } from './hosts/ApiCommunicator';
import { HostInfoCard } from './hosts/HostInfoCard';
import { HostInfo, HostsViewUrlTemplates } from './hosts/types';

type HostServicesAppProps = {
	hostId: string,
	urls: {
		hostInfo: string,
		recheckServices: string,
		commandsList: string,
	} & HostsViewUrlTemplates,
};

type HostServicesAppState = {
	hostInfo: HostInfo|null,
};

class HostServicesApp extends React.Component<HostServicesAppProps, HostServicesAppState> {

	private communicator: ApiCommunicator;

	constructor(props: HostServicesAppProps) {
		super(props);

		this.state = {hostInfo: null};

		this.communicator = new ApiCommunicator(props.urls);
	}

	componentDidMount() {
		this.communicator.fetchHostInfo(this.props.hostId, (err, hostInfo) => {
			if (err === null && hostInfo !== null) {
				this.setState({hostInfo: hostInfo});
			}
		});
	}

	render() {
		if (this.state.hostInfo === null) {
			return <div className="spinner text-center">
				<div className="bounce1"></div>
				<div className="bounce2"></div>
				<div className="bounce3"></div>
			</div>;
		}

		return <HostInfoCard
			entity={this.state.hostInfo}
			communicator={this.communicator}
			urls={this.props.urls}
			onEntityUpdate={(entity) => this.setState({hostInfo: entity})}
		/>;
	}

}

window.Nagadmin.registerReactComponent('HostServicesApp', HostServicesApp);
