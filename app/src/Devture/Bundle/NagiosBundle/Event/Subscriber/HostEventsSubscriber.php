<?php
namespace Devture\Bundle\NagiosBundle\Event\Subscriber;

use Devture\Bundle\NagiosBundle\Event\Events;
use Devture\Bundle\NagiosBundle\Event\ModelEvent;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Repository\ServiceRepository;

class HostEventsSubscriber extends ContainerAwareSubscriber {

	public static function getSubscribedEvents(): array {
		return array(
			Events::BEFORE_HOST_DELETE => 'onBeforeHostDelete',
		);
	}

	public function onBeforeHostDelete(ModelEvent $event): void {
		/** @var Host $host */
		$host = $event->getModel();

		/** @var ServiceRepository $serviceRepository */
		$serviceRepository = $this->get('devture_nagios.service.repository');

		foreach ($serviceRepository->findByHost($host) as $service) {
			$serviceRepository->delete($service);
		}
	}

}
