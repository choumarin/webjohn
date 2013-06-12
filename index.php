<?php
# Copy GET to POST for debug
foreach($_GET as $key => $value){
	$_POST[$key]=$value;
}
//~ phpinfo();

//~ foreach($_REQUEST as $key => $value){
	//~ var_dump($key, $value);
//~ }

include('bgProcess.class.php');

function session_begin($sessname, $hash, $format, $options, $mode, $dictionnary, $rules){
	if (preg_match('/^[a-zA-Z0-9\-_]*$/', $sessname) === 0){
		die ('Session name must be [a-zA-Z0-9]*');  
	}
	$john = new johnSession('', $format, $sessname, $options);
	$hashfile = CONST_SESSIONDIR.$john->session_name.'.hash';
	$john->updateJohnConf(array('hashfile' => $hashfile));
	$dicts = johnSession::getDicts();
	if ($mode == 'brute'){
		$john->updateJohnConf(array('mode' => $mode));
	}
	elseif ($mode == 'dictionnary'){
		$john->updateJohnConf(array('mode' => $mode));
		$john->updateJohnConf(array('dictionnary' => $dicts[$dictionnary]));
		$john->updateJohnConf(array('rules' => $rules));
	}
	//~ var_dump($hashfile);
	$a = explode("\n", $hash);
	foreach($a as $i => $line){
		$a[$i] = rtrim($line, ':');
	}
	$hash = implode("\n", $a);
	file_put_contents($hashfile,$hash);
	$john->start();
}

function session_isalive ( $sessid ){ 
	$john = new johnSession($sessid);
	return $john->isRunning();
}

function list_sessions (){
	$a = johnSession::getSessions();
	//~ var_dump($a);
	return $a;
}

function session_delete ($sessid){ 
	$john = new johnSession($sessid);
	return $john->delete();
}

function session_resume ($sessid){
	$john = new johnSession($sessid);
	return $john->resume();
}

function session_stop ($sessid){
	$john = new johnSession($sessid);
	return $john->stop();
}

function session_status ($sessid){ 
	$john = new johnSession($sessid);
	return $john->status();
}

function session_show ($sessid){
	$john = new johnSession($sessid);
	return $john->listCracked();
}


function unlink_sess($sessid){
	$john = new johnSession($sessid);
	return $john->delete();
}

function list_formats(){
	return johnSession::getFormats();
}

if (!empty($_POST['action']) && $_POST['action'] == 'crack'){
	session_begin($_POST['sess_name'], $_POST['hashes'], $_POST['format'], $_POST['options'], $_POST['mode'], $_POST['dictionnary'], $_POST['rules']);
} 
if (!empty($_POST['action']) && $_POST['action'] == 'delete'){
	session_delete($_POST['sessionid']);
} 

