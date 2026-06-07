<?php
namespace Devture\Bundle\NagiosBundle\Event\Subscriber;

use Devture\Bundle\NagiosBundle\Event\Events;
use Devture\Bundle\NagiosBundle\Event\ModelEvent;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Repository\ServiceRepository;
use Devture\Bundle\NagiosBundle\Repository\ContactRepository;

class CommandEventsSubscriber extends ContainerAwareSubscriber {

	public static function getSubscribedEvents(): array {
		return array(
			Events::BEFORE_COMMAND_DELETE => 'onBeforeCommandDelete',
		);
	}

	public function onBeforeCommandDelete(ModelEvent $event): void {
		/** @var Command $command */
		$command = $event->getModel();

		$commandType = $command->getType();

		if ($commandType === Command::TYPE_SERVICE_CHECK) {
			/** @var ServiceRepository $serviceRepository */
			$serviceRepository = $this->get('devture_nagios.service.repository');

			foreach ($serviceRepository->findByCommand($command) as $service) {
				$serviceRepository->delete($service);
			}
		} else if ($commandType === Command::TYPE_SERVICE_NOTIFICATION) {
			/** @var ContactRepository $contactRepository */
			$contactRepository = $this->get('devture_nagios.contact.repository');

			foreach ($contactRepository->findByCommand($command) as $contact) {
				$contactRepository->delete($contact);
			}
		}
	}

}
