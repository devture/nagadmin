<?php
namespace Devture\Bundle\NagiosBundle\Twig;

use Devture\Bundle\NagiosBundle\Model\User;
use Devture\Bundle\NagiosBundle\Model\Host;
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
			'devture_nagios_can_user_manage_host' => new \Twig_Function_Method($this, 'canUserManageHost'),
			'devture_nagios_can_user_manage_hosts' => new \Twig_Function_Method($this, 'canUserManageHosts'),
			'devture_nagios_can_user_create_hosts' => new \Twig_Function_Method($this, 'canUserCreateHosts'),
			'devture_nagios_can_user_manage_service' => new \Twig_Function_Method($this, 'canUserManageService'),
			'devture_nagios_can_user_manage_contact' => new \Twig_Function_Method($this, 'canUserManageContact'),
			'devture_nagios_get_distinct_groups' => new \Twig_Function_Method($this, 'getDistinctGroups'),
			'devture_nagios_count_host_services' => new \Twig_Function_Method($this, 'getHostServicesCount'),
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

	public function canUserManageHost(User $user, Host $host) {
		return $this->getAccessChecker()->canUserManageHost($user, $host);
	}

	public function canUserManageHosts(User $user) {
		return $this->getAccessChecker()->canUserManageHosts($user);
	}

	public function canUserCreateHosts(User $user) {
		return $this->getAccessChecker()->canUserCreateHosts($user);
	}

	public function canUserManageService(User $user, Service $service) {
		return $this->getAccessChecker()->canUserManageService($user, $service);
	}

	public function canUserManageContact(User $user, Contact $contact) {
		return $this->getAccessChecker()->canUserManageContact($user, $contact);
	}

	public function getDistinctGroups(User $user) {
		$groups = $this->getHostRepository()->getDistinctGroups();
		$groups = array_merge($groups, $user->getGroups());
		return array_unique($groups);
	}

	public function getHostServicesCount(Host $host) {
		return $this->getServiceRepository()->countByHost($host);
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
	 * @return \Devture\Bundle\NagiosBundle\Helper\AccessChecker
	 */
	private function getAccessChecker() {
		return $this->container['devture_nagios.helper.access_checker'];
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\ApiModelBridge\ContactBridge
	 */
	private function getContactApiModelBridge() {
		return $this->container['devture_nagios.contact.api_model_bridge'];
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Repository\HostRepository
	 */
	private function getHostRepository() {
		return $this->container['devture_nagios.host.repository'];
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Repository\ServiceRepository
	 */
	private function getServiceRepository() {
		return $this->container['devture_nagios.service.repository'];
	}

}

