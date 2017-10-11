<?php
require '../lib/session.php';

// Set Classes
$auth = new Auth;
$album_class = new Album;
$config_class = new Config;
d($router->routes);
d($router->route());