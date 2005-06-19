{* $Header: /cvsroot/bitweaver/_bit_search/modules/mod_package_search.tpl,v 1.1 2005/06/19 05:04:25 bitweaver Exp $ *}
{if $gBitSystemPrefs.package_search eq 'y'}
	{bitmodule title="$moduleTitle" name="pkg_search_box"}
		{include file=$miniSearchRsrc}
	{/bitmodule}
{/if}
