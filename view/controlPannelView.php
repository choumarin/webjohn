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
		<form class="form-inline" action="<?php print($_SERVER['SCRIPT_NAME']); ?>" method="post" enctype="multipart/form-data">
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
