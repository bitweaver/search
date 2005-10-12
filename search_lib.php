<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/search_lib.php,v 1.4 2005/10/12 15:13:54 spiderr Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: search_lib.php,v 1.4 2005/10/12 15:13:54 spiderr Exp $
 * @author  Luis Argerich (lrargerich@yahoo.com)
 * @package search
 */
 
/**
 * @package search
 * @subpackage SearchLib
 */
class SearchLib extends BitBase {
	function SearchLib() {
		BitBase::BitBase();
		$this->wordlist_cache = array(); // for caching queries to the LRU-cache-list.
	}

	function register_search($words) {
		$words = strtolower($words);
		$words = addslashes($words);

		$words = preg_split("/\s/", $words);

		foreach ($words as $word) {
			$word = trim($word);

			$cant = $this->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_search_stats` where `term`=?",array($word));

			if ($cant) {
				$query = "update `".BIT_DB_PREFIX."tiki_search_stats` set `hits`= `hits` + 1 where `term`=?";
			} else {
				$query = "insert into `".BIT_DB_PREFIX."tiki_search_stats` (`term`,`hits`) values (?,1)";
			}

			$result = $this->mDb->query($query,array($word));
		}
	}

	function &find($where,$words,$offset, $maxRecords) {
		$words = strtolower($words);
		$exact=$this->find_exact($where,$words,$offset, $maxRecords);
		$part=$this->find_part($where,$words,$offset, $maxRecords);
		if ( $exact["cant"] > 0 )
		{	foreach ($part["data"] as $p) {
				$same = false;
				foreach ($exact["data"] as $e) {
					if ($p["results_key"] == $e["results_key"]) {
						$same = true;
						break;
					}
				}
				if (!$same) {
					array_push($exact["data"], $p);
					$exact["cant"]++;
				}	
			}
		}
		$res=$exact;
//		$res=array();
//		$res["data"]=array_merge($exact["data"],$part["data"]);
//		$res["cant"]=$exact["cant"]+$part["cant"];
		return $res;
	}


	function &find_part($where,$words,$offset, $maxRecords) {
		$words=preg_split("/[\W]+/",$words,-1,PREG_SPLIT_NO_EMPTY);
		if (count($words)>0) {
			switch($where) {
				case "bitcomment":
				  return $this->find_part_bitcomment($words,$offset, $maxRecords);
				  break;
				case "wikis":
				  return $this->find_part_wiki($words,$offset, $maxRecords);
				  break;
				case "bitforums":
				  return $this->find_part_forums($words,$offset, $maxRecords);
				  break;
				case "articles":
				  return $this->find_part_articles($words,$offset, $maxRecords);
				  break;
				case "blogs":
				  return $this->find_part_blogs($words,$offset, $maxRecords);
				  break;
				case "posts":
				  return $this->find_part_blog_posts($words,$offset, $maxRecords);
				  break;
				case "faqs":
				  return $this->find_part_faqs($words,$offset, $maxRecords);
				  break;
				case "directory":
				  return $this->find_part_directory($words,$offset, $maxRecords);
				  break;
				case "galleries":
				  return $this->find_part_imggals($words,$offset, $maxRecords);
				  break;
				case "images":
				  return $this->find_part_img($words,$offset, $maxRecords);
				  break;
				case "trackers":
				  return $this->find_part_trackers($words,$offset, $maxRecords);
				  break;

				default:
				  return $this->find_part_all($words,$offset, $maxRecords);
				  break;
			}
		}
	}

	function &refresh_lru_wordlist($syllable) {
		global $search_max_syllwords;
		global $search_lru_length;
		global $search_lru_purge_rate;
		// delete from wordlist and lru list
		$this->mDb->query("delete from `".BIT_DB_PREFIX."tiki_searchwords` where `syllable`=?",array($syllable),-1,-1);
		$this->mDb->query("delete from `".BIT_DB_PREFIX."tiki_searchsyllable` where `syllable`=?",array($syllable),-1,-1);
		// search the searchindex - can take long time
		$ret=array();
		if (!isset($search_max_syllwords))
			$search_max_syllwords = 100;
		$query="select `searchword`, sum(`count`) as `cnt` from `".BIT_DB_PREFIX."tiki_searchindex`
			where `searchword` like ? group by `searchword` ORDER BY 2 desc";
		$result=$this->mDb->query($query,array('%'.$syllable.'%'),$search_max_syllwords); // search_max_syllwords: how many different searchwords that contain the syllable are taken into account?. Sortet by number of occurences.
		while ($res = $result->fetchRow()) {
			$ret[]=$res["searchword"];
		}
		// cache this long running query
		foreach($ret as $searchword) {
			$this->mDb->query("insert into `".BIT_DB_PREFIX."tiki_searchwords` (`syllable`,`searchword`) values (?,?)",array($syllable,$searchword),-1,-1);
			}
		// set lru list parameters
		$now=time();
		$this->mDb->query("insert into `".BIT_DB_PREFIX."tiki_searchsyllable`(`syllable`,`last_used`,`last_updated`) values (?,?,?)",
			array($syllable,(int) $now,(int) $now));

		// at random rate: check length of lru list and purge these that
		// have not been used for long time. This is what a lru list
		// basically does
		list($usec, $sec) = explode(" ",microtime());
		srand (ceil($sec+100*$usec));
		if(rand(1,$search_lru_purge_rate)==1) {
			$lrulength=$this->mDb->getOne("select count(*) from `".BIT_DB_PREFIX."tiki_searchsyllable`",array());
			if ($lrulength > $search_lru_length) { // only purge if lru list is long.
				//purge oldest
				$diff=$lrulength-$search_lru_length;
				$oldwords=array();
				$query="select `syllable` from `".BIT_DB_PREFIX."tiki_searchsyllable` ORDER BY `last_used` asc";
				$result=$this->mDb->query($query,array(),$diff);
				while ($res = $result->fetchRow()) {
					//we probably cannot delete now. to avoid database deadlocks
					//we save the words and delete later
					$oldwords[]=$res["syllable"];
				}
				foreach($oldwords as $oldword) {
					$this->mDb->query("delete from `".BIT_DB_PREFIX."tiki_searchwords` where `syllable`=?",array($oldword),-1,-1);
					$this->mDb->query("delete from `".BIT_DB_PREFIX."tiki_searchsyllable` where `syllable`=?",array($oldword),-1,-1);
				}

			}
		}
		return $ret;
	}

	function &get_lru_wordlist($syllable) {
		if(!isset($this->wordlist_cache[$syllable])) {
        		$query="select `searchword` from `".BIT_DB_PREFIX."tiki_searchwords` where `syllable`=?";
        		$result=$this->mDb->query($query,array($syllable));
        		while ($res = $result->fetchRow()) {
        			$this->wordlist_cache[$syllable][]=$res["searchword"];
        		}
		}
		return $this->wordlist_cache[$syllable];
	}

	function &get_wordlist_from_syllables($syllables) {
		$ret=array();
		global $search_syll_age;
		foreach($syllables as $syllable) {
		  //Have a look at the lru list (tiki_searchsyllable)
		  $bindvars=array($syllable);
		  $age=time()-$this->mDb->getOne("select `last_updated` from `".BIT_DB_PREFIX."tiki_searchsyllable` where `syllable`=?",$bindvars);
		  if(!$age || $age>($search_syll_age*3600)) {// older than search_syll_age hours
		  	$a=$this->refresh_lru_wordlist($syllable);
			$ret=array_merge($ret,$a);
		  } else {

		  	// get wordlist
		  	if (is_array($this->get_lru_wordlist($syllable)))
			  $ret=array_merge($ret,$this->get_lru_wordlist($syllable));
		  }

		  // update lru list status
		  $now=time();
		  $this->mDb->query("update `".BIT_DB_PREFIX."tiki_searchsyllable` set `last_used`=? where `syllable`=?",array((int) $now,$syllable));
		}
		return $ret;
	}

	function &find_part_bitcomment($words,$offset, $maxRecords) {
		return $this->find_exact_bitcomment($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function &find_part_wiki($words,$offset, $maxRecords) {
		return $this->find_exact_wiki($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function &find_part_articles($words,$offset, $maxRecords) {
		return $this->find_exact_articles($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function &find_part_forums($words,$offset, $maxRecords) {
		return $this->find_exact_forums($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function &find_part_blogs($words,$offset, $maxRecords) {
		return $this->find_exact_blogs($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function &find_part_blog_posts($words,$offset, $maxRecords) {
		return $this->find_exact_blog_posts($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function &find_part_faqs($words,$offset, $maxRecords) {
		return $this->find_exact_faqs($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function &find_part_directory($words,$offset, $maxRecords) {
		return $this->find_exact_directory($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function &find_part_imggals($words,$offset, $maxRecords) {
		return $this->find_exact_imggals($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function &find_part_img($words,$offset, $maxRecords) {
		return $this->find_exact_img($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function &find_part_trackers($words,$offset, $maxRecords) {
		return $this->find_exact_trackers($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function &find_part_all($words,$offset, $maxRecords) {
		global $gBitSystem;
		$commentresults["data"] = array( );
		$wikiresults["data"] = array();
		$artresults["data"] = array();
		$blogresults["data"] = array();
		$blogpostsresults["data"] = array();
		$cant = 0;
		if( $gBitSystem->isPackageActive( 'bitcomment' ) ) {
			$commentresults=$this->find_part_bitcomment($words,$offset, $maxRecords);
			$cant += $commentresults["cant"];
		} 
		if( $gBitSystem->isPackageActive( 'wiki' ) ) {
			$wikiresults=$this->find_part_wiki($words,$offset, $maxRecords);
			$cant += $wikiresults["cant"];
		}
		if( $gBitSystem->isPackageActive( 'articles' ) ) {
			$artresults=$this->find_part_articles($words,$offset, $maxRecords);
			$cant += $artresults["cant"];
		}
		if( $gBitSystem->isPackageActive( 'bitforums' ) ) {
			$forumresults=$this->find_part_forums($words,$offset, $maxRecords);
			$cant += $forumresults["cant"];
		}
		if( $gBitSystem->isPackageActive( 'blogs' ) ) {
			$blogresults=$this->find_part_blogs($words,$offset, $maxRecords);
			$blogpostsresults=$this->find_part_blog_posts($words,$offset, $maxRecords);
			$cant += $blogresults["cant"] + $blogpostsresults["cant"];
		}
		if( $gBitSystem->isPackageActive( 'faqs' ) ) {
			$faqresults=$this->find_part_faqs($words,$offset, $maxRecords);
			$cant += $faqresults["cant"];
		}
		if( $gBitSystem->isPackageActive( 'directory' ) ) {
			$dirresults=$this->find_part_directory($words,$offset, $maxRecords);
			$cant += $dirresults["cant"];
		}
		if( $gBitSystem->isPackageActive( 'imagegals' ) ) {
			$imggalsresults=$this->find_part_imggals($words,$offset, $maxRecords);
			$imgresults=$this->find_part_img($words,$offset, $maxRecords);
			$cant += $imgresults["cant"]+$imggalsresults["cant"];
		}
		if( $gBitSystem->isPackageActive( 'trackers' ) ) {
			$trackerresults=$this->find_part_trackers($words,$offset, $maxRecords);
			$cant += $trackerresults["cant"];
		}

		//merge the results, use @ to silence the warnings
		$res=array();
		$res["data"] = @array_merge($commentresults["data"],$wikiresults["data"]
			,$artresults["data"]
			,$blogresults["data"],$blogpostsresults["data"]
//			,$forumresults["data"]
//			,$faqresults["data"]
//			,$dirresults["data"]
//			,$imggalsresults["data"]
//			,$imgresults["data"]
//			,$trackerresults["data"]
			);
		$res["cant"] = $cant;
		return ($res);
	}

	function &find_exact($where,$words,$offset, $maxRecords) {
		$words=preg_split("/[\W]+/",$words,-1,PREG_SPLIT_NO_EMPTY);
		if (count($words)>0) {
			switch($where) {
			case "bitcomment":
			  return $this->find_exact_bitcomment($words,$offset, $maxRecords);
			  break;
			case "wikis":
			  return $this->find_exact_wiki($words,$offset, $maxRecords);
			  break;
			case "forums":
			  return $this->find_exact_forums($words,$offset, $maxRecords);
			  break;
			case "articles":
			  return $this->find_exact_articles($words,$offset, $maxRecords);
			  break;
			case "blogs":
			  return $this->find_exact_blogs($words,$offset, $maxRecords);
			  break;
			case "posts":
			  return $this->find_exact_blog_posts($words,$offset, $maxRecords);
			  break;
			case "faqs":
			  return $this->find_exact_faqs($words,$offset, $maxRecords);
			  break;
			case "directory":
			  return $this->find_exact_directory($words,$offset, $maxRecords);
			  break;
			case "galleries":
			  return $this->find_exact_imggals($words,$offset, $maxRecords);
			  break;
			case "images":
			  return $this->find_exact_img($words,$offset, $maxRecords);
			  break;
			case "trackers":
			  return $this->find_exact_trackers($words,$offset, $maxRecords);
			  break;

			default:
			  return $this->find_exact_all($words,$offset, $maxRecords);
			  break;
			}
		}
	}

	function &find_exact_all($words,$offset, $maxRecords) {
		global $gBitSystem;
		$commentresults=$this->find_exact_bitcomment($words,$offset, $maxRecords);
		$wikiresults["data"] = array();
		$artresults["data"] = array();
		$blogresults["data"] = array();
		$blogpostsresults["data"] = array();
		if( $gBitSystem->isPackageActive( 'wiki' ) ) {
			$wikiresults=$this->find_exact_wiki($words,$offset, $maxRecords);
		}
		if( $gBitSystem->isPackageActive( 'articles' ) ) {
			$artresults=$this->find_exact_articles($words,$offset, $maxRecords);
		}
		if( $gBitSystem->isPackageActive( 'bitforums' ) ) {
			$forumresults=$this->find_exact_forums($words,$offset, $maxRecords);
		}
		if( $gBitSystem->isPackageActive( 'blogs' ) ) {
			$blogresults=$this->find_exact_blogs($words,$offset, $maxRecords);
			$blogpostsresults=$this->find_exact_blog_posts($words,$offset, $maxRecords);
		}
		if( $gBitSystem->isPackageActive( 'faqs' ) ) {
			$faqresults=$this->find_exact_faqs($words,$offset, $maxRecords);
		}
		if( $gBitSystem->isPackageActive( 'directory' ) ) {
			$dirresults=$this->find_exact_directory($words,$offset, $maxRecords);
		}
		if( $gBitSystem->isPackageActive( 'imagegals' ) ) {
			$imggalsresults=$this->find_exact_imggals($words,$offset, $maxRecords);
			$imgresults=$this->find_exact_img($words,$offset, $maxRecords);
		}
		if( $gBitSystem->isPackageActive( 'trackers' ) ) {
			$trackerresults=$this->find_exact_trackers($words,$offset, $maxRecords);
		}

		//merge the results, use @ to silence the warnings
		$res=array();
		$res["data"]=@array_merge($wikiresults["data"],
			$commentresults["data"],$artresults["data"],
			$blogresults["data"],$blogpostsresults["data"]
//			,$faqresults["data"]
//			,$forumresults["data"]
//			,$dirresults["data"]
//			,$imggalsresults["data"]
//			,$imgresults["data"]
//			,$trackerresults["data"]
			);
		$res["cant"]=@($wikiresults["cant"]+$artresults["cant"]+
			$blogresults["cant"]+$blogpostsresults["cant"]
//			+$faqresults["cant"]+
//			+$forumresults["cant"]
//			+$dirresults["cant"]
//			+$imggalsresults["cant"]+
//			+$imgresults["cant"]
//			+$trackerresults["cant"]
			);
		return ($res);
	}

	function &find_exact_blogs($words,$offset, $maxRecords) {
	  global $gBitSystem;
	  $ret =  array('data' => array(),'cant' => 0);
	  if ($gBitSystem->isPackageActive( 'blogs' ) && count($words) >0) {
		require_once( BLOGS_PKG_PATH.'BitBlog.php' ); // Make sure the CONTENT_TYPE_GUID is defined
		$query="select s.`content_id` || s.`location` AS `results_key`, s.`location`, s.`last_update`, s.`count`,
			b.`description`,b.`hits`,b.`last_modified`,b.`title`, b.`blog_id`
			FROM `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_blogs` b ON ( b.`blog_id`=s.`content_id` )
			WHERE lower(`searchword`) in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='".BITBLOG_CONTENT_TYPE_GUID."'
			ORDER BY `hits` desc";
		$result=$this->mDb->query($query,$words,$maxRecords,$offset);
		$querycant="select count(*) from `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_blogs` b ON ( b.`blog_id`=s.`content_id` ) WHERE `searchword` in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='".BITBLOG_CONTENT_TYPE_GUID."' and s.`content_id`=b.`blog_id`";
		$cant=$this->mDb->getOne($querycant,$words);
		$ret=array();
		while ($res = $result->fetchRow()) {
		  $res['href'] = BitBlog::getBlogUrl( $res["blog_id"] );
		  $ret[] = $res;
		}
		$ret = array('data' => $ret,'cant' => $cant);
	  }
	  return $ret;
	}

	function &find_exact_blog_posts($words,$offset, $maxRecords) {
		global $gBitSystem;
		if ($gBitSystem->isPackageActive( 'blogs' ) && count($words) >0) {
			require_once( BLOGS_PKG_PATH.'BitBlogPost.php' ); // Make sure the CONTENT_TYPE_GUID is defined
			$query="SELECT s.`content_id` || s.`location` AS `results_key`, tc.`title`, tc.`format_guid`, s.`location`, s.`last_update`, s.`count`, tc.`data`,tc.`hits`,b.`title` as `btitle`, tc.`created`,tc.`title`, tc.`format_guid`, tc.`format_guid`,b.`blog_id`,bp.`post_id`
					FROM `".BIT_DB_PREFIX."tiki_searchindex` s
						INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` )
						INNER JOIN `".BIT_DB_PREFIX."tiki_blog_posts` bp ON  ( tc.`content_id`=bp.`content_id` )
						INNER JOIN `".BIT_DB_PREFIX."tiki_blogs` b ON( bp.`blog_id`=b.`blog_id` )
					WHERE lower(`searchword`) in (".implode(',',array_fill(0,count($words),'?')).") and s.`location`='".BITBLOGPOST_CONTENT_TYPE_GUID."'
					ORDER BY `hits` desc";
			$result=$this->mDb->query($query,$words,$maxRecords,$offset);
			$querycant="select count(*) from `".BIT_DB_PREFIX."tiki_searchindex` s where `searchword` in
				(".implode(',',array_fill(0,count($words),'?')).") and
				s.`location`='".BITBLOGPOST_CONTENT_TYPE_GUID."'";
			$cant=$this->mDb->getOne($querycant,$words);
			$ret=array();
			while ($res = $result->fetchRow()) {
				if( empty( $res['title'] ) ) {
					$res['title'] = $res['btitle'];
				}
		  		$res['href'] = BitBlogPost::getDisplayUrl( $res["post_id"] );
				$ret[] = $res;
			}
			return array('data' => $ret,'cant' => $cant);
		} else {
			return array('data' => array(),'cant' => 0);
		}
	}

	function &find_exact_articles($words,$offset, $maxRecords) {
      global $gBitSystem;
	  if ($gBitSystem->isPackageActive( 'articles' )  && count($words) >0) {
		require_once( ARTICLES_PKG_PATH.'BitArticle.php' ); // Make sure the CONTENT_TYPE_GUID is defined
	    $query="select s.`content_id` || s.`location` AS `results_key`, tc.`title`, tc.`format_guid`, tc.`format_guid`, s.`location`, s.`last_update`, s.`count`,
	    	a.`description`,tc.`hits`,a.`publish_date` from
		`".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_articles` a where lower(`searchword`) in
		(".implode(',',array_fill(0,count($words),'?')).") and
		s.`location`='article' and
		".$this->mDb->sql_cast("tc.`title`","int")."=a.`article_id` ORDER BY tc.`hits` desc";
	    $result=$this->mDb->query($query,$words,$maxRecords,$offset);
            $querycant="select count(*) from `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_articles` a where `searchword` in
	     	(".implode(',',array_fill(0,count($words),'?')).") and
		s.`location`='".BITARTICLE_CONTENT_TYPE_GUID."' and
		".$this->mDb->sql_cast("tc.`title`","int")."=a.`article_id`";
	    $cant=$this->mDb->getOne($querycant,$words);
	    $ret=array();
	    while ($res = $result->fetchRow()) {
	      $res['href'] = ARTICLES_PKG_URL."read.php?article_id=".urlencode($res["page"]);
	      $ret[] = $res;
	    }
	    return array('data' => $ret,'cant' => $cant);
	  } else {
	    return array('data' => array(),'cant' => 0);
	  }
	}

	function &find_exact_wiki($words,$offset, $maxRecords) {
		global $gPage;
		global $gBitSystem;
		if ($gBitSystem->isPackageActive( 'wiki' ) && count($words) >0) {
			require_once( WIKI_PKG_PATH.'BitPage.php' ); // Make sure the CONTENT_TYPE_GUID is defined
			$query="SELECT s.`content_id` || s.`location` AS `results_key`, tc.`title`, tc.`format_guid`, tc.`format_guid`, s.`location`, s.`last_update`, s.`count`, tc.`data`, tc.`hits`, tc.`last_modified`, p.`page_id`
			  FROM `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ) INNER JOIN `".BIT_DB_PREFIX."tiki_pages` p ON ( tc.`content_id`=p.`content_id` )
			  WHERE lower(`searchword`) in (".implode(',',array_fill(0,count($words),'?')).") and s.`location`='".BITPAGE_CONTENT_TYPE_GUID."'
			  ORDER BY `count` desc";
			$result=$this->mDb->query($query,$words,$maxRecords,$offset);

			$querycant="SELECT count(*) FROM `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_pages` p
				  WHERE lower(`searchword`) in (".implode(',',array_fill(0,count($words),'?')).") and s.`location`='".BITPAGE_CONTENT_TYPE_GUID."' and tc.`content_id`=p.`content_id`";
			$cant=$this->mDb->getOne($querycant,$words);

			$ret=array();
			while ($res = $result->fetchRow()) {
				$res['href'] = BitPage::getDisplayUrl( $res['title'], $res );
				$ret[] = $res;
			}

			return array('data' => $ret,'cant' => $cant);
		} else {
			return array('data' => array(),'cant' => 0);
		}
	}

	function &find_exact_bitcomment($words,$offset, $maxRecords) {
		global $gPage;
		global $gBitSystem;
		if ($gBitSystem->isPackageActive( 'wiki' ) && count($words) >0) {
			require_once( LIBERTY_PKG_PATH.'LibertyComment.php' ); // Make sure the CONTENT_TYPE_GUID is defined
			$query="SELECT s.`content_id` || s.`location` AS `results_key`, tc.`title`, tc.`format_guid`, tc.`format_guid`, s.`location`, s.`last_update`, s.`count`, tc.`data`, tc.`hits`, tc.`last_modified`, tcc.*
			  FROM `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ) INNER JOIN `".BIT_DB_PREFIX."tiki_comments` tcc ON ( tc.`content_id`=tcc.`content_id` )
			  WHERE lower(`searchword`) in (".implode(',',array_fill(0,count($words),'?')).") and s.`location`='".BITCOMMENT_CONTENT_TYPE_GUID."'
			  ORDER BY `count` desc";
			$result=$this->mDb->query($query,$words,$maxRecords,$offset);

			$querycant="SELECT count(*) FROM `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_pages` p
				  WHERE lower(`searchword`) in (".implode(',',array_fill(0,count($words),'?')).") and s.`location`='".BITCOMMENT_CONTENT_TYPE_GUID."' and tc.`content_id`=p.`content_id`";
			$cant=$this->mDb->getOne($querycant,$words);

			$ret=array();
			while ($res = $result->fetchRow()) {
				$res['href'] = LibertyComment::getDisplayUrl( $res['title'], $res );
				$ret[] = $res;
			}

			return array('data' => $ret,'cant' => $cant);
		} else {
			return array('data' => array(),'cant' => 0);
		}
	}

