<?php
// Set timeout
ini_set('session.gc_maxlifetime', 1200);
session_set_cookie_params(1200);

session_start();

// Set paths
define("_LIB_", (dirname(__FILE__)));
define("_CLASSES_", _LIB_.'/classes');
define("_ALBUMS_", _LIB_.'/../albums');

// Load functions
require _LIB_.'/functions.php';

// Load 3rd party libs
require_once (_LIB_ . '/vendor/autoload.php');

die(_LIB_);

// Load classes
spl_autoload_register(function ($class)
{
	$file = _CLASSES_ . '/class.' . $class . '.php';
	
	if(file_exists($file))
	{
		require_once $file;
	}
});

// Get config
$config_class = new Config;
$config = $config_class->get();
if (!$config && $_SERVER['REQUEST_URI'] != '/setup') {
	header('Location: /setup');
	exit();
} elseif ($config && $_SERVER['REQUEST_URI'] == '/setup') {
	header('Location: /');
	exit();
}

// Configure Twig
$loader = new Twig_Loader_Filesystem(_LIB_.'/pages');
$twig = new Twig_Environment($loader);
$twig->addGlobal('_SESSION_', $_SESSION);

if($config) {
	$twig->addGlobal('_SITE_NAME_', $config['site_name']);
} else {
	$twig->addGlobal('_SITE_NAME_', 'Flutos');
}
