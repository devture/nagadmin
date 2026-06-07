<?php

namespace Devture\Bundle\NagiosBundle\Twig;

use Devture\Bundle\NagiosBundle\ApiModelBridge\ContactBridge;
use Devture\Bundle\NagiosBundle\Helper\Colorizer;
use Devture\Bundle\NagiosBundle\Model\Contact;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Repository\ServiceRepository;
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
        private readonly Colorizer $colorizer,
        private readonly ServiceRepository $serviceRepository,
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
            new TwigFunction('devture_nagios_count_host_services', $this->countHostServices(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('contact_api_model_export', $this->exportContactApiModel(...)),
            new TwigFilter('devture_nagios_colorize', $this->colorize(...)),
        ];
    }

    public function countHostServices(Host $host): int
    {
        return $this->serviceRepository->countByHost($host);
    }

    public function colorize(string $value): string
    {
        return $this->colorizer->colorize($value);
    }

    /**
     * @return array<string, mixed>
     */
    public function exportContactApiModel(Contact $contact): array
    {
        return $this->contactBridge->export($contact);
    }

    /**
     * @return \Devture\Bundle\NagiosBundle\Status\InfoStatus|null
     */
    public function getInfoStatus()
    {
        return $this->statusManager->getInfoStatus();
    }

    /**
     * @return \Devture\Bundle\NagiosBundle\Status\ProgramStatus|null
     */
    public function getProgramStatus()
    {
        return $this->statusManager->getProgramStatus();
    }

    /**
     * @return \Devture\Bundle\NagiosBundle\Status\ServiceStatus|null
     */
    public function getServiceStatus(Service $service)
    {
        return $this->statusManager->getServiceStatus($service);
    }

    public function getNagiosUrl(): string
    {
        return $this->nagiosUrl;
    }
}
