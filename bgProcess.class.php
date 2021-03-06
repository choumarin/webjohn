<?php

include('config.php');

function securedString($str){
	// var_dump($str == htmlspecialchars($str, ENT_QUOTES));
	return htmlspecialchars($str, ENT_QUOTES);
}

class bgProcess{
		
	public $config = array();
		
	function __construct(){
		if (!file_exists(CONST_SESSIONDIR)) {
			mkdir(CONST_SESSIONDIR,0755,true);
		}		
		$argv = func_get_args();
		switch( func_num_args() ) {
			case 0:
				$this->constructNew();
				break;
			case 1:
				$this->constructFromConf($argv[0]);
		}
	}
	
	function constructNew(){
		$sessID = uniqid();
		$this->config['sessID'] = $sessID;
		$this->config['outfile'] = CONST_SESSIONDIR.$sessID.'.out';
		$this->config['errfile'] = CONST_SESSIONDIR.$sessID.'.err';
		//~ $arr['stopped'] = false;
		$this->saveConf();
	}
	
	function updateConf($arr){
		//~ var_dump($this->config);
		$this->config = include(CONST_SESSIONDIR.$this->config['sessID'].CONST_SESSIONEXT);
		foreach($arr as $key => $val){
			$this->config[$key]=$val;
		}
		$this->saveConf();
		//~ var_dump($this->config);
	}

	function saveConf(){
		file_put_contents(CONST_SESSIONDIR.$this->config['sessID'].CONST_SESSIONEXT, '<?php return '.var_export($this->config, true).';');
	}	
	
	function constructFromConf($conf){
		if (($this->config = include(CONST_SESSIONDIR.$conf.CONST_SESSIONEXT)) === false){
			throw new Exception('No valid config file could be loaded.');
		}
	}
	
	function setCmd($command){
		$this->updateConf(array('command' => $command));
		$this->saveConf();
	}
	
	function launchBg($logfile='/tmp/php-bg.log', $priority=19){
		//~ print_r($this->config);
		$pid = false;
		$cmd = sprintf('nohup nice -n %d /usr/bin/php bg.php ', $priority).escapeshellarg($this->config['sessID']).' >>'.escapeshellarg($logfile).' 2>&1 & echo $!';
		//~ var_dump($cmd);
		$pid = exec($cmd);	
		return $pid;
	}
	
	function run(){
		$descriptorspec = array(
		   0 => array('pipe', 'r'),  // // stdin est un pipe où le processus va lire
		   1 => array('file', $this->config['outfile'], 'a'),  // stdout est un pipe où le processus va écrire
		   2 => array('file', $this->config['errfile'], 'a') // stderr est un fichier
		);

		$process = proc_open($this->config['command'], $descriptorspec, $pipes, CONST_SESSIONDIR);

		if (is_resource($process)) {
			
			$proc_status = proc_get_status($process);
			$arr = array();
			$arr['pid'] = $proc_status['pid'];
			//~ $arr['stopped'] = false;
			//~ var_dump('i am here 1');
			$this->updateConf($arr);

			fclose($pipes[0]); // Close all pipes before proc_close

			$return_value = proc_close($process);
			$this->updateConf(array('ret' => $return_value));
			
			if($return_value == 0){
				$this->onFinished();
			}
			
		}else{
			$this->updateConf(array('ret' => 255));
		}
	}
	
	function onFinished(){
		//To be surclassed
		return true;
	}
	function onStopped(){
		//To be surclassed
		return true;
	}
	
	function isRunning(){
		if (isset($this->config['pid'])){
            $result = shell_exec(sprintf('ps %d', $this->config['pid']));
            if(count(preg_split("/\n/", $result)) > 2) {
                return true;
            }
		}
        return false;
	}
	
	function kill(){
		$ret_var = true;
		if (!isset($this->config['pid'])){
			return false;
		}
		$ppid = $this->config['pid'];
		$pids = preg_split('/\s+/', `ps -o pid --no-heading --ppid $ppid`);
		foreach($pids as $pid) {
			if(is_numeric($pid)) {
				//~ echo "Killing $pid\n";
				exec(sprintf('kill %d', $pid), $output, $ret);
				if($ret>0){
					exec(sprintf('kill -9 %d', $pid), $output, $ret);
				}
				//~ var_dump($ret, $ret_var);
				$ret_var = ($ret_var && ($ret == 0));
				//~ var_dump($ret_var);
			}
		}
		//~ var_dump($ret_var);
		if ($ret_var){
			//~ var_dump('here i am 2');
			$this->updateConf(array('stopped' => true));
			$this->onStopped();
		}
		return $ret_var;
	}
	
