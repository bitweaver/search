<?php

$tables = array(

'tiki_searchindex' => "
	searchword C(80) PRIMARY,
	location C(80) PRIMARY,
	content_id I4 PRIMARY,
	count I4 NOTNULL default '1',
	last_update I4 NOTNULL
",

'tiki_searchsyllable' => "
	syllable C(80) PRIMARY,
	last_used I4 NOTNULL,
	last_updated I4 NOTNULL
",

'tiki_searchwords' => "
	syllable C(80) KEY,
	searchword C(80) KEY
",

'tiki_search_stats' => "
	term C(50) PRIMARY,
	hits I4
"

) ;


global $gBitInstaller;

foreach( array_keys( $tables ) AS $tableName ) {
	$gBitInstaller->registerSchemaTable( SEARCH_PKG_NAME, $tableName, $tables[$tableName] );
}

$indices = array (
	'tiki_searchidx_last_update_idx' => array( 'table' => 'tiki_searchindex', 'cols' => 'last_update', 'opts' => NULL ),
	'tiki_searchidx_word_idx' => array( 'table' => 'tiki_searchindex', 'cols' => 'searchword', 'opts' => NULL ),
	'tiki_searchidx_con_idx' => array( 'table' => 'tiki_searchindex', 'cols' => 'content_id', 'opts' => NULL ),
	'tiki_searchidx_loc_idx' => array( 'table' => 'tiki_searchindex', 'cols' => 'location', 'opts' => NULL ),
	'tiki_searchsyl_last_used_idx' => array( 'table' => 'tiki_searchsyllable', 'cols' => 'last_used', 'opts' => NULL )
);

$gBitInstaller->registerSchemaIndexes( SEARCH_PKG_NAME, $indices );

$gBitInstaller->registerPackageInfo( SEARCH_PKG_NAME, array(
	'description' => "This package makes any content on your site searchable.",
	'license' => '<a href="http://www.gnu.org/licenses/licenses.html#LGPL">LGPL</a>',
) );

// ### Default Preferences
//	array(SEARCH_PKG_NAME, 'feature_search_fulltext','y'),
$gBitInstaller->registerPreferences( SEARCH_PKG_NAME, array(
	array(SEARCH_PKG_NAME, 'feature_search_stats','n'),
	array(SEARCH_PKG_NAME, 'search_min_wordlength','3'),
	array(SEARCH_PKG_NAME, 'search_max_syllwords','100'),
	array(SEARCH_PKG_NAME, 'search_lru_purge_rate','5'),
	array(SEARCH_PKG_NAME, 'search_lru_length','100'),
	array(SEARCH_PKG_NAME, 'search_syll_age','48')
) );

$moduleHash = array(
	'mod_package_search' => array(
		'title' => 'Search',
		'ord' => 3,
		'pos' => 'r',
		'module_rsrc' => 'bitpackage:search/mod_package_search.tpl'
) );

$gBitInstaller->registerModules( $moduleHash );

?>
