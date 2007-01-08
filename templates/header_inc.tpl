{if $gBitSystem->isPackageActive( 'search' ) and $gBitSystem->isFeatureActive( 'site_header_extended_nav' )}
	<link rel="search" title="{tr}Search{/tr}" href="{$smarty.const.SEARCH_PKG_URL}" />
{/if}
