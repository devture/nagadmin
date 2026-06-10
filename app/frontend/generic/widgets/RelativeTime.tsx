import * as React from 'react';

type RelativeTimeProps = {
	timestamp: number,
};

export class RelativeTime extends React.Component<RelativeTimeProps> {

	private intervalId: number|null = null;
	private timeRef = React.createRef<HTMLTimeElement>();

	componentDidMount() {
		this.intervalId = window.setInterval(() => this.forceUpdate(), 15000);
	}

	componentWillUnmount() {
		if (this.intervalId !== null) {
			window.clearInterval(this.intervalId);
		}
	}

	handleMouseOver = () => {
		if (this.timeRef.current) {
			window.bootstrap.Tooltip.getOrCreateInstance(this.timeRef.current).show();
		}
	};

	render() {
		const date = new Date(this.props.timestamp);

		return <time
			ref={this.timeRef}
			title={date.toLocaleDateString() + ' ' + date.toLocaleTimeString()}
			onMouseOver={this.handleMouseOver}
		>{window.relativizeTime(date, new Date())}</time>;
	}

}
