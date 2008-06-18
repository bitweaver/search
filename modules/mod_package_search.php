<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/modules/mod_package_search.php,v 1.13 2008/06/18 09:20:01 lsces Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: mod_package_search.php,v 1.13 2008/06/18 09:20:01 lsces Exp $
 * @author  Luis Argerich (lrargerich@yahoo.com)
 * @package search
 * @subpackage modules
 */

/**
 * Initialization
 */
require_once(SEARCH_PKG_PATH."search_lib.php");

	$tplName = strtolower( ACTIVE_PACKAGE ).'_mini_search.tpl';
	$searchTemplatePath = BIT_ROOT_URL.constant( strtoupper( ACTIVE_PACKAGE ).'_PKG_PATH' ).'templates/'.$tplName;
	
	global $gLibertySystem;

	if( file_exists( $searchTemplatePath ) ) {
		$searchTemplateRsrc = 'bitpackage:'.strtolower( ACTIVE_PACKAGE ).'/'.$tplName;
		$searchTitle = ucfirst( ACTIVE_PACKAGE );
	} else {
		$searchTemplateRsrc = 'bitpackage:search/global_mini_search.tpl';
		$searchTitle = '';
	}

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

	$gBitSmarty->assign( 'searchTitle', $searchTitle );
	$gBitSmarty->assign( 'miniSearchRsrc', $searchTemplateRsrc );
?>
