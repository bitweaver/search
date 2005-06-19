{* $Header: /cvsroot/bitweaver/_bit_search/modules/mod_global_search.tpl,v 1.1 2005/06/19 05:04:25 bitweaver Exp $ *}

{if $gBitSystemPrefs.package_search eq 'y'}
	{bitmodule title="$moduleTitle" name="search_new"}
		{include file="bitpackage:search/global_mini_search.tpl"}
	{/bitmodule}
{/if}
