import { ApiCommunicator } from './ApiCommunicator';
import { LogEntry } from './types';

const POLL_INTERVAL_MS = 10000;

export class LogEntriesPoller {

	private running = false;
	private timeoutId: number|null = null;

	constructor(
		private communicator: ApiCommunicator,
		private getLastSeenId: () => string|null,
		private onNewEntries: (entities: LogEntry[]) => void,
	) {
	}

	public start() {
		this.running = true;
		this.scheduleNext();
	}

	public stop() {
		this.running = false;
		if (this.timeoutId !== null) {
			window.clearTimeout(this.timeoutId);
		}
	}

	private scheduleNext() {
		this.timeoutId = window.setTimeout(() => {
			const lastSeenId = this.getLastSeenId();

			this.communicator.fetchAllIfNewerThan(lastSeenId === null ? 'null' : lastSeenId, (err, entities) => {
				if (err === null && entities !== null && entities.length !== 0) {
					this.onNewEntries(entities);
				}

				if (this.running) {
					this.scheduleNext();
				}
			});
		}, POLL_INTERVAL_MS);
	}

}
