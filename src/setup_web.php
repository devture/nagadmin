<?php
use Devture\Website\Twig\Extension\StaticFileStamperExtension;

$webroot = dirname(dirname(__FILE__)) . '/web/';
$app['twig']->addExtension(new StaticFileStamperExtension($webroot));
$app['twig']->addGlobal('layout', 'layout.html.twig');

$app->mount('/', $app['devture_nagios.controllers_provider.management']);
$app->mount('/api', $app['devture_nagios.controllers_provider.api']);
$app['devture_user.access_control']->requireAuthForRoutePrefix('devture_nagios.');
$app['devture_user.access_control']->requireRoleForRoutePrefix('devture_nagios.resource.', 'sensitive');
$app['devture_user.access_control']->requireRoleForRoutePrefix('devture_nagios.configuration.', 'sensitive');
$app['devture_user.access_control']->requireRoleForRoutePrefix('devture_nagios.time_period.', 'configuration_management');
$app['devture_user.access_control']->requireRoleForRoutePrefix('devture_nagios.command.', 'configuration_management');

$app->mount('/user', $app['devture_user.controllers_provider.management']);
$app['devture_user.access_control']->requireRoleForRoutePrefix('devture_user.', 'devture_user', $app['devture_user.public_routes']);

$app->get('/', function () use ($app) {
	return $app->redirect($app['url_generator']->generate('devture_nagios.dashboard'));
})->bind('homepage');