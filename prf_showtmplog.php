<?php 

/* this function is used to display info from a temp log file to the users browser
   a number of query tring params are used 

	GREP : the log file entries begin with "-- code", and the code can be passed as GREP value for 
			selecting out of log to the browser 

	PID : identifies the file created during profiling/tracing of php script 
			the pid can be shared by multiple requests, so an additional KEY  may be used 
			to uniquely identify a log file 

	LIMIT : int line limit for display to avoid humungous downloads, as log files can get large 
	START :	ignore the first START # of lines before displaying - up to LIMIT 


TODO : order by date for easier merges 
	: move log function for easier change of location if needed
*/

# needed for display of data captured in logs ...
# remove ???

#require '../kint/Kint.class.php';

$profiler_on = ''; 
$profiler_show = ''; 
$profiler_dumpvars = ''; 

init_params() ; 

?>

<html><body>
<style>
div.infooff { display: none;  border-style: solid; border-width: 1px;} 
div.helpoff { display: none;  border-style: solid; border-width: 1px;} 
div.infoon { display: block;  border-style: solid; border-width: 1px;} 
div.helpon { display: block;  border-style: solid; border-width: 1px;} 

.button-simple {
  font-family: inherit;
  font-size: 100%;
  padding: .5em 1em;
  color: #444;
  color: rgba(0,0,0,.8);
  border: 1px solid #999;
  border: 0 rgba(0,0,0,0);
  background-color: #E6E6E6;
  text-decoration: none;
  border-radius: 2px;
}
</style>

<a href="prf_showtmplog.php">myscouts profiler version 0.1</a><hr>
<form action= "prf_showtmplog.php" method='POST'>
Turn Profiler On?
<select name="profiler_on">
<option<?php selection('profiler_on',"$profiler_on",'yes') ?>>yes</option>
<option<?php selection('profiler_on',"$profiler_on",'no') ?>>no</option>
</select>
Summary Report at Footer?
<select name="profiler_show">
<option<?php selection('profiler_show',"$profiler_show",'yes') ?>>yes</option>
<option<?php selection('profiler_show',"$profiler_show",'no') ?>>no</option>
</select>
Save Variables?
<select name="profiler_dumpvars">
<option<?php selection('profiler_dumpvars',"$profiler_dumpvars",'yes') ?>>yes</option>
<option<?php selection('profiler_dumpvars',"$profiler_dumpvars",'no') ?>>no</option>
</select>
<input type='submit' value='Update Settings'>
</form>

<script>
function showhelp() { 
document.getElementById("help").className = "helpon";;
} 
function showinfo() { 
document.getElementById("info").className = "infoon";;
} 
</script>

<span class=button-simple onclick=showhelp()>show/hide help</span> 
<span class=button-simple onclick=showinfo()>show/hide info</span> 

<div id=help class=helpoff>
</div>

<div id=info class=infooff>
<pre>
<?php
function init_params() { 
global $profiler_on; 
global $profiler_show; 
global $profiler_dumpvars; 
//---- duplicated in index.php ... 
if (isset($_POST['profiler_on'])) {
    $cval=$_POST['profiler_on'];
    //should change 1,on,yes,ON,YES into 'yes'
	switch(strtolower($cval)) { 
		case 'yes':
		case '1':
		case 'on':
			$cval='yes';
			break;
	 	default : 
			$cval='no'; 
			break;	
	} 	 
    setcookie("PROFILER_ON","$cval", time()+3600, "/");
	$_COOKIE['PROFILER_ON'] = "$cval"; 
}

if (isset($_POST['profiler_show'])) {
    $cval=$_POST['profiler_show'];
    //should change 1,on,yes,ON,YES into 'yes'
	switch(strtolower($cval)) { 
		case 'yes':
		case '1':
		case 'on':
			$cval='yes';
			break;
	 	default : 
			$cval='no'; 
			break;	
	} 	 
    setcookie("PROFILER_SHOW","$cval", time()+3600, "/");
	$_COOKIE['PROFILER_SHOW'] = "$cval"; 
}
if (isset($_POST['profiler_dumpvars'])) {
    $cval=$_POST['profiler_dumpvars'];
    //should change 1,on,yes,ON,YES into 'yes'
	switch(strtolower($cval)) { 
		case 'yes':
		case '1':
		case 'on':
			$cval='yes';
			break;
	 	default : 
			$cval='no'; 
			break;	
	} 	 
    setcookie("PROFILER_DUMPVARS","$cval", time()+3600, "/");
	$_COOKIE['PROFILER_DUMPVARS'] = "$cval"; 
}
$profiler_on=(isset($_COOKIE['PROFILER_ON'])&&($_COOKIE['PROFILER_ON']=='yes')) ? 'yes':'no'; 
$profiler_show=(isset($_COOKIE['PROFILER_SHOW'])&&($_COOKIE['PROFILER_SHOW']=='yes')) ? 'yes':'no'; 
$profiler_dumpvars=(isset($_COOKIE['PROFILER_DUMPVARS'])&&($_COOKIE['PROFILER_DUMPVARS']=='yes')) ? 'yes':'no'; 

}

