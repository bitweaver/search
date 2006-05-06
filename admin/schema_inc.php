<?php

$tables = array(

'search_index' => "
	searchword C(80) PRIMARY,
	content_id I4 PRIMARY,
	i_count I4 NOTNULL DEFAULT '1',
	last_update I4 NOTNULL
",

'search_syllable' => "
	syllable C(80) PRIMARY,
	last_used I4 NOTNULL,
	last_updated I4 NOTNULL
",

'search_words' => "
	syllable C(80) KEY,
	searchword C(80) KEY
",

'search_stats' => "
	term C(50) PRIMARY,
	hits I4
"

) ;


global $gBitInstaller;

foreach( array_keys( $tables ) AS $tableName ) {
	$gBitInstaller->registerSchemaTable( SEARCH_PKG_NAME, $tableName, $tables[$tableName] );
}

$indices = array (
	'searchidx_last_update_idx' => array( 'table' => 'search_index', 'cols' => 'last_update', 'opts' => NULL ),
	'searchidx_word_idx' => array( 'table' => 'search_index', 'cols' => 'searchword', 'opts' => NULL ),
	'searchidx_con_idx' => array( 'table' => 'search_index', 'cols' => 'content_id', 'opts' => NULL ),
	'searchsyl_last_used_idx' => array( 'table' => 'search_syllable', 'cols' => 'last_used', 'opts' => NULL )
);

$gBitInstaller->registerSchemaIndexes( SEARCH_PKG_NAME, $indices );

$gBitInstaller->registerPackageInfo( SEARCH_PKG_NAME, array(
	'description' => "This package makes any content on your site searchable.",
	'license' => '<a href="http://www.gnu.org/licenses/licenses.html#LGPL">LGPL</a>',
) );

// ### Default Preferences
//	array(SEARCH_PKG_NAME, 'search_fulltext','y'),
$gBitInstaller->registerPreferences( SEARCH_PKG_NAME, array(
	array(SEARCH_PKG_NAME, 'search_stats','n'),
	array(SEARCH_PKG_NAME, 'search_index_on_submit','n'),
	array(SEARCH_PKG_NAME, 'search_refresh_rate','5'),
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
