<?php
namespace Devture\Website;

class Application extends \Silex\Application {

	protected $basePath;

	public function __construct($basePath) {
		parent::__construct();

		$this->basePath = $basePath;

		$this->loadConfig();

		$this->initServiceProviders();
	}

	protected function loadConfig() {
		$this->register(new \Devture\SilexProvider\Config\ServicesProvider());

		$configs = array($this->basePath . '/config/config.json');
		$parameters = array($this->basePath . '/config/parameters.json');
		$this['config'] = $this['devture_config.loader']->load($configs, $parameters);
		$this['debug'] = $this['config']['debug'];
	}

	protected function initServiceProviders() {
		$app = $this;

		if ($this['config']['trusted_proxies']) {
			\Symfony\Component\HttpFoundation\Request::setTrustedProxies($this['config']['trusted_proxies']);
		}

		$app['app_base_path'] = $this->basePath;

		$app->register(new \Silex\Provider\UrlGeneratorServiceProvider());

		$app->register(new \Silex\Provider\ServiceControllerServiceProvider());

		$app->register(new \Silex\Provider\TwigServiceProvider(), array(
			'twig.options' => array(
				'cache' => $this->basePath . '/cache/twig',
				'auto_reload' => $this['debug'],
				'strict_variables' => true
			),
			'twig.path' => array($this->basePath . '/src/views'),
		));

		$app->register(new \Silex\Provider\SwiftmailerServiceProvider());

		$app->register(new \Devture\SilexProvider\DoctrineMongoDB\ServicesProvider('mongodb', array()));

		$app['mongodb.database'] = $app->share(function ($app) {
			return $app['mongodb.connection']->selectDatabase($app['config']['mongo']['db_name']);
		});

		$app->register(new \Devture\Bundle\SharedBundle\ServicesProvider($this['config']['SharedBundle']));

		$app->register(new \Devture\Bundle\NagiosBundle\ServicesProvider($app['config']['NagiosBundle']));
	}

	public function boot() {
		parent::boot();

		$this['twig']->addExtension(new \Devture\Website\Twig\Extension\ProjectExtension($this['config']['project.name']));
	}

}
