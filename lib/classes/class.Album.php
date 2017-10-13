<?php

class Album {
	
    public $album_slug = null;
	public $error;
	
	public function __construct ($album_slug = null) {
	    if (!is_null($album_slug) && is_dir(_ALBUMS_.'/'.$album_slug)) {
            if (file_exists(_ALBUMS_.'/'.$album_slug.'/config.json')) {
                $this->album_slug = $album_slug;
            
                return true;
            } else {
                $this->error = 'Album config does not exist';
            }
        } else {
	        $this->error = 'Album does not exist';
        }
        
        return false;
    }
    
    public function config ($album_slug = null) {
        if (is_null($album_slug)) $album_slug = $this->album_slug;
        $iterator = new GlobIterator(_ALBUMS_ . '/' . $album_slug . '/*');

        $config_json = file_get_contents(_ALBUMS_.'/'.$album_slug.'/config.json');
        $config = json_decode($config_json, true);

        $config['image_total'] = $iterator->count() - 1;

        if (!isset($config['default']) || !file_exists(_ALBUMS_.'/'.$album_slug.'/'.$config['default'])) {
            foreach($iterator as $filename){
                if (substr($filename, -5) != '.json') {
                    $img_name = explode('/', $filename);

                    $config['default'] = $img_name[count($img_name)-1];
                    break;
                }
            }
        }
        
        return $config;
    }
	
	public function images($album_slug = null) {

	    if (is_null($album_slug)) $album_slug = $this->album_slug;
        $iterator = new GlobIterator(_ALBUMS_ . '/' . $album_slug . '/*');

        $images = array();

        foreach ($iterator as $filename) {
            if (substr($filename, -5) != '.json') {
                $img_name = explode('/', $filename);
                array_push($images, $img_name[count($img_name) - 1]);
            }
        }

        return $images;
    }

	public function getAll() {
		
		$albums = array();
		foreach(glob(_ALBUMS_.'/*') as $album) {
			
			$album_slug = explode('/', $album);
			$album_slug = $album_slug[count($this->album_slug)-1];
			
			if (($album_config = $this->config($album_slug))) array_push($albums, $album_config);
		}
		
		return $albums;
	}
	
	public function byTag($tag) {
		
		$albums = $this->getAll();
		foreach($albums as $key => $album) {
			if(!isset($album['tags']) || !is_array($album['tags']) || !in_array($tag, $album['tags'])) unset($albums[$key]);
		}
		
		return $albums;
	}
	
	public function allTags() {
		
		$tags = array();
		$albums = $this->getAll();
		foreach($albums as $key => $album) {
			if(isset($album['tags']) || is_array($album['tags'])) $tags = array_merge($tags, $album['tags']);
		}
		
		$tags_weighted = array();
		foreach($tags as $tag) {
			
			if(isset($tags_weighted[$tag])) {
				$tags_weighted[$tag]++;
			} else {
				$tags_weighted[$tag] = 1;
			}
		}
		
		return $tags_weighted;
	}
	
	public function showImage($image) {
		$thumbnail = false;

		if (substr($image, 0, 3) == 'th_') {
		    $image = substr($image, 3);
		    $thumbnail = true;
        }

        if (file_exists(_ALBUMS_.'/'.$this->album_slug.'/'.$image)) {

		    $mime_type = mime_content_type(_ALBUMS_.'/'.$this->album_slug.'/'.$image);
			header('Content-Type: '.$mime_type);

			if ($thumbnail) {

			    $imagick = new \Imagick(realpath(_ALBUMS_.'/'.$this->album_slug.'/'.$image));
				$imagick->thumbnailImage(275, 275, true);
				echo $imagick->getImageBlob();

			} else {
				readfile(_ALBUMS_.'/'.$this->album_slug.'/'.$image);
			}

			 return true;
		}

		$this->error = 'Image does not exist';
		return false;

	}
	
	public function saveImage($image) {
		if (!exif_imagetype($image['tmp_name'])) {
			$this->error = 'Image not valid';
			return false;
		}

        $new_name  = false;
			
		while (!$new_name) {
			$new_name = generateRandomString();

		    if (file_exists(_ALBUMS_.'/'.$this->album_slug.'/'.$image)) $new_name = false;
		}

		if (move_uploaded_file($image['tmp_name'], _ALBUMS_.'/'.$this->album_slug.'/'.$new_name)) return true;

		$this->error = 'Failed to save image';
		return false;
	}
	
