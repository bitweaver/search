<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/refresh_functions.php,v 1.1.1.1.2.21 2006/02/19 03:46:10 seannerd Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: refresh_functions.php,v 1.1.1.1.2.21 2006/02/19 03:46:10 seannerd Exp $
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

function refresh_index( $pContentObject = null ) {
	global $gBitSystem;
	if (is_object($pContentObject)) {
		if (!isset($pContentObject->mInfo["index_data"]) and method_exists($pContentObject, 'setIndexData')) {
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

// Legacy index handlers - blogs (blog headers) are not in tiki_content yet
//  so we can't handle them like the others

function refresh_index_blogs( $pBlogId = 0 ) {
	global $gBitSystem;
	if( $pBlogId > 0 and $gBitSystem->isPackageActive( 'blogs' ) ) {
		require_once( BLOGS_PKG_PATH.'BitBlog.php' );
		$query = "SELECT tb.`title`, tb.`description`, uu.`login` as `user`, uu.`real_name`
					FROM `".BIT_DB_PREFIX."tiki_blogs` tb
					INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON uu.`user_id` = tb.`user_id`
					WHERE `blog_id` = ?";
		$res   = $gBitSystem->mDb->getRow($query, array($pBlogId));
		$words = prepare_words($res["title"]." ".$res["user"]." ".$res["real_name"]." ".$res["description"]);
		insert_index($words, BITBLOG_CONTENT_TYPE_GUID, $pBlogId);
	}
}
// End Legacy Support


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
				// todo: stopwords + common words.
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
	global $gBitSystem, $gLibertySystem;
	$arguments   = array();
	$whereClause = "";
	ini_set("max_execution_time", "300");
	if (!$pUnindexedOnly) {
		delete_index_content_type($pContentType);
	}
	$query  = "SELECT `content_id`, `content_type_guid` FROM `" . BIT_DB_PREFIX . "tiki_content` " ; 
	if ($pContentType <> "pages") {
		$whereClause = " WHERE `content_type_guid` = ?";
		$arguments[] = $pContentType;
	}
	if ($pUnindexedOnly) {
		if (empty($whereClause)) {
			$whereClause = " WHERE ";
		} else {
			$whereClause .= " AND ";
		}
		$whereClause .= " `content_id` NOT IN (SELECT DISTINCT `content_id` FROM `" . BIT_DB_PREFIX . "tiki_searchindex`)" ;
	}
	$orderBy = " ORDER BY `content_type_guid` ";
	$result  = $gBitSystem->mDb->query($query . $whereClause . $orderBy, $arguments);
	$count   = 0;
	if( $result ) {
		$count   = $result->RecordCount();
		while ($res = $result->fetchRow()) {
			if( isset( $gLibertySystem->mContentTypes[$res["content_type_guid"]] ) ) {
				$type = $gLibertySystem->mContentTypes[$res["content_type_guid"]];
				require_once( constant( strtoupper( $type['handler_package'] ).'_PKG_PATH' ).$type['handler_file'] );
				$obj = new $type['handler_class']( NULL, $res["content_id"] );
				//$obj->setIndexData();
				refresh_index($obj);
				unset($obj);
			}
		}
	}
	return $count;
}

?>
