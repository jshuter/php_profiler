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
declare(ticks=1);
register_tick_function('do_profile');


#-------------------------------------------
# test 1 
#-------------------------------------------

// microsecond delay 
$t=1000000;

func_serial() ;

show_profile();


#-------------------------------------------
# reset and start test 2 
#-------------------------------------------

$profile = array();

func_one();

show_profile(); 




#----------------------------------------

function func_one() {
global $t;
print "$t";
usleep($t);
func_two();
usleep($t);
}

function func_two() {
global $t;
print "$t";
usleep($t);
usleep($t);
func_three();
usleep($t);
}

function func_three() {
global $t;
print "$t";
usleep($t);
usleep($t);
}

function func_serial() {
global $t;
print "$t";
usleep($t);
usleep($t);
func_one();
usleep($t);
func_two();
usleep($t);
func_three();
}


?>

</body>
</html>

