<?php
require '../lib/session.php';

// Set Classes
$auth = new Auth;
$album_class = new Album;
$config_class = new Config;

// Breakup request URI
$query = explode('/', $_GET['q']);
if (isset($query[0])) $query[0] = strtolower($query[0]);
if (isset($query[1]))$query[1] = strtolower($query[1]);


//
// Album catalog
//
if (empty($query[0])) {
	$albums = $album_class->getAll();
	echo $twig->render('catalog.twig', array('albums' => $albums));
}


//
// Display Album
//
if ($query[0] == 'a') {
	if (($album = $album_class->get($query[1], true))) {
		echo $twig->render('album.twig', $album);
	} else {
		header('Location: /');
		exit();
	}
}


//
// Show Image
//
if ($query[0] == 'i') {
	$album_class->showImage($query[1], $query[2]);
}


//
// Show Image Thumbnail
//
if ($query[0] == 'it') {
	// Display image thumbnail
	if (!$album_class->showImage($query[1], $query[2], true)) {
		d($album_class->error);
		d($query);
	}
}


//
// Show Tag Cloud
//
if ($query[0] == 'tagcloud') {
	// Display image thumbnail
	$tags = $album_class->getTags();
	echo $twig->render('tagcloud.twig', array('tags' => $tags));
}


//
// Show Tag Results
//
if ($query[0] == 'tag') {
	// Display tag
	$albums = $album_class->getTag($query[1]);
	echo $twig->render('tag.twig', array('albums' => $albums, 'tag' => $query[1]));
}


//
//Display Setup Page
//
if ($query[0] == 'setup') {
	// Initial setup
	$vars = array();
	
	if($_POST) {
		if(isset($_POST['siteName']) && isset($_POST['username']) && isset($_POST['password'])) {
			if($_POST['password'] == $_POST['passwordConf']) {
				if ($config_class->create($_POST['siteName'], $_POST['username'], $_POST['password'])) {
					header('Location: /');
					exit();
				} else {
					$vars['msg'] = array('type' => 'danger', 'msg' => 'Error creating config file');
				}
			} else {
				$vars['msg'] = array('type' => 'danger', 'msg' => 'Passwords do not match');
			}
		} else {
			$vars['msg'] = array('type' => 'danger', 'msg' => 'All fields are required.');
		}
		
		$vars['post'] = $_POST;
	}
	
	echo $twig->render('setup.twig', $vars);
}


//
// Signin Page
//
if ($query[0] == 'signin') {
	//Check to see if the user is currently logged in
	if ($auth->isAuth(false)) {
		header('Location /');
		exit();
	}
	
	$vars = array();
	
	if($_POST) {
		if(isset($_POST['username']) && isset($_POST['password'])) {
			
			if (!$auth->login(trim($_POST['username']), trim($_POST['password']))) {
				$vars['msg'] = array('type' => 'danger', 'msg' => 'Username or password is incorrect.');
			}
			
		} else {
			$vars['msg'] = array('type' => 'danger', 'msg' => 'All fields are required.');
		}
		
		$vars['username'] = $_POST['username'];
	}
	
	echo $twig->render('signin.twig', $vars);
}


//
// Change Password
//
if ($query[0] == 'change-password') {
	
	$auth->isAuth();
	
	$vars = array();
	
	if($_POST) {
		if(isset($_POST['password']) && isset($_POST['password-confirm'])) {
			
			$password = trim($_POST['password']);
			$password_confirm = trim($_POST['password-confirm']);
			
			if($password == $password_confirm) {
				
				if(strlen($password) >= 6) {
			
					if ($config_class->changePassword($_SESSION['username'], $password)) {
						$vars['msg'] = array('type' => 'success', 'msg' => 'Password changed successfully');
					} else {
						$vars['msg'] = array('type' => 'danger', 'msg' => 'Failed to change password');
					}
				} else {
					$vars['msg'] = array('type' => 'danger', 'msg' => 'Password must be atleast 6 charactors long');
				}				
			} else {
				$vars['msg'] = array('type' => 'danger', 'msg' => 'Passwords do not match');
			}
		} else {
			$vars['msg'] = array('type' => 'danger', 'msg' => 'All fields are required');
		}
		
		$vars['username'] = $_POST['username'];
	}
	
	echo $twig->render('change-password.twig', $vars);
}


