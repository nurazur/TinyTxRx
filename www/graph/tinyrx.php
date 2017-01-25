<html><head><title>Azur de la mer Wireless Sensor Network</title>
<link type="text/css" rel="stylesheet" href="../css/calendar.css"/>
<script type="text/javascript" src="../js/calendar.js"></script>
<script type="text/javascript">
    function init() {
    calendar.set("Von");
    calendar.set("Bis");
    calendar.set("neues_datum");
    }

    function NeuerVerlauf(anz_tage)
    {
		var dt = document.forms["Eingabe"].neues_datum.value.split(".");
		var datum = new Date(dt[2], dt[1]-1, dt[0]); // Monate werden in Javascript von 0 bis 11 gezaehlt
		var dts = datum.getTime() + anz_tage* 24*3600*1000; // mache Datum einen Tag vorher oder spaeter
  		var newdate = new Date(dts);
		var cjour= newdate.getDate()+ '.' + (newdate.getMonth()+1) + '.' + newdate.getFullYear() ;

		document.forms["Eingabe"].neues_datum.value = cjour;
		
		if(self.document.getElementsByName("plot1")[0].checked ==true)
		{		
			self.document.getElementById("verlauf").src = 'tinyrx_plot.php?node=1&tag=' + cjour;
			self.document.getElementById("verlauf").style.visibility="visible";
			self.document.getElementById("leistung_zeile").style.visibility="visible";
		}
		else
		{
			self.document.getElementById("verlauf").src = "../space.gif";
            //self.document.getElementById("verlauf").src = "";
			self.document.getElementById("verlauf").style.visibility="hidden";
			self.document.getElementById("leistung_zeile").style.visibility="collapse";
		}
		
		if(self.document.getElementsByName("plot2")[0].checked ==true)
		{		
			self.document.getElementById("temperatures").src = 'tinyrx_plot.php?node=2&tag=' + cjour;
			self.document.getElementById("temperatures").style.visibility="visible";
			self.document.getElementById("temperatur_zeile").style.visibility="visible";
		}
        else
		{
			self.document.getElementById("temperatures").src = '../space.gif';
			self.document.getElementById("temperatures").style.visibility="hidden";
			self.document.getElementById("temperatur_zeile").style.visibility="collapse";
		}

        if(self.document.getElementsByName("plot3")[0].checked ==true)
		{		
			self.document.getElementById("voltages").src = 'tinyrx_plot.php?node=27&tag=' + cjour;
			self.document.getElementById("voltages").style.visibility="visible";
			self.document.getElementById("bmp180_zeile").style.visibility="visible";
		}
		else
		{
			self.document.getElementById("voltages").src = '../space.gif';
			self.document.getElementById("voltages").style.visibility="hidden";
			self.document.getElementById("bmp180_zeile").style.visibility="collapse";
		}
        
        if(self.document.getElementsByName("plot4")[0].checked ==true)
		{		
			self.document.getElementById("sensors").src = 'tinyrx_plot.php?node=17&tag=' + cjour;
			self.document.getElementById("sensors").style.visibility="visible";
			self.document.getElementById("sensor_zeile").style.visibility="visible";
		}
		else
		{
			self.document.getElementById("sensors").src = '../space.gif';
			self.document.getElementById("sensors").style.visibility="hidden";
			self.document.getElementById("sensor_zeile").style.visibility="collapse";
		}
        
        if(self.document.getElementsByName("plot5")[0].checked ==true)
		{		
			self.document.getElementById("node3").src = 'tinyrx_plot.php?node=3&tag=' + cjour;
			self.document.getElementById("node3").style.visibility="visible";
			self.document.getElementById("node3_zeile").style.visibility="visible";
		}
		else
		{
			self.document.getElementById("sensors").src = '../space.gif';
			self.document.getElementById("sensors").style.visibility="hidden";
			self.document.getElementById("sensor_zeile").style.visibility="collapse";
		}
        return;
    }

    function energy_per_time(span)
    {
        if (span == 1)
        {
            self.document.getElementById("production_over_time").src = './graph/daily_plot.php' ;
        }
        else if (span ==7)
        {
            self.document.getElementById("production_over_time").src = './graph/weekly_plot.php' ;
        }
    }
    
    function reload_now()
    {
        clearInterval();
        //alert("Reload page now");
        window.location.reload();
    }
    
    function reload_timer()
    {
        
        var jetzt = new Date();
        var jetzt_t = jetzt.getTime();
        var time_span = 300*1000;
        var interv = time_span  - (jetzt_t % time_span) +10000; // time from now to the next 5 minutes boundary plus 10 seconds
        
        //alert("Next reload in " + (interv/1000) + " s");
        window.setInterval ("reload_now()", interv);
        return;
    }

    function ChoiceDailyPlot (index)
    {
        if (index == 0) // Tage zurueck
        {      
            self.document.getElementById("TageZurueck").style.visibility="visible";
            self.document.getElementById("TageZurueck").style.display="inline";
            
            //self.document.getElementById("Zeitraum").style.visibility="hidden";
            self.document.getElementById("Zeitraum").style.display="none";
            
            //self.document.getElementById("EinMonat").style.visibility="hidden";
            self.document.getElementById("EinMonat").style.display="none";
        }
        else if (index == 1)
        {
            //self.document.getElementById("TageZurueck").style.visibility="hidden";
            self.document.getElementById("TageZurueck").style.display="none";
            
            self.document.getElementById("Zeitraum").style.visibility="visible";
            self.document.getElementById("Zeitraum").style.display="inline";
            
            //self.document.getElementById("EinMonat").style.dvisibility="hidden";
            self.document.getElementById("EinMonat").style.display="none";
        }
        else if (index == 2)
        {
            //self.document.getElementById("TageZurueck").style.visibility="hidden";
            self.document.getElementById("TageZurueck").style.display="none";
            
            //self.document.getElementById("Zeitraum").style.visibility="hidden";
            self.document.getElementById("Zeitraum").style.display="none";
            
            self.document.getElementById("EinMonat").style.visibility="visible";
            self.document.getElementById("EinMonat").style.display="inline";
        }
    }    
    
    
    function energy_per_day ( plottype, tage, von, bis, monat, do_stretch, do_tagnacht)
	{
		var str_do_stretch = "";
		var str_do_tagnacht ="&sun=0";
        var str_monat = "";
		var str_cmdline_17 = 'tinyrx_plot.php?node=27&';
        var str_cmdline_27 = 'tinyrx_plot.php?node=17&';
        var str_cmdline_1 = 'tinyrx_plot.php?node=1&';
        var str_cmdline_2 = 'tinyrx_plot.php?node=2&';
        var str_cmdline_3 = 'tinyrx_plot.php?node=3&';
        
        var src_cmd1=str_cmdline_1;
        var src_cmd2=str_cmdline_2;
        var src_cmd3=str_cmdline_3;
        var src_cmd17=str_cmdline_17;
        var src_cmd27=str_cmdline_27;
		
		if (do_stretch)
		{
			str_do_stretch = '&stretch=1200';
		}
		else
		{
			str_do_stretch ='';
		}
		
		if (do_tagnacht)
		{
			str_do_tagnacht = '&sun=1';
		}
		
		if(plottype ==0) // tage
		{
            var jetzt = new Date();
            var heute = jetzt.getDate()+ '.' + (jetzt.getMonth()+1) + '.' + jetzt.getFullYear() ;
            var vorher = new Date();
            vorher.setDate(jetzt.getDate()- tage);
            var anfang = vorher.getDate()+ '.' + (vorher.getMonth()+1) + '.' + vorher.getFullYear() ;
            
            src_cmd1  = str_cmdline_1  + 'tag=' + anfang + '&bis=' + heute + str_do_stretch + str_do_tagnacht;
            src_cmd2  = str_cmdline_2  + 'tag=' + anfang + '&bis=' + heute + str_do_stretch + str_do_tagnacht;
            src_cmd3  = str_cmdline_3  + 'tag=' + anfang + '&bis=' + heute + str_do_stretch + str_do_tagnacht;
            src_cmd17 = str_cmdline_17 + 'tag=' + anfang + '&bis=' + heute + str_do_stretch + str_do_tagnacht;
            src_cmd27 = str_cmdline_27 + 'tag=' + anfang + '&bis=' + heute + str_do_stretch + str_do_tagnacht;
		}
        
		else if  (plottype == 1) //von bis
		{
            src_cmd1  = str_cmdline_1  + 'tag=' + von + '&bis=' + bis + str_do_stretch + str_do_tagnacht;
            src_cmd2  = str_cmdline_2  + 'tag=' + von + '&bis=' + bis + str_do_stretch + str_do_tagnacht;
            src_cmd3  = str_cmdline_3  + 'tag=' + von + '&bis=' + bis + str_do_stretch + str_do_tagnacht;
            src_cmd17 = str_cmdline_17 + 'tag=' + von + '&bis=' + bis + str_do_stretch + str_do_tagnacht;
            src_cmd27 = str_cmdline_27 + 'tag=' + von + '&bis=' + bis + str_do_stretch + str_do_tagnacht;
		}
		else if (plottype == 2) // param1 = monat
		{
            src_cmd1  = str_cmdline_1  + 'monat=' + monat + str_do_stretch + str_do_tagnacht;
            src_cmd2  = str_cmdline_2  + 'monat=' + monat + str_do_stretch + str_do_tagnacht;
            src_cmd3  = str_cmdline_3  + 'monat=' + monat + str_do_stretch + str_do_tagnacht;
            src_cmd17 = str_cmdline_17 + 'monat=' + monat + str_do_stretch + str_do_tagnacht;
            src_cmd27 = str_cmdline_27 + 'monat=' + monat + str_do_stretch + str_do_tagnacht;
		}

		else 
		{
		}
        
        
        if(self.document.getElementsByName("plot1")[0].checked ==true)
        {
            self.document.getElementById("verlauf").src = src_cmd1;
            self.document.getElementById("verlauf").style.visibility="visible";
            self.document.getElementById("leistung_zeile").style.visibility="visible";
        }
        else
        {
            self.document.getElementById("verlauf").src = "../space.gif";
            self.document.getElementById("verlauf").style.visibility="hidden";
            self.document.getElementById("leistung_zeile").style.visibility="collapse";
        }
        
        
        if(self.document.getElementsByName("plot2")[0].checked ==true)
        {
            self.document.getElementById("temperatures").src = src_cmd2;
            self.document.getElementById("temperatures").style.visibility="visible";
            self.document.getElementById("temperatur_zeile").style.visibility="visible";
        }
        else
        {
            self.document.getElementById("temperatures").src = "../space.gif";
            self.document.getElementById("temperatures").style.visibility="hidden";
            self.document.getElementById("temperatur_zeile").style.visibility="collapse";
        }
        
        if(self.document.getElementsByName("plot3")[0].checked ==true)
        {   
            //alert(src_cmd17);
            self.document.getElementById("voltages").src = src_cmd17;
            self.document.getElementById("voltages").style.visibility="visible";
            self.document.getElementById("bmp180_zeile").style.visibility="visible";
        }
        else
        {
            self.document.getElementById("voltages").src = "../space.gif";
            self.document.getElementById("voltages").style.visibility="hidden";
            self.document.getElementById("bmp180_zeile").style.visibility="collapse";
        }
        
        if(self.document.getElementsByName("plot4")[0].checked ==true)
        {	
            self.document.getElementById("sensors").src = src_cmd27;
            self.document.getElementById("sensors").style.visibility="visible";
            self.document.getElementById("sensor_zeile").style.visibility="visible";
        }
        else
        {
            self.document.getElementById("sensors").src = '../space.gif';
            self.document.getElementById("sensors").style.visibility="hidden";
            self.document.getElementById("sensor_zeile").style.visibility="collapse";
        }
        
        if(self.document.getElementsByName("plot5")[0].checked ==true)
        {	
            self.document.getElementById("node3").src = src_cmd3;
            self.document.getElementById("node3").style.visibility="visible";
            self.document.getElementById("node3_zeile").style.visibility="visible";
        }
        else
        {
            self.document.getElementById("node3").src = '../space.gif';
            self.document.getElementById("node3").style.visibility="hidden";
            self.document.getElementById("node3_zeile").style.visibility="collapse";
        }
        
	}   
