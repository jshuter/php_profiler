<?php
namespace TSI\profiler;

/* 
VERSION		DATE 		REASON 
----------	---------	------------------------------
0.001		20140312	Initial setup 
0.002		20140315	record overhead 
						ignore variable_get(); 

-- convert into object 

*/

// required for date functions 
date_default_timezone_set('EST');

// TEST NEW PROFILER


$last_time = microtime(true); // starting time 

$count = 0;
$threshold_trace=0;  // limit what is printed into trace (see exception below) 
$threshold_profile=0; //0.01;
$output_line_limit=9999; // to limit log gi
$output_line_count=0; 

$trace = 1 ; // require 0/1
$mydebug = 1 ;
$dumpvars_line_limit=999; // to limit log gi
$dumpvars_line_count=0; 
$dumpvars=isset($_COOKIE['PROFILER_DUMPVARS']) && ($_COOKIE['PROFILER_DUMPVARS'] == 'yes')?1:0;  
$profiler_start_time=time(); // time that this file loaded 

// leaves out of $profile ... but still prints all chains.
$partial=0; 
$partial_pattern='_xweb_request'; 


$pid=getmypid(); 
$log_file = "/tmp/prtest_log_" . $pid . ".html"; // duplicated - should move to global ? 

// the following get reset if sub-reports are to be printed 
$profile = array(); // main array to collect data for each function 


function profiler_init (){ 

	global $last_trace_chain,$last_stack_size,$last_function,$profile;
	$last_trace_chain = ""; 
	$last_stack_size=0;
	$last_function = 'none';

	$profile = array(); 

	$profile['none'] = array();
	$profile['none']['name'] = 'none';
	$profile['none']['time'] = 0;
	$profile['none']['wait'] = 0;
	$profile['none']['count'] = 1;
	$profile['none']['starts'] = 1; // updated below 
	$profile['none']['firstcount'] = 1;

}

/* profiler_log() 
 * 
 * write to log , used with if (function_exits()) to added fine grained var dumping ...
 * 
 * requires  -  arg 1 - $key - code for line be printed 
 *           -  arg 1 - $key - code for line be printed 
 * 
 *  to be used by drupal functions - but not by 'do_profile' !
 */

