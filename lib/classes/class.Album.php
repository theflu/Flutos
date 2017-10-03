<?php

class Album {
	
	public $error;
	public $allowed_filetypes = array('jpg', 'jpeg', 'png');
	
	public function get($album_slug, $images = false) {
		
		if (is_dir(_ALBUMS_.'/'.$album_slug)) {
			if (file_exists(_ALBUMS_.'/'.$album_slug.'/config.json')) {
				$config_json = file_get_contents(_ALBUMS_.'/'.$album_slug.'/config.json');
				$config = json_decode($config_json, true);
				
				$iterator = new GlobIterator(_ALBUMS_.'/'.$album_slug.'/*');
				if ($images) {
					$images = array();
					foreach($iterator as $filename){
						$ext = explode('.', $filename);
						$ext = $ext[count($ext)-1];
						
						if (strtolower($ext) != 'json') {
							$img_name = explode('/', $filename);
							array_push($images, $img_name[count($img_name)-1]);
							
							if (!isset($config['preview'])) $config['preview'] = $img_name[count($img_name)-1];
						}
					}
					$config['images'] = $images;
				} else {
					foreach($iterator as $filename){
						$ext = explode('.', $filename);
						$ext = $ext[count($ext)-1];
						
						if (strtolower($ext) != 'json') {
							$img_name = explode('/', $filename);
							
							if (!isset($config['preview'])) {
								$config['preview'] = $img_name[count($img_name)-1];
								break;
							}
						}
					}
				}
				
				$config['image_total'] = $iterator->count() - 1;
				$config['album_slug'] = $album_slug;
				
				return $config;
			}
			
			$this->error = 'Album configuration file missing';
			return false;
		}
		
		$this->error = 'Album does not exist';
		return false;
	}
	
	public function getAll($images = false) {
		
		$albums = array();
		foreach(glob(_ALBUMS_.'/*') as $album) {
			
			$album_slug = explode('/', $album);
			$album_slug = $album_slug[count($album_slug)-1];
			
			if (($album_data = $this->get($album_slug, $images))) array_push($albums, $album_data);
		}
		
		return $albums;
	}
	
	public function getTag($tag, $images = false) {
		
		$albums = $this->getAll($images);
		foreach($albums as $key => $album) {
			if(!isset($album['tags']) || !is_array($album['tags']) || !in_array($tag, $album['tags'])) unset($albums[$key]);
		}
		
		return $albums;
	}
	
	public function getTags() {
		
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
	
	public function showImage($album, $image, $thumbnail = false) {
		
		if (is_dir(_ALBUMS_.'/'.$album)) {
			if (file_exists(_ALBUMS_.'/'.$album.'/'.$image)) {
				 $mime_type = mime_content_type(_ALBUMS_.'/'.$album.'/'.$image);
				 header('Content-Type: '.$mime_type);
				 if ($thumbnail) {
					$imagick = new \Imagick(realpath(_ALBUMS_.'/'.$album.'/'.$image));
					$imagick->thumbnailImage(275, 275, true);
					echo $imagick->getImageBlob(); 
				 } else {
					readfile(_ALBUMS_.'/'.$album.'/'.$image);
				 }
				 
				 return true;
			}
			
			$this->error = 'Image does not exist';
			return false;
		}
		
		$this->error = 'Album does not exist';
		return false;
	}
	
	public function saveImage($album, $image) {
		if (!exif_imagetype($image['tmp_name'])) {
			$this->error = 'Image not valid';
			return false;
		}
		
		if (is_dir(_ALBUMS_.'/'.$album)) {
			
			$new_name  = false;
			
			while (!$new_name) {
				$new_name = generateRandomString();
				
				if (file_exists(_ALBUMS_.'/'.$album.'/'.$image)) $new_name = false;
			}
			d($image);
			d($new_name);
			d(_ALBUMS_.'/'.$album.'/'.$new_name);
			if (move_uploaded_file($image['tmp_name'], _ALBUMS_.'/'.$album.'/'.$new_name)) return true;
			
			$this->error = 'Failed to save image';
			return false;
		}
		
		$this->error = 'Album does not exist';
		return false;
	}
	
	public function create($title, $description, $tags, $users) {
		if (preg_match('/^[a-zA-Z0-9 ]+$/', $title)) {
			$title = trim($title);
			$album_name = str_replace(' ', '-', strtolower($title));
			
			if (!$this->get($album_name)) {
				if (mkdir(_ALBUMS_.'/'.$album_name)) {
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
					
					$fp = fopen(_ALBUMS_.'/'.$album_name.'/config.json', 'w+');
					fwrite($fp, json_encode($config));
					fclose($fp);
					
					if (file_exists(_ALBUMS_.'/'.$album_name.'/config.json')) return true;
					
					$this->error = 'Failed to make album config';
					return false;
				}
				
				$this->error = 'Failed to make album direcory';
				return false;
			}
			
			$this->error = 'An album already exists with that name';
			return false;
		}
		
		$this->error = 'Invalid album title';
		return false;
	}
	
	public function edit($album_name, $title, $description, $tags, $users) {
		if (preg_match('/^[a-zA-Z0-9 ]+$/', $title)) {
			$title = trim($title);

            if (($old_config = $this->get($album_name))) {
                $config = array(
                    'title' => $title,
                    'description' => $description,
                    'owner' => $old_config['owner']
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
				
				$fp = fopen(_ALBUMS_.'/'.$album_name.'/config.json', 'w+');
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
}