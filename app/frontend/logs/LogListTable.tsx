import * as React from 'react';

import { RelativeTime } from '../generic/widgets/RelativeTime';
import { LogEntry } from './types';

export type LogViewUrlTemplates = {
	hostView: string,
	serviceView: string,
};

type LogListTableProps = {
	entities: LogEntry[],
	urlTemplates: LogViewUrlTemplates,
	limit?: number,
};

export class LogListTable extends React.Component<LogListTableProps> {

	private buildInfoLink(entity: LogEntry): string|null {
		if (entity.service.id !== null) {
			return this.props.urlTemplates.serviceView.replace('__ID__', entity.service.id);
		}

		if (entity.host.id !== null) {
			return this.props.urlTemplates.hostView.replace('__ID__', entity.host.id);
		}

		return null;
	}

	render() {
		const entities = (this.props.limit ? this.props.entities.slice(0, this.props.limit) : this.props.entities);

		if (entities.length === 0) {
			return <div className="form-text">No log entries.</div>;
		}

		return <table className="table table-bordered table-striped table-condensed">
			<thead>
				<tr>
					<th>Date</th>
					<th className="d-none d-sm-table-cell">Type</th>
					<th>Value</th>
				</tr>
			</thead>
			<tbody>
				{entities.map((entity) => this.renderRow(entity))}
			</tbody>
		</table>;
	}

	private renderRow(entity: LogEntry) {
		const infoLink = this.buildInfoLink(entity);

		return <tr key={entity.id}>
			<td style={{whiteSpace: 'nowrap'}}>
				<RelativeTime timestamp={entity.timestamp * 1000} /> ago
			</td>
			<td className="d-none d-sm-table-cell">
				{entity.type}
			</td>
			<td>
				<span className="d-inline d-sm-none"><strong>{entity.type}</strong>: </span>
				<span style={{wordBreak: 'break-all'}}>
					{entity.value}
				</span>

				{infoLink !== null &&
					<div>
						<a className="btn btn-outline-secondary btn-sm float-end" href={infoLink}>
							{entity.host.address !== null
								? <img src={'//www.google.com/s2/favicons?domain=' + entity.host.address} alt="" />
								: <i className="fa-solid fa-circle-info"></i>}
						</a>
					</div>
				}
			</td>
		</tr>;
	}

}
