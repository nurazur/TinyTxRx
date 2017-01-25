<?php // content="text/plain; charset=utf-8"
// functions to capture options

// evaluate the "stretch" option. if existing and > 600, apply new plot width.
function plot_width($options)
{
    $stretch = 600;
    if ( !empty($options["stretch"]) ) $stretch = intval($options["stretch"]);
    if ($stretch < 600) $stretch = 600;
    return $stretch;
}

// checks the "sun" option; if it does not exist or is set 0, don't fill night time with blue colour in plot.
function plot_show_night_colour($options)
{
    $do_sunrs = true;
    if ( isset($options["sun"]) ) 
    {
        if ( empty($options["sun"]) ) $do_sunrs = false;
    }
    return $do_sunrs;    
}

function get_option_month($options)
{
    $asked_month = date("n");
    if (!empty($options["monat"]))
    {
        $asked_month = $options["monat"];
    }
    return $asked_month;
}

function get_option_year($options)
{
    $asked_year = date("Y");
    if (!empty($options["jahr"]))
    {
        $asked_year = $options["jahr"];
    }
    return $asked_year;
}


function get_option_day_first($options, $asked_month, $asked_year)
{
    if (!empty($options["tag"]))   // mit Argumenten aufgerufen
    { 
        // uebergebenes Datum
        $today = $options["tag"];
    }
    else if (!empty($options["monat"]))
    {
        $today = "01." .$asked_month. "." .$asked_year;
    }
    else
    {   // heute
        $today = date("d.m.Y");
    }
    return $today;
}


function get_option_time_last($options, $asked_month, $asked_year, $date_first)
{
    if(!empty($options["bis"]))
    {
        $bistag = $options["bis"];
        $bis_time = strtotime($bistag)+ 86399;
    }
    else if (!empty($options["monat"]))
    {
        $bis_time = mktime(0,0,0, $asked_month+1, "01", $asked_year);
    }
    else
    {
        $bis_time = strtotime($date_first) + 86400;  //1 Tag
    }
    return $bis_time;
}


// function splitline is same a explode, but works like a string tokenizer (empty strings are not expanded)
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

// Calculate the week of the year in python style with timestamp as input
// Example year change 2014 to 2015
//          So       Mo      Di      Mi      Do      Fr      Sa      So
//          28.12.   29.12.  30.12.  31.12.  01.01.  02.01.  03.01.  04.01.
//php       52       01      01      01      01      01      01      01
//python    51       52      52      52      00      00      00      00

function calc_python_week_from_time_stamp($day_time)
{
    $python_week = intval(date("W", $day_time)) -1;
    $c_month = intval(date("m", $day_time));
    
    if ($c_month == 12 && $python_week == 0)
    {
        $python_week = 52;
    }
    return $python_week;
}

// calculate the python style week of the year from a date
//    $day = date("d.m.Y")
function calc_python_week($day)
{
    $day_time = strtotime($day);
    return calc_python_week_from_time_stamp($day_time);
}

//calculate log file name from a given timestamp.
function calc_logfilename($timestamp, $sensor = "bmp180")
{
    $w = calc_python_week_from_time_stamp($timestamp) +1;
    $y = date("Y", $timestamp);
    if ($w >9)
        $logfile = "../logfiles/".$w."_".$y;
    else
        $logfile = "../logfiles/0".$w."_".$y;
        
    if ($sensor != "")
    {
    $logfile = $logfile."_".$sensor;
    }
    
    $logfile = $logfile."_log.csv";
    if (!file_exists($logfile))
    {
        $logfile ="";
    }     
    
    return $logfile;
}

function get_time_of_day_as_float($timestamp)
{
    // hours[24h format] + minutes/60
    $time_of_day_float = intval(date("G", $timestamp)) + intval(date("i", $timestamp))/60.0;
    //$time_of_day_float = ($timestamp % 86400) / 3600.0;
    return $time_of_day_float;
}



function get_corrected_pressure($pressure, $timestamp)
{
    $frequency = 1/12;  // frequency is 12 hours
    $time_shift = 4.0;  // offset of sine wave against 00:00
    $time_float = get_time_of_day_as_float($timestamp);
    return  $pressure - 0.55 * sin(M_PI *2 * $frequency *($time_float + $time_shift)); 
}

function errorlog($filename, $message)
{
    $debug = fopen($filename , 'a');
    fwrite($debug, $message."\n");
    fclose($debug);
}


function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
    
