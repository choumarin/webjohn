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
	require('view/listSessionsView.php');
}
