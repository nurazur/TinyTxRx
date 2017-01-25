<?php // content="text/plain; charset=utf-8"
require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_line.php');
require_once( "jpgraph/jpgraph_date.php" );

// this is the new common file. It is better to have <year>_<week>_<sensor>_log.csv
require_once 'common/common_pi2.php';

$debug_file = "../debug_tinyrx.txt";
$sensor = "tinyrx";


$asked_month = get_option_month($_GET);
$asked_year =  get_option_year ($_GET);

// determine first day and its time stamp
$today = get_option_day_first($_GET, $asked_month, $asked_year);
$start_time = strtotime($today);

// determine last day and its time stamp
$stop_time = get_option_time_last($_GET, $asked_month, $asked_year, $today);
$bistag = date('d.m.Y', $stop_time);

// shall we plot nights in blue colour?
$do_sunrs = plot_show_night_colour($_GET);

// custom plot width
$stretch =  plot_width($_GET);

// get node
$node =1;
if ( !empty($_GET["node"]) ) $node = intval($_GET["node"]);

// Generate List of all Entries
$list = generate_list_sensor($start_time, $stop_time, $sensor);
$lzi = count($list);

// x-Axis array
$time = array();
//$time_2 = array();
$time_t = array();
$time_h = array();
$time_v = array();

// list of days in the time period requested, in time stamp format
$days_list = array();

// default y-axis scale
$max = 1;
$min =0;

$t_sens = array();
$v_sens = array();
$h_sens = array();

//Indices:
/* Wed,23.09.2015,16:38:34,n,17,h,5280,a,-4,s,1,t,2110,v,3924
   Wed,23.09.2015,20:22:53,n,1,a,1,s,1,t,2200,v,3019
0= Wochentag
1= Datum dd.mm.yyyy
2= Uhrzeit hh:mm:ss
3= 'n'
4= node
5 + 2*i = parameter
6 + 2*i = value
parameter   Bedeutung
n           sensor
a           AFC value
s           DRSSI ( 1 or 0 )
t           Temperatur
v           AtTiny Supply voltage
*/

$last_date = "";
for ($k=0; $k<$lzi; $k++)
{
    $zeile=splitline(',', $list[$k]);
    if (count($zeile) < 6 )
    {
        continue; // toleriere Leerzeilen
    }
    $ts = strtotime($zeile[1]);
    if ($ts < $start_time) 
    {
        continue;
    }
    else if ($ts < $stop_time)
    {
        if ($zeile[1] != $last_date)
        {
            $days_list[] = $ts;
            $last_date =   $zeile[1];
        }
        
        $c_time = strtotime($zeile[1]." ".$zeile[2]);
        if ($zeile[4] == $node)
        {
            $time[] = $c_time;
            for ($j=5; $j< count($zeile); $j+=2)  // fuer alle Eintraege - Suche nach den richtigen Sensoren
            {
                if ($zeile[$j] =="t")
                {
                    //$v = $j + 1;
                    $temperatur=$zeile[$j+1]/100.0;
                    if ($temperatur > $max) $max = $temperatur;
                    $t_sens[] = $temperatur;
                    $time_t[] = $c_time;
                }
                else if ($zeile[$j] == "h")
                {
                    //$v = $j + 1;
                    // if ($zeile[$v] > $max) $max = $zeile[$v];
                    $h_sens[] =  rtrim($zeile[$j+1])/ 100.0;
                    $time_h[] =  $c_time;
                }
                else if ($zeile[$j] == "v")
                {
                    //$v = $j + 1;
                    //if ($zeile[$v] > $max) $max = $zeile[$v];
                    $v_sens[] =  rtrim($zeile[$j+1]) / 100.0;
                    $time_v[] = $c_time;
                    //$v[] =  $c_time; //?
                }
            }
        }
    }
    else
        break;
}



$max = floor(($max+5)/5.0)*5 ;   // round up

// Berechne Y-Skala
$mintime = $start_time;
$maxtime = $time[count($time)-1];
$maxtime =  strtotime( "+1 day", strtotime(date('d.m.Y', $maxtime))); // its the next day 00:00

// Create array for the sunrise / sunset background colour
$time_ss  = array();
$ydata_ss = array();
generate_sunrise_sunset_data($mintime,$maxtime,$min,$max, $days_list, $time_ss, $ydata_ss);



// Create the graph. These two calls are always required
if($stretch <= 600) $stretch = 600;
$graph = new Graph($stretch,338);

// Grafik formatieren
$graph->SetMargin(60,40,0,50);  // Rahmen
//errorlog($debug_file, "max=$max, min=$min, mintime=$mintime, maxtime=$maxtime");
$graph->SetScale('datlin',$min, $max, $mintime, $maxtime); 
if (count($h_sens) > 0)
{
        $graph->SetY2Scale('lin', 0, 101); 
}
//$graph->SetY2Scale('lin', 220, 250); 

