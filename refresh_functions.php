<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/refresh_functions.php,v 1.1.1.1.2.12 2006/01/29 05:16:17 seannerd Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: refresh_functions.php,v 1.1.1.1.2.12 2006/01/29 05:16:17 seannerd Exp $
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
 * and pass it to refresh_index_tiki_content() to do the work.
 */

function random_refresh_index($pContentType = "") {
	global $gBitSystem;
	switch ($pContentType) {
		case "articles" :
			$table = "tiki_articles";
			break;
		case "wiki" :
			$table = "tiki_pages";
			break;
		case "blog_posts" :
			$table = "tiki_blog_posts";
			break;
		case "comments" :
			$table = "tiki_comments";
			break;
		default :
			$table = "";
	}
	if (!empty($table)) {
		$cant = $gBitSystem->mDb->getOne("SELECT COUNT(*) FROM `" . BIT_DB_PREFIX . $table . "`", array());
		if($cant > 0) {
			$query     = "SELECT `content_id` FROM `" . BIT_DB_PREFIX . $table . "`";
			$contentID = $gBitSystem->mDb->getOne($query, array(), 1, rand(0, $cant - 1));
			refresh_index_tiki_content($contentID);
		}
	}
}

/*
 * Index Refresh Function for Tiki Content
 * This can be called directly to force a refresh for a particular piece of tiki content.
 * This is also called by the Random_Refresh_* indexing functions from tiki.
 * This currently works for wiki pages, blog posts and articles.
 */

function refresh_index_tiki_content( $pContentID = 0 ) {
	global $gBitSystem;
	require_once( LIBERTY_PKG_PATH.'LibertyComment.php' );
	require_once( WIKI_PKG_PATH.'BitPage.php' );
	require_once( BLOGS_PKG_PATH.'BitBlogPost.php' );
	require_once( ARTICLES_PKG_PATH.'BitArticle.php' );
	if ($pContentID > 0) {
		$sql   = "SELECT `content_type_guid` FROM `" . BIT_DB_PREFIX . "tiki_content` WHERE `content_id` = " . $pContentID;
		$cGUID = $gBitSystem->mDb->getOne($sql, array());
		switch ($cGUID) {
			case BITCOMMENT_CONTENT_TYPE_GUID :
				$auxTable = "tiki_comments";
				break;
			case BITBLOGPOST_CONTENT_TYPE_GUID :
				$auxTable = "tiki_pages";
				break;
			case BITBLOGPOST_CONTENT_TYPE_GUID :
				$auxTable = "";
				break;
			case BITARTICLE_CONTENT_TYPE_GUID :
				$auxTable = "tiki_articles";
				break;
			default:
				$auxTable = "";
		}
		if (empty($pAuxTable)) {
			$auxField = '';
			$auxJoin  = '';
		} else {
			$auxField = ', t1.`description`';
			$auxJoin  = 'INNER JOIN `' . BIT_DB_PREFIX . $pAuxTable . '` t1 ON tc.`content_id` = t1.`content_id`';
		}
		$query = "SELECT tc.`title`, tc.`data`, uu.`login`, uu.`real_name`" . $auxField . " " .
					"FROM `" . BIT_DB_PREFIX . "tiki_content` tc " .  
					"INNER JOIN `" . BIT_DB_PREFIX . "users_users` uu ON uu.`user_id` = tc.`user_id`" .
					$auxJoin . " WHERE tc.`content_id` = " . $pContentID;
		$result = $gBitSystem->mDb->query($query, array());
		$res    = $result->fetchRow();
		$words  = search_index($res["title"] . " " . $res["data"] . " " . $res["login"] . " " . $res["real_name"] . 
		          (empty($pAuxTable) ? "" : " " . $res["description"]));
		insert_index($words, $cGUID, $pContentID);
	}
}

// Legacy index handlers - blogs (blog headers) are not in tiki_content yet
//  so we can't handle them like the others

function random_refresh_index_blogs() {
	global $gBitSystem;
	if( $gBitSystem->isPackageActive( 'blogs' ) ) {
		require_once( BLOGS_PKG_PATH.'BitBlog.php' );
		// get random blog
		$cant = $gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_blogs`", array());
		if($cant > 0) {
			$query  = "select `blog_id` from `" . BIT_DB_PREFIX . "tiki_blogs`";
			$blogID = $gBitSystem->mDb->getOne($query, array(), 1, rand(0, $cant - 1));
			refresh_index_blogs($blogID);
		}
	}
}

function refresh_index_blogs( $pBlogID = 0 ) {
	global $gBitSystem;
	if( $pBlogID > 0 and $gBitSystem->isPackageActive( 'blogs' ) ) {
		require_once( BLOGS_PKG_PATH.'BitBlog.php' );
		$query = "SELECT tb.`title`, tb.`description`, uu.`login` as `user`, uu.`real_name`
					FROM `".BIT_DB_PREFIX."tiki_blogs` tb
					INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON uu.`user_id` = tb.`user_id`
					WHERE `blog_id` = " . $pBlogID;
		$result = $gBitSystem->mDb->query($query, array());
		$res    = $result->fetchRow();
		$words  = search_index($res["title"]." ".$res["user"]." ".$res["real_name"]." ".$res["description"]);
		insert_index($words, BITBLOG_CONTENT_TYPE_GUID, $pBlogID);
	}
}

function refresh_index_oldest(){
	global $gBitSystem;
	$contentID = $gBitSystem->mDb->getOne("SELECT content_id FROM `" . BIT_DB_PREFIX . 
				"tiki_searchindex` ORDER BY `last_update`", array());
	if ( isset($contentID) ) {
		refresh_index_tiki_content($contentID);
	}
}

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

function insert_index( &$words, $location, $pContentId ) {
	global $gBitSystem;
	if( !empty( $pContentId ) ) {
		$query = "DELETE FROM `".BIT_DB_PREFIX."tiki_searchindex` WHERE `location`=? and `content_id`=?";
		$gBitSystem->mDb->query($query, array($location, $pContentId));
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

function delete_index($pContentType) {
	global $gBitSystem;
	$sql   = "DELETE FROM `" . BIT_DB_PREFIX . "tiki_searchindex`";
	$array = array();
	if ( !($pContentType == "pages") ) {
		$sql  .= " WHERE `location`=?";
		$array = array($pContentType);
	}
	$gBitSystem->mDb->query( $sql, $array );

}

function rebuild_index($pContentType) {
	global $gBitSystem;
	delete_index($pContentType);
	$query  = "SELECT `content_id` FROM `" . BIT_DB_PREFIX . "tiki_content`";
	if ( !($pContentType == "pages")) $query .= " WHERE `content_type_guid` = '" . $pContentType . "'";
	$result = $gBitSystem->mDb->query($query);
	if( $result ) {
		while ($res = $result->fetchRow()) {
			$contentId = $res["content_id"];
			refresh_index_tiki_content($contentId);
		}
	}
}

?>
