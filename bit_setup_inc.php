<?php
global $gBitSystem, $gLibertySystem ;

$gBitSystem->registerPackage( 'search', dirname( __FILE__).'/', TRUE, LIBERTY_SERVICE_SEARCH );

if( $gBitSystem->isPackageActive( 'search' ) ) {
	$gBitSystem->registerAppMenu( SEARCH_PKG_NAME, ucfirst( SEARCH_PKG_DIR ), SEARCH_PKG_URL.'index.php', '', 'search');

	include_once( SEARCH_PKG_PATH.'refresh_functions.php' );
	$gLibertySystem->registerService( LIBERTY_SERVICE_SEARCH, SEARCH_PKG_NAME, 
		array('content_store_function' => 'refresh_index'));
}

?>
