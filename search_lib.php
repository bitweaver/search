<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/search_lib.php,v 1.1.1.1.2.12 2006/02/08 04:26:35 mej Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: search_lib.php,v 1.1.1.1.2.12 2006/02/08 04:26:35 mej Exp $
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
			$cant = $this->mDb->getOne("select count(*) from `" . BIT_DB_PREFIX . 
				"tiki_search_stats` where `term`=?", array($word));
			if ($cant) {
				$query = "update `" . BIT_DB_PREFIX . "tiki_search_stats` set `hits`= `hits` + 1 where `term`=?";
			} else {
				$query = "insert into `" . BIT_DB_PREFIX . "tiki_search_stats` (`term`,`hits`) values (?,1)";
			}
			$result = $this->mDb->query($query,array($word));
		}
	}

	function find($where, $words, $offset, $maxRecords, $plUsePart = false) {
		$words = preg_split("/[\W]+/", strtolower($words), -1, PREG_SPLIT_NO_EMPTY);
		if ($plUsePart) {
			$wordList = $this->get_wordlist_from_syllables($words);
			if(array($wordList)) {
				$words = array_merge($words, $wordList);
			}
		}
//		$res = $this->find_exact($where, $words, $offset, $maxRecords);
		$res = $this->find_exact_generic($where, $words, $offset, $maxRecords);
		return $res;
	}

	/*
	 * This function checks the tiki_searchsyllable table to see how old the "syllable" is
	 * If the syllable is to old or doesn't exist, it refreshes the syllable/word list stored in tiki_searchwords
	 * Then, it get a list of words from the tiki_searchwords table and returns an array of them
	*/
	function get_wordlist_from_syllables($syllables) {
		global $search_syll_age;
		$ret = array();
		foreach($syllables as $syllable) {
			$bindvars = array($syllable);
			$age      = time() - $this->mDb->getOne(
						"select `last_updated` from `" . BIT_DB_PREFIX . "tiki_searchsyllable` where `syllable`=?",
						$bindvars);
			if(!$age || $age > ($search_syll_age * 3600)) {// older than search_syll_age hours
				$a = $this->refresh_lru_wordlist($syllable);
			}
			$lruList = $this->get_lru_wordlist($syllable);
			if (is_array($lruList)) {
				$ret = array_merge($ret, $lruList);
			}
			// update lru last used value (Used to purge oldest last used records)
			$now = time();
			$this->mDb->query("update `" . BIT_DB_PREFIX . "tiki_searchsyllable` set `last_used`=? where `syllable`=?",
				array((int) $now, $syllable));
		}
		return $ret;
	}

	function get_lru_wordlist($syllable) {
		$ret = array();
		if(!isset($this->wordlist_cache[$syllable])) {
	       		$query  = "select `searchword` from `" . BIT_DB_PREFIX . "tiki_searchwords` where `syllable`=?";
        		$result = $this->mDb->query($query, array($syllable));
        		if ($result->RecordCount() > 0) {
	        		while ($res = $result->fetchRow()) {
    	    			$this->wordlist_cache[$syllable][]=$res["searchword"];
        			}
	        		$ret = $this->wordlist_cache[$syllable];
        		}
		}
		return $ret;
	}

	function refresh_lru_wordlist($syllable) {
		global $search_max_syllwords;
		global $search_lru_length;
		global $search_lru_purge_rate;
		$ret = array();

		// delete from wordlist and lru list
		$this->mDb->query("delete from `".BIT_DB_PREFIX."tiki_searchwords` where `syllable`=?",array($syllable),-1,-1);
		$this->mDb->query("delete from `".BIT_DB_PREFIX."tiki_searchsyllable` where `syllable`=?",array($syllable),-1,-1);
		if (!isset($search_max_syllwords)) {
			$search_max_syllwords = 100;
		}
		$query  = "select `searchword`, sum(`count`) as `cnt` from `" . BIT_DB_PREFIX . 
					"tiki_searchindex` where `searchword` like ? group by `searchword` ORDER BY 2 desc";
		$result = $this->mDb->query($query, array('%' . $syllable . '%'), $search_max_syllwords); // search_max_syllwords: how many different searchwords that contain the syllable are taken into account?. Sortet by number of occurences.
		while ($res = $result->fetchRow()) {
			$ret[] = $res["searchword"];
		}
		// cache this long running query
		foreach($ret as $searchword) {
			$this->mDb->query("insert into `" . BIT_DB_PREFIX . 
				"tiki_searchwords` (`syllable`,`searchword`) values (?,?)",
				array($syllable, $searchword), -1, -1);
			}
		// set lru list parameters
		$now = time();
		$this->mDb->query("insert into `" . BIT_DB_PREFIX . 
			"tiki_searchsyllable`(`syllable`,`last_used`,`last_updated`) values (?,?,?)",
			array($syllable,(int) $now,(int) $now));

		// at random rate: check length of lru list and purge these that
		// have not been used for long time. This is what a lru list
		// basically does
		list($usec, $sec) = explode(" ", microtime());
		srand (ceil($sec + 100 * $usec));
		if(rand(1, $search_lru_purge_rate) == 1) {
			$lrulength = $this->mDb->getOne("select count(*) from `" . BIT_DB_PREFIX . 
				"tiki_searchsyllable`", array());
			if ($lrulength > $search_lru_length) { // only purge if lru list is too long.
				//purge oldest
				$oldwords = array();
				$diff   = $lrulength - $search_lru_length;
				$query  = "select `syllable` from `".BIT_DB_PREFIX."tiki_searchsyllable` ORDER BY `last_used` asc";
				$result = $this->mDb->query($query, array(), $diff);
				while ($res = $result->fetchRow()) {
					$oldwords[]=$res["syllable"];
				}
				foreach($oldwords as $oldword) {
					$this->mDb->query("delete from `" . BIT_DB_PREFIX . 
						"tiki_searchwords`    where `syllable`=?", array($oldword), -1, -1);
					$this->mDb->query("delete from `" . BIT_DB_PREFIX . 
						"tiki_searchsyllable` where `syllable`=?", array($oldword), -1, -1);
				}

			}
		}
		return $ret;
	}

	function find_exact_generic($where, $words, $offset, $maxRecords) {
		global $gPage, $gBitSystem, $gLibertySystem;
		$allowed = array();
		$ret    = array();
		foreach( $gLibertySystem->mContentTypes as $contentType ) {
			if (($where == $contentType["content_type_guid"] or $where == "pages") 
			and $this->has_permission($contentType["content_type_guid"])) {
				$allowed[] = $contentType["content_type_guid"];
			}
		}
		if (count($allowed) > 0) {
			$qPlaceHolders1 = implode(',', array_fill(0, count($words), '?'));
			$qPlaceHolders2 = implode(',', array_fill(0, count($allowed), '?'));
			$query = "SELECT DISTINCT tc.`content_id`, tc.`title`, tc.`format_guid`, si.`location`,
							si.`last_update`, tc.`hits`, tc.`created`, tc.`last_modified`, tc.`data`
						FROM `" . BIT_DB_PREFIX . "tiki_searchindex` si 
			  			INNER JOIN `" . BIT_DB_PREFIX . "tiki_content` tc ON tc.`content_id` = si.`content_id`
			  			WHERE `searchword` IN (" . $qPlaceHolders1 . ") 
			  		 	AND  si.`location` IN (" . $qPlaceHolders2 . ") ORDER BY `hits` desc";
			$querycant = "SELECT COUNT(DISTINCT si.`content_id`)
							FROM `".BIT_DB_PREFIX."tiki_searchindex` si 
			  				INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON tc.`content_id` = si.`content_id`
			  			WHERE `searchword` IN (" . $qPlaceHolders1 . ") 
			  		 	AND  si.`location` IN (" . $qPlaceHolders2 . ")";
			$result = $this->mDb->query($query, array_merge($words, $allowed), $maxRecords, $offset);
			$cant   = $this->mDb->getOne($querycant, array_merge($words, $allowed));
			while ($res = $result->fetchRow()) {
				$res['href'] = BIT_ROOT_URL . "index.php?content_id=" . $res['content_id'];
				$ret[] = $res;
			}
			return array('data' => $ret, 'cant' => $cant);
		} else {
			return array('data' => array(),'cant' => 0);
		}
	}

	function has_permission($pContentType = "") {
		global $gBitUser;
		$ret = false;
		switch ($pContentType) {
			case "bitarticle"     : $perm = "bit_p_read_article";	break;
			case "bitpage"        : $perm = "bit_p_view";			break;
			case "bitblogpost"    : $perm = "bit_p_read_blog";	    break;
			case "bitcomment"     : $perm = "bit_p_read_comments";	break;
			case "fisheyegallery" : $perm = "bit_p_view_fisheye";	break;
			default               : $perm = "";						break;
		}
		return $gBitUser->hasPermission($perm);
	}

} # class SearchLib

?>
