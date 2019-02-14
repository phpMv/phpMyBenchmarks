<?php
use Ubiquity\controllers\Router;
use Ubiquity\cache\CacheManager;

CacheManager::startProd($config);
$db=$config["database"];
\Ubiquity\orm\DAO::connect($db["type"],$db["dbName"],@$db["serverName"],@$db["port"],@$db["user"],@$db["password"],@$db["options"],@$db["cache"]);
Router::start();
Router::addRoute("_default", "controllers\Nol");