function profiler_log($key, $text) { 

	global $pid;
	global $log_file; 

	//$lot  = gmdate("H:i:s:u", microtime(true)) ;
	list($usec, $sec) = explode(" ", microtime());
	preg_match('/0(\.\d\d\d\d)/', $usec, $output);
	$timestamp=gmdate("H:i:s", $sec).$output[1];
	$lot  = $timestamp . " --pid:$pid --$key:"  ;
	file_put_contents($log_file, $lot . " - " . $text . "\n", FILE_APPEND );

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

// modes 0 > default 1 => noop 

$noop = false ; 

	if ($noop == true) { 
		return ; 
	} 
		// This function is triggered by all function calls.
		// Stack trace of 0 is 'this', 1 is the function that has just been popped off the stack ...

		$trace_chain = ""; 
		$threshold_trace_override = 0; 

		global $profile, $last_time, $count, $output_line_count,$dumpvars_line_limit,$dumpvars,
			   $threshold_trace, $output_line_limit, $last_function,$dumpvars_line_count,
			   $last_stack_size, $partial, $partial_pattern, $mydebug, $last_trace_chain, $trace ;

		if ($count == 0) { 
			profiler_init(); 
		}

		//-------------------------------------------------------------------------
		// save the backtrace stack for analysis ... 

		$bt = debug_backtrace();
		if (count($bt) < 1) {
				return ;
		}

		//-------------------------------------------------------------------------
		// check that we have something to work on ... ie - what function are we in ?

		$stack_size=count($bt);
		$stack_pos = 0 ;

		do {	
			// we assume that [0] contains THIS do_profile ... 
			$frame = $bt[$stack_pos];
			$function = $frame['function'];
//debug - dump all 
			profiler_log('ALL',$function);
			$stack_pos++;
			if($stack_pos>=$stack_size){
				break;
			} 
		}while((strpos($function,'do_profile')>0) || $function == 'variable_get');
			
		// added special IGNORE of variable_get() because it gets times that it should not ??

		// check if $function is set ...
		if(strpos($function,'do_profile')>0){
			return ;
		}
		// check if $function is set ...
		if($function == 'variable_get'){
			return ;
		}
		// lets also make a quick exit if we are in the show_profile() function 
		if($function == 'xshow_profileX'){
		 	// no need to fix $last_time, etc if show_profile() is the last thing that is running 
			// but ... could use some adjustment if this is not the case 	
			return ;
		}
		
		//-------------------------------------------------------------------------
		// setting time must be placed properly 
		$wait_time = (microtime(true) - $last_time);

		//-------------------------------------------------------------------------
		// can also track overhead here if wanted - ie, time used by do_profile 
		$overhead_start = microtime(true);

		// maybe only count if function name changes ?
		$count++;


		//-------------------------------------------------------------------------
		// return unless we find specific pattern in stack function or file ...

		$found = 0 ; 

		// build line log with call stack    fn1()->fn2()->fn3()->...
		$trace_chain=""; 
		$x=0; 

		foreach($bt as $k => $slice) { 
			$x++; 
			// trace all - even if partial - but drop THIS do_profile from the end of the chain 
			if( strpos($slice['function'],'do_profile')<1) {  
//TODO review $x condition . below ?
			//if($x!=1 || $slice['function'] != 'do_profile') {  
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
			
			profiler_log('chain',$trace_chain) ; 
			$last_trace_chain = $trace_chain ; 

			//DUMPVARS 
			// and the vars ? -- only when the calling function changes ...
			if ($dumpvars > 0) { 
				if ($dumpvars_line_count < $dumpvars_line_limit) { 
					$dumpvars_line_count++; 

					$lot=serialize(get_defined_vars()) ; 
					profiler_log('vars',$lot);

				}
			} 
		}else{

			//TODO 
			//EXPERIMENTAL - MAY NEED TO REMOVED 

			return ; 

		} 

// TEST - just return to see effect to here - on timing 
//return ; 


		//PARTIALS

		// check results - if we are going to exit, we want to prep data before 
		// exiting the dp_profile() function 

		if (($partial>0) && ($found < 1)) { 

			// pre-exit routine  

			if (isset($profile['_IGNORED_'])) {
				$profile['_IGNORED_']['time'] +=$wait_time;
				$profile['_IGNORED_']['wait'] +=$wait_time; // todo remove ??
			}else{
				// was bypassed somehow ? need to create entry in $profile[] !		
				$profile['_IGNORED_'] = array();
				$profile['_IGNORED_']['name'] = '_IGNORED_' ;
				$profile['_IGNORED_']['time'] = $wait_time; //todo remove ??
				$profile['_IGNORED_']['wait'] = $wait_time;
				$profile['_IGNORED_']['count'] = 1;
				$profile['_IGNORED_']['starts'] = -9999 ; // will SHOW UP on report ... 1;
				$profile['_IGNORED_']['firstcount'] = $count;
			}

			// must mimic exit routine 
			$last_time = microtime(true);
			$last_stack_size = $stack_size; 
			$last_function = '_IGNORED_' ;

			return ; 
		} 

		/* ---- 
		 * this is much more aggressive data dumping - compared to --vars above, because it will 
		 * execute many times within the same function ... --vars only dumps when first called 
		 * from within $function 
		 * ----
		 */ 

		// AVOID use of functions frin within do_profile - which will get then get logged ...
		/* USE WITH CAUTION - watch space - 
		if($output_line_count < 100) { 	
			#$dump=serialize(get_defined_vars()); 
			$dump=@serialize(get_defined_vars()); 
			profiler_log("vars-all",$dump) ; 
		} 
		*/


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
// TODO - review - time for THIS_FUNCTION - OR - LAST_FUNCTION ??

		// EXPERIMENT- put time into prior function ?
		if(isset($function)){ 
			$profile[$last_function]['time'] += $wait_time;
		}


		// everything on the stack is waiting ...


		$function_dupcheck = array(); 

		foreach($bt as $index => $caller) {

				$fn = $caller['function'];

				// don't count time on function on BOTH exec & wait 
// TODO - review
// TODO - review - time for THIS FUNCTION - OR - LAT_FUNCTION ??
// EXPERIMENT- put time into prior function ?

				if ( ($fn != $last_function) && (!isset($function_dupcheck[$fn])) )  { 

					// track stats for all the other items on the stack 
					// NOTE that functions can be included more than once on stack, and we must avoid 
					// double counting the wait stats !

					$function_dupcheck[$fn] = 1; 

					if (isset($profile[$fn]['name'])) {
							$profile[$fn]['wait'] +=$wait_time;
// TODO - split into wait count & exec count 
							$profile[$fn]['count']++;
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

/* debug 
if (!(isset($caller['file']))) { 
print_r(get_defined_vars()) ; 
exit ; 
}
*/
					// caller can be function or object 
					// TODO - check for 'class' if file is missing -- better handle Classes !

					$lot = " fn:$function - count:$count - stack_pos:$index - file:" ;
					$lot .= (isset($caller['file'])) ? $caller['file'] : 'FILE DNE' ;
					$lot .= " - line:" ;
					$lot .= (isset($caller['line'])) ? $caller['line'] : 'LINE DNE' ;
					$lot .= " - function:" . $caller['function'] . " --" ;
					profiler_log('stack',$lot);

					$output_line_count++; 			
				}
		}

//TODO - review logic ...  NOT FOOL PROOF A>B>C can switch to E>F>G !!?

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
								$lot = " count: $count ";
								$lot = $lot . " - wait_time: " . number_format($wait_time,4);
							//	$lot = $lot . " - last_time: " . gmdate("H:i:s",$last_time);
								$lot .= (isset($caller['line'])) ? $caller['line'] : 'FILE DNE' ;
								//$lot = $lot . " - line: " . $frame['line'] ;
								$lot .= (isset($caller['file'])) ? $caller['file'] : 'FILE DNE' ;
								//$lot = $lot . " - file: " . $frame['file'] ;
								$lot = $lot . " - function: $function ";
								$lot = $lot . " - prior_function: $last_function ";
								$lot = $lot . " - stack_size: $stack_size ";
								$lot = $lot . " - last_start_size: $last_stack_size ";
								$lot = $lot . " - time: " . number_format($profile[$caller['function']]['time'],4) ;
								$lot = $lot . " - wait: " . number_format($profile[$caller['function']]['wait'],4) ;
								$lot = $lot . " - starts: " . $profile[$caller['function']]['starts'] ;
								$lot = $lot . "--";

								profiler_log('trace',$lot);
						}
						$output_line_count++; 
				}
		}


		// exit routine 
		$last_time = microtime(true);
		$last_function = $function ;
		$last_stack_size = $stack_size ;

		// record overhead 
		$profile['TSI\profiler\do_profile']['time'] += (microtime(true) - $overhead_start);  

		//unset($bt);

}

