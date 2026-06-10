import * as React from 'react';

import { ServiceStatus } from './types';

const STATE_TO_HUMAN_MAP: {[state: string]: string} = {
	'0': 'ok',
	'1': 'warning',
	'2': 'critical',
	'3': 'unknown',
};

const HUMAN_STATE_TO_LABEL_CLASS_MAP: {[state: string]: string} = {
	'ok': 'text-bg-success',
	'warning': 'text-bg-warning',
	'critical': 'text-bg-danger',
	'unknown': 'text-bg-secondary',
};

function humanizeState(state: string): string {
	return (typeof(STATE_TO_HUMAN_MAP[state]) === 'undefined' ? 'unknown' : STATE_TO_HUMAN_MAP[state]);
}

type ServiceStatusBadgeProps = {
	entity: ServiceStatus|null,
};

export class ServiceStatusBadge extends React.Component<ServiceStatusBadgeProps> {

	render() {
		const entity = this.props.entity;

		let text = '';
		let classes = '';

		if (entity === null) {
			text = 'missing';
			classes = 'text-bg-secondary';
		} else if (entity.has_been_checked === '1') {
			if (entity.current_state !== null) {
				const currentStateHuman = humanizeState(entity.current_state);
				text = currentStateHuman;
				classes = HUMAN_STATE_TO_LABEL_CLASS_MAP[currentStateHuman];
			}

			if (entity.last_hard_state !== entity.current_state) {
				text = text + ' (' + entity.current_attempt + '/' + entity.max_attempts + ')';
			}
		} else {
			text = 'pending';
			classes = 'text-bg-secondary';
		}

		return <span className={'text-center badge ' + classes} style={{display: 'block'}}>
			{text}
		</span>;
	}

}
