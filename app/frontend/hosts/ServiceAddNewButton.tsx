import * as React from 'react';

import { ApiCommunicator } from './ApiCommunicator';
import { Host, ServiceCheckCommand } from './types';

type ServiceAddNewButtonProps = {
	host: Host,
	communicator: ApiCommunicator,
	serviceAddUrlTemplate: string,
};

type ServiceAddNewButtonState = {
	commands: ServiceCheckCommand[],
};

export class ServiceAddNewButton extends React.Component<ServiceAddNewButtonProps, ServiceAddNewButtonState> {

	constructor(props: ServiceAddNewButtonProps) {
		super(props);

		this.state = {commands: []};
	}

	handleExpand = () => {
		if (this.state.commands.length !== 0) {
			return;
		}

		this.props.communicator.fetchServiceCheckCommands((err, commands) => {
			if (err === null && commands !== null) {
				this.setState({commands: [...commands].sort((a, b) => a.title.localeCompare(b.title))});
			}
		});
	};

	private buildServiceAddUrl(command: ServiceCheckCommand): string {
		return this.props.serviceAddUrlTemplate
			.replace('__COMMAND_ID__', command.id)
			.replace('__HOST_ID__', this.props.host.id);
	}

	render() {
		return <div className="dropdown" style={{display: 'inline-block'}}>
			<a className="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" href="#" onClick={this.handleExpand}>
				<i className="fa-solid fa-plus"></i>
				<span className="d-none d-sm-inline"> Add service</span>
			</a>

			<ul className="dropdown-menu" role="menu" style={{minWidth: 0, right: 0, left: 'auto'}}>
				{this.state.commands.length === 0 &&
					<li>
						<span className="dropdown-item">
							Loading..
						</span>
					</li>
				}
				{this.state.commands.map((command) =>
					<li key={command.id}>
						<a className="dropdown-item" href={this.buildServiceAddUrl(command)}>
							{command.title}
						</a>
					</li>,
				)}
			</ul>
		</div>;
	}

}
