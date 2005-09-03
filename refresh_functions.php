<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/refresh_functions.php,v 1.5 2005/09/03 10:21:31 squareing Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: refresh_functions.php,v 1.5 2005/09/03 10:21:31 squareing Exp $
 * @author  Luis Argerich (lrargerich@yahoo.com)
 * @package search
 * @subpackage functions
 */
 
/**
 * random_refresh_index_comments
 */
function random_refresh_index_comments() {
  //find random forum comment
  global $gBitSystem;
  // get random comment
  $cant=$gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_comments`",array());
  if($cant>0) {
	require_once( LIBERTY_PKG_PATH.'LibertyComment.php' );
    $query="select tcm.*,tc.`title`,tc.`data` from `".BIT_DB_PREFIX."tiki_comments` tcm INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON(tcm.`content_id`=tc.`content_id` )";
    $result=$gBitSystem->mDb->query($query,array(),1,rand(0,$cant-1));
    $res=$result->fetchRow();
    $words=&search_index($res["title"]." ".$res["data"]);
    insert_index($words, BITCOMMENT_CONTENT_TYPE_GUID, $res["comment_id"]);
  }
}


function random_refresh_index_wiki(){
	global $gBitSystem;
	if( $gBitSystem->isPackageActive( 'wiki' ) ) {
		require_once( WIKI_PKG_PATH.'BitPage.php' );
		//find random wiki page
		global $wikilib;
		$cant=$gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_pages`",array());
		if($cant>0) {
			$query="select `content_id` from `".BIT_DB_PREFIX."tiki_pages`";
    		if( $conId=$gBitSystem->mDb->getOne($query,array(),1,rand(0,$cant-1)) ) {
				refresh_index_wiki( $conId );
			}
		}
	}
}


function refresh_index_wiki( $pContentId ) {
	global $gBitSystem;
	if( $gBitSystem->isPackageActive( 'wiki' ) ) {
		require_once( WIKI_PKG_PATH.'BitPage.php' );
		$indexPage = new BitPage( NULL, $pContentId );
		if( $indexPage->load() ) {
			$pdata = $indexPage->parseData();
			$pdata.=" ".$indexPage->parseData( $indexPage->mInfo["description"] );
			$words=&search_index( $pdata );
			insert_index( $words, BITPAGE_CONTENT_TYPE_GUID, $pContentId );
		}
	}
}


function random_refresh_index_blogs() {
	global $gBitSystem;
	if( $gBitSystem->isPackageActive( 'blogs' ) ) {
		require_once( BLOGS_PKG_PATH.'BitBlog.php' );
		// get random blog
		$cant=$gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_blogs`",array());
		if($cant>0) {
			$query="select tb.*, uu.`login` as `user`, uu.`real_name` from `".BIT_DB_PREFIX."tiki_blogs` tb, `".BIT_DB_PREFIX."users_users` uu WHERE uu.`user_id` = tb.`user_id`";
			$result=$gBitSystem->mDb->query($query,array(),1,rand(0,$cant-1));
			$res=$result->fetchRow();
			$words=&search_index($res["title"]." ".$res["user"]." ".$res["description"]);
			insert_index($words, BITBLOG_CONTENT_TYPE_GUID, $res["blog_id"]);
		}
	}
}


function random_refresh_index_blog_posts() {
	global $gBitSystem;
	if( $gBitSystem->isPackageActive( 'blogs' ) ) {
		require_once( BLOGS_PKG_PATH.'BitBlogPost.php' );
		// get random blog
		$cant=$gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_blog_posts`",array());
		if($cant>0) {
			$query="SELECT tbp.*, tc.*, uu.`login` as `user`, uu.`real_name`
					FROM `".BIT_DB_PREFIX."tiki_blog_posts` tbp, `".BIT_DB_PREFIX."tiki_content` tc, `".BIT_DB_PREFIX."users_users` uu
					WHERE tbp.`content_id`=tc.`content_id` AND uu.`user_id` = tc.`user_id`";
			$result=$gBitSystem->mDb->query($query,array(),1,rand(0,$cant-1));
			$res=$result->fetchRow();
			$words=&search_index($res["title"]." ".$res["user"]." ".$res["data"]);
			insert_index($words, BITBLOGPOST_CONTENT_TYPE_GUID, $res["content_id"]);
		}
	}
}


