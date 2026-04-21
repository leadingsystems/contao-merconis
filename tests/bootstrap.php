<?php
declare(strict_types=1);

$projectAutoloadPath = __DIR__ . '/../../../autoload.php';
$bundleAutoloadPath = __DIR__ . '/../vendor/autoload.php';

if (is_file($projectAutoloadPath)) {
    require_once $projectAutoloadPath;
    return;
}

if (is_file($bundleAutoloadPath)) {
    require_once $bundleAutoloadPath;
    return;
}

throw new RuntimeException('Kein gueltiger Composer-Autoloader gefunden.');
