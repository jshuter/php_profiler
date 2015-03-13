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

#body { visibility: hidden } 

div.profiler {
	visibility: visible; 
} 

</style>




<?php

require 'prf_lib.php'; 

#start_profile() ; 
 declare(ticks=1);
 register_tick_function('do_profile');


#-------------------------------------------
# test 1 
#-------------------------------------------

print "BEFORE SERIAL()\n";

func_serial() ;

print "AFTER SERIAL()\n";

show_profile();


#-------------------------------------------
# reset and start test 2 
#-------------------------------------------

$profile = array();

print "BEFORE ONE()\n";

func_one();

print "AFTER ONE()\n";

#-------------------------------------------

show_profile(); 



function func_one() {
print "1"; 
sleep(1);
$x=1; $y=3; $x=$x+$y;
print "2"; 
func_two();
print "3"; 
}

function func_two() {
sleep(1);
$x=1; $y=3; $x=$x+$y;
func_three();
}

function func_three() {
sleep(1);
$x=1; $y=3; $x=$x+$y;
}

function func_serial() {
usleep(1);
$x=1; $y=3; $x=$x+$y;
func_one();
$x=1; $y=3; $x=$x+$y;
func_two();
$x=1; $y=3; $x=$x+$y;
func_three();
$x=1; $y=3; $x=$x+$y;
}


?>

</body>
</html>

