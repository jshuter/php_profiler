<?php 

/* this function is used to display info from a temp log file to the users browser
   a number of query tring params are used 

	GREP : the log file entries begin with "-- code", and the code can be passed as GREP value for 
			selecting out of log to the browser 

	PID : identifies the file created during profiling/tracing of php script 
			the pid can be shared by multiple requests, so an additional KEY  may be used 
			to uniquely identify a log file 

	LIMIT : int line limit for display to avoid humungous downloads, as log files can get large 

*/
	
print '<html><body>PROFILE FOR PID<hr>...<pre>'; 

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
	return ; 
}

// set user request params 

if (isset($_GET['LIMIT'])) { 
	$line_limit=$_GET['LIMIT'];
} else { 
	$line_limit=99999; 
}

if (isset($_GET['PID'])) { 


	if(isset($_GET['GREP'])) { 

		// expecting trace,stack, or chain ...
		// extract specific line 

		$pattern=$_GET['GREP'];
		$in = fopen("/tmp/prtest_log_" . $_GET['PID'] . ".html" , 'rb'); 
		while($line = fgets($in)) {
			if (strstr("-- $line", $pattern)) { 
				echo "$lnum - " ; 
    	     	echo($line);
				$lnum++; 
			}
		}	

	}else{
		// dump the whole file 
    	echo file_get_contents( "/tmp/prtest_log_" . $_GET['PID'] . ".html" );	
	}
 

	if($lnum>$line_limit){ 
		return; 
	}
} 

print '</pre></body></html>'; 

 
