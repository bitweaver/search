<?php
/*
 * Command line version of site reindex.
 * Created withstub from jht001 (Thanks)
 * and help from KainX
 */

$_SERVER['SERVER_NAME'] = 'batch';
$_SERVER['HTTP_HOST'] = 'batch';
$_SERVER['HTTP_USER_AGENT'] = 'batch';
$_SERVER['SCRIPT_URL'] = 'batch';
$_SERVER['SERVER_SOFTWARE'] = 'batch';
$HTTP_SERVER_VARS['HTTP_USER_AGENT'] = 'batch';

//foreach (headers_list() as $hdr) { header($hdr); }
require_once( '../bit_setup_inc.php' );
require_once( LIBERTY_PKG_PATH.'LibertyBase.php');
require_once( SEARCH_PKG_PATH.'refresh_functions.php');

$time_start = microtime_float();
echo "\nBeginning Reindex ...\n";
rebuild_index('pages');
$time_end = microtime_float();
$time     = number_format($time_end - $time_start, 4);
echo "Index rebuild complete.\n";
echo "Execution time: $time seconds\n";
die();

function microtime_float() {
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}
?>
