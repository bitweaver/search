{strip}
<ul>
	<li><a class="item" href="{$smarty.const.KERNEL_PKG_URL}admin/index.php?page=search" title="{tr}Search{/tr}" >{tr}Search{/tr} Settings</a></li>
	{if $gBitSystem->isFeatureActive( 'feature_search_stats' )}
		<li><a class="item" href="{$smarty.const.SEARCH_PKG_URL}stats.php">{tr}Search Statistics{/tr}</a></li>
	{/if}
</ul>
{/strip}