</script>
</head><body  onload="init()">

<?php // content="text/plain; charset=utf-8"


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


$t = strtotime(date("d-m-Y"));


?>
<table border=2  rules=cols cellpadding=3 cellspacing=0 bgcolor = #FFFFE0>
<tr><td colspan =3 align =center ><h1>Azur De La Mer Wireless Sensors</h1></td></tr>
<tr>

<?php
$dst_offset = date_offset_get(new DateTime) /3600; // offset from UTC of today , normal European time + DST offset
//$dst_offset = new DateTime('2014-03-28', new DateTimeZone(date_default_timezone_get()));
//$dst_offset = date_offset_get($dst_offset) / 3600;

$d_winter = new DateTime('2000-01-01', new DateTimeZone(date_default_timezone_get())); // offset of UTC to normal European time (1 hour)
$timezoneoffset = date_offset_get($d_winter) / 3600;
$is_dst = $dst_offset-$timezoneoffset;
 
print ("<tr><td>Datum:</td>           <td>Woche ".date("W d-m-Y G:i:s  ") .$is_dst. "</td>           <td rowspan =5 valign=top>"); 
?>
<div id="cont_3e601318c90fec2d2d8c738e03652109">
  <span id="h_3e601318c90fec2d2d8c738e03652109"><a id="a_3e601318c90fec2d2d8c738e03652109" href="http://www.daswetter.com/wetter_Vallauris-Europa-Frankreich-Alpes+Maritimes--1-25753.html" target="_blank" style="color:#808080;font-family:Helvetica;font-size:14px;">Wetter Vallauris</a></span>
  <script type="text/javascript" src="http://www.daswetter.com/wid_loader/3e601318c90fec2d2d8c738e03652109"></script>
