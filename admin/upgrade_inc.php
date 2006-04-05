<?php

global $gBitSystem, $gUpgradeFrom, $gUpgradeTo;

$upgrades = array(

'BWR1' => array(
	'BWR2' => array(
array( 'DATADICT' => array(
	array( 'RENAMETABLE' => array(
		'tiki_searchindex'    => 'search_index',
		'tiki_searchsyllable' => 'search_syllable',
		'tiki_search_stats'   => 'search_stats',
		'tiki_searchwords'    => 'search_words',
	)),
)),
	)
),

'BONNIE' => array(
	'BWR1' => array(
// STEP 1 - Data is transient, so let's recreate the table with proper  multi-column keys
array( 'DATADICT' => array(
	array( 'DROPTABLE' => array(
		'tiki_searchindex'
	)),
	array( 'CREATE' => array (
	'tiki_searchindex' => "
		searchword C(80) PRIMARY,
		location C(80) PRIMARY,
		content_id I4 PRIMARY,
		count I4 NOTNULL default '1',
		last_update I4 NOTNULL
	",
	)),
)),

// STEP 2
array( 'DATADICT' => array(
	array( 'RENAMECOLUMN' => array(
		'tiki_searchsyllable' => array( '`lastUsed`' => '`last_used` I8',
										'`lastUpdated`' => '`last_updated` I8' ),
	)),
)),

// STEP 3
array( 'DATADICT' => array(
	array( 'CREATEINDEX' => array(
		'tiki_searchidx_con_id_idx' => array( 'tiki_searchindex', '`content_id`', array() ),
		'tiki_searchidx_word_idx' => array( 'tiki_searchindex', '`searchword`', array() ),
		'tiki_searchidx_loc_idx' => array( 'tiki_searchindex', '`location`', array() ),
		'tiki_searchidx_update_idx' => array( 'tiki_searchindex', '`last_update`', array() ),
	)),
)),

	)
)

);

if( isset( $upgrades[$gUpgradeFrom][$gUpgradeTo] ) ) {
	$gBitSystem->registerUpgrade( SEARCH_PKG_NAME, $upgrades[$gUpgradeFrom][$gUpgradeTo] );
}


?>
