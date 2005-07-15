{* $Header: /cvsroot/bitweaver/_bit_search/modules/mod_package_search.tpl,v 1.1.1.1.2.1 2005/07/15 12:01:18 squareing Exp $ *}
{if $gBitSystem->isPackageActive( 'search' )}
	{bitmodule title="$moduleTitle" name="pkg_search_box"}
		{include file=$miniSearchRsrc}
	{/bitmodule}
{/if}
