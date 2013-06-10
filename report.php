<?php
include('bgProcess.class.php');

if (empty($_REQUEST['sessID'])){
	die('No session ID');
}

$john = new johnSession($_REQUEST['sessID']);
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
			<!--<h4>Summary of session <?php print $john->session_name; ?></h4>-->
				<script>
				$(document).ready(function(){
					var data = [
						['Cracked', <?php print $stats['nbCracked']; ?>],['Not Cracked', <?php print $stats['nbNotCracked']; ?>]
					];
					var plot1 = jQuery.jqplot ('piePctCracked', [data], 
						{ 
							title: 'Summary of cracking', 
							seriesColors: ['red', '#32CD32'],
							seriesDefaults: {
								// Make this a pie chart.
								renderer: jQuery.jqplot.PieRenderer, 
								rendererOptions: {
									// Put data labels on the pie slices.
									// By default, labels show the percentage of the slice.
									showDataLabels: true
								}
							}, 
							grid: {
								drawGridLines: true,        // wether to draw lines across the grid or not.
									gridLineColor: '#00000000',   // CSS color spec of the grid lines.
									background: 'transparent',      // CSS color spec for background color of grid.
									borderColor: '#00000000',     // CSS color spec for border around grid.
									borderWidth: 0,           // pixel width of border around grid.
									shadow: false,               // draw a shadow for grid.
							}, 														
							legend: { show:true, location: 'e' }
						}
					);
				});	
				</script>
				<div id="piePctCracked" style="height:300px;width:600px; "></div>
				
				<script>
				$(document).ready(function(){        
					$.jqplot.config.enablePlugins = true;     
					plot = $.jqplot('top20', [[<?php foreach($stats['top20'] as $pass => $cnt){
						printf("[%d, '%s'],", $cnt, str_replace("'", "\\'", $pass));
					} ?>]], {
						title: 'Top 20 passwords', 
						seriesDefaults:{
							renderer:$.jqplot.BarRenderer,
								rendererOptions: {
								barDirection: 'horizontal',
								barWidth:5,
							}
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
								ticks:[0,<?php print(reset($stats['top20'])*1.1); ?>],
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

				<div id="top20" style="height:400px;width:600px; "></div>				
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

</body>
</html>
<?php
?>
