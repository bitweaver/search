<?php

// $Header: /cvsroot/bitweaver/_bit_search/index.php,v 1.20 2006/12/31 11:29:56 squareing Exp $

// Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// Initialization
require_once( '../bit_setup_inc.php' );

require_once( SEARCH_PKG_PATH.'/search_lib.php');
// note: lib/search/searchlib.php is new. the old one was lib/searchlib.php

$searchlib = &new SearchLib();

$gBitSystem->verifyPackage( 'search' );

// contentType list created in mod_package_search.php at present
// but this is left in case a different search option is used
if( empty( $contentTypes ) ) {
	$contentTypes = array( '' => tra( 'All Content' ) );
	foreach( $gLibertySystem->mContentTypes as $cType ) {
		$contentTypes[$cType['content_type_guid']] = tra( $cType['content_description'] );
	}
}
$gBitSmarty->assign( 'contentTypes', $contentTypes );

if( !empty($_REQUEST["highlight"]) ) {
  $_REQUEST["words"]=$_REQUEST["highlight"];
} else {
	// a nice big, groovy search will be cool to have one day...
	$gBitSystem->display( 'bitpackage:search/search.tpl', 'Search');
	die;
}

if ($gBitSystem->isFeatureActive("stats_search")) {
	$searchlib->register_search(isset($_REQUEST["words"]) ? $_REQUEST["words"] : '');
}

$content_type_guid = '';
if (isset($_REQUEST["content_type_guid"])) {
	$content_type_guid = $_REQUEST["content_type_guid"];
}

LibertyContent::prepGetList($_REQUEST);

if( isset( $_REQUEST['usePart'] ) && $_REQUEST['usePart']=='on' ) {
	$_REQUEST['usePart']=true;
} else {
	$_REQUEST['usePart']=false;
}
$gBitSmarty->assign('searchType', $_REQUEST['usePart'] ? "Using Partial Word Search" : "Using Exact Word Search");

// Build the query using words
if ((!isset($_REQUEST["words"])) || (empty($_REQUEST["words"]))) {
	$words = '';
} else {
	$words   = strip_tags($_REQUEST["words"]);
}
$gBitSmarty->assign('words', $words);
$results = $searchlib->find($content_type_guid, $words, $_REQUEST['offset'], $_REQUEST['max_records'], $_REQUEST["usePart"]);
$cant    = $results['cant']; 

switch ($content_type_guid) {
	case "bitarticle"  : $where2 = "Article";	break;
	case "bitpage"     : $where2 = "Wiki Page";	break;
	case "bitblogpost" : $where2 = "Blog Post";	break;
	case "bitcomment"  : $where2 = "Comment";	break;
	default            : $where2 = "Page";		break;
}

if ($cant <> 1) $where2 .= "s"; 
$gBitSmarty->assign('content_type_guid', $content_type_guid);
$gBitSmarty->assign('where2', tra($where2));

$stubContent = new LibertyContent();
if ( $cant > 0 ) {
	foreach( array_keys( $results['data'] ) as $k ) {
		if( empty( $results['data'][$k]['title'] ) ) {
			$date_format = $gBitSystem->get_long_date_format();
			if( $gBitSystem->mServerTimestamp->get_display_offset() ) {
				$date_format = preg_replace( "/ ?%Z/", "", $date_format );
			} else {
				$date_format = preg_replace( "/%Z/", "UTC", $date_format );
			}
			$date_string = $gBitSystem->mServerTimestamp->getDisplayDateFromUTC( $results['data'][$k]['created'] );
			$results['data'][$k]['title'] = $gBitSystem->mServerTimestamp->strftime( $date_format, $date_string, true );
		}
		if( !empty( $results['data'][$k]['data'] ) ) {
			$results['data'][$k]['parsed'] = $stubContent->parseData( $results['data'][$k] );
		}
	}
}
// calculate page number
$numPages = ceil( $cant / $gBitSystem->getConfig( 'max_records' ) );
$gBitSmarty->assign( 'numPages', $numPages );

$_REQUEST['cant'] = $cant;
$_REQUEST['control']['parameters']['highlight'] = $_REQUEST["highlight"];
$_REQUEST['control']['parameters']['content_type_guid'] = $content_type_guid;
LibertyContent::postGetList( $_REQUEST );
$gBitSmarty->assign_by_ref( 'listInfo', $_REQUEST["control"] );
$gBitSmarty->assign('cant_results', $cant);

$partialOnOff = $_REQUEST["usePart"] ? 'checked' : '';
$gBitSmarty->assign('partialOnOff', $partialOnOff);

// Find search results (build array)
$gBitSmarty->assign_by_ref('results', $results["data"]);

// Display the template
$gBitSystem->display( 'bitpackage:search/search.tpl', 'Search Results for: '.strip_tags($_REQUEST["highlight"]));

?>
