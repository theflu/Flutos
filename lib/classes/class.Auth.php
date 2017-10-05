<?php

class Auth {
	
	public function isAuth($redirect = true, $admin = false) {
		
		if (isset($_SESSION['username']) && isset($_SESSION['user_type'])) {
			if ($admin && $_SESSION['user_type'] != 'admin') {
				if ($redirect) $this->redirect();
				return false;
			}
			return true;
		}
		
		if ($redirect) $this->redirect();
				
		return false;
	}
	
	public function login($username, $password, $redirect = true) {
		if (isset($_SESSION['config']['users'][$username]) && password_verify($password, $_SESSION['config']['users'][$username]['password'])) {
			$_SESSION['username'] = $username;
			$_SESSION['user_type'] = $_SESSION['config']['users'][$username]['type'];
            $_SESSION['last_active'] = time();
            
			if ($redirect) $this->redirect();
			
			return true;
		}
		
		return false;
	}
	
	public function logout() {
		session_destroy();
		$this->redirect();
	}
	
	public function redirect($location = false) {
		if ($location) {
			header('Location: '.$location);
		} else {
			header('Location: /');
		}
		exit();
	}
}