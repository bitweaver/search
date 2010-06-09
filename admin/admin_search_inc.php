<?php

// $Header$

// Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
// All Rights Reserved. See below for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details.

$feedback = array();

$formSearchToggles = array(
	'search_stats' => array(
		'label' => 'Search Statistics',
		'note' => 'Record searches made and their frequency.',
//		'page' => 'SearchStats',
	),
	'search_index_on_submit' => array(
		'label' => 'Index On Submit',
		'note' => 'Index articles, blogs and wiki pages immdiately on submission. If unchecked, pages will be updated randomly according the the refresh rate below.',
	),
);

$formSearchInts = array(
	'search_refresh_rate' => array(
		'label' => 'Search Refresh Rate',
		'note' => 'Varies the rate at which updates to the search index are made, 1 = every page read, while rate>1 will introduce a random chance of a refresh every "rate" pages',
	),
	'search_min_wordlength' => array(
		'label' => 'Minimum number of letters for search words',
		'note' => 'By settings this value to 3, you can ignore search words such as "a" or "or", however searches for a number like "13" will be ignored as well.',
	),
	'search_max_syllwords' => array(
		'label' => 'Maximum number of words',
		'note' => 'The maximum number of words containing a syllable that can be serached for in any one search.',
	),
	'search_syll_age' => array(
		'label' => 'Age in hours of search cache',
		'note' => 'Define the Maximum age of cached search results for any given syllable. The results cache will be used to provide a search result if it is available, and will be cleared after either the age, or when the results cache reaches it\'s limit',
	),
	'search_lru_purge_rate' => array(
		'label' => 'Least Recently Used (LRU) list purging rate',
		'note' => 'Purge the results cache every "rate" pages. This will keep space available in the cache for new search results',
	),
	'search_lru_length' => array(
		'label' => 'Least Recently Used (LRU) list length',
		'note' => 'Limit the results cache to this number of entries',
	),
);

if( !empty( $_REQUEST['del_index'] ) ) {
	require_once( SEARCH_PKG_PATH.'/refresh_functions.php' );
	delete_index_content_type( $_REQUEST["where"] );
	$feedback['success'] = tra( "The search index was successfully deleted." );
}

if( !empty( $_REQUEST['del_index_reindex'] ) ) {
	require_once( SEARCH_PKG_PATH.'/refresh_functions.php' );
	$count = rebuild_index( $_REQUEST["where"] );
	$feedback['success'] = tra( "The search index was successfully deleted." ).tra( "Number of items re-indexed" ).": ".$count;
}

if( !empty( $_REQUEST['del_searchwords'] ) ) {
	require_once( SEARCH_PKG_PATH.'/refresh_functions.php' );
	delete_search_words_and_syllables();
	$feedback['success'] = tra( "The searchwords were successfully purged from the database." );
}

if( !empty( $_REQUEST['store_prefs'] ) ) {
	foreach( $formSearchInts as $item => $data ) {
		simple_set_int( $item, SEARCH_PKG_NAME );
		$formSearchInts[$item]['value'] = $_REQUEST[$item];
	}
	foreach( $formSearchToggles as $item => $data ) {
		simple_set_toggle( $item, SEARCH_PKG_NAME );
	}
}

foreach( $formSearchInts as $item => $data ) {
	$formSearchInts[$item]['value'] = $gBitSystem->getConfig( $item );
}
$gBitSmarty->assign( 'formSearchToggles', $formSearchToggles );
$gBitSmarty->assign( 'formSearchInts', $formSearchInts );
$gBitSmarty->assign( 'feedback', $feedback );

$formSearchTypeToggles = array(
	'search_restrict_types' => array(
		'label' => 'Restrict Types',
		'note' => 'If selected the search will be limited to those selected below.'
	),
);
$gBitSmarty->assign( 'formSearchTypeToggles', $formSearchTypeToggles );

// allow selection of what packages can have search
foreach( $gLibertySystem->mContentTypes as $cType ) {
	$formSearchable['guids']['search_pkg_'.$cType['content_type_guid']]  = $gLibertySystem->getContentTypeName( $cType['content_type_guid'] );
}

if( !empty( $_REQUEST['store_content'] ) ) {
	foreach( $formSearchTypeToggles as $item => $data ) {
		simple_set_toggle( $item, SEARCH_PKG_NAME );
	}
	foreach( array_keys( $formSearchable['guids'] ) as $searchable ) {
		$gBitSystem->storeConfig( $searchable, ( ( !empty( $_REQUEST['searchable_content'] ) && in_array( $searchable, $_REQUEST['searchable_content'] ) ) ? 'y' : NULL ), SEARCH_PKG_NAME );
	}

}

// check the correct packages in the package selection
foreach( $gLibertySystem->mContentTypes as $cType ) {
	if( $gBitSystem->getConfig( 'search_pkg_'.$cType['content_type_guid'] ) ) {
		$formSearchable['checked'][] = 'search_pkg_'.$cType['content_type_guid'];
	}
}
$gBitSmarty->assign( 'formSearchable', $formSearchable );

/* usually done in mod_package_search.php - but the module can be not here the first time */
if( empty( $contentTypes ) ) {
	$contentTypes = array( '' => tra( 'All Content' ) );
	foreach( $gLibertySystem->mContentTypes as $cType ) {
		if( $gBitSystem->getConfig( 'search_pkg_'.$cType['content_type_guid']) ) {
			$contentTypes[$cType['content_type_guid']] = $gLibertySystem->getContentTypeName( $cType['content_type_guid'] );
		}
	}
	$gBitSmarty->assign( 'contentTypes', $contentTypes );
}
?>