function show_profile() {

		// turn of profiler for reporting
		// declare(ticks=0);

		// print out report of aggregated stack info and timming

		global $profile,$count,$threshold_profile,$profiler_start_time,$pid;

		$fcount=0;
		$prcount=0;

		profiler_log('log','Report Started'); 

		echo html_styles(); 
		echo "<hr><div class='profiler'>";
		echo '<h2>Slow Function Report</h2>';


echo "<hr> SHOW THE LOG <br> 
<a href=\"/php_profiler/prf_showtmplog.php?PID=$pid\" target=\"logfile\">Show Log - ALL</a> |  
<a href=\"/php_profiler/prf_showtmplog.php?GREP=chain&PID=$pid\" target=\"chainlog\">Show Log -- chain</a> | 
<a href=\"/php_profiler/prf_showtmplog.php?GREP=trace&PID=$pid\" target=\"tracelog\">Show Log -- trace</a> | 
<a href=\"/php_profiler/prf_showtmplog.php?GREP=stack&PID=$pid\" target=\"stacklog\">Show Log -- stack</a> |
<a href=\"/php_profiler/prf_showtmplog.php?PURGE=all\" target=\"stacklog\">Purge all</a>"; 


print "<table border=1>" ;

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
		if ( $f['time'] >= $threshold_profile || $f['wait'] >= $threshold_profile) {
		//if ( $f['time'] >= $threshold_profile ) {

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

			if (!isset($f['count'])) { 
				print_r($f);
			}else {
			// avg exec time
			$a = $f['time'] / ($f['count']) ;
			echo "<td>" . number_format($a,4);
			// avg wait time
			$a = $f['wait'] / ($f['count']) ;
			echo "<td>" .  number_format($a,4);
			}
		}
	}	
echo "</table>";

echo "Number of unique functions executed :$fcount<br>";
echo "Number of function execs :$count<br>";
echo "Number of functions with accumlated > $threshold_profile millmilliiseconds :$prcount<br>";
echo "Start time :" . gmdate("H:m:s",$profiler_start_time) ; 
$profiler_end_time=time();
echo " - End time :" . gmdate("H:m:s",$profiler_end_time)  ; 
echo "<br>Elapsed time (s):" . number_format($profiler_end_time - $profiler_start_time, 2) . " seconds." ; 
unset($profiler_start_time) ; 
echo '</div>';

profiler_log('log','Report ended'); 
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
tr.hot td { background: #f78181 ; color: #000000; } 
tr.warm td { background: #f7be81; color: #000000; } 
tr.lukewarm td { background: #f3f781; #color: #000000; } 
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
