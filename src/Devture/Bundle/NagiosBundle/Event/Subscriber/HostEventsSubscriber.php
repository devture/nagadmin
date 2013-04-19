<?php
namespace Devture\Bundle\NagiosBundle\Event\Subscriber;

use Devture\Bundle\NagiosBundle\Event\Events;
use Devture\Bundle\NagiosBundle\Event\ModelEvent;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Repository\ServiceRepository;

class HostEventsSubscriber extends ContainerAwareSubscriber {

	public static function getSubscribedEvents() {
		return array(
			Events::BEFORE_HOST_DELETE => 'onBeforeHostDelete',
		);
	}

	public function onBeforeHostDelete(ModelEvent $event) {
		/* @var $host Host */
		$host = $event->getModel();

		/* @var $serviceRepository ServiceRepository */
		$serviceRepository = $this->get('devture_nagios.service.repository');

		foreach ($serviceRepository->findByHost($host) as $service) {
			$serviceRepository->delete($service);
		}
	}

}