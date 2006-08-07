{* this needs to go in <head>, but we don't have a way of doing that from a module yet. *}
<script type="text/javascript" src="{$smarty.const.UTIL_PKG_URL}javascript/libs/prototype.js"></script>
<script type="text/javascript" src="{$smarty.const.UTIL_PKG_URL}javascript/libs/live_search.js"></script>
<script type="text/javascript">
	var search = new LiveSearch($('search_box'), $('search_results')); 
</script>
{* end of <head> section *}

{if $gBitSystem->isPackageActive( 'search' )}
	{bitmodule title="$moduleTitle" name="search_new"}
		{form}
			<input id="search_box" type="text" name="search" value="Search..." />
			<div id="search_results"></div>
		{/form}
	{/bitmodule}
{/if}
