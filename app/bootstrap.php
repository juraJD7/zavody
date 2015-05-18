<?php

require __DIR__ . '/../vendor/autoload.php';

use Nette\Forms\Container;
use Nextras\Forms\Controls;

Container::extensionMethod('addDatePicker', function (Container $container, $name, $label = NULL) {
    return $container[$name] = new Controls\DatePicker($label);
});

$configurator = new Nette\Configurator;

$configurator->setDebugMode(array('88.100.187.117', '147.251.46.106', '89.177.144.139'));
$configurator->setDebugMode(FALSE);
$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__) 
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();




return $container;
