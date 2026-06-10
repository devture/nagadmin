import * as React from 'react';

import { ApiCommunicator } from './hosts/ApiCommunicator';
import { HostInfoCard } from './hosts/HostInfoCard';
import { HostsInfoSummary } from './hosts/HostsInfoSummary';
import { HostInfo, HostsViewUrlTemplates } from './hosts/types';

type ServicesAppProps = {
	urls: {
		hostsInfoAll: string,
		hostInfo: string,
		recheckServices: string,
		commandsList: string,
		hostAdd: string,
	} & HostsViewUrlTemplates,
};

type ServicesAppState = {
	hostsInfo: HostInfo[]|null,
	selectedHostId: string,
};

class ServicesApp extends React.Component<ServicesAppProps, ServicesAppState> {

	private communicator: ApiCommunicator;

	constructor(props: ServicesAppProps) {
		super(props);

		this.state = {hostsInfo: null, selectedHostId: ''};

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

	private getFilteredHostsInfo(): HostInfo[] {
		const hostsInfo = (this.state.hostsInfo || []);

		if (!this.state.selectedHostId) {
			return hostsInfo;
		}

		return hostsInfo.filter((hostInfo) => hostInfo.id === this.state.selectedHostId);
	}

	private renderSpinner() {
		return <div className="spinner text-center">
			<div className="bounce1"></div>
			<div className="bounce2"></div>
			<div className="bounce3"></div>
		</div>;
	}

	render() {
		const loaded = (this.state.hostsInfo !== null);
		const hostsInfo = (this.state.hostsInfo || []);
		const filteredHostsInfo = this.getFilteredHostsInfo();

		return <>
			<div className="row">
				<div className="col-lg-3 col-6">
					<HostsInfoSummary hostsInfo={hostsInfo} />

					{!loaded &&
						<div className="d-none d-sm-block float-end">
							{this.renderSpinner()}
						</div>}
				</div>

				<div className="col-lg-9 col-6">
					<div className="col-lg-5 offset-lg-7 text-end">
						{hostsInfo.length > 0 &&
							<select
								className="form-select"
								value={this.state.selectedHostId}
								onChange={(event) => this.setState({selectedHostId: event.target.value})}>
								<option value="">-- All hosts --</option>
								{hostsInfo.map((hostInfo) =>
									<option key={hostInfo.id} value={hostInfo.id}>{hostInfo.host.name}</option>,
								)}
							</select>}
					</div>
				</div>
			</div>

			{!loaded &&
				<div className="d-block d-sm-none">
					{this.renderSpinner()}
				</div>}

			<hr />

			{filteredHostsInfo.map((hostInfo) =>
				<HostInfoCard
					key={hostInfo.id}
					entity={hostInfo}
					communicator={this.communicator}
					urls={this.props.urls}
					onEntityUpdate={this.handleEntityUpdate}
				/>,
			)}

			{loaded && filteredHostsInfo.length === 0 &&
				<div className="form-text">
					Not monitoring anything yet.
					{' '}
					<a href={this.props.urls.hostAdd} className="btn btn-primary btn-sm">Create a new host to get started</a>
				</div>}
		</>;
	}

}

window.Nagadmin.registerReactComponent('ServicesApp', ServicesApp);
