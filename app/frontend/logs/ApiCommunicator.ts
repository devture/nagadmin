import { Callback } from '../generic/types';
import { LogEntry } from './types';

export class ApiCommunicator {

	constructor(
		private listUrl: string,
		private listIfNewerThanUrlTemplate: string,
	) {
	}

	public fetchAll(callback: Callback<LogEntry[]>) {
		this.fetchFromUrl(this.listUrl, callback);
	}

	public fetchAllIfNewerThan(lastSeenId: string, callback: Callback<LogEntry[]>) {
		this.fetchFromUrl(this.listIfNewerThanUrlTemplate.replace('__LAST_SEEN_ID__', lastSeenId), callback);
	}

	private fetchFromUrl(url: string, callback: Callback<LogEntry[]>) {
		window.fetch(url).then((response: Response) => {
			if (!response.ok) {
				callback(new Error('Network response was not OK: ' + response.status), null);
				return;
			}

			response.json().then((payload: LogEntry[]) => {
				callback(null, payload);
			}).catch((_reason: unknown) => {
				callback(new Error('Invalid JSON response from server'), null);
			});
		}).catch((reason: unknown) => {
			callback(new Error(String(reason)), null);
		});
	}

}
