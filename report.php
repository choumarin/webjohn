<?php
include('bgProcess.class.php');

if (empty($_REQUEST['sessID'])){
	die('No session ID');
}

$john = new johnSession($_REQUEST['sessID']);

$policy_len = 8;
$policy_Upp = 1;
$policy_Low = 1;
$policy_Num = 1;
$policy_Spe = 1;
$policy_outOf = 3;

if(isset($_POST['action']) && $_POST['action'] == 'update_policy'){
	$filter_res = filter_input_array(INPUT_POST, array(
		'policy_len' => FILTER_VALIDATE_INT,
		'policy_Upp' => FILTER_VALIDATE_INT,
		'policy_Low' => FILTER_VALIDATE_INT,
		'policy_Num' => FILTER_VALIDATE_INT,
		'policy_Spe' => FILTER_VALIDATE_INT,
		'policy_outOf' => FILTER_VALIDATE_INT
		));
	
	//~ var_dump($filter_res);
	$filter_pass = true;
	foreach ($filter_res as $key => $value){
		$filter_pass = $filter_pass && ($value !== false);
	}
	//~ var_dump($filter_pass);
	if($filter_pass){
		$passpolicy = array(
			'len' => $filter_res['policy_len'],
			'nbUp' => $filter_res['policy_Upp'],
			'nbLow' => $filter_res['policy_Low'],
			'nbNum' => $filter_res['policy_Num'],
			'nbSpe' => $filter_res['policy_Spe'],
			'minOutOf' => $filter_res['policy_outOf'],
		);
		$john->updateJohnConf(array('passpolicy' => $passpolicy));	
		$john->getStats(true);
	}
}
elseif (isset($_POST['action']) && $_POST['action'] == 'buildCache') {
	$john->buildCache();	
}

if(isset($john->config['johnSession']['passpolicy'])){
	$policy_len = $john->config['johnSession']['passpolicy']['len'];
	$policy_Upp = $john->config['johnSession']['passpolicy']['nbUp'];
	$policy_Low = $john->config['johnSession']['passpolicy']['nbLow'];
	$policy_Num = $john->config['johnSession']['passpolicy']['nbNum'];
	$policy_Spe = $john->config['johnSession']['passpolicy']['nbSpe'];
	$policy_outOf = $john->config['johnSession']['passpolicy']['minOutOf'];
}

$stats = $john->getStats();
//~ var_dump($stats);

require('view/reportView.php');
