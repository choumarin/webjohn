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
	$passpolicy = array(
		'len' => $_POST['policy_len'],
		'nbUp' => $_POST['policy_Upp'],
		'nbLow' => $_POST['policy_Low'],
		'nbNum' => $_POST['policy_Num'],
		'nbSpe' => $_POST['policy_Spe'],
		'minOutOf' => $_POST['policy_outOf'],
	);
	$john->updateJohnConf(array('passpolicy' => $passpolicy));	
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>WebJohn</title>
	<script src="http://code.jquery.com/jquery-latest.js"></script>
	
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="bootstrap/js/bootstrap.min.js"></script>
    <script src="http://twitter.github.com/bootstrap/assets/js/bootstrap-tab.js"></script>
	
	<script src="http://www.kunalbabre.com/projects/table2CSV.js"></script> 
	
	<script language="javascript" type="text/javascript" src="jqplot/jquery.jqplot.min.js"></script>
	<script class="include" language="javascript" type="text/javascript" src="jqplot/plugins/jqplot.pieRenderer.min.js"></script>
	<script class="include" language="javascript" type="text/javascript" src="jqplot/plugins/jqplot.donutRenderer.min.js"></script>
	<script src="jqplot/plugins/jqplot.categoryAxisRenderer.js" language="javascript" type="text/javascript" ></script>
	<script src="jqplot/plugins/jqplot.dateAxisRenderer.js" language="javascript" type="text/javascript" ></script>
	<script src="jqplot/plugins/jqplot.barRenderer.js" language="javascript" type="text/javascript" ></script>
	<script src="jqplot/plugins/jqplot.pointLabels.js" language="javascript" type="text/javascript" ></script>
	<script src="jqplot/plugins/jqplot.highlighter.js" language="javascript" type="text/javascript" ></script>
 
    <!--<script type="text/javascript" src="https://www.google.com/jsapi"></script>-->

	<link rel="stylesheet" type="text/css" href="jqplot/jquery.jqplot.css" />
	<script>
	$(document).ready(function() {
		$('#hashtable').each(function() {
			var $table = $(this);
			var $button = $("<a class='btn btn-mini' href='#'><i class='icon-download'></i> Export to CSV</a>");
			$button.insertBefore($table);
			$button.click(function() {
				var csv = $table.table2CSV({delivery:'value'});
				window.location.href = 'data:text/csv;charset=UTF-8,'+ encodeURIComponent(csv);
			});
		});
	})
	</script>
