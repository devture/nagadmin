import * as React from 'react';

import { HostInfo } from './types';

type HostsInfoSummaryProps = {
	hostsInfo: HostInfo[],
};

type StatusCounts = {
	ok: number,
	pending: number,
	failing: number,
};

function computeStatusCounts(hostsInfo: HostInfo[]): StatusCounts {
	let totalCount = 0, okCount = 0, pendingCount = 0;

	hostsInfo.forEach((hostInfo) => {
		hostInfo.servicesInfo.forEach((serviceInfo) => {
			const status = serviceInfo.status;

			if (status === null) {
				// Don't count unknown services at all
				return;
			}

			totalCount += 1;

			if (status.current_state === '0') {
				if (status.has_been_checked === '0') {
					pendingCount += 1;
				} else {
					okCount += 1;
				}
			}
		});
	});

	return {ok: okCount, pending: pendingCount, failing: (totalCount - okCount - pendingCount)};
}

export class HostsInfoSummary extends React.Component<HostsInfoSummaryProps> {

	render() {
		const counts = computeStatusCounts(this.props.hostsInfo);

		return <>
			<span className="badge label-nagadmin-status label-nagadmin-success-unobtrusive">{counts.ok} ok</span>
			{' '}
			{counts.pending !== 0 &&
				<span className="badge label-nagadmin-status label-nagadmin-default-unobtrusive">{counts.pending} pending</span>}
			{' '}
			<span className="badge label-nagadmin-status label-nagadmin-important-unobtrusive">{counts.failing} failing</span>
		</>;
	}

}
