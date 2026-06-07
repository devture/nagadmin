<?php
namespace Devture\Bundle\NagiosBundle\Event\Subscriber;

use Devture\Bundle\NagiosBundle\Event\Events;
use Devture\Bundle\NagiosBundle\Event\ModelEvent;
use Devture\Bundle\NagiosBundle\Model\TimePeriod;
use Devture\Bundle\NagiosBundle\Repository\ContactRepository;

class TimePeriodEventsSubscriber extends ContainerAwareSubscriber {

	public static function getSubscribedEvents(): array {
		return array(
			Events::BEFORE_TIME_PERIOD_DELETE => 'onBeforeTimePeriodDelete',
		);
	}

	public function onBeforeTimePeriodDelete(ModelEvent $event): void {
		/** @var TimePeriod $timePeriod */
		$timePeriod = $event->getModel();

		/** @var ContactRepository $contactRepository */
		$contactRepository = $this->get('devture_nagios.contact.repository');

		foreach ($contactRepository->findByTimePeriod($timePeriod) as $contact) {
			$contactRepository->delete($contact);
		}
	}

}
