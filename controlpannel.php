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

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>WebJohn</title>
	<script src="http://code.jquery.com/jquery-latest.js"></script>
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="bootstrap/js/bootstrap.min.js"></script>
    <script src="http://twitter.github.com/bootstrap/assets/js/bootstrap-tab.js"></script>
</head>
<body>
	<div class="navbar navbar-inverse">
		<div class="navbar-inner">
			<a class="brand" href="#">WebJohn</a>
			<ul class="nav">
			  <li><a href="index.php">Home</a></li>
			  <li class="active"><a href="controlpannel.php">Control Pannel</a></li>
			</ul>
		</div>
	</div>
	<div class="container">
		<form class="form-inline" action="<?php print($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
		<h5>Dictionnary upload</h5>
		<ul>
			<li>Allowed extentions are : <?php print(implode(', ', $allowedExts)) ?></p>
			<li>Max size is : <?php print($maxsize/1024/1024); print('MB') ?></p>
			<li>Files will be renamed to .dic, archive files must already contain .dic file(s).</p>
		</ul>
		<?php
		if(isset($error) && $error){
		?>
		<div class="alert alert-error">
			<strong>Error</strong>
			<p><?php print(nl2br(securedString($MESSAGE))) ?></p>
		</div>
		<?php 
		}
		elseif(isset($error) && !$error){
		?>
		<div class="alert alert-success">
			<strong>Success</strong>
			<p><?php print(nl2br(securedString($MESSAGE))) ?></p>
		</div>
		<?php
		}
		 ?>
		<input type="file" name="file" id="file">
		<div class="form-actions">
			<button type="submit" class="btn btn-primary"><i class="icon-upload icon-white"></i>&nbsp;Upload</button>
		</div>
		</form>
	</div>
</body>
</html>