	function isError(){
		//~ var_dump($this->isRunning());
		//~ var_dump($this->isStopped());
		if($this->isRunning() || $this->isStopped()){
			return false;
		}
		if (isset($this->config['ret'])){
			if ($this->config['ret'] > 0) {
				return true;
			}
		}
        return false;
	}
	
	function isFinished(){
		return (!$this->isRunning() && isset($this->config['ret']) && $this->config['ret'] == 0);
	}
	
	function status(){
		$ret = 'unknown';
		if ($this->isRunning()){
			$ret = 'running';
		}
		elseif ($this->isStopped()){
			$ret = 'stopped';
		}
		elseif ($this->isError()){
			$ret = 'error';
		}
		elseif (!$this->isRunning() && isset($this->config['ret']) && $this->config['ret'] == 0){
			$ret = 'finished';
		}
		return $ret;
	}
	
	function isStopped(){
		if (isset($this->config['stopped'])){
			return $this->config['stopped'];
		}
		return false;
	}
	
	function printOut(){
		return $this->printAFile($this->config['outfile']); 
	}
	
	function printErr(){
		return $this->printAFile($this->config['errfile']); 
	}
	
	private function printAFile($file){
		$content = @file_get_contents($file);
		if ($content === FALSE){
			$content = '';
		}
        return $content;
	}
	
	function delete(){
		$succ = FALSE;
		if (!$this->isRunning()){
			$succ = unlink($this->config['outfile']) && 
					unlink($this->config['errfile']) &&
					unlink(CONST_SESSIONDIR.$this->config['sessID'].CONST_SESSIONEXT);
		}
		return $succ;
	}

}

class johnSession extends bgProcess{
	
	public $session_name;
	
	function __construct(){
		$argv = func_get_args();
		switch( func_num_args() ) {
			case 1:
				parent::__construct($argv[0]);
				$this->constructFromConfJohnSession($argv[0]);
				break;
			case 3:
				parent::__construct();
				var_dump($this);
				$this->constructNewJohnSession($argv[0], $argv[1], $argv[2]);
				break;
			case 4:
				parent::__construct();
				$this->constructNewJohnSession($argv[0], $argv[1], $argv[2], $argv[3]);
				break;
		}
	}
	
	function constructNewJohnSession($hashfile, $format, $sessionname, $extraoptions=''){
		$this->updateJohnConf(array(
			'sessionname' => $sessionname,
			'hashfile' => $hashfile,
			'format' => $format,
			'extraoptions' => $extraoptions,
		));
		$this->session_name = $this->config['sessID'].'-'.$this->config['johnSession']['sessionname'];
	}
	
	function constructFromConfJohnSession($conf){
		//~ $this->config = include(CONST_SESSIONDIR.$conf.CONST_SESSIONEXT);
		$this->session_name = $this->config['sessID'].'-'.$this->config['johnSession']['sessionname'];
		//~ print_r($this->session_name);
	}
	
	static function getSessions(){
		$list_sessions = array();
		foreach (glob(CONST_SESSIONDIR.'*'.CONST_SESSIONEXT) as $filename) {
			array_push($list_sessions, basename($filename,CONST_SESSIONEXT));
		}
		return $list_sessions;
	}

	function onFinished(){
		$this->buildCache();
	}

	function onStopped(){
		$this->buildCache();
	}
	
	function buildCache(){
		$this->getStats(true);
		$this->status(true);
	}
	
	private function makeCmd(){
		$cmd = CONST_JOHN.' '.escapeshellarg($this->config['johnSession']['hashfile']).' --session='.escapeshellarg($this->session_name).' --format='.escapeshellarg($this->config['johnSession']['format']);
		if ($this->config['johnSession']['mode'] == 'dictionnary'){
			$dictionnaryfile = CONST_DICTDIR.$this->config['johnSession']['dictionnary'].'.dic';
			$cmd = $cmd.' --wordlist='.escapeshellarg($dictionnaryfile).' --rules='.escapeshellarg($this->config['johnSession']['rules']);
		}
		if (!empty($this->config['johnSession']['extraoptions'])){
			$cmd = $cmd.' '.escapeshellarg($this->config['johnSession']['extraoptions']);
		}
		return $cmd;
	}
		
