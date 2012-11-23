<?php
namespace Devture\Bundle\NagiosBundle\Event\Subscriber;

use Devture\Bundle\NagiosBundle\Event\Events;
use Devture\Bundle\NagiosBundle\Event\ModelEvent;
use Devture\Bundle\NagiosBundle\Model\Contact;
use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Repository\ServiceRepository;

class ContactEventsSubscriber extends ContainerAwareSubscriber {

	public static function getSubscribedEvents() {
		return array(
				Events::BEFORE_CONTACT_DELETE => 'onBeforeContactDelete',
		);
	}

	public function onBeforeContactDelete(ModelEvent $event) {
		/* @var $contact Contact */
		$contact = $event->getModel();

		/* @var $serviceRepository ServiceRepository */
		$serviceRepository = $this->get('devture_nagios.service.repository');

		/* @var $service Service */
		foreach ($serviceRepository->findByContact($contact) as $service) {
			$service->removeContact($contact);
			$serviceRepository->update($service);
		}
	}

}