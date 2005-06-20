<?php

// $Header: /cvsroot/bitweaver/_bit_search/index.php,v 1.2 2005/06/20 14:30:44 lsces Exp $

// Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// Initialization
require_once( '../bit_setup_inc.php' );

require_once( SEARCH_PKG_PATH.'/search_lib.php');
// note: lib/search/searchlib.php is new. the old one was lib/searchlib.php

$searchlib = &new SearchLib();

$gBitSystem->verifyPackage( 'search' );

if( !empty($_REQUEST["highlight"]) ) {
  $_REQUEST["words"]=$_REQUEST["highlight"];
} else {
	// a nice big, groovy search will be cool to have one day...
	$gBitSystem->display( 'bitpackage:search/search.tpl');
	die;
}

if ($feature_search_stats == 'y') {
	$searchlib->register_search(isset($_REQUEST["words"]) ? $_REQUEST["words"] : '');
}

if (!isset($_REQUEST["where"])) {
	$where = 'pages';
} else {
	$where = $_REQUEST["where"];
}

$smarty->assign('where',$where);
$smarty->assign('where2',tra($where));

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

// Already assigned above! $smarty->assign('where',$where);
if (!isset($_REQUEST["offset"])) {
	$offset = 0;
} else {
	$offset = $_REQUEST["offset"];
}
if (isset($_REQUEST['page'])) {
	$page = &$_REQUEST['page'];
	$offset = ($page - 1) * $maxRecords;
}
$smarty->assign_by_ref('offset', $offset);

$fulltext = $feature_search_fulltext == 'y';

// Build the query using words
if ((!isset($_REQUEST["words"])) || (empty($_REQUEST["words"]))) {
	$results = $searchlib->find($where,' ', $offset, $maxRecords, $fulltext);

	$smarty->assign('words', '');
} else {
	$words = strip_tags($_REQUEST["words"]);
	$results = $searchlib->find($where,$words, $offset, $maxRecords, $fulltext);

	$smarty->assign('words', $words);
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

if ( $results['cant'] > 0 ) {
	foreach( array_keys( $results['data'] ) as $k ) {
		if( !empty( $results['data'][$k]['data'] ) ) {
			$results['data'][$k]['parsed'] = $stubContent->parseData( $results['data'][$k]['data'], $results['data'][$k]['format_guid'] );
		}
	}
}

$cant_pages = ceil($results["cant"] / $maxRecords);
$smarty->assign('cant_results', $results["cant"]);
$smarty->assign_by_ref('cant_pages', $cant_pages);
$smarty->assign('actual_page', 1 + ($offset / $maxRecords));

if ($results["cant"] > ($offset + $maxRecords)) {
	$smarty->assign('next_offset', $offset + $maxRecords);
} else {
	$smarty->assign('next_offset', -1);
}

// If offset is > 0 then prev_offset
if ($offset > 0) {
	$smarty->assign('prev_offset', $offset - $maxRecords);
} else {
	$smarty->assign('prev_offset', -1);
}

// Find search results (build array)
$smarty->assign_by_ref('results', $results["data"]);

// Display the template
$gBitSystem->display( 'bitpackage:search/search.tpl');

?>
