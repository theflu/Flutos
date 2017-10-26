<?php

class Config {

    public $site_config = null;
	public $error;
	private $config_path = _LIB_.'/../config/config.json';
	private $config_md5_path = _LIB_.'/../config/config.md5';

    public function __construct () {
        $this->get();
    }

	public function get($get_md5 = true) {
		
		$config_json = file_get_contents($this->config_path);
		
		if($config_json) {
		    $config = json_decode($config_json, true);
		    if ($get_md5) $config['md5'] = $this->getMd5();

            $this->site_config = $config;

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
		return $this->site_config['users'];
	}
	
	public function addUser($username, $password, $password_conf, $type = 'user') {
        if(!array_key_exists($username, $this->site_config['users'])) {
            if($this->passReq($password, $password_conf)) {
                $config_new = $this->site_config;
                $config_new['users'][$username] = array('password' => $this->pHash($password), 'type' => $type);

                $this->write($config_new);

                if ($this->check($config_new)) {
                    return true;
                } else {
                    $this->error = 'An error occurred while changing the password, please try again';
                }
            }
        } else {
            $this->error = 'Username already in use';
        }
		
		return false;
	}
	
	public function deleteUser($username) {
		
		$config = $this->get();
		
		unset($config['users'][$username]);
		
		$this->write($config);
		
		return $this->check($config);
	}

	private function passReq($password, $password_conf) {
        $password = trim($password);
        $password_confirm = trim($password_conf);

        if($password == $password_confirm) {
            if(strlen($password) >= 6) {
                return true;
            } else {
                $this->error = 'Password must be at least 6 characters long';
            }
        } else {
            $this->error = 'Passwords do not match';
        }

        return false;
    }
	
	public function changePassword($username, $password, $password_conf) {

        $password = trim($password);
        $password_conf = trim($password_conf);

        if($this->passReq($password, $password_conf)) {
            if (isset($site_config['users'][$username])) {

                $config_new = $this->site_config;
                $config_new['users'][$username]['password'] = $this->pHash($password);
                $this->write($config_new);

                if ($this->check($config_new)) {
                    return true;
                } else {
                    $this->error = 'An error occurred while changing the password, please try again';
                }
            } else {
                $this->error = 'User does not exist';
            }
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
		
		if($this->site_config == $new_config) return true;
		
		return false;
	}

    public function md5Config() {
        $config_md5 = md5_file($this->config_path);

        if ($this->getMd5() == $config_md5) return true;

        $this->writeMd5($config_md5);

        return true;
    }
	
	public function create($site_name, $username, $password, $password_conf) {
		if (!empty($site_name) && !empty($username) && !empty($password) && !empty($password_conf)) {
		    if ($this->passReq($password, $password_conf)) {
                $new_config = array(
                    'site_name' => $site_name,
                    'users'     => array(
                        $username => array('password' => $this->pHash($password), 'type' => 'admin')
                    )
                );

                $this->write($new_config);

                if ($this->check($new_config)) {
                    return true;
                } else {
                    $this->error = 'An error occurred while creating the config, make sure "config/" is writable';
                }
            }
        } else {
		    $this->error = 'All fields are required';
        }

        return false;
	}
	
	public function write($new_config) {
		
		$fp = fopen($this->config_path, 'w+');
		fwrite($fp, json_encode($new_config));
		fclose($fp);

		$this->md5Config();
        $this->get();
	}

    public function writeMd5($config_md5) {

        $fp = fopen($this->config_md5_path, 'w+');
        fwrite($fp, $config_md5);
        fclose($fp);
    }
}