import * as React from 'react';

import { ContactAvatar } from '../generic/widgets/ContactAvatar';
import { RelativeTime } from '../generic/widgets/RelativeTime';
import { ServiceStatusBadge } from './ServiceStatusBadge';
import { HostsViewUrlTemplates, ServiceInfo } from './types';

type ServiceListTableProps = {
	servicesInfo: ServiceInfo[],
	urls: HostsViewUrlTemplates,
};

export class ServiceListTable extends React.Component<ServiceListTableProps> {

	render() {
		return <table className="table table-bordered table-striped table-condensed">
			<thead>
				<tr>
					<th style={{width: '50px'}}></th>
					<th>Service</th>
					<th>Checked</th>
					<th className="d-none d-sm-table-cell">Status</th>
					<th className="d-none d-sm-table-cell" style={{minWidth: '140px'}}>Notify</th>
				</tr>
			</thead>
			<tbody>
				{this.props.servicesInfo.map((serviceInfo) => this.renderRow(serviceInfo))}
			</tbody>
		</table>;
	}

	private renderRow(serviceInfo: ServiceInfo) {
		const service = serviceInfo.service;

		return <tr key={serviceInfo.id}>
			<td>
				<ServiceStatusBadge entity={serviceInfo.status} />
			</td>
			<td style={{width: '210px'}}>
				<a href={this.props.urls.serviceView.replace('__ID__', service.id)} className="d-block">
					{service.name}
					{!service.enabled && <strong> (disabled)</strong>}
				</a>
			</td>
			<td style={{whiteSpace: 'nowrap'}}>
				{this.renderCheckedCell(serviceInfo)}
			</td>
			<td className="d-none d-sm-table-cell">
				{serviceInfo.status !== null
					? serviceInfo.status.plugin_output
					: <div>-- There is no information about this service --</div>}
			</td>
			<td className="d-none d-sm-table-cell">
				{service.contacts.map((contact) =>
					<span key={contact.id} className="me-1"><ContactAvatar entity={contact} size={24} /></span>,
				)}
				{service.contacts.length === 0 && <span className="form-text">No one</span>}
			</td>
		</tr>;
	}

	private renderCheckedCell(serviceInfo: ServiceInfo) {
		if (serviceInfo.status === null) {
			return <span>-</span>;
		}

		if (serviceInfo.status.last_check === '0') {
			return <span>
				Never (first check in <RelativeTime timestamp={Number(serviceInfo.status.next_check) * 1000} />)
			</span>;
		}

		return <span>
			<RelativeTime timestamp={Number(serviceInfo.status.last_check) * 1000} /> ago
		</span>;
	}

}
