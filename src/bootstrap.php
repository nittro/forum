<?php


declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator;

if (!Kdyby\Console\DI\BootstrapHelper::setupMode($configurator)) {
    if ($env = getenv('APP_ENV')) {
        $configurator->setDebugMode($env !== 'prod');
    } else {
        $configurator->setDebugMode(PHP_SAPI === 'cli-server');
    }
}

$rootDir = dirname(__DIR__);

$configurator->enableTracy($rootDir . '/var/log');
$configurator->setTimeZone('UTC');
$configurator->setTempDirectory($rootDir . '/var');

$configurator->addParameters([
    'rootDir' => $rootDir,
    'srcDir' => $rootDir . '/src',
    'wwwDir' => $rootDir . '/public',
    'version' => trim(@file_get_contents(__DIR__ . '/../etc/version') ?: 'dev'),
]);

$configurator->addConfig($rootDir . '/etc/config.neon');
$configurator->addConfig($rootDir . '/etc/config.local.neon');

return $configurator->createContainer();
