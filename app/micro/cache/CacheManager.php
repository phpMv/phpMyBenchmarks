<?php

namespace micro\cache;

use mindplay\annotations\Annotations;
use mindplay\annotations\AnnotationCache;
use mindplay\annotations\AnnotationManager;
use micro\orm\parser\ModelParser;
use micro\utils\JArray;
use micro\controllers\Router;
use micro\controllers\Startup;

class CacheManager {
	public static $cache;
	private static $routes=[ ];
	private static $cacheDirectory;

	public static function start(&$config) {
		self::$cacheDirectory=self::initialGetCacheDirectory($config);
		$cacheDirectory=ROOT . DS . self::$cacheDirectory;
		Annotations::$config['cache']=new AnnotationCache($cacheDirectory . '/annotations');
		self::register(Annotations::getManager());
		self::$cache=new ArrayCache($cacheDirectory, ".cache");
	}

	public static function startProd(&$config) {
		self::$cacheDirectory=self::initialGetCacheDirectory($config);
		$cacheDirectory=ROOT . DS . self::$cacheDirectory;
		self::$cache=new ArrayCache($cacheDirectory, ".cache");
	}

	public static function getControllerCache() {
		if (self::$cache->exists("controllers/routes"))
			return self::$cache->fetch("controllers/routes");
		return [ ];
	}

	public static function getRouteCache($routePath) {
		$key=self::getRouteKey($routePath);
		if (self::$cache->exists("controllers/" . $key))
			return self::$cache->fetch("controllers/" . $key);
		else {
			$response=Startup::runAsString($routePath);
			return self::storeRouteResponse($key, $response);
		}
	}

	public static function setRouteCache($routePath) {
		$key=self::getRouteKey($routePath);
		$response=Startup::runAsString($routePath);
		return self::storeRouteResponse($key, $response);
	}

	private static function storeRouteResponse($key, $response) {
		self::$cache->store("controllers/" . $key, $response, false);
		return $response;
	}

	private static function getRouteKey($routePath) {
		return "path" . \md5(\implode("", $routePath));
	}

	private static function initialGetCacheDirectory(&$config) {
		$cacheDirectory=@$config["cacheDirectory"];
		if (!isset($cacheDirectory)) {
			$config["cacheDirectory"]="cache/";
			$cacheDirectory=$config["cacheDirectory"];
		}
		return $cacheDirectory;
	}

	public static function getCacheDirectory() {
		return self::$cacheDirectory;
	}

	public static function createOrmModelCache($className) {
		$key=\str_replace("\\", DIRECTORY_SEPARATOR, $className);
		if (!self::$cache->exists($key)) {
			$p=new ModelParser();
			$p->parse($className);
			self::$cache->store($key, $p->__toString());
		}
		return self::$cache->fetch($key);
	}

	private static function addControllerCache($classname) {
		$parser=new ControllerParser();
		try {
			$parser->parse($classname);
			self::$routes=\array_merge($parser->asArray(), self::$routes);
		} catch ( \Exception $e ) {
			// Nothing to do
		}
	}

	public static function checkCache(&$config) {
		$cacheDirectory=self::initialGetCacheDirectory($config);
		$modelsDir=str_replace("\\", DS, $config["mvcNS"]["models"]);
		$controllersDir=str_replace("\\", DS, $config["mvcNS"]["controllers"]);
		echo "cache directory is " . ROOT . DS . $cacheDirectory . "\n";
		$annotationCacheDir=ROOT . DS . $cacheDirectory . DS . "annotations";
		$modelsCacheDir=ROOT . DS . $cacheDirectory . DS . $modelsDir;
		$queriesCacheDir=ROOT . DS . $cacheDirectory . DS . "queries";
		$controllersCacheDir=ROOT . DS . $cacheDirectory . DS . $controllersDir;
		self::safeMkdir($annotationCacheDir);
		self::safeMkdir($modelsCacheDir);
		self::safeMkdir($controllersCacheDir);
		self::safeMkdir($queriesCacheDir);
		return [ "annotations" => $annotationCacheDir,"models" => $modelsCacheDir,"controllers" => $controllersCacheDir,"queries" => $queriesCacheDir ];
	}

