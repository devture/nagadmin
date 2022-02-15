<?php
namespace Devture\Bundle\NagiosBundle\Twig;

use Devture\Bundle\NagiosBundle\Model\User;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Model\Contact;

class NagiosExtension extends \Twig\Extension\AbstractExtension {

	private $container;

	public function __construct(\Pimple\Container $container) {
		$this->container = $container;
	}

	public function getName() {
		return 'devture_nagios_extension';
	}

	public function getFunctions() {
		return array(
			new \Twig\TwigFunction('devture_nagios_get_info_status', [$this, 'getInfoStatus']),
			new \Twig\TwigFunction('devture_nagios_get_program_status', [$this, 'getProgramStatus']),
			new \Twig\TwigFunction('devture_nagios_get_service_status', [$this, 'getServiceStatus']),
			new \Twig\TwigFunction('devture_nagios_get_nagios_url', [$this, 'getNagiosUrl']),
			new \Twig\TwigFunction('devture_nagios_can_user_manage_host', [$this, 'canUserManageHost']),
			new \Twig\TwigFunction('devture_nagios_can_user_manage_hosts', [$this, 'canUserManageHosts']),
			new \Twig\TwigFunction('devture_nagios_can_user_create_hosts', [$this, 'canUserCreateHosts']),
			new \Twig\TwigFunction('devture_nagios_can_user_manage_service', [$this, 'canUserManageService']),
			new \Twig\TwigFunction('devture_nagios_can_user_manage_contact', [$this, 'canUserManageContact']),
			new \Twig\TwigFunction('devture_nagios_can_user_manage_contacts', [$this, 'canUserManageContacts']),
			new \Twig\TwigFunction('devture_nagios_can_user_create_contacts', [$this, 'canUserCreateContacts']),
			new \Twig\TwigFunction('devture_nagios_can_user_do_configuration_management', [$this, 'canUserDoConfigurationManagement']),
			new \Twig\TwigFunction('devture_nagios_get_distinct_groups', [$this, 'getDistinctGroups']),
			new \Twig\TwigFunction('devture_nagios_count_host_services', [$this, 'getHostServicesCount']),
		);
	}

	public function getFilters() {
		return array(
			new \Twig\TwigFilter('devture_nagios_colorize', [$this, 'colorize']),
			new \Twig\TwigFilter('contact_api_model_export', [$this, 'exportContactApiModel']),
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

	public function getNagiosUrl(): string {
		return $this->container['devture_nagios.nagios_url'];
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

	public function canUserManageContacts(User $user) {
		return $this->getAccessChecker()->canUserManageContacts($user);
	}

	public function canUserCreateContacts(User $user) {
		return $this->getAccessChecker()->canUserCreateContacts($user);
	}

	public function canUserDoConfigurationManagement(User $user) {
		return $this->getAccessChecker()->canUserDoConfigurationManagement($user);
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