$diff_time = ($stop_time - $start_time) / 86400.0; // number of displayed days
$titel = "TinyTRx Node ".$node;
if ($node == 1)
    $titel = "Pool (Node ".$node.")";
else if ($node == 2)
    $titel = "Weinkeller (Node ".$node.")";
else if ($node == 17)
    $titel = "Wohnzimmer (Node ".$node.")";
else if ($node == 27)
    $titel = "Bad (Node ".$node.")";
else if ($node == 3)
    $titel = "Garage (Node ".$node.")";   
else if ($node == 26)
    $titel = "Labor (Node ".$node.")"; 

if($diff_time <= 1)

//Titel 
    $graph->title->Set($titel.", ".$today);	// Titel der Grafik
else
    $graph->title->Set($titel.", ".$today." bis ".$bistag);

$graph->title->SetFont(FF_FONT2,FS_BOLD);

// weisser Hintergrund
$graph->ygrid->SetFill(true,'#FFFFFF@0.5','#FFFFFF@0.5');

// Gradient fill
$graph->SetBackgroundGradient('#FFFFA0', '#FFFFFF', GRAD_HOR, BGRAD_PLOT);
//$graph->SetFrame(true,'darkblue',1); 

// X- Achse
if ($diff_time >= 5 ) // mehr als 5 Tage: x-Achse zeigt nur den tag an
{
    $graph->xaxis->scale->SetTimeAlign(DAYADJ_1,DAYADJ_1); 
    $graph->xaxis->SetLabelFormatString('d.M', true); // d,M / d,m,y / d,m,Y / H:i:s
    $graph->xaxis->scale->ticks->Set(86400);
}
else if ($diff_time < 5 && $diff_time > 2) // zwischen 2 und 5 Tagen: x-Achse zeigt Tag und Uhrzeit
{
    $graph->xaxis->scale->SetTimeAlign(DAYADJ_1,DAYADJ_1); 
    $graph->xaxis->SetLabelFormatString('d.m H:i', true); // d,M / d,m,y / d,m,Y / H:i:s
    $graph->xaxis->scale->ticks->Set(43200);
}
else
{
    $graph->xaxis->scale->SetTimeAlign(HOURADJ_1,HOURADJ_1); // 1-2 Tag(e): nur Uhrzeit anzeigen
    $graph->xaxis->SetLabelFormatString('H:i', true); // d,M / d,m,y / d,m,Y / H:i:s
    $graph->xaxis->SetTextLabelInterval(1);
}
//$graph->xaxis->scale->SetTimeAlign(HOURADJ_1,HOURADJ_1); 
$graph->xaxis->SetLabelAngle(45); 
$graph->xaxis->SetTickLabels($time);
//$graph->xaxis->SetLabelFormatString('H:i', true); // d,M / d,m,y / d,m,Y / H:i:s
//$graph->xaxis->SetTextLabelInterval(1);
$graph->xaxis->HideFirstTicklabel() ;

$graph->xgrid->Show(true);
$graph->xgrid->SetLineStyle('dashed');

//Y-Achse
$graph->yaxis->title->Set("Temperatur [deg C]");
$graph->yaxis->SetTitlemargin(40); 
$graph->yaxis->SetLabelMargin(10); 

// Create the linear plot
$lineplot_t=new LinePlot($t_sens, $time_t);

$graph->img->SetAntiAliasing(false);

// Add the plot to the graph
$graph->Add($lineplot_t);
$lineplot_t->SetColor("red");
$lineplot_t->SetWeight(2); 
$lineplot_t->SetLegend("Temperatur");

if (count($h_sens) > 0)
{
    $lineplot_h = new LinePlot($h_sens, $time_h);
    //$graph->Add($lineplot_h);
    $graph->AddY2($lineplot_h); 
    $lineplot_h->SetColor('darkblue');
    $lineplot_h->SetWeight(2);
    $lineplot_h->SetLegend("Luftfeuchtigkeit");
    //$graph->SetY2Scale('lin', 0, 101); 
}
/*
$lineplot_v = new LinePlot($v_sens, $time_v);
$graph->Add($lineplot_v);
$lineplot_v->SetColor('orange');
$lineplot_v->SetWeight(2);
$lineplot_v->SetLegend("voltage");
*/

$graph->yaxis->SetWeight(2);
$graph->xaxis->SetWeight(2);  

//Legende
$graph->legend->Pos(0.5,0.75,"center","bottom");
$graph->legend->SetLayout(LEGEND_HOR); 

// plot sunrise / sunset time zones
if ($do_sunrs)
{
    $lineplot_ss = new LinePlot($ydata_ss, $time_ss);
    $lineplot_ss->SetFillColor('blue@0.8'); 
    $graph->Add($lineplot_ss);
    $lineplot_ss->SetColor('#FFFFA0@0.95');
}

// Display the graph
$graph->Stroke();

?>