function generate_list_sensor($start_time, $stop_time, $sensor="bmp180")
{
    $list = array();
    
    $w_old = "";
    $next_time = $start_time;
    
    // sunezy log is space separated (bad idea!)
    $sep = ',';
    if ($sensor == "") $sep = " ";
    
    do
    {
        $w_new = calc_python_week_from_time_stamp($next_time) +1;
        $logfilename = calc_logfilename($next_time, $sensor);
        
        //if (file_exists($logfilename) && $w_old != $w_new)
        if (file_exists($logfilename))
        {
            if ($w_old != $w_new)
            {
                $w_old = $w_new;
                $list = array_merge($list, file($logfilename));
            
                $lzi = count($list);            
                if ($lzi >0)
                {
                    $zeile=splitline($sep, $list[$lzi-1]);
                    $last_time_in_list = strtotime($zeile[2]." ".$zeile[3]);
                    $next_time = $last_time_in_list + 1800;
                }
            }
            else
            {
                $next_time = $next_time + 84600;
            }
        }
        else
        {
            break;
        }
    } while ($last_time_in_list < $stop_time);

    return $list;
}

function generate_list_default($logfilename)
{
    $list = array();
    if (file_exists($logfilename))
    {    
        $list = file($logfilename);
    }
    return $list;
}


function generate_sunrise_sunset_data($mintime,$maxtime,$min,$max, $days_list, &$time_ss, &$ydata_ss)
{
    $ydata_ss[] = $max;
    $time_ss[] =  $mintime;
    
    for ($d=0; $d < count($days_list); $d++)
    {
        $t_str = date('Y-m-d', $days_list[$d]);
        $dt = new DateTime($t_str, new DateTimeZone(date_default_timezone_get())); // offset of UTC to normal European time (1 hour)
        $dt->setTime(4,0); // set time to 04:00 so that daylight saving time change has happened already.
        $dst_offset = date_offset_get($dt) / 3600;

        $sunrise = date_sunrise($days_list[$d], SUNFUNCS_RET_TIMESTAMP, ini_get("date.default_latitude"), ini_get("date.default_longitude"), ini_get("date.sunrise_zenith"), $dst_offset);
        $sunset =  date_sunset ($days_list[$d], SUNFUNCS_RET_TIMESTAMP, ini_get("date.default_latitude"), ini_get("date.default_longitude"), ini_get("date.sunrise_zenith"), $dst_offset);
        $ydata_ss[] = $max;
        $time_ss[] = $sunrise;
            
        $ydata_ss[] = $min;
        $time_ss[] = $sunrise;

        $ydata_ss[] = $min;
        $time_ss[] = $sunset;
            
        $ydata_ss[] = $max;
        $time_ss[] = $sunset;
    }
    $ydata_ss[] = $max;
    $time_ss[] = $maxtime;
}

function graph_scale_xaxis(&$graph, &$time, $diff_time)
{
    //$difftime = ($time[count($time) -1] - $time[0])/ 84600;
    if ($diff_time >= 5 ) // mehr als 5 Tage: x-Achse zeigt nur den tag an
    {
        $graph->xaxis->scale->SetTimeAlign(DAYADJ_1,DAYADJ_1); 
        $graph->xaxis->SetLabelFormatString('d.M', true); // d,M / d,m,y / d,m,Y / H:i:s
        $graph->xaxis->scale->ticks->Set(86400);
        $graph->xaxis->SetLabelAngle(45);
    }
    else if ($diff_time < 5 && $diff_time > 2) // zwischen 2 und 5 Tagen: x-Achse zeigt Tag und Uhrzeit
    {
        $graph->xaxis->scale->SetTimeAlign(DAYADJ_1,DAYADJ_1); 
        $graph->xaxis->SetLabelFormatString('d.m H:i', true); // d,M / d,m,y / d,m,Y / H:i:s
        $graph->xaxis->scale->ticks->Set(43200);
        $graph->xaxis->SetLabelAngle(0);
    }
    else
    {
        $graph->xaxis->scale->SetTimeAlign(HOURADJ_1,HOURADJ_1); // 1-2 Tag(e): nur Uhrzeit anzeigen
        $graph->xaxis->SetLabelFormatString('H:i', true); // d,M / d,m,y / d,m,Y / H:i:s
        $graph->xaxis->SetTextLabelInterval(1);
        $graph->xaxis->SetLabelAngle(45);
}

    //$graph->xaxis->SetLabelAngle(45); 
    $graph->xaxis->SetTickLabels($time);
    $graph->xaxis->HideFirstTicklabel() ;

    $graph->xgrid->Show(true);
    $graph->xgrid->SetLineStyle('dashed');
}
?>