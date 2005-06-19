<?php

// $Header: /cvsroot/bitweaver/_bit_search/admin/admin_search_inc.php,v 1.1 2005/06/19 05:04:25 bitweaver Exp $

// Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$formSearchToggles = array(
	'feature_search_fulltext' => array(
		'label' => 'Fulltext Search',
		'note' => 'Enable Fulltext Search of all content. This enables users to serach the content of wiki pages, articles, blogs and other similar content.',
//		'page' => 'FullTextSearch',
	),
	'feature_search_stats' => array(
		'label' => 'Search Statistics',
		'note' => 'Record searches made and their frequency.',
//		'page' => 'SearchStats',
	),
);
$formSearchInts = array(
	'search_refresh_rate' => array(
		'label' => 'Search Refresh Rate',
		'note' => 'not quite sure what this does',
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
		'note' => 'Define the Maximum age of cached serach results for any given syllable',
	),
	'search_lru_purge_rate' => array(
		'label' => 'Least Recently Used (LRU) list purging rate',
		'note' => '???',
	),
	'search_lru_length' => array(
		'label' => 'Least Recently Used (LRU) list length',
		'note' => '???',
	),
);
if (isset($_REQUEST["searchprefs"])) {
	
	foreach( $formSearchInts as $item => $data ) {
		simple_set_int( $item );
		$formSearchInts[$item]['value'] = $_REQUEST[$item];
	}

	foreach( $formSearchToggles as $item => $data ) {
		simple_set_toggle( $item );
	}
} else {
	foreach( $formSearchInts as $item => $data ) {
		$formSearchInts[$item]['value'] = $gBitSystem->mPrefs[$item];
	}
}
$smarty->assign( 'formSearchToggles',$formSearchToggles );
$smarty->assign( 'formSearchInts',$formSearchInts );


?>