function random_refresh_index_articles() {
	global $gBitSystem;
	if( $gBitSystem->isPackageActive( 'articles' ) ) {
		require_once( ARTICLES_PKG_PATH.'BitArticle.php' );
		// get random article
		$cant=$gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_articles`",array());
		if($cant>0 && !empty($res)) {
			$query="select * from `".BIT_DB_PREFIX."tiki_articles`";
			$result=$gBitSystem->mDb->query($query,array(),1,rand(0,$cant-1));
			$res=$result->fetchRow();
			$words=&search_index($res["title"]." ".$res["author_name"]." ".$res["heading"]." ".$res["body"]." ".$res["author"]);
			insert_index($words,'article',$res["article_id"]);
		}
	}
}


function random_refresh_index_dir_cats() {
  global $gBitSystem;
  // get random directory ctegory
  $cant=$gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_directory_categories`",array());
  if($cant>0) {
    $query="select * from `".BIT_DB_PREFIX."tiki_directory_categories`";
    $result=$gBitSystem->mDb->query($query,array(),1,rand(0,$cant-1));
    $res=$result->fetchRow();
    $words=&search_index($res["name"]." ".$res["description"]);
    insert_index($words,'dir_cat',$res["category_id"]);
  }
}

function random_refresh_index_dir_sites() {
  global $gBitSystem;
  // get random directory ctegory
  $cant=$gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_directory_sites`",array());
  if($cant>0) {
    $query="select * from `".BIT_DB_PREFIX."tiki_directory_sites`";
    $result=$gBitSystem->mDb->query($query,array(),1,rand(0,$cant-1));
    $res=$result->fetchRow();
    $words=&search_index($res["name"]." ".$res["description"]);
    insert_index($words,'dir_site',$res["site_id"]);
  }
}

function random_refresh_index_faqs() {
  global $gBitSystem;
  // get random faq
  $cant=$gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_faqs`",array());
  if($cant>0) {
    $query="select * from `".BIT_DB_PREFIX."tiki_faqs`";
    $result=$gBitSystem->mDb->query($query,array(),1,rand(0,$cant-1));
    $res=$result->fetchRow();
    $words=&search_index($res["title"]." ".$res["description"]);
    insert_index($words,'faq',$res["faq_id"]);
  }
}

function random_refresh_index_faq_questions() {
  global $gBitSystem;
  // get random faq
  $cant=$gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_faq_questions`",array());
  if($cant>0) {
    $query="select * from `".BIT_DB_PREFIX."tiki_faq_questions`";
    $result=$gBitSystem->mDb->query($query,array(),1,rand(0,$cant-1));
    $res=$result->fetchRow();
    $words=&search_index($res["question"]." ".$res["answer"]);
    insert_index($words,'faq_question',$res["question_id"]);
  }
}

function random_refresh_index_forum() {
  global $gBitSystem;
  // get random forum
  $cant=$gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_forums`",array());
  if($cant>0) {
    $query="select * from `".BIT_DB_PREFIX."tiki_forums`";
    $result=$gBitSystem->mDb->query($query,array(),1,rand(0,$cant-1));
    $res=$result->fetchRow();
    $words=&search_index($res["name"]." ".$res["description"]." ".$res["moderator"]);
    insert_index($words,'forum',$res["forum_id"]);
  }
}

function random_refresh_imggals() {
  global $feature_galleries;
  global $gBitSystem;
  $cant=$gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_galleries`",array());
  if($cant>0) {
    $query="select * from `".BIT_DB_PREFIX."tiki_galleries`";
    $result=$gBitSystem->mDb->query($query,array(),1,rand(0,$cant-1));
    $res=$result->fetchRow();
    $words=&search_index($res["name"]." ".$res["description"]);
    insert_index($words,"imggal",$res["gallery_id"]);
  }
}

function random_refresh_img() {
  global $feature_galleries;
  global $gBitSystem;
  $cant=$gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_images`",array());
  if($cant>0) {
    $query="select * from `".BIT_DB_PREFIX."tiki_images`";
    $result=$gBitSystem->mDb->query($query,array(),1,rand(0,$cant-1));
    $res=$result->fetchRow();
    $words=&search_index($res["name"]." ".$res["description"]);
    insert_index($words,"img",$res["image_id"]);
  }
}

function random_refresh_index_trackers() {
  global $gBitSystem;
  $cant=$gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_trackers`",array());
  if($cant>0) {
    $query="select * from `".BIT_DB_PREFIX."tiki_trackers`";
    $result=$gBitSystem->mDb->query($query,array(),1,rand(0,$cant-1));
    $res=$result->fetchRow();
    $words=&search_index($res["name"]." ".$res["description"]);
    insert_index($words,'tracker',$res["tracker_id"]);
  }
}

function random_refresh_index_tracker_items() {
  global $gBitSystem;
  $cant=$gBitSystem->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_tracker_item_fields` f, `".BIT_DB_PREFIX."tiki_tracker_fields` tf
	where tf.`type` in (?,?) and tf.`field_id`=f.`field_id`",array("t","a"));
  if($cant>0) {
    $query="select f.`value`, f.`item_id`
	from `".BIT_DB_PREFIX."tiki_tracker_item_fields` f, `".BIT_DB_PREFIX."tiki_tracker_fields` tf
	where tf.`type` in (?,?) and tf.`field_id`=f.`field_id`";
    $result=$gBitSystem->mDb->query($query,array("t","a"),1,rand(0,$cant-1));
    $res=$result->fetchRow();
    $words=&search_index($res["value"]);
    insert_index($words,'trackeritem',$res["item_id"]);
  }
}


function refresh_index_oldest(){
  global $gBitSystem;
  $min = $gBitSystem->mDb->getOne("select min(`last_update`) from `".BIT_DB_PREFIX."tiki_searchindex`",array());
  if ( !empty( $min ) )
  { $result = $gBitSystem->mDb->query("select `location`,`content_id` from `".BIT_DB_PREFIX."tiki_searchindex` where `last_update`=?",array($min),1);
    $res = $result->fetchRow();
    switch($res["location"]) {
      case "wiki":
        refresh_index_wiki($res["content_id"]);
        break;
      case "forum":
        refresh_index_forum($res["content_id"]);
        break;
      case "trackers":
        refresh_index_trackers($res["content_id"]);
        break;
    }
  }
}

function refresh_index_forum( $pContentId ) {

}

function refresh_index_trackers( $pContentId ) {

}

function &search_index($data) {
  $data=strip_tags($data);
  // split into words
  $sstrings=preg_split("/[\W]+/",$data,-1,PREG_SPLIT_NO_EMPTY);
  // count words
  $words=array();
  foreach ($sstrings as $key=>$value) {
    if(!isset($words[strtolower($value)]))
      $words[strtolower($value)]=0;
    $words[strtolower($value)]++;
  }

  return($words);
}

function insert_index( &$words, $location, $pContentId ) {
  global $gBitSystem;
  if( !empty( $pContentId ) ) {
	  $query="delete from `".BIT_DB_PREFIX."tiki_searchindex` where `location`=? and `content_id`=?";
	  $gBitSystem->mDb->query($query,array($location,$pContentId ));

	  $now= $gBitSystem->getUTCTime();

	  foreach ($words as $key=>$value) {
		if (strlen($key)>3) {//todo: make min length configurable
		  // todo: stopwords
		  $query="insert into `".BIT_DB_PREFIX."tiki_searchindex`
				  (`location`,`content_id`,`searchword`,`count`,`last_update`) values (?,?,?,?,?)";
		  $gBitSystem->mDb->query($query,array($location,$pContentId,$key,(int) $value,$now));
		}
	  }
	}
}

?>
