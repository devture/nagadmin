<?php

namespace App\Twig;

use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Status\Manager as StatusManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes Nagios live status to the templates (the navbar status badge and
 * the per-page "is Nagios running?" banners). Backed by the Status\Manager,
 * which reads status.dat. Further helpers (access checks, colorize, …) are
 * added here as the templates that need them are ported.
 */
class NagiosExtension extends AbstractExtension
{
    public function __construct(
        private readonly StatusManager $statusManager,
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
