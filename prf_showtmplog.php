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

require '../kint/Kint.class.php';

print '<html><body><a href="prf_showtmplog.php">myscouts profiler version 0.1</a><hr>'; 
?>
settings:
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
<input type='submit'>
</form>
<pre>
<?php









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



// params 

$lnum=0; 

// during testing ... PURGE all logs 
if (isset($_GET['PURGE'])) { 

	foreach(glob("/tmp/prtest_log_*.html") as $filename ) { 
	if(unlink($filename)){ 
	print "$filename has been deleted<br> "; 
	}else{
	print "***WARNING *** $filename could not be deleted<br> "; 
	}
	} 
}

$stats = array(); 
$times = array(); 

if (isset($_GET['ANALYZE'])) { 
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


// set user request params 

if (isset($_GET['LIMIT'])) { 
	$line_limit=$_GET['LIMIT'];
} else { 
	$line_limit=99999; 
}

$start_line = isset($_GET['START']) ? $_GET['START'] : 1 ; 

if (isset($_GET['PID'])) { 

//TODO:
// should DEFAULT to "-" or somehting that will get every line !
	
	if(isset($_GET['GREP'])) { 
		// expecting trace,stack,chain etc...
		// extract specific line 
		$pattern=$_GET['GREP'];
		$in = fopen("/tmp/prtest_log_" . $_GET['PID'] . ".html" , 'rb'); 

		$lines_read=1;

		while(($line = fgets($in)) && ($lnum < $line_limit)) {
			if ($start_line < $lines_read ) {
				if (strstr($line, $pattern)) { 
					echo "$lnum - $lines_read " ; 
    	     		echo($line);
					$lnum++; 
				}
			}
			$lines_read++;
		}	

    }elseif(isset($_GET['LIMIT'])) { 

    	echo file_get_contents("/tmp/prtest_log_" . $_GET['PID'] . ".html",false,NULL,0,$line_limit * 100  );	

	}else{
		// dump the whole file 
    	echo file_get_contents( "/tmp/prtest_log_" . $_GET['PID'] . ".html" );	
	}
 

	if($lnum>$line_limit){ 
		return; 
	}
} 

//list all files


print "<hr>log file:<br>";
$nums=array();
foreach(glob("/tmp/prtest_log_*.html") as $filename ) { 
	preg_match("/[0-9]+/",$filename,$nums);
	print '<a href="prf_showtmplog.php?PID='.$nums[0].'">' . "$filename $nums[0] DUMP ALL</a> "; 
	print '<a href="prf_showtmplog.php?LIMIT=100&PID='.$nums[0].'">HEAD</a> '; 
	print '<a href="prf_showtmplog.php?GREP=--chain&PID='.$nums[0].'">CHAIN</a> '; 
	print '<a href="prf_showtmplog.php?ANALYZE&PID='.$nums[0].'">ANALYZE</a> '; 
	print "\n<br>";
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

print '<hr>* <a href="prf_showtmplog.php?LIST">' . "List all logs</a>"; 
print '<br>* <a href="prf_showtmplog.php?MERGE">' . "Merge all logs</a>"; 
print '<br>* <a href="prf_showtmplog.php?PURGE">' . "Purge all logs</a>"; 


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