	function start(){
		$this->setCmd($this->makeCmd());
		return $this->launchBg();
	}
	
	function updateJohnConf($arr){
		$this->config = include(CONST_SESSIONDIR.$this->config['sessID'].CONST_SESSIONEXT);
		foreach($arr as $key => $val){
			$this->config['johnSession'][$key]=$val;
		}
		$this->saveConf();
	}	
	
	function stop(){
		return $this->kill();
	}
	
	function resume(){
		$cmd = CONST_JOHN.' --restore='.escapeshellarg($this->session_name);
		$this->setCmd($cmd);
		return $this->launchBg();
	}
	
	function XgetHashs(){ //Fixme to be removed
		$hashs = array();
		$lines = file($this->config['johnSession']['hashfile']);
		foreach($lines as $line){
			$s = explode(':', $line, 2); // Hopefully, noone has ':' in its username ...
			$hashs[] = array(
				'user' => $s[0],
				'hash' => $s[1],
				'pass' => '>NOTFOUND<',
			);
		}
		// var_dump($hashs);
		return $hashs;
	}	
	
	static function getDicts($forceUpdate=false){
		$files = glob(CONST_DICTDIR.'*.dic');
		$ret = array();
		foreach ($files as $file){
			$ret[] = basename($file, '.dic');
		}
		return $ret;
	}
	
	static function getRules($forceUpdate=false){
		$geninfo = new GeneralInfoManager();
		if (!$forceUpdate && $geninfo->exists('rules')){
			return $geninfo->get('rules');
		}		
		$ret = array();
		$conf_file = dirname(CONST_JOHN).'/john.conf';
		//~ var_dump($conf_file);
		$file = file($conf_file);
		foreach($file as $line){
			$matches = array();
			if(preg_match('/^\[List\.Rules:(.*)\].*$/', $line, $matches)){
				$ret[]=$matches[1];
			}
		}
		$geninfo->set('rules', $ret);
		return $ret;
	}
	
	function listCracked($forceUpdate=false){
		//~ if (!$forceUpdate && isset($this->config['johnSession']['cache_cracked'])){
			//~ return $this->config['johnSession']['cache_cracked'];
		//~ }
		$cmd = CONST_JOHN.' '.escapeshellarg($this->config['johnSession']['hashfile']).' --show --format='.escapeshellarg($this->config['johnSession']['format']);
		exec($cmd, $output);
		// $output = implode(' ', $output);
		$hashs = array();
		foreach($output as $i => $line){
			$matches = array();
			if($this->config['johnSession']['format'] == 'nt' && preg_match('/^([^:]+):(.*):([A-Fa-f0-9]{32}|NO PASSWORD\*{21}):([A-Fa-f0-9]{32}|NO PASSWORD\*{21}):?.*$/', $line, $matches)){
				$hashs[] = array(
					'user' => $matches[1],
					'hash' => '',
					'pass' => $matches[2] == '' ? '**empty**' : $matches[2],
					'cracked' => true,
				);
			}
			elseif($this->config['johnSession']['format'] == 'lm' && preg_match('/^([^:]+):(.*):([A-Fa-f0-9]{32}|NO PASSWORD\*{21}):([A-Fa-f0-9]{32}|NO PASSWORD\*{21}):?.*$/', $line, $matches)){
				$hashs[] = array(
					'user' => $matches[1],
					'hash' => '',
					'pass' => $matches[2] == '' ? '**empty**' : $matches[2],
					'cracked' => true,
				);
			}
			elseif($this->config['johnSession']['format'] == 'sybasease' && preg_match('/^([^:]+):(.*)::.*$/', $line, $matches)){
				$hashs[] = array(
					'user' => $matches[1],
					'hash' => '',
					'pass' => $matches[2] == '' ? '**empty**' : $matches[2],
					'cracked' => true,
				);
			}
			elseif(preg_match('/^.+:.*$/', $line)){
				//~ var_dump($line);
				$s = explode(':', $line, 2); // Hopefully, noone has ':' in its username ...
				$hashs[] = array(
					'user' => $s[0],
					'hash' => '',
					'pass' => $s[1] == '' ? '**empty**' : $s[1],
					'cracked' => true,
				);
			}
		}
		unset($output);
		$cmd = CONST_JOHN.' '.escapeshellarg($this->config['johnSession']['hashfile']).' --show=LEFT --format='.escapeshellarg($this->config['johnSession']['format']);
		exec($cmd, $output);
		$hashsLeft = array();
		foreach($output as $i => $line){
			if(preg_match('/^.+:.+$/', $line)){
				//~ var_dump($line);
				$s = explode(':', $line, 2); // Hopefully, noone has ':' in its username ...
				$hashsLeft[] = array(
					'user' => $s[0],
					'hash' => $s[1],
					'pass' => '',
					'cracked' => false,
				);
			}
		}
		//~ var_dump($hashsLeft);
		$res = array_merge($hashs, $hashsLeft);
		//~ var_dump($hashs, $hashsLeft, $res);
		//~ $this->updateJohnConf(array(
			//~ 'cache_cracked' => $res,
		//~ ));		
		return $res;
	}
	
