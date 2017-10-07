<?php

class Config {
	
	public $error;

	private $config_path = _LIB_.'/../config/config.json';
	private $config_md5_path = _LIB_.'/../config/config.md5';

	public function get($get_md5 = true) {
		
		$config_json = file_get_contents($this->config_path);
		
		if($config_json) {
		    $config = json_decode($config_json, true);
		    if ($get_md5) $config['md5'] = $this->getMd5();
		    return $config;
        }
		
		return false;
	}

    public function getMd5() {
	    $config_md5 = file_get_contents($this->config_md5_path);
        return trim($config_md5);
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
		$username = strtolower($username);
		
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

    public function changeName($site_name) {

        $config = $this->get();

        $config['site_name'] = $site_name;
        $this->write($config);

        return $this->check($config);
    }

    public function changeDesc($site_desc) {

        $config = $this->get();

        if (is_null($site_desc)) {
            unset($config['site_description']);
        } else {
            $config['site_description'] = $site_desc;
        }
        $this->write($config);

        return $this->check($config);
    }

    public function changeGaid($ga_id) {

        $config = $this->get();

        if (is_null($ga_id)) {
            unset($config['ga_id']);
        } else {
            $config['ga_id'] = $ga_id;
        }
        $this->write($config);

        return $this->check($config);
    }
	
	public function pHash($password) {
		return password_hash($password, PASSWORD_BCRYPT);
	}
	
	public function check($new_config) {
		$config = $this->get(false);
		
		if($config == $new_config) return true;
		
		return false;
	}

    public function md5Config() {
        $config_md5 = md5_file($this->config_path);

        if ($this->getMd5() == $config_md5) return true;

        $this->writeMd5($config_md5);

        return true;
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
		
		$fp = fopen($this->config_path, 'w+');
		fwrite($fp, json_encode($new_config));
		fclose($fp);

		$this->md5Config();
	}

    public function writeMd5($config_md5) {

        $fp = fopen($this->config_md5_path, 'w+');
        fwrite($fp, $config_md5);
        fclose($fp);
    }
}