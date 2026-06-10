import { Contact } from '../generic/widgets/ContactAvatar';

export type Host = {
	id: string,
	name: string,
	address: string,
	editable: boolean,
};

export type Service = {
	id: string,
	enabled: boolean,
	name: string,
	host: Host,
	contacts: Contact[],
};

export type ServiceStatus = {
	has_been_checked: string,
	current_state: string|null,
	last_hard_state: string|null,
	current_attempt: string,
	max_attempts: string,
	last_state_change: string,
	last_hard_state_change: string,
	plugin_output: string,
	performance_data: string,
	last_check: string,
	next_check: string,
};

export type ServiceInfo = {
	id: string,
	service: Service,
	status: ServiceStatus|null,
};

export type HostInfo = {
	id: string,
	host: Host,
	servicesInfo: ServiceInfo[],
};

export type ServiceCheckCommand = {
	id: string,
	title: string,
};

export type RecheckResult = {
	ok: boolean,
	unauthorized?: boolean,
	scheduledCount?: number,
};

export type HostsViewUrlTemplates = {
	hostView: string,
	serviceView: string,
	serviceAdd: string,
};
