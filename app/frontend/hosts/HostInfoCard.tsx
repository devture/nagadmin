import * as React from 'react';

import { ApiCommunicator } from './ApiCommunicator';
import { HostInfoPoller } from './HostInfoPoller';
import { HostRecheckButton } from './HostRecheckButton';
import { ServiceAddNewButton } from './ServiceAddNewButton';
import { ServiceListTable } from './ServiceListTable';
import { HostInfo, HostsViewUrlTemplates } from './types';

type HostInfoCardProps = {
	entity: HostInfo,
	communicator: ApiCommunicator,
	urls: HostsViewUrlTemplates,
	onEntityUpdate: (entity: HostInfo) => void,
};

type HostInfoCardState = {
	isBeingRechecked: boolean,
};

export class HostInfoCard extends React.Component<HostInfoCardProps, HostInfoCardState> {

	private poller: HostInfoPoller|null = null;

	constructor(props: HostInfoCardProps) {
		super(props);

		this.state = {isBeingRechecked: false};
	}

	componentDidMount() {
		this.poller = new HostInfoPoller(
			this.props.communicator,
			() => this.props.entity,
			{
				onUpdateStart: () => this.setState({isBeingRechecked: true}),
				onUpdateEnd: () => this.setState({isBeingRechecked: false}),
				onUpdated: (entity) => this.props.onEntityUpdate(entity),
			},
		);
		this.poller.start();
	}

	componentWillUnmount() {
		if (this.poller !== null) {
			this.poller.stop();
		}
	}

	handleDirty = () => {
		if (this.poller !== null) {
			this.poller.update();
		}
	};

	render() {
		const entity = this.props.entity;
		const host = entity.host;

		return <>
			<div className="clearfix">
				<a href={this.props.urls.hostView.replace('__ID__', host.id)}>
					<h4 style={{margin: 0, padding: 0, display: 'inline-block'}}>
						<img src={'//www.google.com/s2/favicons?domain=' + host.address} alt="" />
						{' '}{host.name}
					</h4>
				</a>
				<div className="float-end">
					{host.editable &&
						<ServiceAddNewButton
							host={host}
							communicator={this.props.communicator}
							serviceAddUrlTemplate={this.props.urls.serviceAdd}
						/>}
					{' '}
					{entity.servicesInfo.length !== 0 &&
						<HostRecheckButton
							host={host}
							communicator={this.props.communicator}
							rechecking={this.state.isBeingRechecked}
							onDirty={this.handleDirty}
						/>}
				</div>
			</div>

			<div className="top-spaced-minor"></div>

			{entity.servicesInfo.length === 0
				? <div>No services</div>
				: <ServiceListTable servicesInfo={entity.servicesInfo} urls={this.props.urls} />}
		</>;
	}

}
