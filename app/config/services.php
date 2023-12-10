<?php
use Ubiquity\controllers\Router;
use Ubiquity\cache\CacheManager;

CacheManager::startProd($config);
\Ubiquity\orm\DAO::start();
Router::start();
Router::addRoute("_default", "controllers\Nol");
\Ubiquity\assets\AssetsManager::start($config);