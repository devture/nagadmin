import * as React from 'react';

import { BounceSpinner } from './generic/widgets/BounceSpinner';
import { ApiCommunicator } from './hosts/ApiCommunicator';
import { HostInfoCard } from './hosts/HostInfoCard';
import { HostsInfoSummary } from './hosts/HostsInfoSummary';
import { HostInfo, HostsViewUrlTemplates } from './hosts/types';

type DashboardHostsAppProps = {
	urls: {
		hostsInfoAll: string,
		hostInfo: string,
		recheckServices: string,
		commandsList: string,
		hostAdd: string,
	} & HostsViewUrlTemplates,
};

type DashboardHostsAppState = {
	hostsInfo: HostInfo[]|null,
};

class DashboardHostsApp extends React.Component<DashboardHostsAppProps, DashboardHostsAppState> {

	private communicator: ApiCommunicator;

	constructor(props: DashboardHostsAppProps) {
		super(props);

		this.state = {hostsInfo: null};

		this.communicator = new ApiCommunicator(props.urls);
	}

	componentDidMount() {
		this.communicator.fetchAllHostsInfo((err, hostsInfo) => {
			if (err === null && hostsInfo !== null) {
				this.setState({hostsInfo: hostsInfo});
			}
		});
	}

	handleEntityUpdate = (updatedEntity: HostInfo) => {
		this.setState((state) => ({
			hostsInfo: (state.hostsInfo || []).map(
				(entity) => (entity.id === updatedEntity.id ? updatedEntity : entity),
			),
		}));
	};

	render() {
		const hostsInfo = this.state.hostsInfo;

		return <>
			<div className="float-end">
				<HostsInfoSummary hostsInfo={hostsInfo || []} />
			</div>

			<h3>Services</h3>

			{hostsInfo === null && <BounceSpinner />}

			{(hostsInfo || []).map((hostInfo) =>
				<HostInfoCard
					key={hostInfo.id}
					entity={hostInfo}
					communicator={this.communicator}
					urls={this.props.urls}
					onEntityUpdate={this.handleEntityUpdate}
				/>,
			)}

			{hostsInfo !== null && hostsInfo.length === 0 &&
				<div className="form-text">
					Not monitoring anything yet.
					{' '}
					<a href={this.props.urls.hostAdd} className="btn btn-primary btn-sm">Create a new host to get started</a>
				</div>}
		</>;
	}

}

window.Nagadmin.registerReactComponent('DashboardHostsApp', DashboardHostsApp);
