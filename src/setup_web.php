<?php
use Symfony\Component\HttpFoundation\Request;
use Devture\Website\Twig\Extension\StaticFileStamperExtension;

$webroot = dirname(dirname(__FILE__)) . '/web/';
$app['twig']->addExtension(new StaticFileStamperExtension($webroot));
$app['twig']->addGlobal('layout', 'layout.html.twig');

$app->mount('/', $app['devture_nagios.controllers_provider.management']);

$app->get('/', function () use ($app) {
	return $app['twig']->render('index.html.twig');
})->bind('homepage');