</div>

<?php
print("</td></tr>");
print ("<tr><td>Sonnenaufgang:</td>   <td>".date_sunrise($t, SUNFUNCS_RET_STRING, ini_get("date.default_latitude"), ini_get("date.default_longitude"), ini_get("date.sunrise_zenith"), $dst_offset)."</td> </tr>");
print ("<tr><td>Sonnenuntergang:</td> <td>".date_sunset( $t, SUNFUNCS_RET_STRING, ini_get("date.default_latitude"), ini_get("date.default_longitude"), ini_get("date.sunrise_zenith"), $dst_offset)."</td>  </tr>");
print ("<tr><td>Produktion: </td><td>");
print("</td> </tr>");
?>
</tr>

<tr>
<td valign = "top"><form name="Eingabe">
&nbsp<br>
<input type="checkbox" name="plot1" value="leistung">Node 1<br>
<input type="checkbox" name="plot2" value="temperaturen" >Node 2&nbsp<br>
<input type="checkbox" name="plot4" value="temperaturen" checked="checked">Node 17<br>
<input type="checkbox" name="plot3" value="temperaturen" checked="checked">Node 27<br>
<input type="checkbox" name="plot5" value="temperaturen" checked="checked">Node  3<br><br>
&nbspDatum:&nbsp<br>
&nbsp <input type="text" name="neues_datum" id="neues_datum" size ="11" value =<?php echo date('d.m.Y')?> ><br>

