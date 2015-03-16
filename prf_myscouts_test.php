<?php

require 'prf_lib.php'; 

#start_profile() ; 

declare(ticks=1);
register_tick_function('do_profile');


//################################################################
define('DRUPAL_ROOT', getcwd());


require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
menu_execute_active_handler();
//################################################################

show_profile(); 

?>

