<?php

class Config {
	
	public $error;	
	
	public function get() {
		
		$config_json = file_get_contents(_LIB_.'/config.json');
		
		if($config_json) return json_decode($config_json, true);
		
		return false;
	}
	
	public function siteName() {
		$config = $this->get();
		
		return $config['site_name'];
	}
	
	public function users() {
		$config = $this->get();
		
		return $config['users'];
	}
	
	public function addUser($username, $password, $type = 'user') {
		$password_hash = $this->pHash($password);
		
		$config = $this->get();
		
		$config['users'][$username] = array('password' => $password_hash, 'type' => $type);
		
		$this->write($config);
		
		return $this->check($config);
	}
	
	public function deleteUser($username) {
		
		$config = $this->get();
		
		unset($config['users'][$username]);
		
		$this->write($config);
		
		return $this->check($config);
	}
	
	public function changePassword($username, $password) {
		
		$config = $this->get();
		$password_hash = $this->pHash($password);
		
		if (isset($config['users'][$username])) {
			
			$config['users'][$username]['password'] = $password_hash;
			$this->write($config);
		
			return $this->check($config);
		}
		
		return false;
	}
	
	public function pHash($password) {
		return password_hash($password, PASSWORD_BCRYPT);
	}
	
	public function check($new_config) {
		$config = $this->get();
		
		if($config == $new_config) return true;
		
		return false;
	}
	
	public function create($site_name, $username, $password) {
		
		$password_hash = $this->pHash($password);
		
		$new_config = array(
			'site_name' => $site_name,
			'users'     => array(
				$username => array('password' => $password_hash, 'type' => 'admin')
			)
		);
		
		$this->write($new_config);
		
		return $this->check($new_config);
	}
	
	public function write($new_config) {
		
		$fp = fopen(_LIB_.'/config.json', 'w+');
		fwrite($fp, json_encode($new_config));
		fclose($fp);
	}
}