	private static function safeMkdir($dir) {
		if (!is_dir($dir))
			return mkdir($dir, 0777, true);
	}

	private static function deleteAllFilesFromFolder($folder) {
		$files=glob($folder . '/*');
		foreach ( $files as $file ) {
			if (is_file($file))
				unlink($file);
		}
	}

	public static function clearCache(&$config, $type="all") {
		$cacheDirectories=self::checkCache($config);
		if ($type === "all") {
			self::deleteAllFilesFromFolder($cacheDirectories["annotations"]);
		}
		if ($type === "all" || $type === "controllers")
			self::deleteAllFilesFromFolder($cacheDirectories["controllers"]);
		if ($type === "all" || $type === "models")
			self::deleteAllFilesFromFolder($cacheDirectories["models"]);
		if ($type === "all" || $type === "queries")
			self::deleteAllFilesFromFolder($cacheDirectories["queries"]);
	}

	public static function initCache(&$config, $type="all") {
		self::checkCache($config);
		self::start($config);
		if ($type === "all" || $type === "models")
			self::initModelsCache($config);
		if ($type === "all" || $type === "controllers")
			self::initControllersCache($config);
	}

	private static function initModelsCache(&$config) {
		$modelsNS=$config["mvcNS"]["models"];
		$modelsDir=ROOT . DS . str_replace("\\", DS, $modelsNS);
		echo "Models directory is " . ROOT . $modelsNS . "\n";
		$files=glob($modelsDir . DS . '*');
		$namespace="";
		if (isset($modelsNS) && $modelsNS !== "")
			$namespace=$modelsNS . "\\";
		foreach ( $files as $file ) {
			if (is_file($file)) {
				$model=self::getClassNameFromFile($file, $namespace);
				new $model();
			}
		}
	}

	private static function getClassNameFromFile($file, $namespace="") {
		$fileName=pathinfo($file, PATHINFO_FILENAME);
		return $namespace . ucfirst($fileName);
	}

	private static function initControllersCache(&$config) {
		$controllersNS=$config["mvcNS"]["controllers"];
		$controllersDir=ROOT . DS . str_replace("\\", DS, $controllersNS);
		echo "Controllers directory is " . ROOT . $controllersNS . "\n";
		$files=glob($controllersDir . DS . '*');
		$namespace="";
		if (isset($controllersNS) && $controllersNS !== "")
			$namespace=$controllersNS . "\\";
		foreach ( $files as $file ) {
			if (is_file($file)) {
				$controller=self::getClassNameFromFile($file, $namespace);
				self::addControllerCache($controller);
			}
		}
		if ($config["debug"])
			self::addAdminRoutes();
		self::$cache->store("controllers/routes", "return " . JArray::asPhpArray(self::$routes, "array") . ";");
	}

	private static function register(AnnotationManager $annotationManager) {
		$annotationManager->registry=array_merge($annotationManager->registry, [ 'id' => 'micro\annotations\IdAnnotation','manyToOne' => 'micro\annotations\ManyToOneAnnotation','oneToMany' => 'micro\annotations\OneToManyAnnotation','manyToMany' => 'micro\annotations\ManyToManyAnnotation','joinColumn' => 'micro\annotations\JoinColumnAnnotation','table' => 'micro\annotations\TableAnnotation','transient' => 'micro\annotations\TransientAnnotation','column' => 'micro\annotations\ColumnAnnotation','joinTable' => 'micro\annotations\JoinTableAnnotation','route' => 'micro\annotations\router\RouteAnnotation' ]);
	}

	public static function addAdminRoutes() {
		self::addControllerCache("micro\controllers\Admin");
	}

	public static function getRoutes() {
		$result=self::getControllerCache();
		return $result;
	}

	public static function addRoute($path, $controller, $action="index", $methods=null, $name="") {
		$controllerCache=self::getControllerCache();
		Router::addRouteToRoutes($controllerCache, $path, $controller, $action, $methods, $name);
		self::$cache->store("controllers/routes", "return " . JArray::asPhpArray($controllerCache, "array") . ";");
	}
}
