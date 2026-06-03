<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides the static_url() Twig helper: it appends the file's modification
 * time as a cache-busting query string, so updated CSS/JS bypass the browser
 * cache (the nginx vhost serves \.(css|js)\?<digits> with a far-future expiry).
 *
 * The legacy version pointed its base path at a non-existent web/ directory
 * (the docroot was public/), so stamping silently no-op'd. Here it is wired to
 * the real document root.
 */
class StaticFileStamperExtension extends AbstractExtension
{
    private readonly string $basePath;

    public function __construct(
        #[Autowire('%kernel.project_dir%/public')]
        string $basePath,
        private readonly string $baseUri = '',
    ) {
        $this->basePath = rtrim($basePath, '/');
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('static_url', $this->getStaticUrl(...)),
        ];
    }

    public function getStaticUrl(string $relativePath): string
    {
        $filePath = $this->basePath . '/' . ltrim($relativePath, '/');
        if (file_exists($filePath)) {
            return $this->baseUri . $relativePath . '?' . filemtime($filePath);
        }

        return $this->baseUri . $relativePath;
    }
}