<br>Navigation:<br>
<input type="button" value="<<" onclick="NeuerVerlauf(-7)">
<input type="button" value=" < " onclick="NeuerVerlauf(-1)">
<input type="button" value=" > " onclick="NeuerVerlauf(1)">
<input type="button" value=">>" onclick="NeuerVerlauf(7)">
<br>
<input type="button" value="        OK        " size = "10" onclick="NeuerVerlauf(0)">
<br>
<br>Darstellung:<br>
<select name="Auswahl1" size="" onchange="ChoiceDailyPlot(this.form.Auswahl1.selectedIndex)" style="border:0px">
<option selected>Tage</option><option>Zeitraum</option><option>Monat</option>
</select>
<div id = "TageZurueck" style="display:inline"><input type="text" name = "Tage" size ="10" value="30" style="border:0px" ><br></div>
<div id="Zeitraum" style="display:none">
    <div>von:<input type="text" name = "Von" id="Von" size ="9" style="border:0px" value=<?php echo date('1.1.Y')?>></div>
    <div>bis:<input type="text" name = "Bis" id="Bis" size ="9" style="border:0px" value=<?php echo date('d.m.Y')?> ></div>
</div>
<div id ="EinMonat" style="display:none"><input type="text" name = "Monat" size ="10" style="border:0px" value=<?php echo date('m')?>><br></div>
<input type="checkbox" name="do_stretch" value ="" checked="checked">doppelte Breite<br>
<input type="checkbox" name="do_tagnacht" value ="">Tag&Nacht <br>
<input type="button"  value="Anzeigen" onclick="energy_per_day(this.form.Auswahl1.selectedIndex, this.form.Tage.value, this.form.Von.value, this.form.Bis.value, this.form.Monat.value, this.form.do_stretch.checked, this.form.do_tagnacht.checked)">
</form></td> 

