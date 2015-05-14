<?php

require __DIR__ . '/../vendor/autoload.php';

use Nette\Forms\Container;
use Nextras\Forms\Controls;

Container::extensionMethod('addDatePicker', function (Container $container, $name, $label = NULL) {
    return $container[$name] = new Controls\DatePicker($label);
});

$configurator = new Nette\Configurator;

$configurator->setDebugMode(array('88.100.187.117', '2001:718:801:22e:1101:f2ff:4f4a:14d5', '147.251.46.106', '89.177.144.139'));
$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__) 
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();




return $container;
