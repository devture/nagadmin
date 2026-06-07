<?php
namespace Devture\Bundle\NagiosBundle\Event\Subscriber;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class ContainerAwareSubscriber implements EventSubscriberInterface {

	protected ContainerInterface $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	/**
	 * @param string $serviceId
	 * @return mixed
	 */
	public function get($serviceId) {
		return $this->container->get($serviceId);
	}

}