	function listHashs(){
	}
	
	function status($forceUpdate=false){
		$output = 'unknown';
		//~ print_r($this->session_name);
		//~ var_dump(parent::status());
                $stats = $this->getStats($forceUpdate);
                if ($stats['nbCracked']+$stats['nbNotCracked'] == 0){
                    $ministats = 'No hashs loaded (wrong format ?)';
                }else{
                    $ministats = sprintf('%d hash(s) cracked out of %d (%.0f%%)', $stats['nbCracked'], $stats['nbCracked']+$stats['nbNotCracked'],  $stats['nbCracked']*100/($stats['nbCracked']+$stats['nbNotCracked']));
                }
		switch (parent::status()){
			case 'running':
                                $stats = $this->getStats(true);
                                if ($stats['nbCracked']+$stats['nbNotCracked'] == 0){
                                    $ministats = 'No hashs loaded (wrong format ?)';
                                }else{
                                    $ministats = sprintf('%d hash(s) cracked out of %d (%.0f%%)', $stats['nbCracked'], $stats['nbCracked']+$stats['nbNotCracked'],  $stats['nbCracked']*100/($stats['nbCracked']+$stats['nbNotCracked']));
                                }
				$cmd = CONST_JOHN.' --status='.escapeshellarg($this->session_name).' 2>&1';
				//~ print_r($cmd);
				exec('cd '.CONST_SESSIONDIR.' && '.$cmd, $output);
				$output = 'Running: '.implode(' ', $output);
                                $output = preg_replace('/guesses: [0-9]+/', $ministats, $output);
				break;
			case 'stopped':
				if (!$forceUpdate && isset($this->config['johnSession']['cache_status'])){
					return $this->config['johnSession']['cache_status'];
				}
                                $stats = $this->getStats($forceUpdate);
//				$short_status = exec(CONST_JOHN.' '.escapeshellarg($this->config['johnSession']['hashfile']).' --show --format='.escapeshellarg($this->config['johnSession']['format']));
				$output = 'Stopped: '.$ministats;
				$this->updateJohnConf(array(
					'cache_status' => $output,
				));		
				break;
			case 'error':
				$output = sprintf('Error (%d): %s',escapeshellarg($this->config['ret']), escapeshellarg($this->printErr())) ; 
				break;
			case 'finished':
				if (!$forceUpdate && isset($this->config['johnSession']['cache_status'])){
					return $this->config['johnSession']['cache_status'];
				}
//				$short_status = exec(CONST_JOHN.' '.escapeshellarg($this->config['johnSession']['hashfile']).' --show --format='.escapeshellarg($this->config['johnSession']['format']));
				$output = 'Finished: '.$ministats;
				$this->updateJohnConf(array(
					'cache_status' => $output,
				));		

		}
		return $output;
	}

	static function getFormats($forceUpdate=false){
		$geninfo = new GeneralInfoManager();
		if (!$forceUpdate && $geninfo->exists('formats')){
			return $geninfo->get('formats');
		}		
		exec(CONST_JOHN, $output);
		//~ var_dump($output);
		$formats=array();
		$i = 0;
		while (isset($output[$i]) && preg_match('/^--format=NAME/', $output[$i]) === 0){
			$i++;
			continue;
		}
		while (isset($output[$i]) && preg_match('/^--[^f][^o][^r][^m]/', $output[$i]) === 0){
			//~ var_dump($output[$i]);
			preg_match('/^\s*(.*)$/', $output[$i], $matches);
			array_push($formats, $matches[1]);
			$i++;
		}
		$formats=implode(' ',$formats);
		preg_match('/^.+ NAME: (.*)$/', $formats, $matches);
		if (substr(php_uname(), 0, 7) == 'Windows'){
			$sep = '/';
		}else{
			$sep = ' ';
			//~ $sep = '/';
		}
		$formats = explode($sep, $matches[1]);
		$geninfo->set('formats', $formats);
		return $formats;  
	}
	
