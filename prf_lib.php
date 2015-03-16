<?php

/* i
VERSION		DATE 		REASON 
----------	---------	------------------------------
0.001		20140312	Initial setup 
0.002		20140315	record overhead 
						ignore variable_get(); 
TOFO 

-- error trapping 
-- convert into object 

*/


date_default_timezone_set('UTC');

// TEST NEW PROFILER

$profile = array(); // main array to collect data for each function 
$last_time = microtime(true); // starting time 
$count = 0;
$threshold_trace=0;  // limit what is printed into trace (see exception below) 
$threshold_profile=0.01;
$output_line_limit=1999; // to limit log gi
$trace = 1 ; // require 0/1
$mydebug = 1 ;
$last_function = "";
$last_stack_size=0;

$profiler_start_time=time(); 

$partial=0; 
$partial_pattern=''; 

$output_line_count=0; 
$last_trace_chain = ""; 



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

		$trace_chain = ""; 
		$threshold_trace_override = 0; 

		global $profile, $last_time, $count, $output_line_count, 
			   $threshold_trace, $output_line_limit, $last_function,
			   $last_stack_size, $partial, $partial_pattern, $mydebug, $last_trace_chain, $trace ;

		
		//-------------------------------------------------------------------------
		// get log file name  

		$pid = getmypid();
		$log_file = "/tmp/prtest_log_" . $pid . ".html";

		//-------------------------------------------------------------------------
		// save the backtrace stack for analysis ... 

		$bt = debug_backtrace();

		if (count($bt) < 1) {
				return ;
		}

		//-------------------------------------------------------------------------
		// check that we have something to work on ...

		$stack_size=count($bt);
		$stack_pos = 0 ;

		do {	
			// we assume that [0] contains THIS do_profile ... 
			$frame = $bt[$stack_pos];
			$function = $frame['function'];
			$stack_pos++;
			if($stack_pos>=$stack_size){
				break;
			} 
		// added special IGNORE of variable_get() because it gets times that it should not ??
		}while($function == 'do_profile' || $function == 'variable_get');

		// check if $function is set ...
		if($function == 'do_profile'){
			return ;
		}


		// lets also make a quick exit if we are in the showshow_profile() function 
		if($function == 'show_profile'){
		 	// no need to fix $last_time, etc if show_profile() is the last thing that is running 
			// but ... could use some adjustment if this is not the case 	
			return ;
		}
		
		
		//-------------------------------------------------------------------------

		// setting time must be placed properly 
		$wait_time = (microtime(true) - $last_time);

		// can also track overhead here if wanted - ie, time used by do_profile 
		$overhead_start = microtime(true);

		$count++;
		// maybe only count of function name changes ?


		//-------------------------------------------------------------------------
		// return unless we find specific pattern in stack function or file ...

		$found = 0 ; 

		// build line log with call stack    fn1()->fn2()->fn3()->...
		$trace_chain=""; 
		$x=0; 
		foreach($bt as $k => $slice) { 
			$x++; 
			// trace all - even if partial - but drop THIS do_profile from the end of the chain 
			if($x!=1 || $slice['function'] != 'do_profile') {  
				$trace_chain = $slice['function'] . "->" . $trace_chain ; 
				if($partial > 0) { 
					if (preg_match("/$partial_pattern/",$slice['function']) >0 ) { 
						$found++; 
					}	 
				}
			}
		}


		// write chain log to file 
	
		if ($trace_chain != $last_trace_chain) { 
			$lot = "-- chain:" . gmdate("H:i:s", time()) ; 
			file_put_contents($log_file, $lot, FILE_APPEND );
			file_put_contents($log_file, " - ", FILE_APPEND );
			$lot = $trace_chain . "\n";
			file_put_contents($log_file, $lot, FILE_APPEND );
			$last_trace_chain = $trace_chain ; 
		} 

		// check results - if we are going to exit, we want to prep data before 
		// exiting the dp_profile() function 

		if (($partial>0) && ($found < 1)) { 

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

//		$profile[$function]['time'] += $wait_time;
// TODO - review - time for THIS FUNCTION - OR - LAT_FUNCTION ??

		// EXPERIMENT- put time into prior function ?
		if(isset($function)){ 
			$profile[$function]['time'] += $wait_time;
		}


		// everything on the stack is waiting ...


		$function_dupcheck = array(); 

		foreach($bt as $index => $caller) {

				$fn = $caller['function'];

				// don't count time on function on BOTH exec & wait 
// TODO - review
// TODO - review - time for THIS FUNCTION - OR - LAT_FUNCTION ??
// EXPERIMENT- put time into prior function ?

				if ( ($fn != $function) && (!isset($function_dupcheck[$fn])) )  { 

					// track stats for all the other items on the stack 
					// NOTE that functions can be included more than once on stack, and we must avoid 
					// double counting the wait stats !

					$function_dupcheck[$fn] = 1; 

					if (isset($profile[$fn]['name'])) {
							$profile[$caller['function']]['wait'] +=$wait_time;
// TODO - split into wait count & exec count 
							$profile[$caller['function']]['count']++;
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
				}


				if( $output_line_count < $output_line_limit ) { 
					$lot = "-- stack: fn:$function - count:$count - stack_pos:$index - file:" ;
					file_put_contents($log_file, $lot, FILE_APPEND );
					$lot=$caller['file'] . " - line:" . $caller['line'] . " - function:" . $caller['function'] . " --\n" ;
					file_put_contents($log_file, $lot, FILE_APPEND );
					$output_line_count++; 			
				}
		}

//TODO - review logic ... 

		// assume [0] is profiler .... [1] real FUNCTION ...  
		if($stack_size > $last_stack_size) { 
				$profile[$function]['starts']++;
		}elseif (($stack_size == 2) && ($last_stack_size==2) && ($last_function != $function)) { 
				$profile[$function]['starts']++;
		} 


		// trace all - all lines ?
		if($trace > 0) {

				// check line limit ?
				if ($output_line_count < $output_line_limit ) {

						if(($wait_time >= $threshold_trace) || ($threshold_trace_override>0)) {
								// check that function changes ?
								$lot = "-- trace:" . gmdate("H:i:s", time()) ;
								$lot = $lot . " count: $count ";
								$lot = $lot . " - wait_time: " . number_format($wait_time,4);
							//	$lot = $lot . " - last_time: " . gmdate("H:i:s",$last_time);
								$lot = $lot . " - line: " . $frame['line'] ;
								$lot = $lot . " - file: " . $frame['file'] ;
								$lot = $lot . " - function: $function ";
								$lot = $lot . " - prior_function: $last_function ";
								$lot = $lot . " - stack_size: $stack_size ";
								$lot = $lot . " - last_start_size: $last_stack_size ";
								$lot = $lot . " - time: " . number_format($profile[$caller['function']]['time'],4) ;
								$lot = $lot . " - wait: " . number_format($profile[$caller['function']]['wait'],4) ;
								$lot = $lot . " - starts: " . $profile[$caller['function']]['starts'] ;
								$lot = $lot . "--\n";
								file_put_contents($log_file, $lot, FILE_APPEND );
						}
						$output_line_count++; 
				}
		}

		$last_time = microtime(true);
		$last_function = $function ;
		$last_stack_size = $stack_size ;

		// record overhead 
		$profile['do_profile']['time'] += (microtime(true) - $overhead_start);  

		//unset($bt);

}

function show_profile() {

		// turn of profiler for reporting
		// declare(ticks=0);

		// print out report of aggregated stack info and timming

		global $profile,$count,$threshold_profile;

		$fcount=0;
		$prcount=0;

		echo html_styles(); 
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
		//if ( $f['time'] >= $threshold_profile || $f['wait'] >= $threshold_profile) {
		if ( $f['time'] >= $threshold_profile ) {

			if ($f['time'] > 0.1) { $class=" class=hot" ; 
			}elseif($f['time'] > 0.01) { $class=" class=warm" ; 
			}elseif($f['time'] > 0) { $class=" class=lukewarm" ; 
			}else{ $class="";}

			$prcount++;
			print "<tr$class><td>$prcount<td>$fcount";
			foreach($f as $k => $v) {
				if (is_float($v)) { 
					$padded = number_format($v,2);
					echo "<td>$padded";
				} else { 
					echo "<td>$v";
				} 
			}
			// avg exec time
			$a = $f['time'] / ($f['count']) ;
			echo "<td>" . number_format($a,4);
			// avg wait time
			$a = $f['wait'] / ($f['count']) ;
			echo "<td>" .  number_format($a,4);
		}
	}	

$pid=getmypid(); 
echo "</table>Number of unique functions executed :$fcount<br>";
echo "Number of execs :$count<br>";
echo "Number of functions with accumlated > $threshold_profile millmilliiseconds :$prcount<br>";
echo "Start time :" . gmdate("H:m:s",$profiler_start_time) ; 
echo " - End time :" . gmdate("H:m:s",time())  ; 
echo "<br>Elapsed time (s):" . number_format((microtime() - $profiler_start_time), 4) ; 
echo "<hr> SHOW THE LOG <br> 
<a href=\"/prf_showtmplog.php?PID=$pid\" target=\"logfile\">Show Log - ALL</a> |  
<a href=\"/prf_showtmplog.php?GREP=chain&PID=$pid\" target=\"chainlog\">Show Log -- chain</a> | 
<a href=\"/prf_showtmplog.php?GREP=trace&PID=$pid\" target=\"tracelog\">Show Log -- trace</a> | 
<a href=\"/prf_showtmplog.php?GREP=stack&PID=$pid\" target=\"stacklog\">Show Log -- stack</a>"; 
echo '</div>';

}

