<?php
/**
 * $Header$
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * $Id$
 * @author  Luis Argerich (lrargerich@yahoo.com)
 * @package search
 * @subpackage modules
 */

/**
 * Initialization
 */
require_once(SEARCH_PKG_PATH."search_lib.php");

	$tplName = $gBitSystem->getActivePackage().'_mini_search.tpl';
	$searchTemplatePath = BIT_ROOT_URL.constant( strtoupper( $gBitSystem->getActivePackage() ).'_PKG_PATH' ).'templates/'.$tplName;
	
	global $gLibertySystem;

	if( file_exists( $searchTemplatePath ) ) {
		$searchTemplateRsrc = 'bitpackage:'.$gBitSystem->getActivePackage().'/'.$tplName;
		$searchTitle = ucfirst( $gBitSystem->getActivePackage() );
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
				$contentTypes[$cType['content_type_guid']] = $gLibertySystem->getContentTypeName( $cType['content_type_guid'] );
			}
		}
	}
	$gBitSmarty->assign( 'contentTypes', $contentTypes );

	$gBitSmarty->assign( 'searchTitle', $searchTitle );
	$gBitSmarty->assign( 'miniSearchRsrc', $searchTemplateRsrc );
?>
