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




// TEST NEW PROFILER 

declare(ticks=1);
register_tick_function('do_profile');
$profile = array();
$last_time = microtime(true);


/**
 * Root directory of Drupal installation.
 */

define('DRUPAL_ROOT', getcwd());

require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
menu_execute_active_handler();


//Kint::dump( $_SERVER );

//Kint::trace() ; 


//
echo "<pre>"; 
show_profile();
echo "</pre>"; 


function do_profile() {
    global $profile, $last_time;
    $bt = debug_backtrace();
    if (count($bt) <= 1) {
        return ;
    }
    $frame = $bt[1];
    unset($bt);
    $function = $frame['function'];
    if (!isset($profile[$function])) {
        $profile[$function] = 0;
    }
    $profile[$function] += (microtime(true) - $last_time);
    $last_time = microtime(true);
}

function show_profile() {
    global $profile;
    print_r($profile);
}

