<?php

// $Header: /cvsroot/bitweaver/_bit_search/index.php,v 1.29 2010/04/17 15:36:08 wjames5 Exp $

// Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
// All Rights Reserved. See below for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details.

// Initialization
require_once( '../kernel/setup_inc.php' );

require_once( SEARCH_PKG_PATH.'/search_lib.php');

$searchlib = &new SearchLib();

$gBitSystem->verifyPackage( 'search' );

// contentType list created in mod_package_search.php at present
// but this is left in case a different search option is used
if( empty( $contentTypes ) ) {
	$contentTypes = array( '' => tra( 'All Content' ) );
	foreach( $gLibertySystem->mContentTypes as $cType ) {
		$contentTypes[$cType['content_type_guid']] = $gLibertySystem->getContentTypeName( $cType['content_type_guid'] );
	}
}
asort($contentTypes);
$gBitSmarty->assign( 'contentTypes', $contentTypes );

if( !empty($_REQUEST["highlight"]) ) {
  $_REQUEST["words"]=$_REQUEST["highlight"];
} else {
	// a nice big, groovy search will be cool to have one day...
	$gBitSystem->display( 'bitpackage:search/search.tpl', 'Search', array( 'display_mode' => 'display' ));
	die;
}

if ($gBitSystem->isFeatureActive("search_stats")) {
	$searchlib->register_search(isset($_REQUEST["words"]) ? $_REQUEST["words"] : '');
}

if (!isset($_REQUEST["content_type_guid"])) {
	$_REQUEST["content_type_guid"] = '';
	$where2 = "Page";
} else {
	$where2 = $contentTypes[$_REQUEST["content_type_guid"]];
}

LibertyContent::prepGetList($_REQUEST);

if( isset( $_REQUEST['usePart'] ) && $_REQUEST['usePart']=='on' ) {
	$_REQUEST['usePart']=true;
} else {
	$_REQUEST['usePart']=false;
}
$gBitSmarty->assign('usePart', $_REQUEST['usePart']);
$gBitSmarty->assign('searchType', $_REQUEST['usePart'] ? "Using Partial Word Search" : "Using Exact Word Search");

if( isset( $_REQUEST['useAnd'] ) && $_REQUEST['useAnd']=='on' ) {
	$_REQUEST['useAnd']=true;
} else {
	$_REQUEST['useAnd']=false;
}
$gBitSmarty->assign('useAnd', $_REQUEST['useAnd']);

// Build the query using words
if ((!isset($_REQUEST["words"])) || (empty($_REQUEST["words"]))) {
	$_REQUEST["words"] = '';
} else {
	$_REQUEST["words"]   = strip_tags($_REQUEST["words"]);
}
$gBitSmarty->assign('words', $_REQUEST["words"]);
$results = $searchlib->find( $_REQUEST );

if ($_REQUEST['cant'] <> 1) $where2 .= "s"; 
$gBitSmarty->assign('where2', tra($where2));
$gBitSmarty->assign('content_type_guid', $_REQUEST["content_type_guid"]);

$stubContent = new LibertyContent();
if ( $_REQUEST['cant'] > 0 ) {
	foreach( array_keys( $results ) as $k ) {
		if( empty( $results[$k]['title'] ) ) {
			$date_format = $gBitSystem->get_long_date_format();
			if( $gBitSystem->mServerTimestamp->get_display_offset() ) {
				$date_format = preg_replace( "/ ?%Z/", "", $date_format );
			} else {
				$date_format = preg_replace( "/%Z/", "UTC", $date_format );
			}
			$date_string = $gBitSystem->mServerTimestamp->getDisplayDateFromUTC( $results[$k]['created'] );
			$results[$k]['title'] = $gBitSystem->mServerTimestamp->strftime( $date_format, $date_string, true );
		}
		if( !empty( $results[$k]['data'] ) ) {
			$results[$k]['parsed'] = $stubContent->parseData( $results[$k] );
		}
	}
}
LibertyContent::postGetList( $_REQUEST );
$gBitSmarty->assign_by_ref( 'listInfo', $_REQUEST['listInfo'] );

// Find search results (build array)
$gBitSmarty->assign_by_ref('results', $results);

// Display the template
$gBitSystem->display( 'bitpackage:search/search.tpl', 'Search Results for: '.strip_tags($_REQUEST["highlight"]), array( 'display_mode' => 'display' ));

?>
