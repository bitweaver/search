<?php
/**
 * Command Line Site Reindex
 * Created with stub from jht001 and help from KainX (Thanks to you both)
 * 
 * This script is designed to be called from the command line to allow you
 * to reindex all the liberty content on your site.
 * 
 * cmd_line_reindex takes up to three optional arguments
 * Argument 1 - ContentType
 * 		This is the type of content you wish to reindex using the content type guids
 * 		"pages" will attempt to reindex all content
 * 			Content Type Guids allowed (so far):
 * 			bitarticle, bitblogpost, bitcomment, bitpage, bituser, fisheyegallery, fisheyeimage
 * 
 * Argument 2 - Silent
 * 		Silent = no messages displayed to the console
 * 
 * Argument 3 - UnindexedOnly
 * 		UnindexedOnly = Only index content that isn't already in the index. This function
 * 			is useful for sites that import data from other sites. 
 * 			Note: This function employs sub-selects in the SQL. This will break
 * 				  MySQL 3.x - however works fine on MySQL 4.x, Postgres, Firebird and MSSQL.
 * 
 * Examples:
 * 
 *		php cmd_line_reindex			// reindexes all content on your site with messages
 *		php cmd_line_reindex pages silent unindexedonly // Indexes entire site, no messages - and only content not in the index yet
 *		php cmd_line_reindex bitarticle unindexedonly // Indexes only articles that haven't been indexed yet
 *
 * I have run the "unindexedonly" option several times in a row and was told it attempted to 
 * reindex 20 pieces of content each time. 
 *
 */

// Define Server Variables so script won't puke on command line
$_SERVER['SERVER_NAME']     = 'batch';
$_SERVER['HTTP_HOST']       = 'batch';
$_SERVER['HTTP_USER_AGENT'] = 'batch';
$_SERVER['SCRIPT_URL']      = 'batch';
$_SERVER['SERVER_SOFTWARE'] = 'batch';
$HTTP_SERVER_VARS['HTTP_USER_AGENT'] = 'batch';

require_once( '../bit_setup_inc.php' );
require_once( LIBERTY_PKG_PATH.'LibertyBase.php');
require_once( SEARCH_PKG_PATH.'refresh_functions.php');

$whatToIndex   = "pages";
$unindexedOnly = false;
$silent        = false;
if ($argc > 1) {
	for ($i = 1; $i < $argc; $i++) {
		$arg = strtolower($argv[$i]);
		switch ($arg) {
			case "silent" :
				$silent = true;
				break;
			case "unindexedonly" :
				$unindexedOnly = true; // only index content that hasn't been indexed yet
				break;
			default :
				$whatToIndex = $arg;
				break;
		}
	}
}

$time_start = microtime_float();
if (!$silent) echo "\nBeginning Reindex of $whatToIndex ...\n";
if (!$silent && $unindexedOnly) echo "Warning: unindexed only flag set. Will break MySQL 3.x because of sub-selects\n";
$count    = rebuild_index($whatToIndex, $unindexedOnly);
$time_end = microtime_float();
$time     = number_format($time_end - $time_start, 4);
if (!$silent) echo "Index rebuild complete.\n";
if (!$silent) echo "Attempted to index $count pieces of content\n";
if (!$silent) echo "(Note: Some content may not be indexable. This is normal)\n";
if (!$silent) echo "Execution time: $time seconds\n";
die();

function microtime_float() {
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}
?>