/*
	function &find_exact_trackers($words,$offset, $maxRecords) {
	  global $gBitSystem;
	  if ($gBitSystem->isPackageActive( 'trackers' ) && count($words) >0 ) {
		$query="select s.`content_id` || s.`location` AS `results_key`, tc.`title`, tc.`format_guid`, tc.`format_guid`, s.`location`, s.`last_update`, s.`count`,
			t.`description`,t.`last_modified`,t.`name` from
			`".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_trackers` t where lower(`searchword`) in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='tracker' and
			".$this->mDb->sql_cast("tc.`title`","int")."=t.`tracker_id`";
		$result=$this->mDb->query($query,$words,$maxRecords,$offset);
		$querycant="select count(*) from `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_trackers` t where `searchword` in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='tracker' and
			".$this->mDb->sql_cast("tc.`title`","int")."=t.`tracker_id`";
		$cant1=$this->mDb->getOne($querycant,$words);
		$ret1=array();
		while ($res = $result->fetchRow()) {
		  $href = TRACKERS_PKG_URL."view_tracker.php?tracker_id=".urlencode($res["page"]);
		  $ret1[] = array(
			'title' => $res["name"],
			'location' => tra("Tracker"),
			'data' => substr($res["description"],0,250),
			'hits' => tra("Unknown"),
			'last_modified' => $res["last_modified"],
			'href' => $href,
			'relevance' => 1
		  );
		}

	//tracker items
	$ret2=array();
	$cant2=0;
	if ($cant1 < $offset+$maxRecords) {

	  //new offset and maxRecords
	  $offset-=$cant1;
	  if ($offset < 0) {
		$maxRecords+=$offset;
	$offset=0;
	  }

	  $query="select s.`content_id` || s.`location` AS `results_key`, tc.`title`, tc.`format_guid`, tc.`format_guid`, s.`location`, s.`last_update`, s.`count`,
		  t.`last_modified`,t.`tracker_id` from
	  `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_tracker_items` t where lower(`searchword`) in
	  (".implode(',',array_fill(0,count($words),'?')).") and
	  s.`location`='trackeritem' and
	  ".$this->mDb->sql_cast("tc.`title`","int")."=t.`item_id`";
	  $result=$this->mDb->query($query,$words,$maxRecords,$offset);
	  $querycant="select count(*) from `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_tracker_items` t where `searchword` in
		  (".implode(',',array_fill(0,count($words),'?')).") and
	  s.`location`='trackeritem' and
	  ".$this->mDb->sql_cast("tc.`title`","int")."=t.`item_id`";
	  $cant2=$this->mDb->getOne($querycant,$words);
	  while ($res = $result->fetchRow()) {
		$href = TRACKERS_PKG_URL."view_tracker_item.php?tracker_id=".urlencode($res["tracker_id"])."&amp;item_id=".urlencode($res["page"]);
		$ret2[] = array(
		  'title' => $res["page"],
	  'location' => tra("Trackeritem"),
	  'data' => tra("Unknown"),
	  'hits' => tra("Unknown"),
	  'last_modified' => $res["last_modified"],
	  'href' => $href,
	  'relevance' => 1
		);
	  }
	}
	$ret=array();
	$ret["data"]=array_merge($ret1,$ret2);
	$ret["cant"]=$cant1+$cant2;
	return $ret;

	  } else {
		return array('data' => array(),'cant' => 0);
	  }
	}



	function &find_exact_imggals($words,$offset, $maxRecords) {
	  global $gBitSystem;
	  if ($gBitSystem->isPackageActive( 'imagegals' )  && count($words) >0) {
		$query="select s.`content_id` || s.`location` AS `results_key`, tc.`title`, tc.`format_guid`, tc.`format_guid`, s.`location`, s.`last_update`, s.`count`,
			g.`description`,g.`hits`,g.`last_modified`,g.`name` from
			`".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_galleries` g where lower(`searchword`) in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='imggal' and
			".$this->mDb->sql_cast("tc.`title`","int")."=g.`gallery_id` ORDER BY `hits` desc";
		$result=$this->mDb->query($query,$words,$maxRecords,$offset);
		$querycant="select count(*) from `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_galleries` g where `searchword` in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='imggal' and
			".$this->mDb->sql_cast("tc.`title`","int")."=g.`gallery_id`";
		$cant=$this->mDb->getOne($querycant,$words);
		$ret=array();
		while ($res = $result->fetchRow()) {
		  $href = IMAGEGALS_PKG_URL."browse_gallery.php?gallery_id=".urlencode($res["page"]);
		  $ret[] = array(
			'title' => $res["name"],
			'location' => tra("Image Gallery"),
			'data' => substr($res["description"],0,250),
			'hits' => $res["hits"],
			'last_modified' => $res["last_modified"],
			'href' => $href,
			'relevance' => $res["hits"]
		  );
		}
		return array('data' => $ret,'cant' => $cant);
	  } else {
		return array('data' => array(),'cant' => 0);
	  }
	}

	function &find_exact_img($words,$offset, $maxRecords) {
	  global $gBitSystem;
	  if ($gBitSystem->isPackageActive( 'imagegals' ) && count($words) >0) {
		$query="select s.`content_id` || s.`location` AS `results_key`, tc.`title`, tc.`format_guid`, tc.`format_guid`, s.`location`, s.`last_update`, s.`count`,
			g.`description`,g.`hits`,g.`created`,g.`name` from
			`".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_images` g where lower(`searchword`) in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='img' and
			".$this->mDb->sql_cast("tc.`title`","int")."=g.`image_id` ORDER BY `hits` desc";
		$result=$this->mDb->query($query,$words,$maxRecords,$offset);
		$querycant="select count(*) from `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_images` g where `searchword` in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='img' and
			".$this->mDb->sql_cast("tc.`title`","int")."=g.`image_id`";
		$cant=$this->mDb->getOne($querycant,$words);
		$ret=array();
		while ($res = $result->fetchRow()) {
		  $href = IMAGEGALS_PKG_URL."browse_image.php?image_id=".urlencode($res["page"]);
		  $ret[] = array(
			'title' => $res["name"],
			'location' => tra("Image"),
			'data' => substr($res["description"],0,250),
			'hits' => $res["hits"],
			'last_modified' => $res["created"],
			'href' => $href,
			'relevance' => $res["hits"]
		  );
		}
		return array('data' => $ret,'cant' => $cant);
	  } else {
		return array('data' => array(),'cant' => 0);
	  }
	}

	function &find_exact_directory($words,$offset, $maxRecords) {
	  global $gBitSystem;
	  if ($gBitSystem->isPackageActive( 'directory' ) && count($words) >0) {
		$query="select s.`content_id` || s.`location` AS `results_key`, tc.`title`, tc.`format_guid`, tc.`format_guid`, s.`location`, s.`last_update`, s.`count`,
			d.`description`,d.`hits`,d.`name` from
			`".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_directory_categories` d where lower(`searchword`) in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='dir_cat' and
			".$this->mDb->sql_cast("tc.`title`","int")."=d.`category_id` ORDER BY `hits` desc";
		$result=$this->mDb->query($query,$words,$maxRecords,$offset);
		$querycant="select count(*) from `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_directory_categories` d where `searchword` in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='dir_cat' and
			".$this->mDb->sql_cast("tc.`title`","int")."=d.`category_id`";
		$cant=$this->mDb->getOne($querycant,$words);
		$ret=array();
		while ($res = $result->fetchRow()) {
		  $href = DIRECTORY_PKG_URL."index.php?parent=".urlencode($res["page"]);
		  $ret[] = array(
			'title' => $res["name"],
			'location' => tra("Directory category"),
			'data' => substr($res["description"],0,250),
			'hits' => $res["hits"],
			'last_modified' => time(), //not determinable
			'href' => $href,
			'relevance' => $res["hits"]
		  );
		}
		$dsiteres=$this->find_exact_directory_sites($words,$offset, $maxRecords);
		return array('data' => array_merge($ret,$dsiteres["data"]),'cant' => $cant+$dsiteres["cant"]);
	  } else {
		return array('data' => array(),'cant' => 0);
	  }
	}

	function &find_exact_directory_sites($words,$offset, $maxRecords) {
	  global $gBitSystem;
	  if ($gBitSystem->isPackageActive( 'directory' ) && count($words) >0) {
		$query="select s.`content_id` || s.`location` AS `results_key`, tc.`title`, tc.`format_guid`, tc.`format_guid`, s.`location`, s.`last_update`, s.`count`,
			d.`description`,d.`hits`,d.`name`,d.`last_modified`,cs.`category_id` from
			`".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_directory_sites` d ,`".BIT_DB_PREFIX."tiki_category_sites` cs where lower(`searchword`) in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='dir_site' and
			".$this->mDb->sql_cast("tc.`title`","int")."=d.`site_id` and
	cs.`site_id`=d.`site_id`
	ORDER BY `hits` desc";
		$result=$this->mDb->query($query,$words,$maxRecords,$offset);
		$querycant="select count(*) from `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_directory_sites` d , `".BIT_DB_PREFIX."tiki_category_sites` cs where `searchword` in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='dir_site' and
			".$this->mDb->sql_cast("tc.`title`","int")."=d.`site_id` and
	cs.`site_id`=d.`site_id`";
		$cant=$this->mDb->getOne($querycant,$words);
		$ret=array();
		while ($res = $result->fetchRow()) {
		  $href = DIRECTORY_PKG_URL."index.php?parent=".urlencode($res["category_id"]);
		  $ret[] = array(
			'title' => $res["name"],
			'location' => tra("Directory"),
			'data' => substr($res["description"],0,250),
			'hits' => $res["hits"],
			'last_modified' => $res["last_modified"],
			'href' => $href,
			'relevance' => $res["hits"]
		  );
		}
		return array('data' => $ret,'cant' => $cant);
	  } else {
		return array('data' => array(),'cant' => 0);
	  }
	}


	function &find_exact_faqs($words,$offset, $maxRecords) {
	  global $gBitSystem;
	  if ($gBitSystem->isPackageActive( 'faqs' )  && count($words) >0) {
		$query="select s.`content_id` || s.`location` AS `results_key`, tc.`title`, tc.`format_guid`, tc.`format_guid`, s.`location`, s.`last_update`, s.`count`,
			f.`description`,f.`hits`,f.`created`,f.`title` from
			`".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_faqs` f where lower(`searchword`) in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='faq' and
			".$this->mDb->sql_cast("tc.`title`","int")."=f.`faq_id` ORDER BY `hits` desc";
		$result=$this->mDb->query($query,$words,$maxRecords,$offset);
		$querycant="select count(*) from `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_faqs` f where `searchword` in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='faq' and
			".$this->mDb->sql_cast("tc.`title`","int")."=f.`faq_id`";
		$cant=$this->mDb->getOne($querycant,$words);
		$ret=array();
		while ($res = $result->fetchRow()) {
		  $href = FAQS_PKG_URL."view.php?faq_id=".urlencode($res["page"]);
		  $ret[] = array(
			'title' => $res["title"],
			'location' => tra("FAQ"),
			'data' => substr($res["description"],0,250),
			'hits' => $res["hits"],
			'last_modified' => $res["created"],
			'href' => $href,
			'relevance' => $res["hits"]
		  );
		}
		$fquesres=$this->find_exact_faqquestions($words,$offset, $maxRecords);
		return array('data' => array_merge($ret,$fquesres["data"]),'cant' => $cant+$fquesres["cant"]);
	  } else {
		return array('data' => array(),'cant' => 0);
	  }
	}

	function &find_exact_faqquestions($words,$offset, $maxRecords) {
	  global $gBitSystem;
	  if ($gBitSystem->isPackageActive( 'faqs' ) && count($words) >0) {
		$query="select s.`content_id` || s.`location` AS `results_key`, tc.`title`, tc.`format_guid`, tc.`format_guid`, s.`location`, s.`last_update`, s.`count`,
			f.`question`,faq.`hits`,faq.`created`,faq.`title`,f.`answer`,f.`faq_id` from
			`".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_faqs` faq, `".BIT_DB_PREFIX."tiki_faq_questions` f where `searchword` in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='faq_question' and
			".$this->mDb->sql_cast("tc.`title`","int")."=f.`question_id` and
	f.`faq_id`=faq.`faq_id` ORDER BY `hits` desc";
		$result=$this->mDb->query($query,$words,$maxRecords,$offset);
		$querycant="select count(*) from `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_faqs` faq, `".BIT_DB_PREFIX."tiki_faq_questions` f  where `searchword` in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='faq_question' and
			".$this->mDb->sql_cast("tc.`title`","int")."=f.`question_id` and
			f.`faq_id`=faq.`faq_id`";
		$cant=$this->mDb->getOne($querycant,$words);
		$ret=array();
		while ($res = $result->fetchRow()) {
		  $href = FAQS_PKG_URL."view.php?faq_id=".urlencode($res["faq_id"])."#".urlencode($res["page"]);
		  $ret[] = array(
			'title' => substr($res["question"],0,40),
			'location' => tra("FAQ")."::".$res["title"],
			'data' => substr($res["answer"],0,250),
			'hits' => $res["hits"],
			'last_modified' => $res["created"],
			'href' => $href,
			'relevance' => $res["hits"]
		  );
		}
		return array('data' => $ret,'cant' => $cant);
	  } else {
		return array('data' => array(),'cant' => 0);
	  }
	}


	function &find_exact_forums($words,$offset, $maxRecords) {
	  global $gBitSystem;
	  if ($gBitSystem->isPackageActive( 'tiki_forums' ) && count($words) >0) {
		$query="select s.`content_id` || s.`location` AS `results_key`, tc.`title`, tc.`format_guid`, tc.`format_guid`, s.`location`, s.`last_update`, s.`count`,
			f.`description`,f.`hits`,f.`last_post`,f.`name` from
			`".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_forums` f where `searchword` in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='forum' and
			".$this->mDb->sql_cast("tc.`title`","int")."=f.`forum_id` ORDER BY `hits` desc";
		$result=$this->mDb->query($query,$words,$maxRecords,$offset);
		$querycant="select count(*) from `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_forums` f where `searchword` in
			(".implode(',',array_fill(0,count($words),'?')).") and
			s.`location`='forum' and
			".$this->mDb->sql_cast("tc.`title`","int")."=f.`forum_id`";
		$cant=$this->mDb->getOne($querycant,$words);
		$ret=array();
		while ($res = $result->fetchRow()) {
		  $href = "tiki-view_forum.php?forum_id=".urlencode($res["page"]);
		  $ret[] = array(
			'title' => $res["name"],
			'location' => tra("Forum"),
			'data' => substr($res["description"],0,250),
			'hits' => $res["hits"],
			'last_modified' => $res["last_post"],
			'href' => $href,
			'relevance' => $res["hits"]
		  );
		}
		$fcommres=$this->find_exact_forumcomments($words,$offset, $maxRecords);
		return array('data' => array_merge($ret,$fcommres["data"]),'cant' => $cant+$fcommres["cant"]);
	  } else {
		return array('data' => array(),'cant' => 0);
	  }
	}

	function &find_exact_forumcomments($words,$offset, $maxRecords) {
          global $gBitSystem;
          if ($gBitSystem->isPackageActive( 'tiki_forums' ) && count($words) >0) {
	  $query="select s.`content_id` || s.`location` AS `results_key`, tc.`title`, tc.`format_guid`, tc.`format_guid`,, s.`location`, s.`last_update`, s.`count`,
	  	f.`data`,f.`hits`,f.`comment_date`,f.`object`,f.`title`,fo.`name` from
		`".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_comments` f,`".BIT_DB_PREFIX."tiki_forums` fo where `searchword` in
		(".implode(',',array_fill(0,count($words),'?')).") and
		s.`location`='forumcomment' and
		".$this->mDb->sql_cast("tc.`title`","int")."=f.`thread_id` and
		fo.`forum_id`=".$this->mDb->sql_cast("f.`object`","int")." ORDER BY `count` desc";
	  $result=$this->mDb->query($query,$words,$maxRecords,$offset);

	  $querycant="select count(*) from `".BIT_DB_PREFIX."tiki_searchindex` s INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON ( tc.`content_id`=s.`content_id` ), `".BIT_DB_PREFIX."tiki_comments` f ,`".BIT_DB_PREFIX."tiki_forums` fo where `searchword` in
	  	(".implode(',',array_fill(0,count($words),'?')).") and
		s.`location`='forumcomment' and
		".$this->mDb->sql_cast("tc.`title`","int")."=f.`thread_id` and
		fo.`forum_id`=".$this->mDb->sql_cast("f.`object`","int")." ORDER BY `count` desc";
	  $cant=$this->mDb->getOne($querycant,$words);
	  $ret=array();
	  while ($res = $result->fetchRow()) {
	    $href = "tiki-view_forum_thread.php?comments_parent_id=".urlencode($res["page"])."&amp;forum_id=".urlencode($res["object"]);
	    $ret[] = array(
	      'title' => $res["title"],
	      'location' => tra("Forum")."::".$res["name"],
	      'data' => substr($res["data"],0,250),
	      'hits' => $res["hits"],
	      'last_modified' => $res["comment_date"],
	      'href' => $href,
	      'relevance' => $res["count"]
	    );
	  }
	  return array('data' => $ret,'cant' => $cant);
	  }else {
	  return array('data' => array(),'cant' => 0);
	  }
	}
*/

} # class SearchLib

?>
