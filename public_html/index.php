<?php
require '../lib/session.php';

// Set Classes
$auth = new Auth;
$album_class = new Album;
$config_class = new Config;

$router->route();