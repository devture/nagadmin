<?php
namespace Devture\Bundle\NagiosBundle\Twig;

use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Model\Contact;
use Devture\Bundle\NagiosBundle\Status\ServiceStatus;

class NagiosExtension extends \Twig_Extension {

	private $colors = array('#014de7', '#3a87ad', '#06cf99', '#8fcf06', '#dda808', '#e76d01', '#7801e7', '#353535', '#888888',);
	private $container;

	public function __construct(\Pimple $container) {
		$this->container = $container;
	}

	public function getName() {
		return 'devture_nagios_extension';
	}

	public function getFunctions() {
		return array(
			'devture_nagios_get_info_status' => new \Twig_Function_Method($this, 'getInfoStatus'),
			'devture_nagios_get_program_status' => new \Twig_Function_Method($this, 'getProgramStatus'),
			'devture_nagios_get_service_status' => new \Twig_Function_Method($this, 'getServiceStatus'),
			'devture_nagios_get_ok_services_count' => new \Twig_Function_Method($this, 'getOkServicesCount'),
			'devture_nagios_get_failing_services_count' => new \Twig_Function_Method($this, 'getFailingServicesCount'),
		);
	}

	public function getFilters() {
		return array(
			'devture_nagios_colorize' => new \Twig_Filter_Method($this, 'colorize'),
			'devture_nagios_contact_avatar_url' => new \Twig_Filter_Method($this, 'getContactAvatarUrl'),
		);
	}

	public function colorize($value) {
		$value = (string)$value;

		$sum = hexdec(substr(hash('crc32', $value), 0, 2));

		$idx = $sum % count($this->colors);

		return $this->colors[$idx];
	}

	public function getContactAvatarUrl(Contact $contact, $size) {
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

	public function getInfoStatus() {
		return $this->getStatusManager()->getInfoStatus();
	}

	public function getProgramStatus() {
		return $this->getStatusManager()->getProgramStatus();
	}

	public function getServiceStatus(Service $service) {
		return $this->getStatusManager()->getServiceStatus($service);
	}

	public function getOkServicesCount() {
		return count(array_filter($this->getStatusManager()->getServicesStatus(), function (ServiceStatus $status) {
			return ($status->getLastHardState() === ServiceStatus::STATUS_OK);
		}));
	}

	public function getFailingServicesCount() {
		return (count($this->getStatusManager()->getServicesStatus()) - $this->getOkServicesCount());
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Status\Manager
	 */
	private function getStatusManager() {
		return $this->container['devture_nagios.status.manager'];
	}

}

