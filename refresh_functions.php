<?php
/**
 * $Header$
 *
 * @copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * $Id$
 * @author  Luis Argerich (lrargerich@yahoo.com)
 * @package search
 * @subpackage functions
 */

// to do - move blogs into tiki content.

/**
 * random_refresh_index_comments
 * I believe these functiions are from tiki. They are called every x refreshes of a browser.
 * They appear to pick a random wiki page, comment, blog or article and index it.
 * With the exception of blogs (blog headers not blog posts) they pick the content_id
 * and pass it to refresh_index() to do the work.
 */

function random_refresh_index($pContentType = "") {
	global $gBitSystem;
	switch ($pContentType) {
		case "articles" :
			$table = "articles";
			break;
		case "wiki" :
			$table = "wiki_pages";
			break;
		case "blogs" :
			$table = "blogs";
			break;
		case "blog_posts" :
			$table = "blog_posts";
			break;
		case "comments" :
			$table = "liberty_comments";
			break;
		default :
			$table = "";
	}
	if (!empty($table)) {
		$cant = $gBitSystem->mDb->getOne("SELECT COUNT(*) FROM `" . BIT_DB_PREFIX . $table . "`", array());
		if($cant > 0) {
			$query     = "SELECT `content_id` FROM `" . BIT_DB_PREFIX . $table . "`";
			$contentId = $gBitSystem->mDb->getOne($query, array(), 1, rand(0, $cant - 1));
			refresh_index($contentId);
		}
	}
}

/*
 * Index Refresh Function for Liberty Content
 * This can be called directly to force a refresh for a particular piece of tiki content.
 * This is also called by the Random_Refresh_* indexing functions from tiki.
 * This currently works for wiki pages, blog posts and articles.
 */

function refresh_index( $pContentObject = null ) {
	global $gBitSystem;
	if (is_object($pContentObject)) {
		if ( (!isset($pContentObject->mInfo["index_data"])) and method_exists($pContentObject, 'setIndexData')) {
			$pContentObject->setIndexData() ;
		}
		if (isset($pContentObject->mInfo["index_data"]) and isset($pContentObject->mContentId)) {
			if (isset($pContentObject->mType["content_type_guid"])) {
				$contentTypeGuid = $pContentObject->mType["content_type_guid"];
			} elseif (isset($pContentObject->mContentTypeGuid)) {
				$contentTypeGuid = $pContentObject->mContentTypeGuid;
			}
			if (isset($contentTypeGuid)) {
				$words = prepare_words($pContentObject->mInfo["index_data"]);
				insert_index($words, $contentTypeGuid, $pContentObject->mContentId);
			}
		}
	}
}

function refresh_index_oldest(){
	global $gBitSystem;
	$contentId = $gBitSystem->mDb->getOne("SELECT `content_id` FROM `" . BIT_DB_PREFIX .
				"search_index` ORDER BY `last_update`", array());
	if ( isset($contentId) ) {
		refresh_index($contentId);
	}
}

function prepare_words($data) {
	$data = strip_tags($data);
	// split into words
	$sstrings = preg_split("/[\W]+/", $data, -1, PREG_SPLIT_NO_EMPTY);
	// count words
	$words = array();
	foreach ($sstrings as $key=>$value) {
		if(!isset($words[strtolower($value)])) {
			$words[strtolower($value)] = 0;
		}
		$words[strtolower($value)]++;
	}
	return($words);
}

function delete_index ($pContentId) {
	global $gBitSystem;
	if( !empty( $pContentId ) ) {
		$sql = "DELETE FROM `".BIT_DB_PREFIX."search_index` WHERE `content_id`=?";
		$gBitSystem->mDb->query($sql, array($pContentId));
	}
}

function insert_index( &$words, $location, $pContentId ) {
	global $gBitSystem;
	if( !empty( $pContentId ) ) {
		delete_index($pContentId);
		$now = $gBitSystem->getUTCTime();
		foreach ($words as $key=>$value) {
			if (strlen($key) >= $gBitSystem->getConfig( 'search_min_wordlength') ) {
				// todo: stopwords + common words.
				$query = "INSERT INTO `" . BIT_DB_PREFIX . "search_index`
					(`content_id`,`searchword`,`i_count`,`last_update`) values (?,?,?,?)";
				$gBitSystem->mDb->query($query, array($pContentId, $key, (int) $value, $now));
			} // What happened to location?
		}
	}
}

function delete_search_words_and_syllables() {
	global $gBitSystem;
	$gBitSystem->mDb->query( "DELETE FROM `" . BIT_DB_PREFIX . "search_words`", array() );
	$gBitSystem->mDb->query( "DELETE FROM `" . BIT_DB_PREFIX . "search_syllable`", array() );
}

function delete_index_content_type($pContentType) {
	global $gBitSystem;
	$sql   = "DELETE FROM `" . BIT_DB_PREFIX . "search_index`";
	$array = array();
	if ( $pContentType <> "pages" ) {
		$sql  .= " WHERE `content_id` IN (SELECT `content_id` FROM `" . BIT_DB_PREFIX .
				 "liberty_content` where `content_type_guid` = ?)";
		$array = array($pContentType);
	}
	$gBitSystem->mDb->query( $sql, $array );
}

function rebuild_index($pContentType, $pUnindexedOnly = false) {
	global $gBitSystem, $gLibertySystem;
	$arguments   = array();
	$whereClause = "";
	ini_set("max_execution_time", "3000");
	if (!$pUnindexedOnly) {
		delete_index_content_type($pContentType);
	}
	$query  = "SELECT `content_id`, `content_type_guid` FROM `" . BIT_DB_PREFIX . "liberty_content`";
	if( !empty( $pContentType ) && $pContentType != "pages" ) {
		$whereClause = " WHERE `content_type_guid` = ?";
		$arguments[] = $pContentType;
	}

	if( $pUnindexedOnly ) {
		if (empty($whereClause)) {
			$whereClause = " WHERE ";
		} else {
			$whereClause .= " AND ";
		}
		$whereClause .= "`content_id` NOT IN (SELECT DISTINCT `content_id` FROM `" . BIT_DB_PREFIX . "search_index`)" ;
	}

	$orderBy = " ORDER BY `content_type_guid` ";
	$result = $gBitSystem->mDb->query( $query.$whereClause.$orderBy, $arguments );
	$count  = 0;
	if( $result ) {
		$count  = $result->RecordCount();
		while ($res = $result->fetchRow()) {
			if( isset( $gLibertySystem->mContentTypes[$res["content_type_guid"]] ) ) {
				$type = $gLibertySystem->mContentTypes[$res["content_type_guid"]];
				require_once( constant( strtoupper( $type['handler_package'] ).'_PKG_PATH' ).$type['handler_file'] );
				$obj = new $type['handler_class']( NULL, $res["content_id"] );
				refresh_index($obj);
				unset($obj);
			}
		}
	}
	return $count;
}
?>
