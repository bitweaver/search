<?php
global $gBitSystem, $gLibertySystem ;

$registerHash = array(
	'package_name' => 'search',
	'package_path' => dirname( __FILE__ ).'/',
	'service' => LIBERTY_SERVICE_SEARCH,
);
$gBitSystem->registerPackage( $registerHash );

if( $gBitSystem->isPackageActive( 'search' ) ) {
	$menuHash = array(
		'package_name'  => SEARCH_PKG_NAME,
		'index_url'     => SEARCH_PKG_URL.'index.php',
	);
	$gBitSystem->registerAppMenu( $menuHash );

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
