<?php
/**
 * $Header$
 *
 * @copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * $Id$
 * @author  Luis Argerich (lrargerich@yahoo.com)
 * @package search
 * @subpackage functions
 */

/**
 * refresh_search_index
 */
function refresh_search_index() {
	global $gBitSystem;
	// first write close the session. refreshing can take a huge amount of time
	session_write_close();

	// check if we have to run. Run every n-th click:
	global $search_refresh_rate, $gBitSystem;

	//$search_refresh_rate=1; //debug
	list($usec, $sec) = explode(" ",microtime());
	srand (ceil($sec+100*$usec));
	if(rand(1,$search_refresh_rate)==1) {
		require_once('refresh_functions.php');
		// get a random location
		$locs=array();
		if( $gBitSystem->isPackageActive( 'wiki' ) ) {
			// if wiki is active, let's always refresh
			random_refresh_index("wiki");
		}
		if( $gBitSystem->isPackageActive( 'articles' ) ) {
			$locs[''] = ARTICLES_PKG_NAME;
		}
		if( $gBitSystem->isPackageActive( 'blogs' ) ) {
			// if blogs is active, let's always refresh
			random_refresh_index("blogs");
			$locs['random_refresh_index']="blog_posts";
		}

		// comments can be everywhere?
		$locs['random_refresh_index'] = "comments";
		// some refreshes to enhance the refreshing stats
		$locs['refresh_index_oldest'] = "";
		$key = array_rand( $locs );
		// random refresh

		// hack around php database driver issues when a different database from bitweaver is accessed elsewhere during page  render
		// this happens in the phpBB package when phpBB is in a different db from bitweaver in MySQL
		// This only works on some databases
		global $gBitSystem, $gBitDbName;
		$gBitSystem->mDb->mDb->SelectDB( $gBitDbName );

		if ( !empty ($key) )
			call_user_func( $key, $locs[$key] );
	}
}

?>
