{* $Header: /cvsroot/bitweaver/_bit_search/modules/mod_package_search.tpl,v 1.2 2005/07/17 17:36:15 squareing Exp $ *}
{if $gBitSystem->isPackageActive( 'search' )}
	{bitmodule title="$moduleTitle" name="pkg_search_box"}
		{include file=$miniSearchRsrc}
	{/bitmodule}
{/if}
