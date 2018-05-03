<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs(
    [
        $config->application->controllersDir,
        $config->application->modelsDir,
        $config->application->utilDir,
    ]
)->register();

$loader->registerNamespaces([
    'CKC\Utilities'     => $config->application->utilDir
])->register();