// process query string from links 

$lnum=0; 

/* PURGE 
 * removes all log files from system 
 */

if (isset($_GET['PURGE'])) { 
	foreach(glob("/tmp/prtest_log_*.html") as $filename ) { 
		if(unlink($filename)){ 
			print "$filename has been deleted<br> "; 
		}else{
			print "***WARNING *** $filename could not be deleted<br> "; 
		}
	} 
}

/* ANALYZE 
 * perform some simple analysis on the file to get info 
 * such as count of --types, count of functions per second etc 
 */

if (isset($_GET['ANALYZE'])) { 
	$stats = array(); 
	$times = array(); 
	// initially prepared for MERGED files ... ie if PID=0000...
	// ASSUME that PID exists !
	$lnum=0; 
	$in = fopen("/tmp/prtest_log_" . $_GET['PID'] . ".html" , 'rb'); 
	while($line = fgets($in)) {
		$lnum++; 
		$matches = array(); 
		if (preg_match("/(.*?)\s+--pid:(\d+)\s*(--\w+):/",$line,$matches)) { 
			$stats[$matches[3]]++; 
			if (preg_match("/(\d\d:\d\d:\d\d)/",$matches[1],$matches)) { 
				$times[$matches[1]]++; 
			}
		}
	}	

	print "<h3>STATS</h3>"; 
	foreach($stats as $key => $val ) { 
		print " $key : $val <br>"; 
	} 
	foreach($times as $key => $val ) { 
		print " $key : $val <br>"; 
	} 

	return; 

}


/* MERGE 
 * take all log files - and merge into a single log file 
 * sorted by time. in some cases a DRUPAL page will rely upon 
 * multiple calls via ajax/iframes/xWeb etc and will stripe 
 * stats to mutiple log file. Merge allows data to be realt with 
 * in a single file with pseudo pid 00000
 */

if (isset($_GET['MERGE'])) { 
// careful here - spawning to linux shell !
$O=`
# RE-CREATE the merged file 
cat /tmp/prtest_log_*.html | sort > /tmp/prmerged_log.html
# delete ALL old logs - keep means douple the space is used... 
rm  /tmp/prtest_log_*.html
mv  /tmp/prmerged_log.html /tmp/prtest_log_00000.html
`; 
print $O;
}


//list all files

print "<hr>";
print ' [ <a href="prf_showtmplog.php?LIST">' . "List all logs</a> ] "; 
print ' [ <a href="prf_showtmplog.php?MERGE">' . "Merge all logs</a> ] "; 
print ' [ <a href="prf_showtmplog.php?PURGE">' . "Purge all logs</a> ] "; 
print "<hr>log file:<br>";
$nums=array();
foreach(glob("/tmp/prtest_log_*.html") as $filename ) { 
	preg_match("/[0-9]+/",$filename,$nums);
	print '>' . "$filename $nums[0]  > "; 
	print '<a href="prf_showtmplog.php?LIMIT=1000&PID='.$nums[0].'">HEAD</a> '; 
	print '<a href="prf_showtmplog.php?LIMIT=1000&GREP=--chain&PID='.$nums[0].'">CHAIN</a> '; 
	print '<a href="prf_showtmplog.php?LIMIT=1000&GREP=--trace&PID='.$nums[0].'">TRACE</a> '; 
	print '<a href="prf_showtmplog.php?LIMIT=1000&GREP=--stack&PID='.$nums[0].'">STACK</a> '; 
	print '<a href="prf_showtmplog.php?LIMIT=1000&GREP=--soap&PID='.$nums[0].'">SOAP</a> '; 
	print '<a href="prf_showtmplog.php?ANALYZE&PID='.$nums[0].'">ANALYZE</a> '; 
	print "<br>";
} 
print "<hr>"; 
$O=`
df
`;
print $O;
print "<hr>"; 
$O=`
ls -ltr /tmp/prtest*.html
`;
print $O;



