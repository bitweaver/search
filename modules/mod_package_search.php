<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/modules/mod_package_search.php,v 1.1.1.1.2.3 2006/01/29 07:33:38 seannerd Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: mod_package_search.php,v 1.1.1.1.2.3 2006/01/29 07:33:38 seannerd Exp $
 * @author  Luis Argerich (lrargerich@yahoo.com)
 * @package search
 * @subpackage modules
 */

	$tplName = strtolower( ACTIVE_PACKAGE ).'_mini_search.tpl';
	$searchTemplatePath = BIT_ROOT_URL.constant( strtoupper( ACTIVE_PACKAGE ).'_PKG_PATH' ).'templates/'.$tplName;

	if( file_exists( $searchTemplatePath ) ) {
		$searchTemplateRsrc = 'bitpackage:'.strtolower( ACTIVE_PACKAGE ).'/'.$tplName;
		$searchTitle = ucfirst( ACTIVE_PACKAGE );
	} else {
		$searchTemplateRsrc = 'bitpackage:search/global_mini_search.tpl';
		$searchTitle = '';
	}
	$contentTypes 		 = array("bitarticle", "bitpage",    "bitblogpost", "bitcomment");
	$contentDescriptions = array("Articles",   "Wiki Pages", "Blog Posts",  "Comments");
	$gBitSmarty->assign( 'contentTypes', $contentTypes );
	$gBitSmarty->assign( 'contentDescriptions', $contentDescriptions );

	$gBitSmarty->assign( 'searchTitle', $searchTitle );
	$gBitSmarty->assign( 'miniSearchRsrc', $searchTemplateRsrc );
?>
