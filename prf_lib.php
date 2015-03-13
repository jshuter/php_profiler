<?php

date_default_timezone_set('UTC');

// TEST NEW PROFILER

$profile = array(); // main array to collect data for each function 
$last_time = microtime(true); // starting time 
$count = 0;
$threshold_trace=0.1;
$threshold_profile=0.01;
$output_line_limit=999; // to limit log gi
$trace = 1 ; // require 0/1
$debug =1 ;
$last_function = "";
$last_stack_size=0;

### TESTING

function start_profile() { 
		declare(ticks=1);
		register_tick_function('do_profile');
}

/* 
   print "BEFORE SERIAL()\n";
   func_serial() ;
   print "AFTER SERIAL()\n";

   show_profile();

// reset

$profile = array();
print "BEFORE ONE()\n";
func_one();
print "AFTER ONE()\n";
show_profile();
 */



function do_profile() {

		// This function is triggered by all function calls.
		// Stack trace of 0 is 'this', 1 is the function that has just been popped off the stack ...

		global $profile, $last_time, $count, 
			   $threshold_trace, $output_line_limit, $last_function,
			   $last_stack_size;

		$debug = 1;
		$pid = getmypid();
		$log_file = "/tmp/prtest_log_" . $pid . ".html";
		$trace = 1;

		// save the stack array
		$bt = debug_backtrace();

		if (count($bt) < 1) {
				return ;
		}

		$stack_size=count($bt);
		$stack_pos = 0 ;

		do {
				$frame = $bt[$stack_pos];
				$function = $frame['function'];
				$stack_pos++;
				if($stack_pos>=$stack_size){
						break;
				} 
		}while($function == 'do_profile');


		// check if $function is set ...
		if($function == 'do_profile'){
				return ;
		}


		$wait_time = (microtime(true) - $last_time);


		// maybe only count of function name changes ?
		$count++;

		// make new entry for function if it is new
		if (!isset($profile[$function]['name'])) {
				$profile[$function] = array();
				$profile[$function]['name'] = $function ;
				$profile[$function]['time'] = 0;
				$profile[$function]['wait'] = 0;
				$profile[$function]['count'] = 1;
				$profile[$function]['starts'] = 0; // updated below 
				$profile[$function]['firstcount'] = $count;
		}else{
				$profile[$function]['count'] += 1 ;
		}

		// exec time ...
		$profile[$function]['time'] += $wait_time;


		// everything on the stack is waiting ...
		foreach($bt as $index => $caller) {

				$fn = $caller['function'];

				if (isset($profile[$fn]['name'])) {
						$profile[$caller['function']]['wait'] +=$wait_time;
				}else{
						// was bypassed somehow ? need to create entry in $profile[] !		
						$profile[$fn] = array();
						$profile[$fn]['name'] = $fn ;
						$profile[$fn]['time'] = 0;
						$profile[$fn]['wait'] = $wait_time;
						$profile[$fn]['count'] = 1;
						$profile[$fn]['starts'] = -9999 ; // will SHOW UP on report ... 1;
						$profile[$fn]['firstcount'] = $count;
				}

				if ( $debug > 0 ) {
					if( $count < $output_line_limit ) { 
						$lot = "<!-- stack: fn:$function - count:$count - stack_pos:$index - file:" ;
						file_put_contents($log_file, $lot, FILE_APPEND );
						$lot=$caller['file'] . " - line:" . $caller['line'] . " - function:" . $caller['function'] . " -->\n" ;
						file_put_contents($log_file, $lot, FILE_APPEND );
					}
				}
		}


		// assume [0] is profiler .... [1] real FUNCTION ...  
		if($stack_size > $last_stack_size) { 
				$profile[$function]['starts']++;
		}elseif (($stack_size == 2) && ($last_stack_size==2) && ($last_function != $function)) { 
				$profile[$function]['starts']++;
		} 


		// trace all - all lines ?
		if($trace==1) {

				// check line limit ?
				if ($count < $output_line_limit ) {

						if ($count==1) {
								$log = "<!-- count last_time line file function last_function stack_size last_stack_size time wait -->\n" ;
								file_put_contents($log_file, $lot, FILE_APPEND );

						}

						if($wait_time > $threshold_trace) {
								// check that function changes ?
								$lot = "<!-- trace : ";
								$lot = $lot . " count: $count ";
								$lot = $lot . " - last_time: $last_time ";
								$lot = $lot . " - line: " . $frame['line'] ;
								$lot = $lot . " - file: " . $frame['file'] ;
								$lot = $lot . " - function: $function ";
								$lot = $lot . " - last_function: $last_function ";
								$lot = $lot . " - stack_size: $stack_size ";
								$lot = $lot . " - last_start_size: $last_stack_size - time: ";
								$lot = $lot . $profile[$caller['function']]['time'] ;
								$lot = $lot . " - wait: " ;
								$lot = $lot . $profile[$caller['function']]['wait'] ;
								$lot = $lot . " - starts: " ;
								$lot = $lot . $profile[$caller['function']]['starts'] ;
								$lot = $lot . "-->\n";
								file_put_contents($log_file, $lot, FILE_APPEND );
						}
				}
		}

		$last_time = microtime(true);
		$last_function = $function ;
		$last_stack_size = $stack_size ;

		unset($bt);

}

function show_profile() {

		// turn of profiler for reporting
		// declare(ticks=0);

		// print out report of aggregated stack info and timming

		global $profile,$count,$threshold_profile;

		$fcount=0;
		$prcount=0;

		echo "<div class='profiler'>";
		echo '<h2>Slow Function Report</h2><table border=1>' ;

		foreach($profile as $f) {

				$fcount++;

				// print headers...
				if($fcount ==1) { 
						echo "<tr><td>item<td>fn num"; 
						foreach($f as $k => $v) {
								echo "<td>$k";
						}
						echo "<td>avg exec<td>avg wait</tr>"; 
				}

				// print the data 
		if ( $f['time'] > $threshold_profile ) {
			$prcount++;
			print "<tr><td>$prcount<td>$fcount";
			foreach($f as $k => $v) {
				echo "<td>$v";
			}
			// avg exec time
			$a = $f['time'] / ($f['count']+0.1) ;
			echo "<td>$a";
			// avg wait time
			$a = $f['wait'] / ($f['count']+0.1) ;
			echo "<td>$a<tr>";
		}
	}	

echo "</table>Number of unique functions executed :$fcount<br>";
echo "Number of execs :$count<br>";
echo "Number of functions with accumlated > $threshold_profile millmilliiseconds :$prcount<br>";
echo "<hr> SHOW THE LOG <br> 
<a href=\"showtmplog.php?PID=<?php echo getmypid()?>\" target=\"logfile\">Show Log</a>"; 
echo '</div>';

}


