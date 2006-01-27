<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/directory_search.php,v 1.4 2006/01/27 21:56:30 squareing Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: directory_search.php,v 1.4 2006/01/27 21:56:30 squareing Exp $
 * @author  Luis Argerich (lrargerich@yahoo.com)
 * @package search
 * @subpackage functions
 */
 
/**
 * required setup
 */
require_once( '../bit_setup_inc.php' );

include_once( DIRECTORY_PKG_PATH.'dir_lib.php' );

$gBitSystem->verifyFeature( 'feature_directory' );
$gBitSystem->verifyPermission( 'bit_p_view_directory' );

$gBitSmarty->assign('words', $_REQUEST['words']);
$gBitSmarty->assign('where', $_REQUEST['where']);
$gBitSmarty->assign('how', $_REQUEST['how']);

if ( empty( $_REQUEST["sort_mode"] ) ) {
	$sort_mode = 'hits_desc';
} else {
	$sort_mode = $_REQUEST["sort_mode"];
}
$gBitSmarty->assign_by_ref('sort_mode', $sort_mode);

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

if ($_REQUEST['where'] == 'all') {
	$items = $dirlib->dir_search($_REQUEST['words'], $_REQUEST['how'], $offset, $maxRecords, $sort_mode);
} else {
	$items = $dirlib->dir_search_cat($_REQUEST['parent'], $_REQUEST['words'], $_REQUEST['how'], $offset, $maxRecords, $sort_mode);
}

$cant_pages = ceil($items["cant"] / $maxRecords);
$gBitSmarty->assign_by_ref('cant_pages', $cant_pages);
$gBitSmarty->assign('actual_page', 1 + ($offset / $maxRecords));

if ($items["cant"] > ($offset + $maxRecords)) {
	$gBitSmarty->assign('next_offset', $offset + $maxRecords);
} else {
	$gBitSmarty->assign('next_offset', -1);
}

if ($offset > 0) {
	$gBitSmarty->assign('prev_offset', $offset - $maxRecords);
} else {
	$gBitSmarty->assign('prev_offset', -1);
}

$gBitSmarty->assign_by_ref('items', $items["data"]);

$section = 'directory';

// Display the template
$gBitSystem->display( 'bitpackage:search/directory_search.tpl');

?>
