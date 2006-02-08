<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/modules/mod_package_search.php,v 1.4 2006/02/08 08:24:21 lsces Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: mod_package_search.php,v 1.4 2006/02/08 08:24:21 lsces Exp $
 * @author  Luis Argerich (lrargerich@yahoo.com)
 * @package search
 * @subpackage modules
 */

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
	foreach( $gLibertySystem->mContentTypes as $contentType ) {
		switch ($contentType["content_type_guid"]) {
			case "bitarticle"  : $perm = "bit_p_read_article";		break;
			case "bitpage"     : $perm = "bit_p_view";				break;
			case "bitblogpost" : $perm = "bit_p_read_blog";	    	break;
			case "bitcomment"  : $perm = "bit_p_read_comments";		break;
			case "fisheyegallery" : $perm = "bit_p_view_fisheye";	break;
			default            : $perm = "";	break;
		}
		$show = false;
		if (!empty($perm) and $gBitUser->hasPermission($perm)) {
			$contentTypes[]        = $contentType["content_type_guid"];
			$contentDescriptions[] = $contentType["content_description"];
		}
	}
	$gBitSmarty->assign( 'contentTypes', $contentTypes );
	$gBitSmarty->assign( 'contentDescriptions', $contentDescriptions );

	$gBitSmarty->assign( 'searchTitle', $searchTitle );
	$gBitSmarty->assign( 'miniSearchRsrc', $searchTemplateRsrc );
?>
