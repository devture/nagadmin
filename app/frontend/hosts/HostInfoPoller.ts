import { ApiCommunicator } from './ApiCommunicator';
import { HostInfo } from './types';

const POLL_INTERVAL_MS = 10000;

type HostInfoPollerCallbacks = {
	onUpdateStart: () => void,
	onUpdateEnd: () => void,
	onUpdated: (entity: HostInfo) => void,
};

export class HostInfoPoller {

	private updatingNow = false;
	private timeoutId: number|null = null;

	constructor(
		private communicator: ApiCommunicator,
		private getEntity: () => HostInfo,
		private callbacks: HostInfoPollerCallbacks,
	) {
	}

	public start() {
		this.scheduleNext();
	}

	public stop() {
		if (this.timeoutId !== null) {
			window.clearTimeout(this.timeoutId);
		}
	}

	public update() {
		if (this.updatingNow) {
			return;
		}
		this.updatingNow = true;

		this.callbacks.onUpdateStart();

		this.communicator.fetchHostInfo(this.getEntity().host.id, (err, entity) => {
			if (err === null && entity !== null) {
				this.callbacks.onUpdated(entity);
			}

			this.updatingNow = false;
			this.callbacks.onUpdateEnd();
		});
	}

	private scheduleNext() {
		this.timeoutId = window.setTimeout(() => {
			if (this.needsUpdate()) {
				this.update();
			}
			this.scheduleNext();
		}, POLL_INTERVAL_MS);
	}

	private needsUpdate(): boolean {
		const timeNow = new Date().getTime() / 1000;

		return this.getEntity().servicesInfo.some((serviceInfo) => {
			if (serviceInfo.status === null) {
				// Service not known to Nagios at all.
				return false;
			}

			return Number(serviceInfo.status.next_check) < timeNow;
		});
	}

}
