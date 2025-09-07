<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$root = rtrim(getenv('CHARM_TEST_APP_ROOT') ?: 'tests/FrameworkApp', '/');
define('CHARM_APP_ROOT', $root);

// minimal env
putenv('APP_ENV=test');
putenv('ENVIRONMENT=test');
putenv('APP_DEBUG=0');

// start the kernel in test mode
Charm\Vivid\Kernel\Handler::getInstance()->startTesting();

// set up environment
Charm\Vivid\C::Storage()->deleteDirectory(Charm\Vivid\C::Storage()->getVarPath());
Charm\Vivid\C::Storage()->deleteDirectory(Charm\Vivid\C::Storage()->getDataPath());
Charm\Vivid\C::Storage()->initDirs();

// global teardown after the *last* test
register_shutdown_function(function () {
    // remove sandbox dirs
    Charm\Vivid\C::Storage()->deleteDirectory(Charm\Vivid\C::Storage()->getVarPath());
    Charm\Vivid\C::Storage()->deleteDirectory(Charm\Vivid\C::Storage()->getDataPath());

    // optional: clear test redis db, temp upload dirs, etc.

    Charm\Vivid\C::shutdown();
});