</head>
<body>
	<div class="navbar navbar-inverse">
		<div class="navbar-inner">
			<a class="brand" href="#">WebJohn</a>
			<ul class="nav">
			  <li><a href="index.php">Home</a></li>
			  <li><a href="controlpannel.php">Control Pannel</a></li>
			</ul>
		</div>
	</div>
	<div class="container">
		
		<ul class="nav nav-tabs" id="myTab">
		  <li class="active"><a href="#summary">Summary</a></li>
		  <li><a href="#results">Results</a></li>
		  <li><a href="#output">Output</a></li>
		  <li><a href="#error">Error</a></li>
		</ul>
		 
		<div class="tab-content">
		  <div class="tab-pane active" id="summary">
			<div class="row">
				<div class="span12">
					<legend>General information</legend>
					<p><strong>Session ID</strong>: <?php print($john->config['sessID']);?></p>
					<p><strong>Session Name</strong>: <?php print($john->session_name);?></p>
					<p><strong>Dictionnary</strong>: 
					<?php
					if(isset($john->config['johnSession']['mode']) && $john->config['johnSession']['mode']=='dictionnary'){
						print(securedString($john->config['johnSession']['dictionnary']));
					?></p>
					<p><strong>Rules</strong>: <?php
						print(securedString($john->config['johnSession']['rules']));
					}else{
						print('None');
					}?></p>
					<p><strong>Total hash count</strong>: <?php print($stats['nbNotCracked']+$stats['nbCracked']);?></p>
					<p><strong>Status</strong>: <?php print($john->status());?></p>
				</div>
			</div>
			<div class="row">
				<form class="form-inline span12" action="" method="post" enctype="multipart/form-data">
					<legend>Password policy <button type="submit" class="btn btn-info btn-mini"><i class="icon-refresh icon-white"></i>&nbsp;Update</button></legend>
					<label>Length:&nbsp;</label><input class="span1" type="text" name="policy_len" id="policy_len" value="<?php print(securedString($policy_len));?>">
					<label>Uppercase:&nbsp;</label><input class="span1" type="text" name="policy_Upp" id="policy_Upp" value="<?php print(securedString($policy_Upp));?>">
					<label>Lowercase:&nbsp;</label><input class="span1" type="text" name="policy_Low" id="policy_Low" value="<?php print(securedString($policy_Low));?>">
					<label>Numbers:&nbsp;</label><input class="span1" type="text" name="policy_Num" id="policy_Num" value="<?php print(securedString($policy_Num));?>">
					<label>Special chars.:&nbsp;</label><input class="span1" type="text" name="policy_Spe" id="policy_Spe" value="<?php print(securedString($policy_Spe));?>">
					<label>Count of each:&nbsp;</label><input class="span1" type="text" name="policy_outOf" id="policy_outOf" value="<?php print(securedString($policy_outOf));?>">
					<input type="hidden" name="action" value="update_policy">
				</form>
			</div>
			<legend>Statistics</legend>
			<div class="row">
					<script>
					$(document).ready(function(){
						var data = [
							['Not Cracked', <?php print $stats['nbNotCracked']; ?>],['Cracked', <?php print $stats['nbCracked']; ?>]
						];

						var plot1 = jQuery.jqplot ('piePctCracked', [data], 
							{ 
								title: 'Summary of cracking', 
								seriesColors: ['#00BB3F', '#FF2800'],
								seriesDefaults: {
									renderer: jQuery.jqplot.PieRenderer, 
									rendererOptions: {
										startAngle: 90,
										sliceMargin: 2,
										showDataLabels: true,
										shadow: false,
									},
								}, 
								highlighter: {
								  show: false,
								},
								grid: {
									drawGridLines: true,        // wether to draw lines across the grid or not.
										gridLineColor: '#00000000',   // CSS color spec of the grid lines.
										background: 'transparent',      // CSS color spec for background color of grid.
										borderColor: '#00000000',     // CSS color spec for border around grid.
										borderWidth: 0,           // pixel width of border around grid.
										shadow: false,               // draw a shadow for grid.
								}, 														
								legend: {
									show:true,
									location: 'e',
									border: 'none',
								},
							}
						);
						$('.jqplot-data-label').css('color','white'); 
						$('.jqplot-table-legend-swatch-outline').css('border','none');
					});	
					</script>
					<!--
		<script type="text/javascript">
		  google.load("visualization", "1", {packages:["corechart"]});
		  google.setOnLoadCallback(drawChart);
		  function drawChart() {
			var data = google.visualization.arrayToDataTable([
				['Status', 'Number'],
				['Cracked', <?php print $stats['nbCracked']; ?>],
				['Not Cracked', <?php print $stats['nbNotCracked']; ?>],
			]);

			var options = {
				colors: ['red', '#32CD32'],
				title: 'Summary of password cracking',
			};

			var chart = new google.visualization.PieChart(document.getElementById('piePctCracked'));
			chart.draw(data, options);
		  }
		</script>-->
					<div id="piePctCracked" class="span4" style="height:300px;width:400px; "></div>
					<script>
					$(document).ready(function(){
						var data = [
							['Compliant', <?php print $stats['checkPolicy']['true']; ?>],['Not compliant', <?php print $stats['checkPolicy']['false']; ?>]
						];
						var plot1 = jQuery.jqplot ('piePctPolicy', [data], 
							{ 
								title: 'Password policy compliance', 
								seriesColors: ['#00BB3F', '#FF2800'],
								seriesDefaults: {
									renderer: jQuery.jqplot.PieRenderer, 
									rendererOptions: {
										startAngle: 90,
										sliceMargin: 2,
										showDataLabels: true,
										shadow: false,
									}
								}, 
								highlighter: {
								  show: false,
								},
								grid: {
									drawGridLines: true,        // wether to draw lines across the grid or not.
										gridLineColor: '#00000000',   // CSS color spec of the grid lines.
										background: 'transparent',      // CSS color spec for background color of grid.
										borderColor: '#00000000',     // CSS color spec for border around grid.
										borderWidth: 0,           // pixel width of border around grid.
										shadow: false,               // draw a shadow for grid.
								}, 														
								legend: {
									show:true,
									location: 'e',
									border: 'none',
								},
							}
						);
						$('.jqplot-data-label').css('color','white'); 
						$('.jqplot-table-legend-swatch-outline').css('border','none');
					});	
					</script>
					<div id="piePctPolicy" class="span4" style="height:300px;width:400px; "></div>
				</div>
				<div class="row">
				<script>
				$(document).ready(function(){        
					$.jqplot.config.enablePlugins = true;     
					plot = $.jqplot('top20', [[<?php foreach($stats['top20'] as $pass => $cnt){
						printf("[%d, '%s'],", $cnt, str_replace("'", "\\'", $pass));
					} ?>]], {
						title: 'Top 20 passwords', 
						seriesColors:['#1533AD	'],
						seriesDefaults:{
							renderer:$.jqplot.BarRenderer,
								rendererOptions: {
								barDirection: 'horizontal',
								barWidth:5,
								shadow: false,
							}
						},
						highlighter: {
						  show: false,
						},
						axes: {
							yaxis: {
								renderer: $.jqplot.CategoryAxisRenderer,
								tickOptions:{
									showGridline:false, 
									markSize:0
								}
							},
							xaxis:{
								ticks:[0,<?php print(end($stats['top20'])*1.1); ?>],
								showTicks:false,
								tickOptions:{
									formatString:'%d',
									showGridline:false, 
									markSize:0,
								}
							}
						},
						grid: {
							drawGridLines: false,        // wether to draw lines across the grid or not.
							background: 'transparent',      // CSS color spec for background color of grid.
							borderWidth: 0,           // pixel width of border around grid.
							shadow: false,               // draw a shadow for grid.
						}, 														
					});
				});
				</script>
					<div id="top20" class="span12" style="height:400px;width:600px; "></div>
				</div>
				<div class="row">
					<script>
					$(document).ready(function(){
						var s1 = [<?php foreach($stats['passLen'] as $len => $cnt){
							printf("[%d, %d],", $len, $cnt);
						} ?>];
						var plot1 = $.jqplot('passLen', [s1], {
							title: "Password length distribution",
							seriesDefaults:{
								renderer:$.jqplot.BarRenderer,
								rendererOptions: {fillToZero: true},
								shadow: false,
							},
							highlighter: {
							  show: false,
							},
							seriesColors:['#1533AD'],
							legend: {
								show: false,
							},
							axes: {
								xaxis: {
									renderer: $.jqplot.CategoryAxisRenderer,
									tickOptions:{
										showGridline:false, 
										markSize:0
									},
								},
								yaxis: {
									pad: 1.15,
									tickOptions: {formatString: '%d'},
									min: 0,
								}
							},
							grid: {
								drawGridLines: false,        // wether to draw lines across the grid or not.
								background: 'transparent',      // CSS color spec for background color of grid.
								borderWidth: 0,           // pixel width of border around grid.
								shadow: false,               // draw a shadow for grid.
							}, 														
						});
					});
					</script>
					<div id="passLen" class="span12" style="height:400px;width:600px; "></div>					
				</div>
		  </div>
		  <div class="tab-pane" id="results">
		  
			<table class="table" id="hashtable">
				<thead>
					<th>User</th>
					<th>Hash</th>
					<th>Password</th>
				</thead>
				<tbody>
					<?php
					// var_dump($result);
					foreach ($john->listCracked() as $data){
						print '<tr>';
						print '<td>'.securedString($data['user']).'</td>';
						print '<td>'.securedString($data['hash']).'</td>';
						print '<td>'.securedString($data['pass']).'</td>';
						print '</tr>';
					}
					?>
				</tbody>    
			</table>
					  
		  </div>
		  <div class="tab-pane" id="output">
			<?php
				print(nl2br(securedString($john->printOut())));
			?>
		  </div>
		  <div class="tab-pane" id="error">
			<?php
				print(nl2br(securedString($john->printErr())));
			?>
		  </div>
		</div>
		<script>
		$('#myTab a').click(function(e) {
			e.preventDefault();
			$(this).tab('show');
		})
		</script>		
	</div>
	<div class="row">
		&nbsp;
		&nbsp;
		&nbsp;
	</div>

</body>
</html>
<?php
?>