//
// Add User
//
if ($query[0] == 'add-user') {
	
	$auth->isAuth(true, true);
	
	$vars = array();
	
	if($_POST) {
		if(isset($_POST['username']) && isset($_POST['password']) && isset($_POST['password-confirm'])) {
			
			$username = trim($_POST['username']);
			$password = trim($_POST['password']);
			$password_confirm = trim($_POST['password-confirm']);
			
			if (!array_key_exists($username, $_SESSION['config']['users'])) {
			
				if (strlen($username) >= 3) {
				
					if($password == $password_confirm) {
						
						if(strlen($password) >= 6) {
					
							if ($config_class->addUser($username, $password)) {
								$vars['msg'] = array('type' => 'success', 'msg' => 'User added successfully');
							} else {
								$vars['msg'] = array('type' => 'danger', 'msg' => 'Failed to add user');
							}
						} else {
							$vars['msg'] = array('type' => 'danger', 'msg' => 'Password must be atleast 6 charactors long');
						}				
					} else {
						$vars['msg'] = array('type' => 'danger', 'msg' => 'Passwords do not match');
					}
				} else {
					$vars['msg'] = array('type' => 'danger', 'msg' => 'Username must be atleast 3 charactors long');
				}
			} else {
				$vars['msg'] = array('type' => 'danger', 'msg' => 'Username is already in use');
			}
		} else {
			$vars['msg'] = array('type' => 'danger', 'msg' => 'All fields are required');
		}
		
		$vars['username'] = $_POST['username'];
	}
	
	echo $twig->render('add-user.twig', $vars);
}


//
// Delete User
//
if ($query[0] == 'delete-user') {
	
	$auth->isAuth(true, true);
	$vars = array();
	
	if($_POST) {
		$username = trim($_POST['username']);
		
		if(!empty($username) && $username != $_SESSION['username']) {
			$config_class->deleteUser($username);
			$vars['msg'] = array('type' => 'danger', 'msg' => 'User deleted successfully');
		} else {
			$vars['msg'] = array('type' => 'danger', 'msg' => 'Invalid username');
		}
	}
	
	$vars['users'] = $_SESSION['config']['users'];
	
	echo $twig->render('delete-user.twig', $vars);
}


//
// Signout Page//
if ($query[0] == 'signout') {
	$auth->logout();
}


//
// Album Upload Page
//
if ($query[0] == 'u') {
	
	$auth->isAuth();
	
	if (($album = $album_class->get($query[1], true))) {
		if ($_SESSION['username'] == $album['owner'] or in_array($_SESSION['username'], $album['users'])) {
			if ($_FILES && isset($_FILES['image'])) {
				if (!$album_class->saveImage($query[1], $_FILES['image'])) echo $album_class->error;
			} else {
				echo $twig->render('upload.twig', $album);
			}
		} else {
			header('Location: /');
			exit();
		}
	} else {
		header('Location: /');
		exit();
	}
}


//
// Edit Album Config
//
if ($query[0] == 'e') {
	// Edit album
	$auth->isAuth();
	
	if (($album = $album_class->get($query[1], true))) {
		if ($_SESSION['username'] == $album['owner'] or in_array($_SESSION['username'], $album['users'])) {
			
			$users = $config_class->users();
			$vars = array('album_slug' => $query[1], 'album' => $album, 'users' => $users);
		
			if ($_POST) {
				if (isset($_POST['title']) && isset($_POST['description'])) {
					if (($album = $album_class->edit($query[1], $_POST['title'], $_POST['description'], $_POST['tags'], $_POST['allowedUsers']))) {
						header('Location: /a/'.$query[1]);
						exit();
					} else {
						$vars['msg'] = array('type' => 'danger', 'msg' => $album_class->error);
					}
				} else {
					$vars['msg'] = array('type' => 'danger', 'msg' => 'Title and description are required');
				}
				$vars['post'] = $_POST;
			}
			
			echo $twig->render('edit.twig', $vars);
		} else {
			header('Location: /');
			exit();
		}
	} else {
		header('Location: /');
		exit();
	}
}


//
// Create New Album
//
if ($query[0] == 'create') {
	// Create album
	$auth->isAuth();
	
	$users = $config_class->users();
	$vars = array('users' => $users);
	
	if ($_POST) {
		if (isset($_POST['title']) && isset($_POST['description'])) {
			if (($album = $album_class->create($_POST['title'], $_POST['description'], $_POST['tags'], $_POST['allowedUsers']))) {
				header('Location: /a/'.$album);
				exit();
			} else {
				$vars['msg'] = array('type' => 'danger', 'msg' => $album_class->error);
			}
		} else {
			$vars['msg'] = array('type' => 'danger', 'msg' => 'Title and description are required');
		}
		$vars['post'] = $_POST;
	}
	
	echo $twig->render('create.twig', $vars);
}


//
// Delete Album
//
if ($query[0] == 'd') {

    $auth->isAuth();

    if (isset($query[1])) $album_class->delete($query[1]);

    header('Location: /');
    exit();
}


//
// Site Setting
//
if ($query[0] == 'settings') {

    $auth->isAuth(true, true);

    $vars = array();

    echo $twig->render('settings.twig', $vars);
}