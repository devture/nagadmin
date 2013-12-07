<?php
namespace Devture\Bundle\NagiosBundle\Twig;

use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Model\Contact;

class NagiosExtension extends \Twig_Extension {

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
		);
	}

	public function getFilters() {
		return array(
			'devture_nagios_colorize' => new \Twig_Filter_Method($this, 'colorize'),
			'contact_api_model_export' => new \Twig_Filter_Method($this, 'exportContactApiModel'),
		);
	}

	public function colorize($value) {
		return $this->getColorizer()->colorize($value);
	}

	public function exportContactApiModel(Contact $contact) {
		return $this->getContactApiModelBridge()->export($contact);
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

	/**
	 * @return \Devture\Bundle\NagiosBundle\Status\Manager
	 */
	private function getStatusManager() {
		return $this->container['devture_nagios.status.manager'];
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Helper\Colorizer
	 */
	private function getColorizer() {
		return $this->container['devture_nagios.helper.colorizer'];
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\ApiModelBridge\ContactBridge
	 */
	private function getContactApiModelBridge() {
		return $this->container['devture_nagios.contact.api_model_bridge'];
	}

}

