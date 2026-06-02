<?php
namespace Devture\Bundle\NagiosBundle\Event\Subscriber;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class ContainerAwareSubscriber implements EventSubscriberInterface {

	protected $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	public function get($serviceId) {
		return $this->container->get($serviceId);
	}

}
