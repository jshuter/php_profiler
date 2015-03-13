<?php 

print '<html><body>PROFILE FOR PID<hr>...<pre>'; 

if (isset($_GET['PID'])) { 

    echo file_get_contents( "/tmp/prtest_log_" . $_GET['PID'] . ".html" );	

} 

print '</pre></body></html>'; 

 