unset($d1);
$d1= get_defined_vars();
$all_keys=array_keys($d1);
foreach ($all_keys as $key) { 
	switch($key) { 
		case 'GLOBALS';
		case '_SESSION';
		case '_GET';
		case '_COOKIE';
		case '_SERVER';
		case '_FILES';	
			break;
		default: 
			$x=json_encode($d1[$key]);
			$x=preg_replace("/\n/"," ",$x);
			$x=preg_replace("/\r/"," ",$x);
			print_js_deserializer($x,$key);		
			break;
	}
} 
unset($d1);

function print_js_deserializer($s,$k) { 
	print "\n<a onclick=display_vars('";
	//print '{"' . $k . '":' . $s . '}';
	print  $s ;
	print "') > $k ::==>> $s</a> - " ;
}


print "</div>"; // end of menu 





/* PID => a file to process 
 * the PID indicates which file we will process. If there is no pid, 
 * as in the case of the MERGE, or the PURGE, then a file will not be 
 * displayed 
 */

if (isset($_GET['PID'])) { 

	$start_line = isset($_GET['START']) ? $_GET['START'] : 1 ; 
	$line_limit = isset($_GET['LIMIT']) ? $_GET['LIMIT'] : 99999; 
	$pattern = isset($_GET['GREP']) ? $_GET['GREP'] : 'pid'; 

	$prior_line = $start_line - $line_limit ; 
	$next_line = $start_line + $line_limit ; 

	print "<hr>";
	print "<form action='prf_showtmplog.php' method='get'>";
	print " START LINE <input name=START value=$start_line> 
			LINES TO DISPLAY <input name=LIMIT value=$line_limit> 
			LOG FILE PID <input name=PID value=".$_GET['PID'].">
			PATTERN <input name=GREP value='$pattern'> 
			<input type=submit value='Adjust Display'>";


	// expecting trace,stack,chain etc...
	// extract specific line 
	$in = fopen("/tmp/prtest_log_" . $_GET['PID'] . ".html" , 'rb'); 

	$lines_read=0;
	$lnum=1;
	print "<pre>"; 
	$printed=false;
	while(($line = fgets($in)) && ($lnum <= $line_limit)) {
		if ($start_line <= $lines_read ) {
			if (strstr($line, $pattern)) { 
				if (!$printed){
					$printed=true;
					$prior_line=$lines_read;
	print "<a href='prf_showtmplog.php?PID=".$_GET['PID']."&GREP=$pattern&START=$prior_line&LIMIT=$line_limit'><< PRIOR $line_limit lines <<</A> | "; 
				}
				echo "$lnum - $lines_read - " ; 
   	     		echo($line);
				$lnum++; 
			}
		}
		$lines_read++;
	}	

	print "<a href='prf_showtmplog.php?PID=".$_GET['PID']."&GREP=$pattern&START=$prior_line&LIMIT=$line_limit'><< PRIOR $line_limit lines <<</A> | "; 
	print "<a href='prf_showtmplog.php?PID=".$_GET['PID']."&GREP=$pattern&START=$lines_read&LIMIT=$line_limit'>>> NEXT $line_limit lines R>></A>"; 

	print "</pre>"; 

} 


?>




<script>
function display_vars(JSN) { 
JSON.parse(JSN, function(k, v) {
  console.log(k + " ==> " + v ); // log the current property name, the last is "".
  //return v;       // return the unchanged property value.
});
}
</script>


</pre></body></html>


<?php  
function selection($parm,$parmval,$val) {  
if($parmval == $val) { 
	print " selected='selected' ";
} 
}
