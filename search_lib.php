<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/search_lib.php,v 1.6 2006/01/15 07:59:28 squareing Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: search_lib.php,v 1.6 2006/01/15 07:59:28 squareing Exp $
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

	function find($where,$words,$offset, $maxRecords) {
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


	function find_part($where,$words,$offset, $maxRecords) {
		$words=preg_split("/[\W]+/",$words,-1,PREG_SPLIT_NO_EMPTY);
		if (count($words)>0) {
			switch($where) {
				case "bitcomment":
				  return $this->find_part_bitcomment($words,$offset, $maxRecords);
				  break;
				case "wikis":
				  return $this->find_part_wiki($words,$offset, $maxRecords);
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

				default:
				  return $this->find_part_all($words,$offset, $maxRecords);
				  break;
			}
		}
	}

	function refresh_lru_wordlist($syllable) {
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

	function get_lru_wordlist($syllable) {
		if(!isset($this->wordlist_cache[$syllable])) {
        		$query="select `searchword` from `".BIT_DB_PREFIX."tiki_searchwords` where `syllable`=?";
        		$result=$this->mDb->query($query,array($syllable));
        		while ($res = $result->fetchRow()) {
        			$this->wordlist_cache[$syllable][]=$res["searchword"];
        		}
		}
		return $this->wordlist_cache[$syllable];
	}

	function get_wordlist_from_syllables($syllables) {
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

	function find_part_bitcomment($words,$offset, $maxRecords) {
		return $this->find_exact_bitcomment($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function find_part_wiki($words,$offset, $maxRecords) {
		return $this->find_exact_wiki($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function find_part_articles($words,$offset, $maxRecords) {
		return $this->find_exact_articles($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function find_part_blogs($words,$offset, $maxRecords) {
		return $this->find_exact_blogs($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function find_part_blog_posts($words,$offset, $maxRecords) {
		return $this->find_exact_blog_posts($this->get_wordlist_from_syllables($words),$offset, $maxRecords);
	}

	function find_part_all($words,$offset, $maxRecords) {
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

		//merge the results, use @ to silence the warnings
		$res=array();
		$res["data"] = @array_merge($commentresults["data"],$wikiresults["data"]
			,$artresults["data"]
			,$blogresults["data"],$blogpostsresults["data"]
			);
		$res["cant"] = $cant;
		return ($res);
	}

	function find_exact($where,$words,$offset, $maxRecords) {
		$words=preg_split("/[\W]+/",$words,-1,PREG_SPLIT_NO_EMPTY);
		if (count($words)>0) {
			switch($where) {
			case "bitcomment":
			  return $this->find_exact_bitcomment($words,$offset, $maxRecords);
			  break;
			case "wikis":
			  return $this->find_exact_wiki($words,$offset, $maxRecords);
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

			default:
			  return $this->find_exact_all($words,$offset, $maxRecords);
			  break;
			}
		}
	}

	function find_exact_all($words,$offset, $maxRecords) {
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
		if( $gBitSystem->isPackageActive( 'blogs' ) ) {
			$blogresults=$this->find_exact_blogs($words,$offset, $maxRecords);
			$blogpostsresults=$this->find_exact_blog_posts($words,$offset, $maxRecords);
		}

		//merge the results, use @ to silence the warnings
		$res=array();
		$res["data"]=@array_merge($wikiresults["data"],
			$commentresults["data"],$artresults["data"],
			$blogresults["data"],$blogpostsresults["data"]
			);
		$res["cant"]=@($wikiresults["cant"]+$artresults["cant"]+
			$blogresults["cant"]+$blogpostsresults["cant"]
			);
		return ($res);
	}

	function find_exact_blogs($words,$offset, $maxRecords) {
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

	function find_exact_blog_posts($words,$offset, $maxRecords) {
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

	function find_exact_articles($words,$offset, $maxRecords) {
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

	function find_exact_wiki($words,$offset, $maxRecords) {
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

	function find_exact_bitcomment($words,$offset, $maxRecords) {
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

} # class SearchLib

?>
