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
$config_class = new Config;
if (!isset($_SESSION['config']['md5']) || $_SESSION['config']['md5'] != $config_class->getMd5()) {
    $config = $config_class->get();
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
unset($config_class);

// Configure Twig
$loader = new Twig_Loader_Filesystem(_LIB_.'/pages');
$twig = new Twig_Environment($loader);
$twig->addGlobal('_SESSION_', $_SESSION);

// Add Config to twig
if($_SESSION['config']) {
	$twig->addGlobal('_SITE_CONFIG_', $_SESSION['config']);
}

// Check if inactive for 20min
$auth =  new Auth();
if ($auth->isAuth(false)) {
    if (($_SESSION['last_active'] + 1200) < time()) {
        d($_SESSION['last_active']);
        d(time());
        d($_SESSION['last_active'] + 1200);
        d(($_SESSION['last_active'] + 1200) < time());
        die();
        $auth->logout();
    }
    $_SESSION['last_active'] = time();
}
unset($auth);
