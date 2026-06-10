import { Callback } from '../generic/types';
import { HostInfo, RecheckResult, ServiceCheckCommand } from './types';

export type HostsApiUrls = {
	hostsInfoAll?: string,
	hostInfo: string,
	recheckServices: string,
	commandsList: string,
};

export class ApiCommunicator {

	constructor(private urls: HostsApiUrls) {
	}

	public fetchAllHostsInfo(callback: Callback<HostInfo[]>) {
		if (!this.urls.hostsInfoAll) {
			callback(new Error('No hostsInfoAll URL configured'), null);
			return;
		}

		this.fetchJson(this.urls.hostsInfoAll, callback);
	}

	public fetchHostInfo(id: string, callback: Callback<HostInfo>) {
		this.fetchJson(this.urls.hostInfo.replace('__ID__', id), callback);
	}

	public fetchServiceCheckCommands(callback: Callback<ServiceCheckCommand[]>) {
		this.fetchJson(this.urls.commandsList, callback);
	}

	public recheckServices(hostId: string, recheckType: string, callback: Callback<RecheckResult>) {
		const url = this.urls.recheckServices
			.replace('__ID__', hostId)
			.replace('__RECHECK_TYPE__', recheckType);

		this.fetchJson(url, callback, {method: 'POST'});
	}

	private fetchJson<T>(url: string, callback: Callback<T>, init?: RequestInit) {
		window.fetch(url, init).then((response: Response) => {
			if (!response.ok) {
				callback(new Error('Network response was not OK: ' + response.status), null);
				return;
			}

			response.json().then((payload: T) => {
				callback(null, payload);
			}).catch((_reason: unknown) => {
				callback(new Error('Invalid JSON response from server'), null);
			});
		}).catch((reason: unknown) => {
			callback(new Error(String(reason)), null);
		});
	}

}
