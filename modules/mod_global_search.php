<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/modules/mod_global_search.php,v 1.10 2009/10/01 13:45:47 wjames5 Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * $Id: mod_global_search.php,v 1.10 2009/10/01 13:45:47 wjames5 Exp $
 * @author  Luis Argerich (lrargerich@yahoo.com)
 * @package search
 * @subpackage modules
 */

/**
 * Initialization
 */
global $gLibertySystem;
require_once(SEARCH_PKG_PATH."search_lib.php");

if( empty( $contentTypes ) ) {
	$contentTypes = array( '' => tra( 'All Content' ) );
	foreach( $gLibertySystem->mContentTypes as $cType ) {
		if (SearchLib::has_permission($cType["content_type_guid"])
			and ( ! $gBitSystem->getConfig('search_restrict_types') ||
				  $gBitSystem->getConfig('search_pkg_'.$cType["content_type_guid"]) ) ) {
			$contentTypes[$cType['content_type_guid']] = $cType['content_description'];
		}
	}
}
$gBitSmarty->assign( 'contentTypes', $contentTypes );
?>
