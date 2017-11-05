<?php
session_start();

// Set paths
define("_LIB_", (dirname(__FILE__)));
define("_CLASSES_", _LIB_.'/classes');
define("_ALBUMS_", _LIB_.'/../albums');

// Load functions
require _LIB_.'/functions.php';

// Load 3rd party libs
require_once (_LIB_ . '/vendor/autoload.php');

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
$site_config = new Config;
if (!isset($_SESSION['config']['md5']) || $_SESSION['config']['md5'] != $site_config->getMd5()) {
    $config = $site_config->get();
    if (!$config && $_SERVER['REQUEST_URI'] != '/setup') {
        header('Location: /setup');
        exit();
    } elseif ($config) {

        $_SESSION['config'] = $config;

        if($_SERVER['REQUEST_URI'] == '/setup') {
            header('Location: /');
            exit();
        }
    }
}
unset($site_config);

// Configure Twig
$loader = new Twig_Loader_Filesystem(_LIB_.'/pages');
$twig = new Twig_Environment($loader);
$twig->addGlobal('_SESSION_', $_SESSION);
$twig->addGlobal('_DOMAIN_', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]");
$twig->addGlobal('_URL_', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

// Add Config to twig
if($_SESSION['config']) {
	$twig->addGlobal('_SITE_CONFIG_', $_SESSION['config']);
}

// Check if inactive for 20min
$auth =  new Auth();
if ($auth->isAuth(false)) {
    if (($_SESSION['last_active'] + 1200) < time()) {
        $auth->logout();
    }
    $_SESSION['last_active'] = time();
}
unset($auth);

// Load Routes
require _LIB_.'/routes.php';
