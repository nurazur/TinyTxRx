<html><head><title>Azur de la mer Wireless Sensor Network</title>
<style>
table {
    font-family: arial, sans-serif, verdana;
    border-collapse: collapse;
    width: 100%;
	
	font-size:300%
}

td, th {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 8px;
}

tr:nth-child(even) {
    background-color: #dddddd;
}
</style>
</head><body>
<div>
<p>
<?php  

// split a line into an array. Aufeinanderfolgende tokens werden als ein einziger interpretiert.
function splitline($sep, $string)
{
	$tok = strtok($string, $sep);
	$arr = array();
	while ($tok !== false) 
	{
		array_push($arr, $tok);
		$tok = strtok($sep);
	}
	return $arr;
}


//$t = strtotime(date("d-m-Y"));
$filename = "/var/tmp/last_tinytx_log.txt";
$sensor_list=file($filename);   //lese Datei in Array $sensor_list (1 Element pro Zeile)
?>
<!-- Tabelle -->
<table>
<tr><td>Tag</td><td>Zeit</td><td>Node</td><td>Temperatur</td><td>Luftfeuchte</td></tr>
<?php
for ($i=0; $i< count($sensor_list); $i++)
{
	$sensor = trim($sensor_list[$i]);
	$content_list = explode(",", $sensor);
	if (count($content_list) > 4)
	{	
		$node = $content_list[4];
		if (strcmp("26", $content_list[4]) == 0) 
			$node = "Labo\t\t";	
		else if (strcmp("1", $content_list[4]) == 0) 
			$node = "Pool";	
		else if (strcmp("2", $content_list[4]) == 0) 
			$node = "Weinkeller";
		else if (strcmp("3", $content_list[4]) == 0) 
			$node = "Garage";
		else if (strcmp("17", $content_list[4]) == 0) 
			$node = "Wohnzimmer";
		else if (strcmp("27", $content_list[4]) == 0) 
			$node = "Bad";
		
		print("<b>");
		printf ("<tr><td>%s</td><td>%s</td><td>%s</td><td>%.2f</td>", $content_list[1], $content_list[2], $node, floatval($content_list[12])/100);
		if (strcmp("h", $content_list[13]) == 0)
			printf("<td>%.2f</td>", floatval($content_list[14])/100);
		else 
			print("<td></td>");
		print ("</tr>");
		print("</b>");
	}
}

?>
</table>
</p>
</div>
<br>
<a href="javascript: window.location.reload()">Reload</a>
</body>
</html>