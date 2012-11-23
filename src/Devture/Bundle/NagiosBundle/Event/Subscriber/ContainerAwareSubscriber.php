<?php
namespace Devture\Bundle\NagiosBundle\Event\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class ContainerAwareSubscriber implements EventSubscriberInterface {

	protected $container;

	public function __construct(\Pimple $container) {
		$this->container = $container;
	}

	public function get($serviceId) {
		return $this->container[$serviceId];
	}

}