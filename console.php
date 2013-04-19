<?php
$app = require 'src/bootstrap.php';

$app['console'] = new \Symfony\Component\Console\Application();

$app->boot();

$app['console']->run();