<?php
global $gBitSystem;
$gBitSystem->registerPackage( 'search', dirname( __FILE__).'/' );

if( $gBitSystem->isPackageActive( 'search' ) ) {
	$gBitSystem->registerAppMenu( SEARCH_PKG_DIR, 'Search', SEARCH_PKG_URL.'index.php', '', 'search');

	// Stuff found in kernel that is wiki dependent - wolff_borg
	// **********  SEARCH  ************
	// Register the search refresh function
	include_once( SEARCH_PKG_PATH.'refresh.php' );
	register_shutdown_function("refresh_search_index");
}

?>
