import * as React from 'react';

import { ApiCommunicator } from './ApiCommunicator';
import { Host } from './types';

type HostRecheckButtonProps = {
	host: Host,
	communicator: ApiCommunicator,
	rechecking: boolean,
	onDirty: () => void,
};

type HostRecheckButtonState = {
	isRecheckRunning: boolean,
};

export class HostRecheckButton extends React.Component<HostRecheckButtonProps, HostRecheckButtonState> {

	constructor(props: HostRecheckButtonProps) {
		super(props);

		this.state = {isRecheckRunning: false};
	}

	private isRechecking(): boolean {
		return (this.props.rechecking || this.state.isRecheckRunning);
	}

	private recheck(event: React.MouseEvent, recheckType: string) {
		event.preventDefault();

		this.setState({isRecheckRunning: true});

		this.props.communicator.recheckServices(this.props.host.id, recheckType, (err, data) => {
			if (err !== null || data === null) {
				this.setState({isRecheckRunning: false});
				return;
			}

			if (data.unauthorized) {
				window.alert('Unauthorized. Reload the page and try again.');
				this.setState({isRecheckRunning: false});
				return;
			}

			if (data.scheduledCount === 0) {
				// Intentional delay, to make sure we indicate that rechecking takes place
				// (in case the API returns too fast and makes the button appear to not do anything).
				window.setTimeout(() => {
					this.setState({isRecheckRunning: false});
				}, 1000);
				return;
			}

			// It could take up to `status_update_interval` seconds for the scheduled checks to propagate
			// to the status file. We should do a few updates at intervals.
			[6, 14, 30].forEach((seconds, idx) => {
				window.setTimeout(() => {
					this.props.onDirty();

					if (idx === 1) {
						// Stop the recheck indication after 14 seconds (in the middle).
						this.setState({isRecheckRunning: false});
					}
				}, seconds * 1000);
			});
		});
	}

	render() {
		return <div className="dropdown" style={{display: 'inline-block'}}>
			<a
				className={'btn btn-outline-primary btn-sm dropdown-toggle' + (this.isRechecking() ? ' disabled' : '')}
				data-bs-toggle="dropdown"
				href="#">
				<i className={'fa-solid fa-arrows-rotate' + (this.isRechecking() ? ' spin-animate' : '')}></i>
				<span className="d-none d-sm-inline"> Recheck</span>
			</a>

			<ul className="dropdown-menu" role="menu" style={{minWidth: 0, right: 0, left: 'auto'}}>
				<li>
					<a className="dropdown-item" href="#" onClick={(event) => this.recheck(event, 'failing')}>
						Recheck failing
					</a>
				</li>
				<li>
					<a className="dropdown-item" href="#" onClick={(event) => this.recheck(event, 'all')}>
						Recheck all
					</a>
				</li>
			</ul>
		</div>;
	}

}
