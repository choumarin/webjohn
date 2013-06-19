<?php

include('bgProcess.class.php');

function tempdir($dir=false,$prefix='php') {
    $tempfile=tempnam(sys_get_temp_dir(),'');
    if (file_exists($tempfile)) { unlink($tempfile); }
    mkdir($tempfile);
    if (is_dir($tempfile)) { return $tempfile; }
}

$dictsDir = 'dicts/';
$allowedExts = array('dic', 'txt', 'gz', 'bz2', '7z');
$maxsize = 100*1024*1024;

if (isset($_FILES['file'])){
	$MESSAGE = '';

	function log_message($str){
		global $MESSAGE;
		$MESSAGE .= $str."\n";
	}
	
	$error = true;
	$allowedTypes = array('text/plain', 'application/x-gzip');
	$fna = explode('.', $_FILES['file']['name']);
	$extension = end($fna);
	if ($_FILES['file']['error'] > 0){
		log_message('Return Code: ' . $_FILES['file']['error'] );
	}else{
		log_message('Upload: ' . $_FILES['file']['name'] );
		log_message('Type: ' . $_FILES['file']['type'] );
		log_message('Size: ' . ($_FILES['file']['size'] / 1024 / 1024) . ' MB');
		//~ log_message('Temp file: ' . $_FILES['file']['tmp_name'] );
		if (($_FILES['file']['size'] < $maxsize) && in_array($extension, $allowedExts)){
			switch($extension){
				case 'txt':
					if (file_exists($dictsDir . $_FILES['file']['name'])){
						log_message($_FILES['file']['name'] . ' already exists. ');
					}else{
						$res = move_uploaded_file($_FILES['file']['tmp_name'], $dictsDir . basename($_FILES['file']['name'], '.txt').'.dic');
						if ($res){
							log_message('Stored as: ' . $dictsDir . basename($_FILES['file']['name'], '.txt').'.dic');
							$error = false;
						}
					}
					break;
				case 'dic':
					if (file_exists($dictsDir . $_FILES['file']['name'])){
						log_message($_FILES['file']['name'] . ' already exists. ');
					}else{
						$res = move_uploaded_file($_FILES['file']['tmp_name'], $dictsDir . basename($_FILES['file']['name']));
						if ($res){
							log_message('Stored as: ' . $dictsDir . $_FILES['file']['name']);
							$error = false;
						}
					}
					break;
				case 'gz':
				case 'bz2':
					$tmpdir = tempdir();
					exec('tar -	xf '.escapeshellarg($_FILES['file']['tmp_name']).' -C '.$tmpdir, $output, $ret_value);
					if ($ret_value == 0){
						log_message('Extracted to: ' . $tmpdir);
						foreach(glob($tmpdir.'/*.dic') as $file){
							$ret = rename($file, $dictsDir.basename($file));
							log_message('Stored as: '.$dictsDir.basename($file));
						}
						$error = false;
					}
					break;
				case '7z':
					$tmpdir = tempdir();
					exec('7zr e -o'.$tmpdir.' -y '.escapeshellarg($_FILES['file']['tmp_name']), $output, $ret_value);
					if ($ret_value == 0){
						log_message('Extracted to: ' . $tmpdir);
						foreach(glob($tmpdir.'/*.dic') as $file){
							$ret = rename($file, $dictsDir.basename($file));
							log_message('Stored as:'.$dictsDir.basename($file));
						}
						$error = false;
					}
					break;
			}
		}else{
			log_message('Invalid file (extention, size, etc. does not match policy)');
		}
	}
}

require('view/controlPannelView.php');
