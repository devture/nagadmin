export type LogEntry = {
	id: string,
	type: string,
	timestamp: number,
	value: string,
	host: {
		id: string|null,
		address: string|null,
	},
	service: {
		id: string|null,
	},
};
