<html>
<head>
</head>

<body>

<style>
/* ------------------ Table Styles ------------------ */

table {
  border: 0;
  border-spacing: 0;
  font-size: 0.857em;
  margin: 10px 0;
  width: 100%;
}
table table {
  font-size: 1em;
}
#footer-wrapper table {
  font-size: 1em;
}
table tr th {
  background: #757575;
  background: rgba(0, 0, 0, 0.51);
  border-bottom-style: none;
}
table tr th,
table tr th a,
table tr th a:hover {
  color: #FFF;
  font-weight: bold;
}
table tbody tr th {
  vertical-align: top;
}
tr td,
tr th {
  padding: 4px 9px;
  border: 1px solid #fff;
  text-align: left; /* LTR */
}
#footer-wrapper tr td,
#footer-wrapper tr th {
  border-color: #555;
  border-color: rgba(255, 255, 255, 0.18);
}
tr.odd {
  background: #e4e4e4;
  background: rgba(0, 0, 0, 0.105);
}
tr,
tr.even {
  background: #efefef;
  background: rgba(0, 0, 0, 0.063);
}
table ul.links {
  margin: 0;
  padding: 0;
  font-size: 1em;
}
table ul.links li {
  padding: 0 1em 0 0;
}
</style>



<?php

/**
 * @file
 * The PHP page that serves all page requests on a Drupal installation.
 *
 * The routines here dispatch control to the appropriate handler, which then
 * prints the appropriate page.
 *
 * All Drupal code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 */


date_default_timezone_set('UTC'); 


// TEST NEW PROFILER 

declare(ticks=1);
register_tick_function('do_profile');

$profile = array();
$last_time = microtime(true);
$last_function = ""; 
$last_stack_size=0;
$level = 0; 
$count = 0; 
$func_aggregate_threshold=0.0; 
$wait_threshold=0.0; 
$output_line_limit=9999; // 
$trace = 1 ; // require 0/1

/**
 * Root directory of Drupal installation.
 */


func_serial() ; 

show_profile();

// reset 
$profile = []; 

print "BEFORE ONE()\n"; 
func_one(); 
print "AFTER ONE()\n"; 

show_profile();

return ; 


function do_profile() {

	// This function is triggered by all function calls. 
	// Stack trace of 0 is 'this', 1 is the function that has just been popped off the stack ...
    global $profile,$last_time,$level,$count,$wait_threshold,$output_line_limit,$trace,$last_function
	,$last_stack_size;

	// save the stack array 
    $bt = debug_backtrace();

    if (count($bt) <= 1) {
        return ;
    }

    $frame = $bt[1];
 
    $function = $frame['function'] ;

/*
	if($function == $last_function) { 
		// just running through code in same function ... 
		// return ? i???
		return ; 
	} 
*/

// count number of items on the stack- but ignore do_profile()  
/* 
print "FUNCTION : $function " ; 
print_r($bt) ; 
*/

$stack_size=count($bt); 

	// maybe only count of function name changes ?
	$count++; 

	// make new entry for function if it is new 
    if (!isset($profile[$function]['name'])) {
        $profile[$function] = [];
        $profile[$function]['name'] = $function ;
        $profile[$function]['time'] = 0;
        $profile[$function]['wait'] = 0;
        $profile[$function]['count'] = 0;
    }

	$wait_time = (microtime(true) - $last_time);

    $profile[$function]['time'] += $wait_time;
    $profile[$function]['count'] += 1 ;  

	// everything on the stack is waiting ...
	foreach($bt as $index => $caller) { 
		if (isset($profile[$caller['function']]['name'])) {
   			$profile[$caller['function']]['wait'] +=$wait_time;
		}else{
			if ($caller['function'] != 'do_profile' ) { 
				print "\nWARNING: function not found:" ; 
				print  $caller['function'] ;  
			} 
		}
	} 


	// trace all - all lines ?
	if($trace==1) { 

		// check line limit ?
		if ($count < $output_line_limit ) { 

		// check that function changes ?
		print "<!--"; 
		print " $count "; 
		print " $last_time "; 
		print  $frame['line'] ; 
		print " " ; 
		print  $frame['file'] ; 
		print " $function ";  
		print " $last_function ";  
		print " $stack_size "; 
		print " $last_stack_size "; 
		print  $profile[$caller['function']]['time'] ; 
		print " " ; 
		print  $profile[$caller['function']]['wait'] ; 
		print "-->\n"; 

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

    global $profile,$count,$func_aggregate_threshold;

	$fcount=0; 
	$prcount=0;

	echo '<h2>Slow Function Report</h2><table>' ; 

	echo "<tr><td>item<td>function number<td>name<td>time<td>wait<td>exec count<td>avg exec time</tr>"; 

	foreach($profile as $f) { 

		$fcount++; 

		if ( $f['time'] > $func_aggregate_threshold ) { 

			$prcount++; 
			print "<tr><td>$prcount<td>$fcount"; 
			foreach($f as $k => $v) { 
				echo "<td>$v"; 
			} 
			// avg exec time  
			$a = $f['time'] / $f['count'] ; 
			echo "<td>$a"; 
			// avg wait time  
			$a = $f['wait'] / $f['count'] ; 
			echo "<td>$a<tr>"; 
		} 
	} 

	echo "Number of unique functions executed :$count<br>"; 
	echo "Number of execs :$fcount<br>"; 
	echo "Number of functions with accumlated > 0.5 seconds :$prcount<br>"; 

}


function test_profile() {
    global $profile;
    print_r($profile);
}

function func_one() {
	print "TOP ONE(50000) "; 
    echo date('h:i:s') . "\n";

    sleep(2);
	$x=1; 
	$y=3; 
	$x=$x+$y; 
    func_two();
	print "BOTTOM ONE() "; 
    echo date('h:i:s') . "\n";
}

function func_two() {
    sleep(3);
	$x=1; 
	$y=3; 
	$x=$x+$y; 
    func_three();
}

function func_three() {
 print "TOP THREE() "; 
    echo date('h:i:s') . "\n";
    sleep(5);
	$x=1; 
	$y=3; 
	$x=$x+$y; 
	print "BOTTOM THREE()"; 
    echo date('h:i:s') . "\n";
}

function func_serial() { 

    usleep(20 * 1000);
	$x=1; 
	$y=3; 
	$x=$x+$y; 
func_one(); 
	$x=1; 
	$y=3; 
	$x=$x+$y; 
func_two(); 
	$x=1; 
	$y=3; 
	$x=$x+$y; 
func_three(); 
	$x=1; 
	$y=3; 
	$x=$x+$y; 
} 



function readable_bt($trace) { 

#print_r($trace) ; 

	$s=''; 

	foreach($trace as $call) {  	

print_r($call); 

		# assume first is do_profile 	
		if ($call['function'] != 'do_profile') 	

   			 $s=  (isset($call['file']) ? $call['file'] : ''); 
			 $s+= (isset($call['line']) ? $call['line'] : '');
			 $s+= (isset($call['function']) ? $call['function'] : '');
			 $s+= isset($call['class']) ? 'yes':'no';  
			 $s+= isset($call['object']) ? 'yes':'no';  
			print " $s <br>";
	} 


} 
?>

</body>
</html>