	function delete(){
		if (!$this->isRunning()){
			return unlink($this->config['johnSession']['hashfile']) && parent::delete();
		}
		return FALSE;
	}
	
	function getStats($forceUpdate=false){
		if (!$forceUpdate && isset($this->config['johnSession']['cache_stats'])){
			return $this->config['johnSession']['cache_stats'];
		}
		$ret = array();
		$results = $this->listCracked($forceUpdate);
		$array_cracked = array_filter($results, function ($var){
				return($var['cracked']);
			});
		$ret['nbCracked'] = count($array_cracked);
		$ret['nbNotCracked'] = count($results) - $ret['nbCracked'];
		$passArray = array_map(function($elem){
				return $elem['pass'];
			},
			$array_cracked);
		$cntPass = array_count_values($passArray);
		arsort($cntPass);
		$ret['top20'] = array_reverse(array_slice($cntPass, 0, 20));
		unset($cntPass);
		$lenArray = array_map(function($elem){
				return strlen($elem['pass']);
			},
			$array_cracked);
		$ret['passLen'] = array_count_values($lenArray);
		
		$tmpArray = array();
		foreach($array_cracked as $key => $value){
			if(isset($this->config['johnSession']['passpolicy'])){
				if(checkPolicy($value['pass'], 
					$this->config['johnSession']['passpolicy']['len'],
					$this->config['johnSession']['passpolicy']['nbUp'],
					$this->config['johnSession']['passpolicy']['nbLow'],
					$this->config['johnSession']['passpolicy']['nbNum'],
					$this->config['johnSession']['passpolicy']['nbSpe'],
					$this->config['johnSession']['passpolicy']['minOutOf']
					)){
					$localret = 'true';
				}else{
					$localret = 'false';
				}
			}else{
				$localret = checkPolicy($value['pass']) ? 'true':'false';
			}
			$tmpArray[$key] = $localret;
		}
				
		$ret['checkPolicy'] = array_count_values($tmpArray);
		if(!isset($ret['checkPolicy']['true']))
			$ret['checkPolicy']['true'] = 0;
		if(!isset($ret['checkPolicy']['false']))
			$ret['checkPolicy']['false'] = 0;
		$this->updateJohnConf(array(
			'cache_stats' => $ret,
		));		
		return $ret;
	}
}

function checkPolicy($pass, $len=8, $nbUp=1, $nbLow=1, $nbNum=1, $nbSpe=1, $minOutOf=3, $specialsChars='/[^a-zA-Z0-9]/'){
	$matches = array();
	$outOf = 0;
	if(strlen($pass)<$len)
		return false;
	if(preg_match_all("/[0-9]/", $pass, $matches)>=$nbNum)
		$outOf++;
	if(preg_match_all("/[A-Z]/", $pass, $matches)>=$nbUp)
		$outOf++;
	if(preg_match_all("/[a-z]/", $pass, $matches)>=$nbLow)
		$outOf++;
	if(preg_match_all($specialsChars, $pass, $matches)>=$nbSpe)
		$outOf++;
	if($outOf<$minOutOf)
		return false;
	return true;
}

class GeneralInfoManager{
	
	private $infos = array();
		
	function __construct(){
		if(!file_exists(CONST_GENINFOFILE)){
			file_put_contents(CONST_GENINFOFILE, '<?php return '.var_export($this->infos, true).';');
		}
		//~ if (($this->infos = include(CONST_GENINFOFILE)) === false){
			//~ throw new Exception('No valid general information file could be loaded (check config).');
		//~ }
		$this->infos = include(CONST_GENINFOFILE);
	}
	function get($var){
		return $this->infos[$var];
	}
	function getAll(){
		return $this->infos;
	}
	function exists($var){
		return isset($this->infos[$var]);
	}
	function set($var, $value){
		$this->infos[$var] = $value;
		$res = file_put_contents(CONST_GENINFOFILE, '<?php return '.var_export($this->infos, true).';');
		//~ var_dump($this->infos, $res);
		return $res;
	}
}
?>
