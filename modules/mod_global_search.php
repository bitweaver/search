<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/modules/mod_global_search.php,v 1.3 2005/12/18 22:31:56 squareing Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: mod_global_search.php,v 1.3 2005/12/18 22:31:56 squareing Exp $
 * @author  Luis Argerich (lrargerich@yahoo.com)
 * @package search
 * @subpackage modules
 */
global $gLibertySystem;
//vd($gLibertySystem->mContentTypes);
foreach( $gLibertySystem->mContentTypes as $cType ) {
	$contentTypes[$cType['content_type_guid']] = $cType['content_description'];
}
$gBitSmarty->assign( 'contentTypes', $contentTypes );
?>
