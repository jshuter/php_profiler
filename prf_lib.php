<?php
/* i
VERSION		DATE 		REASON 
----------	---------	------------------------------
0.001		20140312	Initial setup 


*/


date_default_timezone_set('UTC');

// TEST NEW PROFILER

$profile = array(); // main array to collect data for each function 
$last_time = microtime(true); // starting time 
$count = 0;
$threshold_trace=0;
$threshold_profile=0;
$output_line_limit=999; // to limit log gi
$trace = 1 ; // require 0/1
$debug =1 ;
$last_function = "";
$last_stack_size=0;

$partial=1; 
$partial_pattern='two'; 

$trace_count=0; 


/* 
 *   start_profile() 
 * 
 * this starts the process 
 * 2 lines can be called ... but sometimes 
 * code needs to be executed directly, without 
 * calling from a function 
 * 
 */

function start_profile() { 
		declare(ticks=1);
		register_tick_function('do_profile');
		return true ; 
}




/* 
 *   do_profile() 
 * 
 * do_profile is the workhorse of the Profiler and the Trace 
 * the do_profile() function is called for every 'tick' of execution 
 * this adds a little overhead that can be identified on the report 
 * do_profile() should be at the top of every backtrace that it calls, and the 
 * function that is executing should be the second entry in the backtrace array. 
 * 
 * a few funny things seem to happen with require/require_once, etc 
 * and do_profile can be triggered from a stack with 3 entries, followed imediately 
 * by a stack with 8 entries. Therefore the code needs to handle a variety of 
 * situations  
 * 
 */



function do_profile() {

		// This function is triggered by all function calls.
		// Stack trace of 0 is 'this', 1 is the function that has just been popped off the stack ...

		global $profile, $last_time, $count, 
			   $threshold_trace, $output_line_limit, $last_function,
			   $last_stack_size,$partial,$partial_pattern;

		//-------------------------------------------------------------------------
		// init params 

		$debug = 0;
		$pid = getmypid();
		$log_file = "/tmp/prtest_log_" . $pid . ".html";
		$trace = 1;

		//-------------------------------------------------------------------------
		// save the stack array

		$bt = debug_backtrace();

		if (count($bt) < 1) {
				return ;
		}

		//-------------------------------------------------------------------------
		// check that we have something to work on ...

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


		//-------------------------------------------------------------------------

		// setting time must be placed properly 
		$wait_time = (microtime(true) - $last_time);
		// maybe only count of function name changes ?
		$count++;



		//-------------------------------------------------------------------------
		// return unless we find specific pattern in stack function or file ...

		if ($partial>0) { 
			$found = 0 ; 
			foreach($bt as $k => $slice) { 
				# rule 1 - only trace if pattern is found 
				if($debug > 0 ) {  
					echo $slice['function'] . "->"; 
				} 
				if (preg_match("/$partial_pattern/",$slice['function']) >0 ) { 
					$found++; 
				}	 
			} 

			if($debug){
				echo "<br>\n";
			} 

			// check results - if we are going to exit, we want to prep data before 
			// exiting the dp_profile() function 
 
			if($found < 1) { 

				// pre-exit routine  

				if (isset($profile['_IGNORED_'])) {
					$profile['_IGNORED_']['time'] +=$wait_time;
					$profile['_IGNORED_']['wait'] +=$wait_time;
				}else{
					// was bypassed somehow ? need to create entry in $profile[] !		
					$profile['_IGNORED_'] = array();
					$profile['_IGNORED_']['name'] = '_IGNORED_' ;
					$profile['_IGNORED_']['time'] = $wait_time;
					$profile['_IGNORED_']['wait'] = $wait_time;
					$profile['_IGNORED_']['count'] = 1;
					$profile['_IGNORED_']['starts'] = -9999 ; // will SHOW UP on report ... 1;
					$profile['_IGNORED_']['firstcount'] = $count;
				}

				$last_time = microtime(true);
				$last_stack_size = $stack_size; 
				$last_function = '_IGNORED_' ;

				return ; 
			}	 
		} 



		//-------------------------------------------------------------------------
		// BEGIN 



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
					if( $output_line_count < $output_line_limit ) { 
						$lot = "<!-- stack: fn:$function - count:$count - stack_pos:$index - file:" ;
						file_put_contents($log_file, $lot, FILE_APPEND );
						$lot=$caller['file'] . " - line:" . $caller['line'] . " - function:" . $caller['function'] . " -->\n" ;
						file_put_contents($log_file, $lot, FILE_APPEND );
						$output_line_count++; 			
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
				if ($output_line_count < $output_line_limit ) {

						if ($count==1) {
								$lot = "<!-- count last_time line file function last_function stack_size last_stack_size time wait -->\n" ;
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
								$lot = $lot . " - last_start_size: $last_stack_size ";
								$lot = $lot . " - time: " . $profile[$caller['function']]['time'] ;
								$lot = $lot . " - wait: " . $profile[$caller['function']]['wait'] ;
								$lot = $lot . " - starts: " . $profile[$caller['function']]['starts'] ;
								$lot = $lot . "-->\n";
								file_put_contents($log_file, $lot, FILE_APPEND );
						}
						$output_line_count++; 
				}
		}

		$last_time = microtime(true);
		$last_function = $function ;
		$last_stack_size = $stack_size ;

		//unset($bt);

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
				if (is_float($v)) { 
					$padded = number_format($v,2);
					echo "<td>$padded";
				} else { 
					echo "<td>$v";
				} 
			}
			// avg exec time
			$a = $f['time'] / ($f['count']+0.1) ;
			echo "<td>" . number_format($a,4);
			// avg wait time
			$a = $f['wait'] / ($f['count']+0.1) ;
			echo "<td>" .  number_format($a,4);
		}
	}	

$pid=getmypid(); 
echo "</table>Number of unique functions executed :$fcount<br>";
echo "Number of execs :$count<br>";
echo "Number of functions with accumlated > $threshold_profile millmilliiseconds :$prcount<br>";
echo "<hr> SHOW THE LOG <br> 
<a href=\"prf_showtmplog.php?PID=$pid\" target=\"logfile\">Show Log</a>"; 
echo '</div>';

}


