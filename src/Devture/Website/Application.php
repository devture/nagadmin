<?php
namespace Devture\Website;

use Symfony\Component\HttpFoundation\Request;

class Application extends \Silex\Application {

	protected $basePath;

	public function __construct($basePath) {
		parent::__construct();

		//Make sure we're running with some UTF-8,
		//otherwise things like `escapeshellarg()` may ignore UTF-8 characters.
		setlocale(LC_CTYPE, 'en_US.UTF-8');

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
			Request::setTrustedProxies($this['config']['trusted_proxies'], Request::HEADER_X_FORWARDED_ALL);
		}

		$app['app_base_path'] = $this->basePath;

		$app->register(new \Silex\Provider\TwigServiceProvider(), array(
			'twig.options' => array(
				'cache' => $this->basePath . '/var/cache/twig',
				'auto_reload' => $this['debug'],
				'strict_variables' => true
			),
			'twig.path' => array($this->basePath . '/src/views'),
		));

		$app->register(new \Silex\Provider\SwiftmailerServiceProvider());

		$app->register(new \Devture\SilexProvider\DoctrineMongoDB\ServicesProvider('mongodb', $this['config']['MongoDBProviderConfig']));

		$app['mongodb.database'] = function ($app) {
			return $app['mongodb.connection']->selectDatabase($app['config']['mongo']['db_name']);
		};

		$app->register(new \Devture\Bundle\FrameworkBundle\ServicesProvider($this['config']['FrameworkBundle']));
		$app->register(new \Devture\Bundle\LocalizationBundle\ServicesProvider($app['config']['LocalizationBundle']));
		$app->register(new \Devture\Bundle\UserBundle\ServicesProvider($app['config']['UserBundle']));

		$app->register(new \Devture\Bundle\NagiosBundle\ServicesProvider($app['config']['NagiosBundle']));
	}

}
