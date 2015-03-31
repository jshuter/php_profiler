<?php

#$prf->off(); 
#$prf->show_profile() ; 

$prf = new Profiler() ; 

func_serial(); 

print_r( debug_backtrace() ); 

return ; 

/* 
 * Profiler for trace and performance monitoring 
 */

class Profiler { 

	private $last_stack_size = 0;
	private $level = 0; 
	private $count = 0; 
	private $threshold_trace = 0.0; 
	private $last_time = 0;
	private $profile = array();

	public $trace = 1 ; //  0/1
	public $debug = 1 ; 
	public $threshold_profile = 0.0; 
	public $output_line_limit = 9999; // 


	function __construct() { 
		date_default_timezone_set('UTC'); 
		//ini_set('apc.enabled',0); 
		declare(ticks=1);
		register_tick_function(array(&$this, 'tick_tracer')) ;
	}


	function debugging() { 
		return ($this->debug == 1) ; 
	} 

	public function tick_tracer() { 
			$bt=debug_backtrace(); 
			if (count($bt) > 0) { 
				print $bt[0]['function'] ; 
				print "\n";  
			} 
	}


	public function show_profile() {

	// turn of profiler for reporting 
	// declare(ticks=0); 

		// print out report of aggregated stack info and timming 

	    /* global $profile,$count,$threshold_profile; */ 

		$fcount=0; 
		$prcount=0;

		echo "<div class='profiler'>"; 

		echo '<h2>Slow Function Report</h2><table>' ; 

		echo "<tr><td>item<td>function number<td>name<td>time<td>wait<td>exec count<td>avg exec time</tr>"; 

		foreach($p as $f) { 

			$fcount++; 

			if ( $f['time'] > $threshold_profile ) { 

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

		echo "Number of unique functions executed :$fcount<br>"; 
		echo "Number of execs :$count<br>"; 
		echo "Number of functions with accumlated > $threshold_profile millmilliiseconds :$prcount<br>"; 

		echo '</div>'; 
	}

	public function dump_profile() {
   	 print_r($this->profile);
	}

}



function func_one() {

	print "TOP ONE(50000) "; 
    echo date('h:i:s') . "\n";

    sleep(1);
	$x=1; 
	$y=3; 
	$x=$x+$y; 
    func_two();
	print "BOTTOM ONE() "; 
    echo date('h:i:s') . "\n";
}

function func_two() {
    sleep(1);
	$x=1; 
	$y=3; 
	$x=$x+$y; 
    func_three();
}

function func_three() {
 print "TOP THREE() "; 
    echo date('h:i:s') . "\n";
    sleep(1);
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



