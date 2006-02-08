<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/refresh_functions.php,v 1.14 2006/02/08 15:58:09 sylvieg Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: refresh_functions.php,v 1.14 2006/02/08 15:58:09 sylvieg Exp $
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
 * Index Refresh Function for Tiki Content
 * This can be called directly to force a refresh for a particular piece of tiki content.
 * This is also called by the Random_Refresh_* indexing functions from tiki.
 * This currently works for wiki pages, blog posts and articles.
 */

function refresh_index( $pvContentId = 0 ) {
	global $gBitSystem;
	if (is_object($pvContentId)) {  // InvokeService calls pass objects.
		$contentId = $pvContentId->mContentId;
	} else {
		$contentId = $pvContentId;
	}
	require_once( LIBERTY_PKG_PATH.'LibertyComment.php' );
	require_once( WIKI_PKG_PATH.'BitPage.php' );
	require_once( BLOGS_PKG_PATH.'BitBlogPost.php' );
	require_once( ARTICLES_PKG_PATH.'BitArticle.php' );
	if ($contentId > 0) {
		$sql   = "SELECT `content_type_guid` FROM `" . BIT_DB_PREFIX . "liberty_content` WHERE `content_id` = " . $contentId;
		$cGUID = $gBitSystem->mDb->getOne($sql, array());
		switch ($cGUID) {
			case BITCOMMENT_CONTENT_TYPE_GUID :
				$auxTable = "liberty_comments";
				break;
			case BITBLOGPOST_CONTENT_TYPE_GUID :
				$auxTable = "wiki_pages";
				break;
			case BITBLOGPOST_CONTENT_TYPE_GUID :
				$auxTable = "";
				break;
			case BITARTICLE_CONTENT_TYPE_GUID :
				$auxTable = "articles";
				break;
			default:
				$auxTable = "";
		}
		if (empty($pAuxTable)) {
			$auxField = '';
			$auxJoin  = '';
		} else {
			$auxField = ', t1.`description`';
			$auxJoin  = 'INNER JOIN `' . BIT_DB_PREFIX . $pAuxTable . '` t1 ON lc.`content_id` = t1.`content_id`';
		}
		$query = "SELECT lc.`title`, lc.`data`, uu.`login`, uu.`real_name`" . $auxField . " " .
					"FROM `" . BIT_DB_PREFIX . "liberty_content` lc " .  
					"INNER JOIN `" . BIT_DB_PREFIX . "users_users` uu ON uu.`user_id` = lc.`user_id`" .
					$auxJoin . " WHERE lc.`content_id` = " . $contentId;
		$result = $gBitSystem->mDb->query($query, array());
		$res    = $result->fetchRow();
		$words  = search_index($res["title"] . " " . $res["data"] . " " . $res["login"] . " " . $res["real_name"] . 
		          (empty($pAuxTable) ? "" : " " . $res["description"]));
		insert_index($words, $cGUID, $contentId);
	}
}

// Legacy index handlers - blogs (blog headers) are not in liberty_content yet
//  so we can't handle them like the others

function random_refresh_index_blogs() {
	global $gBitSystem;
	if( $gBitSystem->isPackageActive( 'blogs' ) ) {
		require_once( BLOGS_PKG_PATH.'BitBlog.php' );
		// get random blog
		$cant = $gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."blogs`", array());
		if($cant > 0) {
			$query  = "select `blog_id` from `" . BIT_DB_PREFIX . "blogs`";
			$blogId = $gBitSystem->mDb->getOne($query, array(), 1, rand(0, $cant - 1));
			refresh_index_blogs($blogId);
		}
	}
}

function refresh_index_blogs( $pBlogId = 0 ) {
	global $gBitSystem;
	if( $pBlogId > 0 and $gBitSystem->isPackageActive( 'blogs' ) ) {
		require_once( BLOGS_PKG_PATH.'BitBlog.php' );
		$query = "SELECT b.`title`, b.`description`, uu.`login` as `user`, uu.`real_name`
					FROM `".BIT_DB_PREFIX."blogs` b
					INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON uu.`user_id` = b.`user_id`
					WHERE `blog_id` = " . $pBlogId;
		$result = $gBitSystem->mDb->query($query, array());
		$res    = $result->fetchRow();
		$words  = search_index($res["title"]." ".$res["user"]." ".$res["real_name"]." ".$res["description"]);
		insert_index($words, BITBLOG_CONTENT_TYPE_GUID, $pBlogId);
	}
}

function refresh_index_oldest(){
	global $gBitSystem;
	$contentId = $gBitSystem->mDb->getOne("SELECT `content_id` FROM `" . BIT_DB_PREFIX . 
				"searchindex` ORDER BY `last_update`", array());
	if ( isset($contentId) ) {
		refresh_index($contentId);
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
		$query = "DELETE FROM `".BIT_DB_PREFIX."searchindex` WHERE `location`=? and `content_id`=?";
		$gBitSystem->mDb->query($query, array($location, $pContentId));
		$now = $gBitSystem->getUTCTime();
		foreach ($words as $key=>$value) {
			if (strlen($key) >= $gBitSystem->mPrefs["search_min_wordlength"]) {
				// todo: stopwords
				$query = "INSERT INTO `" . BIT_DB_PREFIX . "searchindex`
					(`location`,`content_id`,`searchword`,`count`,`last_update`) values (?,?,?,?,?)";
				$gBitSystem->mDb->query($query, array($location, $pContentId, $key, (int) $value, $now));
			}
		}
	}
}

function delete_search_words_and_syllables() {
	global $gBitSystem;
	$gBitSystem->mDb->query( "DELETE FROM `" . BIT_DB_PREFIX . "searchwords`", array() );
	$gBitSystem->mDb->query( "DELETE FROM `" . BIT_DB_PREFIX . "searchsyllable`", array() );
}

function delete_index($pContentType) {
	global $gBitSystem;
	$sql   = "DELETE FROM `" . BIT_DB_PREFIX . "searchindex`";
	$array = array();
	if ( !($pContentType == "pages") ) {
		$sql  .= " WHERE `location`=?";
		$array = array($pContentType);
	}
	$gBitSystem->mDb->query( $sql, $array );

}

function rebuild_index($pContentType) {
	global $gBitSystem;
	ini_set("max_execution_time", "300");
	delete_index($pContentType);
	$query  = "SELECT `content_id` FROM `" . BIT_DB_PREFIX . "liberty_content`";
	if ( !($pContentType == "pages")) $query .= " WHERE `content_type_guid` = '" . $pContentType . "'";
	$result = $gBitSystem->mDb->query($query);
	if( $result ) {
		while ($res = $result->fetchRow()) {
			$contentId = $res["content_id"];
			refresh_index($contentId);
		}
	}
}
?>
