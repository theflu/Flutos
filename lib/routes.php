<?php
/**
 * Created by PhpStorm.
 * User: Flu
 * Date: 10/11/2017
 * Time: 7:29 PM
 */

$router = new Router;

$router->get('/', function (){
    $album_class = new Album;
    $albums = $album_class->getAll();

    echo $twig->render('catalog.twig', array('albums' => $albums));
});


$router->get('/album/{album_slug}', function ($album_slug){
    $album_class = new Album;
    
    if (($album = $album_class->get($album_slug, true))) {
        echo $twig->render('album.twig', $album);
    } else {
        header('Location: /');
        exit();
    }
});