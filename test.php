<?php 
print "\n----\n";
print microtime() ; 
print "\n----\n";

print gmdate("H:i:s", microtime(true)) ;
list($usec, $sec) = explode(" ", microtime());
print "\n";
print gmdate("H:i:s", $sec) ;
print ".$usec";
print "\n preg matched :";
preg_match('/0(\.\d\d\d\d)/', $usec, $output);
print gmdate("H:i:s", $sec) ;
echo $output[1]; // 

print "\n----\n";
print microtime() ; 
print "\n---------------------\n";

list($usec, $sec) = explode(" ", microtime());
preg_match('/0(\.\d\d\d\d)/', $usec, $output);
print gmdate("H:i:s", $sec).$output[1]; 
print "\n";
return ; 

$profiler_start_time = time(); 
$profiler_start_utime = microtime(true); 
sleep (2); 

echo "\nStart time :" . $profiler_start_time;  
echo "\nStart utime :" . $profiler_start_utime;  

echo "\n - End time :" . microtime()  ;
$t=time(); 
$x=microtime(true); 
echo "\nElapsed time (s):" . ($t  - $profiler_start_time)  ;
echo "\nElapsed utime (s):" . ($x  - $profiler_start_utime)  ;

//print_r(get_defined_vars()) ; 


print "\n"; 

print time(); 
print "\n"; 
print microtime() ; 
print "\n"; 
print "\n"; 

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

$time_start = microtime_float();

// Sleep for a while - 1.5 seconds
usleep(1500000);

$time_end = microtime_float();
$time = $time_end - $time_start;

echo "Did nothing in $time seconds\n";




