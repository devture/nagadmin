<?php

namespace Devture\Bundle\NagiosBundle\Twig;

use Devture\Bundle\NagiosBundle\ApiModelBridge\ContactBridge;
use Devture\Bundle\NagiosBundle\Model\Contact;
use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Status\Manager as StatusManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Exposes Nagios live status to the templates (the navbar status badge and
 * the per-page "is Nagios running?" banners) plus the contact API-model
 * export filter. Backed by the Status\Manager (reads status.dat) and the
 * ContactBridge. Further helpers are added here as the templates that need
 * them are ported.
 */
class NagiosExtension extends AbstractExtension
{
    public function __construct(
        private readonly StatusManager $statusManager,
        private readonly ContactBridge $contactBridge,
        #[Autowire('%nagadmin.nagios.url%')]
        private readonly string $nagiosUrl,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('devture_nagios_get_info_status', $this->getInfoStatus(...)),
            new TwigFunction('devture_nagios_get_program_status', $this->getProgramStatus(...)),
            new TwigFunction('devture_nagios_get_service_status', $this->getServiceStatus(...)),
            new TwigFunction('devture_nagios_get_nagios_url', $this->getNagiosUrl(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('contact_api_model_export', $this->exportContactApiModel(...)),
        ];
    }

    public function exportContactApiModel(Contact $contact): array
    {
        return $this->contactBridge->export($contact);
    }

    public function getInfoStatus()
    {
        return $this->statusManager->getInfoStatus();
    }

    public function getProgramStatus()
    {
        return $this->statusManager->getProgramStatus();
    }

    public function getServiceStatus(Service $service)
    {
        return $this->statusManager->getServiceStatus($service);
    }

    public function getNagiosUrl(): string
    {
        return $this->nagiosUrl;
    }
}
