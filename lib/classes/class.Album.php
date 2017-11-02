<?php

class Album {
	
    public $album_slug = null;
    public $album_config = null;
	public $error;
	
	public function __construct ($album_slug = null) {
	    if (!is_null($album_slug) && is_dir(_ALBUMS_.'/'.$album_slug)) {
            if (file_exists(_ALBUMS_.'/'.$album_slug.'/config.json')) {
                $this->album_slug = $album_slug;
            }
        }
    }
    
    public function config ($album_slug = null) {
        if (is_null($album_slug)) $album_slug = $this->album_slug;

        $iterator = new GlobIterator(_ALBUMS_ . '/' . $album_slug . '/[!(th_)]*');

        $config_json = file_get_contents(_ALBUMS_.'/'.$album_slug.'/config.json');
        $config = json_decode($config_json, true);

        $config['album_slug'] = $album_slug;
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

        $this->album_config = $config;

        return $config;
    }

    public function owner () {
	    if (is_null($this->album_config)) $this->config();

	    return $this->album_config['owner'];
    }

    public function users () {
        if (is_null($this->album_config)) $this->config();

        return $this->album_config['users'];
    }
	
	public function images($album_slug = null) {

	    if (is_null($album_slug)) $album_slug = $this->album_slug;
        $iterator = new GlobIterator(_ALBUMS_ . '/' . $album_slug . '/*');

        $images = array();

        foreach ($iterator as $filename) {
            if (substr($filename, -5) != '.json') {
                $img_name = explode('/', $filename);
                $img_name = $img_name[count($img_name) - 1];

                if (substr($img_name, 0, 3) != 'th_') {
                    array_push($images, $img_name);
                }
            }
        }

        return $images;
    }

	public function getAll($page = 1, $limit = 9) {

	    $start = ($page - 1) * 9;
	    if ($start == 0) $limit++;
	    $i = 1;
		$albums = array();
		foreach(glob(_ALBUMS_.'/*') as $album) {

		    if ($i >= $start) {
                if ($i < ($start + $limit)) {
                    $album_slug = explode('/', $album);
                    $album_slug = $album_slug[count($album_slug) - 1];

                    if (($album_config = $this->config($album_slug))) array_push($albums, $album_config);
                } else {
                    break;
                }
            }
            $i++;
		}
		
		return $albums;
	}
	
	public function byTag($tag, $page = 1, $limit = 9) {

        $start = ($page - 1) * 9;
        if ($start == 0) $limit++;
        $i = 1;
        $albums = array();
        foreach(glob(_ALBUMS_.'/*') as $album) {
            $album_slug = explode('/', $album);
            $album_slug = $album_slug[count($album_slug) - 1];

            if (($album_config = $this->config($album_slug))) {
                if(isset($album_config['tags']) && is_array($album_config['tags']) && in_array($tag, $album_config['tags'])) {
                    if ($i > $start) {
                        if ($i <= ($start + $limit)) {
                            array_push($albums, $album_config);
                        } else {
                            break;
                        }
                    }
                    $i++;
                }
            }
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
                if (!file_exists(_ALBUMS_.'/'.$this->album_slug.'/th_'.$image)) {
                    $imagick = new \Imagick(realpath(_ALBUMS_ . '/' . $this->album_slug . '/' . $image));
                    $imagick->thumbnailImage(275, 275, true);
                    $imagick->writeImage(_ALBUMS_.'/'.$this->album_slug.'/th_'.$image);
                }

                readfile(_ALBUMS_.'/'.$this->album_slug.'/th_'.$image);
			} else {
				readfile(_ALBUMS_.'/'.$this->album_slug.'/'.$image);
			}

			 return true;
		}

		$this->error = 'Image does not exist';
		return false;

	}
	
	public function upload($image) {
		if (!exif_imagetype($image['tmp_name'])) {
			$this->error = 'Image not valid';
			return false;
		}

        $new_name  = false;
			
		while (!$new_name) {
			$new_name = generateRandomString();

		    if (file_exists(_ALBUMS_.'/'.$this->album_slug.'/'.$image)) $new_name = false;
		}

		$this->rotateImage($image['tmp_name']);

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
					
					if (file_exists(_ALBUMS_.'/'.$album_slug.'/config.json')) return $album_slug;
					
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
					$users = array_map('strtolower', $users);

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

    public function deleteImage($image) {
        if (preg_match('/^[a-zA-Z0-9-]+$/', $image)) {
            unlink(_ALBUMS_ . '/' . $this->album_slug.'/'.$image);
            unlink(_ALBUMS_ . '/' . $this->album_slug.'/th_'.$image);
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

    private function rotateImage($image_path) {
        $image = new \Imagick(realpath($image_path));

        switch ($image->getImageOrientation()) {
            case Imagick::ORIENTATION_TOPLEFT:
                break;
            case Imagick::ORIENTATION_TOPRIGHT:
                $image->flopImage();
                break;
            case Imagick::ORIENTATION_BOTTOMRIGHT:
                $image->rotateImage("#000", 180);
                break;
            case Imagick::ORIENTATION_BOTTOMLEFT:
                $image->flopImage();
                $image->rotateImage("#000", 180);
                break;
            case Imagick::ORIENTATION_LEFTTOP:
                $image->flopImage();
                $image->rotateImage("#000", -90);
                break;
            case Imagick::ORIENTATION_RIGHTTOP:
                $image->rotateImage("#000", 90);
                break;
            case Imagick::ORIENTATION_RIGHTBOTTOM:
                $image->flopImage();
                $image->rotateImage("#000", 90);
                break;
            case Imagick::ORIENTATION_LEFTBOTTOM:
                $image->rotateImage("#000", -90);
                break;
            default: // Invalid orientation
                break;
        }

        $image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
        $image->writeImage($image_path);
    }
}