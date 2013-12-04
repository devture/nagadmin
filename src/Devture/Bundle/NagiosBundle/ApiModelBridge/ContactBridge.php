<?php
namespace Devture\Bundle\NagiosBundle\ApiModelBridge;

use Devture\Bundle\NagiosBundle\Model\Contact;
use Devture\Bundle\NagiosBundle\Helper\Colorizer;

class ContactBridge {

	private $colorizer;

	public function __construct(Colorizer $colorizer) {
		$this->colorizer = $colorizer;
	}

	public function export(Contact $entity) {
		return array(
			'id' => (string) $entity->getId(),
			'name' => $entity->getName(),
			'avatar_url' => $this->getAvatarUrl($entity, '__SIZE__'),
			'color' => $this->colorizer->colorize((string) $entity->getName()),
		);
	}

	private function getAvatarUrl(Contact $contact, $size) {
		//Force the default image for gravatars based on non-email values.
		//This is to avoid a collision of the hash based on address fields with some
		//random internet user's email address (who might have a photo for an avatar)
		if ($contact->getEmail()) {
			$identifier = $contact->getEmail();
			$forceDefault = false;
		} else {
			$identifier = implode(', ', $contact->getAddresses());
			$forceDefault = true; //not an email, so force
		}
		$identifier = trim(strtolower($identifier));
		$hash = md5($identifier);
		return 'https://secure.gravatar.com/avatar/' . $hash . '?s=' . $size . '&d=wavatar' . ($forceDefault ? '&f=y' : '');
	}

}