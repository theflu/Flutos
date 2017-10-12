<?php
/**
 * Created by PhpStorm.
 * User: Flu
 * Date: 10/11/2017
 * Time: 7:29 PM
 */

$router = new Router;

$router->get('/', function () use ($twig) {
    $album_class = new Album;
    $albums = $album_class->getAll();

    echo $twig->render('catalog.twig', array('albums' => $albums));
});


$router->get('/album/{album_slug}', function ($album_slug) use ($twig) {
    $album_class = new Album;

    if (($album = $album_class->get($album_slug, true))) {
        echo $twig->render('album.twig', $album);
    } else {
        header('Location: /');
        exit();
    }
});


$router->get('/album/{album_slug}/upload', function ($album_slug, $image) use ($twig) {
    $album_class = new Album;

    $album_class->showImage($album_slug, $image);
});

$router->get('/album/{album_slug}/edit', function ($album_slug, $image) use ($twig) {
    $album_class = new Album;

    $album_class->showImage($album_slug, $image);
});

$router->get('/album/{album_slug}/delete', function ($album_slug, $image) {
    $album_class = new Album;

    $album_class->showImage($album_slug, $image);
});

$router->get('/album/{album_slug}/{image}', function ($album_slug, $image) use ($twig) {
    $album_class = new Album;

    $album_class->showImage($album_slug, $image);
});

$router->get('/album/{album_slug}/{image}/default', function ($album_slug, $image) {
    $album_class = new Album;

    $album_class->showImage($album_slug, $image);
});

$router->get('/album/{album_slug}/{image}/delete', function ($album_slug, $image) {
    $album_class = new Album;

    $album_class->showImage($album_slug, $image);
});

$router->get('/tags', function () use ($twig) {
    $album_class = new Album;
    $tags = $album_class->getTags();

    echo $twig->render('tags.twig', array('tags' => $tags));
});

$router->get('/tag/{tag}', function ($tag) use ($twig) {
    $album_class = new Album;
    $albums = $album_class->getTag($tag);

    echo $twig->render('tag.twig', array('albums' => $albums, 'tag' => $tag));
});

$router->get('/login', function () use ($twig) {
    $auth = new Auth();

    if ($auth->isAuth(false)) {
        header('Location /');
        exit();
    }

    echo $twig->render('login.twig');
});

$router->post('/login', function () use ($twig) {
    $auth = new Auth();

    if ($auth->isAuth(false)) {
        header('Location /');
        exit();
    }

    $vars = array();

    if(isset($_POST['username']) && isset($_POST['password'])) {

        if (!$auth->login(trim($_POST['username']), trim($_POST['password']))) {
            $vars['msg'] = array('type' => 'danger', 'msg' => 'Username or password is incorrect.');
        }

    } else {
        $vars['msg'] = array('type' => 'danger', 'msg' => 'All fields are required.');
    }

    $vars['username'] = $_POST['username'];

    echo $twig->render('login.twig', $vars);
});

$router->get('/create', function ($album_slug, $image) use ($twig) {
    $album_class = new Album;

    $album_class->showImage($album_slug, $image);
});

$router->get('/change-password', function ($album_slug, $image) use ($twig) {
    $album_class = new Album;

    $album_class->showImage($album_slug, $image);
});

$router->get('/settings', function ($album_slug, $image) use ($twig) {
    $album_class = new Album;

    $album_class->showImage($album_slug, $image);
});

$router->get('/add-user', function ($album_slug, $image) use ($twig) {
    $album_class = new Album;

    $album_class->showImage($album_slug, $image);
});

$router->get('/delete-user', function () use ($twig) {
    $auth = new Auth();
    $auth->isAuth(true, true);

    $vars = array('users' => $_SESSION['config']['users']);

    echo $twig->render('delete-user.twig', $vars);
});

$router->post('/delete-user', function () use ($twig) {
    $auth = new Auth();
    $auth->isAuth(true, true);

    $vars = array();
    $username = trim($_POST['username']);

    if(!empty($username) && $username != $_SESSION['username']) {
        $config_class = new Config();
        $config_class->deleteUser($username);
        $vars['users'] =$config_class->users();
        $vars['msg'] = array('type' => 'danger', 'msg' => 'User deleted successfully');
    } else {
        $vars['msg'] = array('type' => 'danger', 'msg' => 'Invalid username');
    }

    if (!isset($vars['users']))$vars['users'] = $_SESSION['config']['users'];

    echo $twig->render('delete-user.twig', $vars);
});

$router->get('/logout', function () {
    Album::logout();
});