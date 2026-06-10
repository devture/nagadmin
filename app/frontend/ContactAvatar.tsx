import * as React from 'react';

type Contact = {
	id: string,
	name: string,
	avatar_url: string,
	color: string,
};

type ContactAvatarProps = {
	entity: Contact,
	size: number,
};

class ContactAvatar extends React.Component<ContactAvatarProps> {

	private imgRef = React.createRef<HTMLImageElement>();

	handleMouseOver = () => {
		if (this.imgRef.current) {
			window.bootstrap.Tooltip.getOrCreateInstance(this.imgRef.current).show();
		}
	};

	render() {
		return <img
			ref={this.imgRef}
			src={this.props.entity.avatar_url.replace('__SIZE__', String(this.props.size))}
			className="rounded"
			style={{border: '3px solid ' + this.props.entity.color}}
			title={this.props.entity.name}
			onMouseOver={this.handleMouseOver}
		/>;
	}

}

window.Nagadmin.registerReactComponent('ContactAvatar', ContactAvatar);
