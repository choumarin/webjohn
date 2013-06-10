<?php

include('bgProcess.class.php');

$sessID = $argv[1];

$proc = new bgProcess($sessID);
$proc->run();


?>


