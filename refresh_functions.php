<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/refresh_functions.php,v 1.1.1.1.2.18 2006/02/15 02:20:27 seannerd Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: refresh_functions.php,v 1.1.1.1.2.18 2006/02/15 02:20:27 seannerd Exp $
 * @author  Luis Argerich (lrargerich@yahoo.com)
 * @package search
 * @subpackage functions
 */

// to do - move blogs into tiki content.

/*
 * Index Refresh Function for Liberty Content
 * This can be called directly to force a refresh for a particular piece of tiki content.
 * This is also called by the Random_Refresh_* indexing functions from tiki.
 * This currently works for wiki pages, blog posts and articles.
 */

function refresh_index( $pvContentId = 0 ) {
	global $gBitSystem;
	if (is_object($pvContentId)) {  // InvokeService calls pass objects.
		$contentId   = $pvContentId->mContentId;
		$contentGUID = $pvContentId->mContentTypeGuid;
	} else {
		$contentId = $pvContentId;
		$contentGUID = "";
	}

	// This are for the constants. The defines need to be moved from bitblog to bit_setup_inc ... 
	if ($contentId > 0) {
		if (empty($contentGUID)) {
			$sql = "SELECT `content_type_guid` FROM `" . BIT_DB_PREFIX . "tiki_content` WHERE `content_id` = " . $contentId;
			$contentGUID = $gBitSystem->mDb->getOne($sql, array());
		}
		$fields = "";
		$joins  = "";
		switch ($contentGUID) {
			//case BITPAGE_CONTENT_TYPE_GUID    :
			case "bitpage"    :
				$fields = ", t1.`description`";
				$joins  = " INNER JOIN `" . BIT_DB_PREFIX . "tiki_pages` t1 ON tc.`content_id` = t1.`content_id`";
				break;
			//case BITARTICLE_CONTENT_TYPE_GUID :
			case "bitarticle" :
				$fields = ", t1.`description`, t1.`status_id`";
				$joins  = " INNER JOIN `" . BIT_DB_PREFIX . "tiki_articles` t1 ON tc.`content_id` = t1.`content_id`";
				break;
			default:
		}
		$query = "SELECT tc.`title`, tc.`data`, uu.`login`, uu.`real_name` " . $fields . " " .
				 "FROM `" . BIT_DB_PREFIX . "tiki_content` tc " .  
				 "INNER JOIN `" . BIT_DB_PREFIX . "users_users` uu ON uu.`user_id` = tc.`user_id`" .
				 $joins . " WHERE tc.`content_id` = " . $contentId;
		$result = $gBitSystem->mDb->query($query, array());
		$res    = $result->fetchRow();
		if (($contentGUID <> "bitarticle" ) //BITARTICLE_CONTENT_TYPE_GUID) 
			or ($contentGUID == "bitarticle" and $res["status_id"] == 300)) {
				$words  = search_index($res["title"] . " " . $res["data"] . " " . $res["login"] . 
						" " . $res["real_name"] . (empty($pAuxTable) ? "" : " " . $res["description"]));
				insert_index($words, $contentGUID, $contentId);
		}
	}
}

// Legacy index handlers - blogs (blog headers) are not in tiki_content yet
//  so we can't handle them like the others

function refresh_index_blogs( $pBlogId = 0 ) {
	global $gBitSystem;
	if( $pBlogId > 0 and $gBitSystem->isPackageActive( 'blogs' ) ) {
		require_once( BLOGS_PKG_PATH.'BitBlog.php' );
		$query = "SELECT tb.`title`, tb.`description`, uu.`login` as `user`, uu.`real_name`
					FROM `".BIT_DB_PREFIX."tiki_blogs` tb
					INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON uu.`user_id` = tb.`user_id`
					WHERE `blog_id` = " . $pBlogId;
		$result = $gBitSystem->mDb->query($query, array());
		$res    = $result->fetchRow();
		$words  = search_index($res["title"]." ".$res["user"]." ".$res["real_name"]." ".$res["description"]);
		//insert_index($words, BITBLOG_CONTENT_TYPE_GUID, $pBlogId);
		insert_index($words, "bitblog", $pBlogId);
	}
}
// End Legacy Support


function search_index($data) {
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
		$sql = "DELETE FROM `".BIT_DB_PREFIX."tiki_searchindex` WHERE `content_id`=?";
		$gBitSystem->mDb->query($sql, array($pContentId));
	}
}

function insert_index( &$words, $location, $pContentId ) {
	global $gBitSystem;
	if( !empty( $pContentId ) ) {
		delete_index($pContentId);
		$now = $gBitSystem->getUTCTime();
		foreach ($words as $key=>$value) {
			if (strlen($key) >= $gBitSystem->mPrefs["search_min_wordlength"]) {
				// todo: stopwords
				$query = "INSERT INTO `" . BIT_DB_PREFIX . "tiki_searchindex`
					(`location`,`content_id`,`searchword`,`count`,`last_update`) values (?,?,?,?,?)";
				$gBitSystem->mDb->query($query, array($location, $pContentId, $key, (int) $value, $now));
			}
		}
	}
}

function delete_search_words_and_syllables() {
	global $gBitSystem;
	$gBitSystem->mDb->query( "DELETE FROM `" . BIT_DB_PREFIX . "tiki_searchwords`", array() );
	$gBitSystem->mDb->query( "DELETE FROM `" . BIT_DB_PREFIX . "tiki_searchsyllable`", array() );
}

function delete_index_content_type($pContentType) {
	global $gBitSystem;
	$sql   = "DELETE FROM `" . BIT_DB_PREFIX . "tiki_searchindex`";
	$array = array();
	if ( $pContentType <> "pages" ) {
		$sql  .= " WHERE `location`=?";
		$array = array($pContentType);
	}
	$gBitSystem->mDb->query( $sql, $array );
}

function rebuild_index($pContentType, $pUnindexedOnly = false) {
	global $gBitSystem;
	$whereClause = "";
	ini_set("max_execution_time", "300");
	if (!$pUnindexedOnly) {
		delete_index_content_type($pContentType);
	}
	$query  = "SELECT `content_id` FROM `" . BIT_DB_PREFIX . "tiki_content`";
	if ( $pContentType <> "pages") {
		$whereClause = " WHERE `content_type_guid` = '" . $pContentType . "'";
	}
	if ($pUnindexedOnly) {
		if (empty($whereClause)) {
			$whereClause = " WHERE ";
		} else {
			$whereClause .= " AND ";
		}
		$whereClause .= "`content_id` NOT IN (SELECT DISTINCT `content_id` FROM `" . BIT_DB_PREFIX . "tiki_searchindex`)" ;
	}
	$result = $gBitSystem->mDb->query($query . $whereClause);
	$count  = 0;
	if( $result ) {
		$count  = $result->RecordCount();
		while ($res = $result->fetchRow()) {
			$contentId = $res["content_id"];
			refresh_index($contentId);
		}
	}
	return $count;
}

?>