if (!empty($_POST['json']) && $_POST['json']=1){
	if (!empty($_POST['action']) && $_POST['action'] == 'list'){
		print json_encode(list_sessions());
	}
	if (!empty($_POST['action']) && $_POST['action'] == 'resume'){
		$result = FALSE;
		if (!empty($_POST['sessionid'])){
			$result = session_resume($_POST['sessionid']);
		} 
		print json_encode(array('result' => $result));
	}
	if (!empty($_POST['action']) && $_POST['action'] == 'stop'){
		$result = FALSE;
		if (!empty($_POST['sessionid'])){
			$result = session_stop($_POST['sessionid']);
		} 
		print json_encode(array('result' => $result));
	}
	if (!empty($_POST['action']) && $_POST['action'] == 'status'){
		$result = FALSE;
		if (!empty($_POST['sessionid'])){
			$result = session_status($_POST['sessionid']);
		} 
		print json_encode(array('result' => $result));
	}
	if (!empty($_POST['action']) && $_POST['action'] == 'show'){
		$result = FALSE;
		if (!empty($_POST['sessionid'])){
			$result = session_show($_POST['sessionid']);
		} 
		print json_encode(array('result' => $result));
	}
	if (!empty($_POST['action']) && $_POST['action'] == 'list_formats'){
		$result = FALSE;
		$result = list_formats(); 
		print json_encode(array('result' => $result));
	}
}else{
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>WebJohn</title>
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<style>
		body .modal {
			/* new custom width */
			width: 1100px;
			/* must be half of the width, minus scrollbar on the left (30px) */
			margin-left: -550px;
		}
	</style>
</head>
<body>
	<div class="navbar navbar-inverse">
		<div class="navbar-inner">
			<a class="brand" href="#">WebJohn</a>
			<ul class="nav">
			  <li class="active"><a href="index.php">Home</a></li>
			  <li><a href="controlpannel.php">Control Pannel</a></li>
			</ul>
		</div>
	</div>
	<div class="container">
		<table class="table">
			<thead>
				<th>Session</th>
				<th>Status</th>
				<th>Actions</th>
			</thead>
			<tbody>
<?php


foreach(list_sessions() as $sessid){
	//~ var_dump($sessid);

	$john = new johnSession($sessid);
	//~ var_dump($john);
	print '<tr>';
	print '<td>'.$john->session_name.'</td>';
	print '<td id="status_'.$sessid.'">';
	print securedString($john->status());
	print '</td>';
	print '<td id=buttons_'.$sessid.'>';
	if ($john->isRunning()) {
		print '&nbsp;<button id="stop_'.$sessid.'" type="button" class="btn btn-danger btn-small" data-loading-text="..."><i class="icon-stop icon-white"></i> Stop</button>';
	}
	elseif (!$john->isFinished()){
		print '&nbsp;<button id="start_'.$sessid.'" type="button" class="btn btn-success btn-small" data-loading-text="..."><i class="icon-play icon-white"></i> Resume</button>';
	}
	print '&nbsp;<button id="update_'.$sessid.'" type="button" class="btn btn-small" data-loading-text="..."';
	if (!$john->isRunning()){
		print ' style="display: none"';
	}
	print '><i class="icon-refresh"></i> Update</button>';
	//~ print '&nbsp;<a data-remote="'.$_SERVER['SCRIPT_NAME'].'?json=0&action=show&sessionid='.$sessid.'" data-target="#showRes" role="button" class="btn btn-small" data-toggle="modal"><i class="icon-list"></i> Results</a>';
	print '&nbsp;<a href="report.php?sessID='.$sessid.'" role="button" class="btn btn-small"><i class="icon-list"></i> Results</a>';
	if (!$john->isRunning()){
		print '&nbsp;<a href="'.$_SERVER['SCRIPT_NAME'].'?action=delete&sessionid='.$sessid.'" role="button" class="btn btn-small btn-danger"><i class="icon-remove icon-white"></i> Delete</a>';
	}
	print '</td>';
	print '</tr>';  
}
		
?>
			</tbody>    
		</table>
	</div>
	<div class="container">
		<button type="button" class="btn btn-large btn-primary" data-toggle="collapse" data-target="#add_hashs"><i class="icon-stop icon-plus icon-white"></i> Add session</button>
		<div id="add_hashs" class="collapse">
			<form action="<?php $_SERVER['SCRIPT_NAME']; ?>" method="POST">
				<div class="row">
					<div class="span12">
						<textarea placeholder="Hashes ..." class="span12" name="hashes" id="hashes" rows="10" required></textarea>
					</div>
				</div>
				<div class="row">
					<div class="span2">
							Format
						<select class="span2" name="format" id="format" required>
	<?php
	foreach(list_formats() as $format){
		print '<option value='.$format.'>'.$format.'</option>';
	}
	?>
						</select>
					</div>
					<div class="span2">
						Session name
						<input class="span2" type="text" placeholder="(only Alphanum)" name="sess_name" id="sess_name">
					</div>
					<div class="span1">
							Fork
						<select class="span1" name="mpi_np" id="fork">
							<option value=1>Off</option>
							<option value=2>2</option>
							<option value=3>3</option>
							<option value=4>4</option>
							<option value=5>5</option>
							<option value=6>6</option>
							<option value=7>7</option>
							<option value=8>8</option>
						</select>
					</div>
					<div class="span1">
							Nodes
						<input class="span1" type="text" placeholder="X(-Y)/Z" name="nodes" id="nodes">
					</div>
					<div class="span2">
						Mode
						<select class="span2" name="mode" id="mode">
							<option value="brute">Brute</option>
							<option value="dictionnary">Dictionnary</option>
						</select>
					</div>
					<div class="span2">
						Dictionnary
						<select class="span2" name="dictionnary" id="dictionnary">
	<?php
	foreach(johnSession::getDicts() as $key => $file){
		print '<option value='.$key.'>'.$file.'</option>';
	}
	?>
						</select>
					</div>
					<div class="span2">
						Rules
						<select class="span2" name="rules" id="rules">
	<?php
	foreach(johnSession::getRules() as $rule){
		print '<option value='.$rule.'>'.$rule.'</option>';
	}
	?>
						</select>
					</div>
					<div class="span2">
						Other options
						<input class="span2" type="text" placeholder="other options..." name="options" id="options">
					</div>
				</div>
				<div class="row">
					<div class="span2">
						<button class="btn btn-primary btn-large" style="text-align:center" name="action" id="action" value="crack">WebCrack it!</button>
					</div>
				</div>
		</form>
		
		</div>
	</div>

	<!-- Popup -->  
	<div id="showRes" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<h3 id="myModalLabel">Results</h3>
		</div>
		<div class="modal-body">
			<p>Should be promptly replaced with results</p>
	 </div>
		<div class="modal-footer">
			<button class="btn" data-dismiss="modal">Close</button>
		</div>
	</div>


	<script src="http://code.jquery.com/jquery-latest.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>
	<script>
		$('[id^="start"],[id^="stop"],[id^="update"]').button('reset');
		$('[id^="start"],[id^="stop"],[id^="update"]')
			.click(function () {
				var btn = $(this);
				btn.button('loading');
				action = btn.attr('id').split('_')[0];
				sessionid = btn.attr('id').split('_')[1];
				if(action === 'start'){
					session_start(sessionid, btn);
				}
				else if(action === 'stop'){
					session_stop(sessionid, btn);
				}
				else if(action === 'update'){
					session_update(sessionid, btn);
				}
			});
		
		function session_update(sessionid, btn){
			$('#status_'+sessionid).html('updating...');
			postdata = { json: "1", action: "status", 'sessionid': sessionid };
			$.post('<?php echo $_SERVER['SCRIPT_NAME']; ?>',
				postdata,
				function(data){
					btn.button('reset');
					json = $.parseJSON(data);
					$('#status_'+sessionid).html(json.result);
				}
			)
		}
		
		function session_stop(sessionid, btn){
			postdata = { json: "1", action: "stop", 'sessionid': sessionid };
			$.post('<?php echo $_SERVER['SCRIPT_NAME']; ?>',
				postdata,
				function(data){
					btn.button('reset');
					json = $.parseJSON(data);
					if (json.result){
						btn.removeClass("btn-danger");
						btn.addClass("btn-success");
						btn.attr('id', 'start_'+sessionid);
						btn.html('<i class="icon-play icon-white"></i> Resume');
						$('#status_'+sessionid).html('Stopped');
						$('#update_'+sessionid).hide();
					}else{
						alert('Error : '+data);
					}
				}
			)
		}
		
		function session_start(sessionid, btn){
			postdata = { json: "1", action: "resume", 'sessionid': sessionid };
			$.post('<?php echo $_SERVER['SCRIPT_NAME']; ?>',
				postdata,
				function(data){
					btn.button('reset');
					json = $.parseJSON(data);
					if (json.result){
						btn.removeClass("btn-success");
						btn.addClass("btn-danger");
						btn.attr('id', 'stop_'+sessionid);
						btn.html('<i class="icon-stop icon-white"></i> Stop');
						$('#update_'+sessionid).show();
						$('#status_'+sessionid).html('updating...');
						postdata = { json: "1", action: "status", 'sessionid': sessionid };
						$.post('<?php echo $_SERVER['SCRIPT_NAME']; ?>',
							postdata,
							function(data){
								json = $.parseJSON(data);
								$('#status_'+sessionid).html(json.result);
							}
						)
					}else{
						alert('Error : '+data);
					}
				}
			)
		}
		
		$('body').on('hidden', '.modal', function () {
			$(this).removeData('modal');
		});

	</script>
</body>
<?php
	}
?>