	public function create($title, $description, $tags, $users) {
		if (preg_match('/^[a-zA-Z0-9 ]+$/', $title)) {
			$title = trim($title);
			$album_slug = str_replace(' ', '-', strtolower($title));
			
			if (!is_dir(_ALBUMS_.'/'.$album_slug)) {
				if (mkdir(_ALBUMS_.'/'.$album_slug)) {
					$config = array(
						'title' => $title,
						'description' => $description,
						'owner' => $_SESSION['username']
					);
					
					if ($tags) {
						$tags = explode(',', $tags);
						$tags = array_map('trim', $tags);
						
						foreach ($tags as $k => $tag) {
							if (!preg_match('/^[a-zA-Z0-9 ]+$/', $tag)) unset($tags[$k]);
						}
						
						$tags = array_unique($tags);
					} else {
						$tags = array();
					}
					
					if ($users) {
						$users = array_map('trim', $users);
						
						foreach ($users as $k => $user) {
							if (!ctype_alnum($user)) unset($users[$k]);
						}
						
						$users = array_unique($users);
					} else {
						$users = array();
					}
					
					$config['tags'] = $tags;
					$config['users'] = $users;
					
					$fp = fopen(_ALBUMS_.'/'.$album_slug.'/config.json', 'w+');
					fwrite($fp, json_encode($config));
					fclose($fp);
					
					if (file_exists(_ALBUMS_.'/'.$album_slug.'/config.json')) return true;
					
					$this->error = 'Failed to make album config';
					return false;
				}
				
				$this->error = 'Failed to make album directory';
				return false;
			}
			
			$this->error = 'An album already exists with that name';
			return false;
		}
		
		$this->error = 'Invalid album title';
		return false;
	}
	
	public function edit($title, $description, $tags, $users) {
		if (preg_match('/^[a-zA-Z0-9 ]+$/', $title)) {
			$title = trim($title);

            if (($old_config = $this->config())) {
                $config = array(
                    'title' => $title,
                    'description' => $description,
                    'owner' => $old_config['owner'],
                    'preview' => $old_config['preview']
                );
				
				if ($tags) {
					$tags = explode(',', $tags);
					$tags = array_map('trim', $tags);
					
					foreach ($tags as $k => $tag) {
						if (!preg_match('/^[a-zA-Z0-9 ]+$/', $tag)) unset($tags[$k]);
					}
					
					$tags = array_unique($tags);
				} else {
					$tags = array();
				}
				
				if ($users) {
					$users = array_map('trim', $users);
					
					foreach ($users as $k => $user) {
						if (!ctype_alnum($user)) unset($users[$k]);
					}
					
					$users = array_unique($users);
				} else {
					$users = array();
				}
				
				$config['tags'] = $tags;
				$config['users'] = $users;
				
				$fp = fopen(_ALBUMS_.'/'.$this->album_slug.'/config.json', 'w+');
				fwrite($fp, json_encode($config));
				fclose($fp);
				
				 return true;
			}
			
			$this->error = 'Album does not exist';
			return false;
		}
		
		$this->error = 'Invalid album title';
		return false;
	}

    public function delete() {

        if (preg_match('/^[a-zA-Z0-9-]+$/', $this->album_slug)) {
            if (is_dir(_ALBUMS_ . '/' . $this->album_slug)) {
                $dir = _ALBUMS_ . '/' . $this->album_slug;

                $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
                $files = new RecursiveIteratorIterator($it,
                    RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($files as $file) {
                    if ($file->isDir()) {
                        rmdir($file->getRealPath());
                    } else {
                        unlink($file->getRealPath());
                    }
                }
                rmdir($dir);
            }
        }
    }

    public function deletePhoto($image) {
        if (preg_match('/^[a-zA-Z0-9-]+$/', $$image)) {
            unlink(_ALBUMS_ . '/' . $this->album_slug.'/'.$image);
        }
    }

    public function setDefault($image) {
        if (preg_match('/^[a-zA-Z0-9-]+$/', $image) && file_exists(_ALBUMS_.'/'.$this->album_slug.'/'.$image)) {
            if (($config = $this->config())) {
                $config['default'] = $image;

                $fp = fopen(_ALBUMS_.'/'.$this->album_slug.'/config.json', 'w+');
                fwrite($fp, json_encode($config));
                fclose($fp);
            }
        }
    }
}