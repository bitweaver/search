<?php
global $gBitSystem, $gLibertySystem ;

$gBitSystem->registerPackage( 'search', dirname( __FILE__).'/', TRUE, LIBERTY_SERVICE_SEARCH );

if( $gBitSystem->isPackageActive( 'search' ) ) {
	$gBitSystem->registerAppMenu( SEARCH_PKG_NAME, ucfirst( SEARCH_PKG_DIR ), SEARCH_PKG_URL.'index.php', '', 'search');

	// **********  SEARCH  ************
	// Register the search refresh function
	// But only if the Index On Submit isn't set
	if( ! $gBitSystem->isFeatureActive("search_index_on_submit") ) {
		include_once( SEARCH_PKG_PATH.'refresh.php' );
		register_shutdown_function("refresh_search_index");
	}
	include_once( SEARCH_PKG_PATH.'refresh_functions.php' );
	$gLibertySystem->registerService( LIBERTY_SERVICE_SEARCH, SEARCH_PKG_NAME, 
		array('content_store_function' => 'refresh_index'));
}

?>
