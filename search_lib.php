<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/search_lib.php,v 1.35 2007/06/25 09:03:46 nickpalmer Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: search_lib.php,v 1.35 2007/06/25 09:03:46 nickpalmer Exp $
 * @author  Luis Argerich (lrargerich@yahoo.com)
 * @package search
 */

/**
 * @package search
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
			$cant = $this->mDb->getOne("SELECT COUNT(*) FROM `" . BIT_DB_PREFIX .
				"search_stats` WHERE `term`=?", array($word));
			if ($cant) {
				$query = "UPDATE `" . BIT_DB_PREFIX . "search_stats` SET `hits`= `hits` + 1 WHERE `term`=?";
			} else {
				$query = "INSERT INTO `" . BIT_DB_PREFIX . "search_stats` (`term`,`hits`) VALUES (?,1)";
			}
			$result = $this->mDb->query($query,array($word));
		}
	}

	function find($where, $words, $offset, $max_records, $plUsePart = false) {
		$words = preg_split("/[\W]+/", strtolower($words), -1, PREG_SPLIT_NO_EMPTY);
		if ($plUsePart) {
			$wordList = $this->get_wordlist_from_syllables($words);
			if(array($wordList)) {
				$words = array_merge($words, $wordList);
			}
		}
//		$res = $this->find_exact($where, $words, $offset, $max_records);
		$res = $this->find_exact_generic($where, $words, $offset, $max_records);
		return $res;
	}

	/*
	 * This function checks the search_syllable table to see how old the "syllable" is
	 * If the syllable is to old or doesn't exist, it refreshes the syllable/word list stored in search_words
	 * Then, it get a list of words from the search_words table and returns an array of them
	*/
	function get_wordlist_from_syllables($syllables) {
		global $gBitSystem;
		$search_syll_age = $gBitSystem->getConfig( 'search_syll_age', SEARCH_PKG_NAME );
		$ret = array();
		foreach($syllables as $syllable) {
			$bindvars = array($syllable);
			$age      = time() - $this->mDb->getOne(
						"select `last_updated` from `" . BIT_DB_PREFIX . "search_syllable` where `syllable`=?",
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
			$this->mDb->query("update `" . BIT_DB_PREFIX . "search_syllable` set `last_used`=? where `syllable`=?",
				array((int) $now, $syllable));
		}
		return $ret;
	}

	function get_lru_wordlist($syllable) {
		$ret = array();
		if(!isset($this->wordlist_cache[$syllable])) {
	       		$query  = "select `searchword` from `" . BIT_DB_PREFIX . "search_words` where `syllable`=?";
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
		global $gBitSystem;
		$search_max_syllwords = $gBitSystem->getConfig( 'search_max_syllwords', SEARCH_PKG_NAME );;
		$search_lru_length = $gBitSystem->getConfig( 'search_lru_length', SEARCH_PKG_NAME );;
		$search_lru_purge_rate = $gBitSystem->getConfig( 'search_lru_purge_rate', SEARCH_PKG_NAME );
		$ret = array();

		// delete from wordlist and lru list
		$this->mDb->query("delete from `".BIT_DB_PREFIX."search_words` where `syllable`=?",array($syllable),-1,-1);
		$this->mDb->query("delete from `".BIT_DB_PREFIX."search_syllable` where `syllable`=?",array($syllable),-1,-1);
		if (!isset($search_max_syllwords)) {
			$search_max_syllwords = 100;
		}
		$query  = "SELECT `searchword`, SUM(`i_count`) AS `cnt` FROM `" . BIT_DB_PREFIX .
					"search_index` WHERE `searchword` LIKE ? GROUP BY `searchword` ORDER BY 2 desc";
		$result = $this->mDb->query($query, array('%' . $syllable . '%'), $search_max_syllwords); // search_max_syllwords: how many different search_words that contain the syllable are taken into account?. Sortet by number of occurences.
		while ($res = $result->fetchRow()) {
			$ret[] = $res["searchword"];
		}
		// cache this long running query
		foreach($ret as $searchword) {
			$this->mDb->query("INSERT INTO `" . BIT_DB_PREFIX .
				"search_words` (`syllable`,`searchword`) VALUES (?,?)",
				array($syllable, $searchword), -1, -1);
			}
		// set lru list parameters
		$now = time();
		$this->mDb->query("INSERT INTO `" . BIT_DB_PREFIX .
			"search_syllable`(`syllable`,`last_used`,`last_updated`) values (?,?,?)",
			array($syllable,(int) $now,(int) $now));

		// at random rate: check length of lru list and purge these that
		// have not been used for long time. This is what a lru list
		// basically does
		list($usec, $sec) = explode(" ", microtime());
		srand (ceil($sec + 100 * $usec));
		if(rand(1, $search_lru_purge_rate) == 1) {
			$lrulength = $this->mDb->getOne("SELECT COUNT(*) FROM `" . BIT_DB_PREFIX .
				"search_syllable`", array());
			if ($lrulength > $search_lru_length) { // only purge if lru list is too long.
				//purge oldest
				$oldwords = array();
				$diff   = $lrulength - $search_lru_length;
				$query  = "select `syllable` from `".BIT_DB_PREFIX."search_syllable` ORDER BY `last_used` asc";
				$result = $this->mDb->query($query, array(), $diff);
				while ($res = $result->fetchRow()) {
					$oldwords[]=$res["syllable"];
				}
				foreach($oldwords as $oldword) {
					$this->mDb->query("delete from `" . BIT_DB_PREFIX .
						"search_words`    where `syllable`=?", array($oldword), -1, -1);
					$this->mDb->query("delete from `" . BIT_DB_PREFIX .
						"search_syllable` where `syllable`=?", array($oldword), -1, -1);
				}

			}
		}
		return $ret;
	}

	function find_exact_generic($where, $words, $offset, $max_records) {
		global $gPage, $gBitSystem, $gLibertySystem, $gBitDbType;
		$allowed = array();
		$ret    = array();
		foreach( $gLibertySystem->mContentTypes as $contentType ) {
			if (($where == $contentType["content_type_guid"] or $where == "") // pages ?
			and $this->has_permission($contentType["content_type_guid"])) {
				$allowed[] = $contentType["content_type_guid"];
			}
		}

		if (count($allowed) > 0 && count($words) > 0) {
			// Putting in the below hack because mssql cannot select distinct on a text blob column.
			$qPlaceHolders1 = implode(',', array_fill(0, count($words), '?'));

			$selectSql = '';
			$joinSql = '';
			$whereSql = " AND  lc.`content_type_guid` IN (" . implode(',', array_fill(0, count($allowed), '?')) . ") ";
			$bindVars = array_merge( $words, $allowed );
			LibertyContent::getServicesSql( 'content_list_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );

			$query = "SELECT
						lc.`content_id`,
						lc.`title`,
						lc.`format_guid`,
						lc.`content_type_guid`,
						COALESCE(lch.`hits`,0) AS hits,
						lc.`created`,
						lc.`last_modified`,
						lc.`data`,
						COALESCE((
							SELECT SUM(i_count)
							FROM `" . BIT_DB_PREFIX . "search_index` si
							WHERE si.`content_id`=lc.`content_id` AND si.`searchword` IN (" . $qPlaceHolders1 . ")
						),0) AS relevancy
						$selectSql
					FROM `" . BIT_DB_PREFIX . "liberty_content` lc
					LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content_hits` lch ON (lc.`content_id` = lch.`content_id`)
					$joinSql
					WHERE (
						SELECT SUM(i_count)
						FROM `" . BIT_DB_PREFIX . "search_index` si
						WHERE si.`content_id`=lc.`content_id`
						AND si.`searchword` IN (" . $qPlaceHolders1 . ")
						GROUP BY
						si.`content_id` 
						)>0 $whereSql
					ORDER BY 9 DESC, 5 DESC
					";
			$querycant = "SELECT
					COUNT(*)
					FROM `" . BIT_DB_PREFIX . "liberty_content` lc
					LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content_hits` lch ON (lc.`content_id` = lch.`content_id`)
					$joinSql
					WHERE (
						SELECT SUM(i_count)
						FROM `" . BIT_DB_PREFIX . "search_index` si
						WHERE si.`content_id`=lc.`content_id`
						AND si.`searchword` IN (" . $qPlaceHolders1 . ")
						GROUP BY
						si.`content_id`
						)>0 $whereSql";
			$result = $this->mDb->query( $query,  array_merge( $words ,$bindVars), $max_records, $offset );
			$cant   = $this->mDb->getOne( $querycant, $bindVars );
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
			case "bitarticle"     : $perm = "p_articles_read";	break;
			case "bitpage"        : $perm = "p_wiki_view_page";			break;
			case "bitblogpost"    : $perm = "p_blogs_view";	    break;
			case "bitcomment"     : $perm = "p_liberty_read_comments";	break;
			case "fisheyegallery" : $perm = "p_fisheye_view";	break;
			default               : $perm = "";						break;
		}
		return $gBitUser->hasPermission($perm);
	}

} # class SearchLib

?>
