<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/stats.php,v 1.1.1.1.2.2 2005/07/26 15:50:26 drewslater Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: stats.php,v 1.1.1.1.2.2 2005/07/26 15:50:26 drewslater Exp $
 * @author  Luis Argerich (lrargerich@yahoo.com)
 * @package search
 * @subpackage functions
 */
 
/**
 * requires setup
 */
require_once( '../bit_setup_inc.php' );

include_once( SEARCH_PKG_PATH.'searchstats_lib.php');

if ($feature_search_stats != 'y') {
	$gBitSmarty->assign('msg', tra("This feature is disabled").": feature_search_stats");

	$gBitSystem->display( 'error.tpl' );
	die;
}

if (!$gBitUser->isAdmin()) {
	$gBitSmarty->assign('msg', tra("You dont have permission to use this feature"));

	$gBitSystem->display( 'error.tpl' );
	die;
}

if (isset($_REQUEST["clear"])) {
	
	$searchstatslib->clear_search_stats();
}

if ( empty( $_REQUEST["sort_mode"] ) ) {
	$sort_mode = 'hits_desc';
} else {
	$sort_mode = $_REQUEST["sort_mode"];
}

if (!isset($_REQUEST["offset"])) {
	$offset = 0;
} else {
	$offset = $_REQUEST["offset"];
}
if (isset($_REQUEST['page'])) {
	$page = &$_REQUEST['page'];
	$offset = ($page - 1) * $maxRecords;
}
$gBitSmarty->assign_by_ref('offset', $offset);

if (isset($_REQUEST["find"])) {
	$find = $_REQUEST["find"];
} else {
	$find = '';
}

$gBitSmarty->assign('find', $find);

$gBitSmarty->assign_by_ref('sort_mode', $sort_mode);
$channels = $searchstatslib->list_search_stats($offset, $maxRecords, $sort_mode, $find);

$cant_pages = ceil($channels["cant"] / $maxRecords);
$gBitSmarty->assign_by_ref('cant_pages', $cant_pages);
$gBitSmarty->assign('actual_page', 1 + ($offset / $maxRecords));

if ($channels["cant"] > ($offset + $maxRecords)) {
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

$gBitSmarty->assign_by_ref('channels', $channels["data"]);



// Display the template
$gBitSystem->display( 'bitpackage:stats/search_stats.tpl');

?>
