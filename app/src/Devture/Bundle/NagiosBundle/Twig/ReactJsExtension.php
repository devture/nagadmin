<?php

namespace Devture\Bundle\NagiosBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides reactjs_render_component(name, props): a placeholder element
 * that the front-end (Nagadmin._initReact in public/js/nagadmin.js) finds,
 * lazy-loads the component bundle for (via comploader) and mounts React onto.
 */
class ReactJsExtension extends AbstractExtension
{
	public function getFunctions(): array
	{
		return [
			new TwigFunction('reactjs_render_component', $this->renderComponent(...), ['is_safe' => ['html']]),
		];
	}

	/**
	 * @param array<string, mixed> $props
	 * @param array<string, mixed> $options
	 */
	public function renderComponent(string $name, array $props = [], array $options = []): string
	{
		$options = array_merge([
			'placeholderHtml' => '<i class="fas fa-2x fa-circle-notch fa-spin mt-1"></i>',
		], $options);

		return '
			<div class="js-react-component"
				data-component-name="' . htmlspecialchars($name, ENT_QUOTES) . '"
				data-component-props=\'' . json_encode($props, JSON_HEX_APOS | JSON_THROW_ON_ERROR) . '\'>
				' . $options['placeholderHtml'] . '
			</div>
		';
	}
}
