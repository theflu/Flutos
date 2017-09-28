<?php
function d($display) {
	echo '<br><font color="red">**START**</font> <pre>';
	
	if(is_array($display)) {
		
		$display = array_replace($display,
					array_fill_keys(
						array_keys($display, true, true),
						'bool(true)'
				));
				
		$display = array_replace($display,
					array_fill_keys(
						array_keys($display, false, true),
						'bool(false)'
				));
		
		print_r($display);
	} elseif(is_object($display)) {
		$d = @get_object_vars($display);
		
		if(is_array($d)) {
			print_r(array_map(__FUNCTION__, $d));
		} else {
			print_r($d);
		}
	} elseif (is_bool($display)) {
		 if ($display) {
			echo 'Boolean: True';
		 } else {
			echo 'Boolean: False';
		 }
	} elseif (is_null($display)) {
		echo 'NULL';
	} else {
		echo $display;
	}
	
	echo '</pre><font color="red">**END**</font>';
}

function generateRandomString($length = 20) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}