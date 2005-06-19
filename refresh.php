<?php

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
			random_refresh_index_wiki();
		}
		if( $gBitSystem->isPackageActive( 'forums' ) ) {
			$locs[]="random_refresh_index_forum";
		}
		if( $gBitSystem->isPackageActive( 'trackers' ) ) {
			$locs[]="random_refresh_index_trackers";
			$locs[]="random_refresh_index_tracker_items";
		}
		if( $gBitSystem->isPackageActive( 'articles' ) ) {
			$locs[]="random_refresh_index_articles";
		}
		if( $gBitSystem->isPackageActive( 'blogs' ) ) {
			$locs[]="random_refresh_index_blogs";
			$locs[]="random_refresh_index_blog_posts";
		}
		if( $gBitSystem->isPackageActive( 'faqs' ) ) {
			$locs[]="random_refresh_index_faqs";
			$locs[]="random_refresh_index_faq_questions";
		}
		if( $gBitSystem->isPackageActive( 'directory' ) ) {
			$locs[]="random_refresh_index_dir_cats";
			$locs[]="random_refresh_index_dir_sites";
		}
		if( $gBitSystem->isPackageActive( 'imagegals' ) ) {
			$locs[]="random_refresh_imggals";
			$locs[]="random_refresh_img";
		}

		// comments can be everywhere?
		$locs[]="random_refresh_index_comments";
		// some refreshes to enhance the refreshing stats
		$locs[]="refresh_index_oldest";
		//print_r($locs);
		$location=$locs[rand(0,count($locs)-1)];
		// random refresh

		// hack around php database driver issues when a different database from bitweaver is accessed elsewhere during page  render
		// this happens in the phpBB package when phpBB is in a different db from bitweaver in MySQL
		global $gBitSystem, $gBitDbName;
		$gBitSystem->mDb->mDb->SelectDB( $gBitDbName );

		//echo "$location";
		call_user_func ($location);
	}
}

?>
