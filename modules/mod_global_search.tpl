{* $Header: /cvsroot/bitweaver/_bit_search/modules/mod_global_search.tpl,v 1.2 2005/07/17 17:36:15 squareing Exp $ *}

{if $gBitSystem->isPackageActive( 'search' )}
	{bitmodule title="$moduleTitle" name="search_new"}
		{include file="bitpackage:search/global_mini_search.tpl"}
	{/bitmodule}
{/if}
