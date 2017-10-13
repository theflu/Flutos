<?php
/**
 * Created by PhpStorm.
 * User: Flu
 * Date: 10/11/2017
 * Time: 7:29 PM
 */

$router = new Router;


//
// Show albums
//

$router->get('/', function () use ($twig) {
    $album = new Album;
    $albums = $album->getAll();

    echo $twig->render('catalog.twig', array('albums' => $albums));
});


//
// Display album
//

$router->get('/album/{album_slug}', function ($album_slug) use ($twig) {
    $album = new Album($album_slug);

    if ($album) {
        echo $twig->render('album.twig', array('config' => $album->config(), 'images' => $album->images()));
    } else {
        header('Location: /');
        exit();
    }
});


//
// Upload image to album
//

$router->get('/album/{album_slug}/upload', function ($album_slug) use ($twig) {
    $auth = new Auth();
    $auth->isAuth();

    $album = new Album($album_slug);

    if ($album && ($_SESSION['username'] == $album->owner() || in_array($_SESSION['username'], $album->users()))) {
        echo $twig->render('upload.twig', array('config' => $album->config()));
    } else {
        $auth->redirect();
    }
});


//
// Submit image to album
//

$router->post('/album/{album_slug}/upload', function ($album_slug) {
    $auth = new Auth();
    $auth->isAuth();

    $album = new Album($album_slug);

    if ($album && ($_SESSION['username'] == $album->owner() || in_array($_SESSION['username'], $album->users()))) {
        if ($_FILES && isset($_FILES['image'])) $album->upload($_FILES['image']);
    } else {
        $auth->redirect();
    }
});


//
// Edit album settings
//

$router->get('/album/{album_slug}/edit', function ($album_slug) use ($twig) {
    $auth = new Auth();
    $auth->isAuth();

    $album = new Album($album_slug);

    if ($album && ($_SESSION['username'] == $album->owner() or $_SESSION['user_type'] == 'admin')) {
        $site_config = new Config();

        echo $twig->render('edit.twig', array('config' => $album->config(), 'users' => $site_config->users()));
    } else {
        $auth->redirect();
    }
});


//
// Submit album settings
//

$router->post('/album/{album_slug}/edit', function ($album_slug) use ($twig) {
    $auth = new Auth();
    $auth->isAuth();

    $album = new Album($album_slug);

    $vars = array();

    if ($album && ($_SESSION['username'] == $album->owner or $_SESSION['user_type'] == 'admin')) {
        if (isset($_POST['title']) && isset($_POST['description'])) {
            if ($album->edit($_POST['title'], $_POST['description'], $_POST['tags'], $_POST['allowedUsers'])) {
                $vars['msg'] = array('type' => 'success', 'msg' => 'Album updated successfully');
            } else {
                $vars['msg'] = array('type' => 'danger', 'msg' => $album->error);
            }
        } else {
            $vars['msg'] = array('type' => 'danger', 'msg' => 'Title and description are required');
        }

        $site_config = new Config();

        $vars['config'] = $album->config();
        $vars['users'] =$site_config->users();
        $vars['post'] = $_POST;

        echo $twig->render('edit.twig', $vars);
    } else {
        $auth->redirect();
    }
});


//
// Delete album
//

$router->get('/album/{album_slug/delete', function ($album_slug) {
    $auth = new Auth();
    $auth->isAuth();

    $album = new Album($album_slug);

    if ($album && ($_SESSION['username'] == $album->owner() or $_SESSION['user_type'] == 'admin')) {
        $album->delete();
    } else {
        $auth->redirect();
    }
});


//
// Set default image for album
//

$router->get('/album/{album_slug/{image}}/default', function ($album_slug, $image) {
    $auth = new Auth();
    $auth->isAuth();

    $album = new Album($album_slug);

    if ($album && ($_SESSION['username'] == $album->owner() or $_SESSION['user_type'] == 'admin')) {
        $album->setDefault($image);
    } else {
        $auth->redirect();
    }
});


//
// Delete image from album
//

$router->get('/album/{album_slug}/{image}/delete', function ($album_slug, $image) {
    $auth = new Auth();
    $auth->isAuth();

    $album = new Album($album_slug);

    if ($album && ($_SESSION['username'] == $album->owner() or $_SESSION['user_type'] == 'admin')) {
        $album->deletePhoto($image);
    } else {
        $auth->redirect();
    }
});


//
// Display single image
//

$router->get('/album/{album_slug}/{image}', function ($album_slug, $image) use ($twig) {
    $album = new Album($album_slug);

    if ($album) $album->showImage($image);
});


//
// Show weighted album tags
//

$router->get('/tags', function () use ($twig) {
    $album = new Album;

    echo $twig->render('tags.twig', array('tags' => $album->allTags()));
});


//
// Display albums with certain tag
//

$router->get('/tag/{tag}', function ($tag) use ($twig) {
    $album = new Album;
    $albums = $album->byTag($tag);

    echo $twig->render('tag.twig', array('albums' => $albums, 'tag' => $tag));
});


//
// Login page
//

$router->get('/login', function () use ($twig) {
    $auth = new Auth();

    if ($auth->isAuth(false)) {
        header('Location /');
        exit();
    }

    echo $twig->render('login.twig');
});


//
// Login page submit
//

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
    $auth = new Auth();
    $auth->logout();
});