<td>
<table>
<tr id="leistung_zeile" style="visibility:visible"><td valign = "top"><img id="verlauf" src = "../space.gif" style="visibility:visible"></img></td></tr>
<tr id="temperatur_zeile" style="visibility:visible"><td><img id="temperatures" src = "../space.gif" style="visibility:visible"></img></td></tr>
<tr id="sensor_zeile" style="visibility:visible"><td><img id="sensors" src = "./tinyrx_plot.php?node=17" style="visibility:visible"></img></td></tr>
<tr id="bmp180_zeile" style="visibility:visible"><td><img id="voltages" src = "./tinyrx_plot.php?node=27" style="visibility:visible"></img></td></tr>
<tr id="node3_zeile" style="visibility:visible"><td><img id="node3" src = "./tinyrx_plot.php?node=3" style="visibility:visible"></img></td></tr>
</table>
</td>

</tr>
</table>
<br>

<?php
/*
$temp_sensor_count=file("/sys/devices/w1_bus_master1/w1_master_slave_count");   //lese Datei in Array $list0 (1 Element pro Zeile)
if ($temp_sensor_count[0] > 0)
{
    $temp_sensor_list = file("/sys/devices/w1_bus_master1/w1_master_slaves");
    print ("<table border=1><tr><td colspan = 2>Temperaturen</td></tr>");
    for ($i=0; $i< count($temp_sensor_list); $i++)
    {
        $tempsensor = trim($temp_sensor_list[$i]);
        $t_sensor_filename = "/sys/devices/w1_bus_master1/" . $tempsensor . "/w1_slave";
        $temp_sensor_file = file($t_sensor_filename);
        
        
        if (count($temp_sensor_file) > 1)
        {
            
            $zeile = splitline(' ', $temp_sensor_file[1]);
            $tarr = splitline('=', $zeile[9]);
            $t = $tarr[1]/1000.0;
            
            if (strpos($tempsensor,"28-00000543326e") !== false)
            {
                printf ("<tr><td>6er</td><td>%.1f degC</td></tr>", $t);
            }
            else if (strpos($tempsensor,"28-000005436d91") !== false)
            {
                printf ("<tr><td>Aussen Nord</td><td>%.1f degC</td></tr>", $t);
            }
           //'28-00000543189e' // ruecklauf
           //'28-000005437180' // vorlauf
            else if (strpos($tempsensor,"28-000005437180") !== false)
            {
                printf ("<tr><td>Heizung Vorlauf</td><td>%.1f degC</td></tr>", $t);
            }
			else if (strpos($tempsensor,"28-00000543189e") !== false)
            {
                printf ("<tr><td>Heizung Ruecklauf</td><td>%.1f degC</td></tr>", $t);
            }
            else if (strpos($tempsensor,"28-0000055b6bc5") !== false)
            {
                printf ("<tr><td>Gang Thermostat</td><td>%.1f degC</td></tr>", $t);
            }
            else
            {
                printf ("<tr><td>".$tempsensor . "</td><td>%.1f degC</td></tr>", $t);
            }
        }
    }
    print("</table><br>");
}



print("<table border=1><tr><td colspan=3>BMP180 Status</td></tr>");
$fp = popen("../../../home/pi/bmp180test.py",'r');
$read = fread($fp, 128);

pclose($fp);
$ld = splitline(chr(10).chr(13), $read);


for ($i=0; $i<count($ld); $i++)
{   printf("<tr>");
    $varset = splitline(' =', $ld[$i]);
    for ($v=0; $v<count($varset); $v++)
    {
        $var = trim($varset[$v]);
        //if (strlen($var) > 0)
        printf("<td>%s</td>", $var);
    }
    if (count($varset) < 3) print ("<td>&nbsp</td>");
    print("</tr>");
} 
*/
?>
<br>
<a href="javascript: window.location.reload()">Reload</a>
</body>
</html>