function html_styles() { 

print '
<style>
/* ------------------ Table Styles ------------------ */
table { border: 0; border-spacing: 0; font-size: 0.857em; margin: 10px 0; width: 100%; background-color: #ddd}
table table { font-size: 1em; }
#footer-wrapper table { font-size: 1em; }
table tr th { background: #FFFFFF; border-bottom-style: none; }
table tr th,
table tr th a,
table tr th a:hover { color: #FFF; font-weight: bold; }
table tbody tr th { vertical-align: top; }
tr.hot td { background: #eeaaaa ; color: #000000; } 
tr.warm td { background: #f8cc9c; color: #000000; } 
tr.lukewarm td { background: #f6fa94; #color: #000000; } 
tr td,
tr th { padding: 4px 9px; border: 1px solid #fff; text-align: left; /* LTR */ }
#footer-wrapper tr td,
#footer-wrapper tr th { border-color: #555; border-color: rgba(255, 255, 255, 0.18); }
tr.odd { background: #e4e4e4; background: rgba(0, 0, 0, 0.105); }
tr,
tr.even { background: #efefef; background: rgba(0, 0, 0, 0.063); }
table ul.links { margin: 0; padding: 0; font-size: 1em; }
table ul.links li { padding: 0 1em 0 0; }

#body { visibility: hidden }
#div.profiler { visibility: visible; position: absolute; top:1; left:1; }

</style>
'; 

} 
