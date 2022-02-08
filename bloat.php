<?php
/**
 * Author: Stefano Moioli <smxdev4@gmail.com>
 */
function symbols(string $file){
	$argFile = escapeshellarg($file);
	$descs = array(
		1 => ['pipe', 'w']
	);
	$hProc = proc_open("readelf -sW {$argFile}", $descs, $pipes);

	$seen = array();

	while(!feof($pipes[1])){
		$line = rtrim(fgets($pipes[1]));
		if($line === false) continue;
		if(!preg_match("/\d+: [0-9a-f]+/", $line)) continue;
		$line = preg_replace("/\ +/", ' ', $line);
		$line = preg_replace("/^\ +/", '', $line);
		$p = explode(' ', $line);
		if(count($p) < 8) continue;
		
		$addr = hexdec($p[1]);
		if(isset($seen[$addr])) continue;
		$seen[$addr] = true;

		list($size, $type, $name) = [
			intval($p[2]),
			$p[3], $p[7]
		];
		yield [$size, $type, $name];
	}
	proc_close($hProc);
}

$allSyms = iterator_to_array(symbols($argv[1]));
usort($allSyms, function($a, $b){
	return $a[0] <=> $b[0];
});
$allSyms = array_filter($allSyms, function($itm){
	return $itm[0] > 4096;
});
?>

<!DOCTYPE HTML>
<html>
<head>  
<script>
window.onload = function () {

var chart = new CanvasJS.Chart("chartContainer", {
	animationEnabled: false,
	title:{
		text: <?php print json_encode($argv[1]) ?>,
		horizontalAlign: "left"
	},
	data: [{
		type: "doughnut",
		startAngle: 0,
		//innerRadius: 60,
		indexLabelFontSize: 17,
		indexLabel: "{label} - #percent%",
		toolTipContent: "<b>{label}:</b> {y} (#percent%)",
		dataPoints: [ <?php
			foreach($allSyms as $item){
				$j = json_encode(array(
					'y' => $item[0],
					'label' => "{$item[1]}: {$item[2]}"
				));
				print($j . ",\n");
			}
		?> ]
	}]
});
chart.render();

}
</script>
</head>
<body>
<div id="chartContainer" style="height: 370px; width: 100%;"></div>
<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
  </body>
</html>