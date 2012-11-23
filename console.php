<?php
$app = require 'src/bootstrap.php';
require 'src/setup_console.php';

$app->boot();

$app['console']->run();