<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * @return list<string> An array of allowed values for APP_ENV
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }

    /**
     * When NAGADMIN_RUNTIME_DIR is set, the kernel's mutable runtime state
     * (cache, build, log) is kept there rather than under app/var. This lets
     * the container (running as the Nagios uid 100:101) write to a directory
     * that lives in the repo's prepared var/ tree, keeping the code tree clean.
     * When unset (e.g. a plain CLI run during development), it falls back to
     * Symfony's defaults under app/var.
     */
    private function getRuntimeDir(): ?string
    {
        $runtimeDir = $_SERVER['NAGADMIN_RUNTIME_DIR'] ?? $_ENV['NAGADMIN_RUNTIME_DIR'] ?? null;

        return ($runtimeDir === null || $runtimeDir === '') ? null : rtrim($runtimeDir, '/');
    }

    #[\Override]
    public function getCacheDir(): string
    {
        $runtimeDir = $this->getRuntimeDir();

        return $runtimeDir === null ? parent::getCacheDir() : $runtimeDir . '/cache/' . $this->environment;
    }

    #[\Override]
    public function getBuildDir(): string
    {
        return $this->getCacheDir();
    }

    #[\Override]
    public function getLogDir(): string
    {
        $runtimeDir = $this->getRuntimeDir();

        return $runtimeDir === null ? parent::getLogDir() : $runtimeDir . '/log';
    }
}
