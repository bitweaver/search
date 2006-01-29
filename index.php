<?php

// $Header: /cvsroot/bitweaver/_bit_search/index.php,v 1.2.2.3 2006/01/29 07:38:24 seannerd Exp $

// Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// Initialization
require_once( '../bit_setup_inc.php' );
require_once( SEARCH_PKG_PATH.'/search_lib.php');
// note: lib/search/searchlib.php is new. the old one was lib/searchlib.php

$searchlib = &new SearchLib();

$gBitSystem->verifyPackage( 'search' );

$contentTypes 		 = array("bitarticle", "bitpage",    "bitblogpost", "bitcomment");
$contentDescriptions = array("Articles",   "Wiki Pages", "Blog Posts",  "Comments");
$gBitSmarty->assign( 'contentTypes', $contentTypes );
$gBitSmarty->assign( 'contentDescriptions', $contentDescriptions );

if( !empty($_REQUEST["highlight"]) ) {
  $_REQUEST["words"]=$_REQUEST["highlight"];
} else {
	// a nice big, groovy search will be cool to have one day...
	$gBitSystem->display( 'bitpackage:search/search.tpl');
	die;
}

if ($gBitSystem->isFeatureActive("feature_search_stats")) {
	$searchlib->register_search(isset($_REQUEST["words"]) ? $_REQUEST["words"] : '');
}

$where = 'pages';
if (isset($_REQUEST["where"])) {
	$where = $_REQUEST["where"];
}

/*
 * Seannerd poses question:
 * Not sure why we are checking the permissions - I get why you want to, 
 * but we don't check when you have "entire site" selected - so why does
 * it matter about the individual pages? Security needs to be fixed 
 * on the entire site search too. Commenting out his section for now.
 */

/*
if($where=='wikis') {
	$gBitSystem->verifyPackage( 'wiki' );
	$gBitSystem->verifyPermission( 'bit_p_view' );
}

if($where=='directory') {
	$gBitSystem->verifyPackage( 'directory' );
	$gBitSystem->verifyPermission( 'bit_p_view_directory' );
}

if($where=='faqs') {
	$gBitSystem->verifyPackage( 'faqs' );
	$gBitSystem->verifyPermission( 'bit_p_view_faqs' );
}

if($where=='forums') {
	$gBitSystem->verifyPackage( 'forums' );
	$gBitSystem->verifyPermission( 'bit_p_forum_read' );
}

if($where=='files') {
	$gBitSystem->verifyPackage( 'files' );
	$gBitSystem->verifyPermission( 'bit_p_view_file_gallery' );
}

if($where=='articles') {
	$gBitSystem->verifyPackage( 'articles' );
	$gBitSystem->verifyPermission( 'bit_p_read_article' );
}

if (($where=='galleries' || $where=='images')) {
	$gBitSystem->verifyPackage( 'image_gals' );
	$gBitSystem->verifyPermission( 'bit_p_view_image_gallery' );
}

if(($where=='blogs' || $where=='posts')) {
	$gBitSystem->verifyPackage( 'blogs' );
	$gBitSystem->verifyPermission( 'bit_p_read_blog' );
}

if(($where=='trackers')) {
	$gBitSystem->verifyPackage( 'trackers' );
	$gBitSystem->verifyPermission( 'bit_p_view_trackers' );
}
*/

$offset = 0;
if (isset($_REQUEST["offset"])) {
	$offset = $_REQUEST["offset"];
}
if (isset($_REQUEST['page'])) {
	$page = &$_REQUEST['page'];
	$offset = ($page - 1) * $maxRecords;
}
$gBitSmarty->assign_by_ref('offset', $offset);

$fulltext = $gBitSystem->isFeatureActive("feature_search_fulltext");

// Build the query using words
if ((!isset($_REQUEST["words"])) || (empty($_REQUEST["words"]))) {
	$results = $searchlib->find($where,' ', $offset, $maxRecords, $fulltext);
	$gBitSmarty->assign('words', '');
} else {
	$words   = strip_tags($_REQUEST["words"]);
	$results = $searchlib->find($where,$words, $offset, $maxRecords, $fulltext);
	$gBitSmarty->assign('words', $words);
}

//if ($fulltext == 'y') {
//	$CurrentIndex = -1;
//	$CurrentData = NULL;
//	foreach ($results["data"] as $current) {
//		if ($current["relevance"] > 0) {
//			$CurrentData[++$CurrentIndex] = $current;
//		}
//	}
//	$results['data'] = $CurrentData;
//	$results['cant'] = $CurrentIndex + 1;
//}

$stubContent = new LibertyContent();
$cant        = $results['cant']; 

if ( $cant > 0 ) {
	foreach( array_keys( $results['data'] ) as $k ) {
		if( !empty( $results['data'][$k]['data'] ) ) {
			$results['data'][$k]['parsed'] = $stubContent->parseData( $results['data'][$k]['data'], $results['data'][$k]['format_guid'] );
		}
	}
}

$cant_pages = ceil($cant / $maxRecords);
$gBitSmarty->assign('cant_results', $cant);
$gBitSmarty->assign('actual_page', 1 + ($offset / $maxRecords));
$gBitSmarty->assign_by_ref('cant_pages', $cant_pages);

switch ($where) {
	case "bitarticle"  : $where2 = "Article";	break;
	case "bitpage"     : $where2 = "Wiki Page";	break;
	case "bitblogpost" : $where2 = "Blog Post";	break;
	case "bitcommant"  : $where2 = "Comment";	break;
	default            : $where2 = "Page";		break;
}
if ($cant <> 1) $where2 .= "s"; 
$gBitSmarty->assign('where', $where);
$gBitSmarty->assign('where2', tra($where2));

if ($cant > ($offset + $maxRecords)) {
	$gBitSmarty->assign('next_offset', $offset + $maxRecords);
} else {
	$gBitSmarty->assign('next_offset', -1);
}

// If offset is > 0 then prev_offset
if ($offset > 0) {
	$gBitSmarty->assign('prev_offset', $offset - $maxRecords);
} else {
	$gBitSmarty->assign('prev_offset', -1);
}

// Find search results (build array)
$gBitSmarty->assign_by_ref('results', $results["data"]);

// Display the template
$gBitSystem->display( 'bitpackage:search/search.tpl');

?>
