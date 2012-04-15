<?php
/**
 * $Header$
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * $Id$
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

	function find( &$pParamHash ) { // $where, $words, $offset, $max_records, $plUsePart = false) {
		$pParamHash['words'] = preg_split("/[\W]+/", strtolower($pParamHash['words']), -1, PREG_SPLIT_NO_EMPTY);
		if ( isset($pParamHash['$plUsePart']) && $pParamHash['$plUsePart'] ) {
			$wordList = $this->get_wordlist_from_syllables( $pParamHash['words'] );
			if ( array( $wordList ) ) {
				$pParamHash['words'] = array_merge( $pParamHash['words'], $wordList );
			}
		}
		$res = $this->find_exact_generic( $pParamHash );
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

	function find_with_or($allowed, $selectSql, $joinSql, $whereSql, $bindVars,&$pParamHash) {
		// Putting in the below hack because mssql cannot select distinct on a text blob column.
		$qPlaceHolders1 = implode(',', array_fill(0, count($pParamHash['words']), '?'));
		$bindVars = array_merge( $pParamHash['words'], $allowed );
		LibertyContent::getServicesSql( 'content_list_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );
		$ret = array();
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
			$result = $this->mDb->query( $query,  array_merge( $pParamHash['words'] ,$bindVars), $pParamHash['max_records'], $pParamHash['offset'] );
			$pParamHash['cant'] = $this->mDb->getOne( $querycant, $bindVars );
			while ($res = $result->fetchRow()) {
				$res['href'] = BIT_ROOT_URL . "index.php?content_id=" . $res['content_id'];
				$ret[] = $res;
			}
			return $ret;
	}

	function find_with_and($allowed, $selectSql, $joinSql, $whereSql, $bindVars, &$pParamHash) {
		// Make a slot for the search word.
		$bindVars[0] = NULL;
		$bindVars = array_merge( $bindVars, $allowed );
		LibertyContent::getServicesSql( 'content_list_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );

		$ret = array();
		$first = true;
		foreach($pParamHash['words'] as $word) {
			$query = "SELECT lc.`content_id` AS hash_key,
						lc.`content_id`,
						lc.`title`,
						lc.`format_guid`,
						lc.`content_type_guid`,
						COALESCE(lch.`hits`,0) AS hits,
						lc.`created`,
						lc.`last_modified`,
						lc.`data`,
						si.`i_count` AS relevancy
						$selectSql
					FROM `" . BIT_DB_PREFIX . "liberty_content` lc
					LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content_hits` lch ON (lc.`content_id` = lch.`content_id`)
					$joinSql
					INNER JOIN `".BIT_DB_PREFIX."search_index` si ON (si.`content_id`=lc.`content_id` AND si.`searchword` = ? )
					WHERE `i_count` > 0 $whereSql
					ORDER BY 9 DESC, 5 DESC
					";
				$bindVars[0] = $word;
				$result = $this->mDb->getAssoc( $query, $bindVars );
				if ($first) {
					$ret = $result;
					$first = false;
				}
				else {
					$this->mergeResults($ret, $result);
				}
			}
			/* count it */
			$pParamHash['cant'] = count($ret);

			/* Sort it */
			uasort($ret, 'search_relevance_sort');

			/* slice it */
			$ret = array_slice($ret, $pParamHash['offset'], $pParamHash['offset'] + $pParamHash['max_records']);

			/* Set the hrefs. */
			foreach ($ret as $content_id => $data) {
				$ret[$content_id]['href'] = BIT_ROOT_URL . "index.php?content_id=" . $data['content_id'];
			}

			return $ret;
	}

	function find_exact_generic( &$pParamHash ) {
		global $gPage, $gBitSystem, $gLibertySystem, $gBitDbType;
		$allowed = array();
		$ret    = array();
		foreach( $gLibertySystem->mContentTypes as $contentType ) {
			if (( $pParamHash['content_type_guid'] == $contentType["content_type_guid"] or $pParamHash['content_type_guid'] == "" ) // pages ?
			and $this->has_permission($contentType["content_type_guid"])
			and ( ! $gBitSystem->getConfig('search_restrict_types') ||
				  $gBitSystem->getConfig('search_pkg_'.$contentType["content_type_guid"]) ) ) {
				$allowed[] = $contentType["content_type_guid"];
			}
		}

		if (count($allowed) > 0 && count($pParamHash['words']) > 0) {
			$selectSql = '';
			$joinSql = '';
			$whereSql = " AND  lc.`content_type_guid` IN (" . implode(',', array_fill(0, count($allowed), '?')) . ") ";
			$bindVars = array();

			if (isset($pParamHash['useAnd']) && $pParamHash['useAnd']) {
				return $this->find_with_and($allowed, $selectSql, $joinSql, $whereSql, $bindVars, $pParamHash);
			}
			else {
				return $this->find_with_or($allowed, $selectSql, $joinSql, $whereSql, $bindVars, $pParamHash);
			}
		} else {
			$pParamHash['cant'] = 0;
			return array();
		}
	}

	function mergeResults(&$ret, $result) {
		// Remove those that don't overlap or update relevance
		foreach ($ret as $content_id => $data) {
			if (!isset($result[$content_id])) {
				unset($ret[$content_id]);
			}
			else {
				$ret[$content_id]['relevancy'] += $result[$content_id]['relevancy'];
			}
		}
	}

	public static function has_permission($pContentType = NULL) {
		global $gBitUser, $gLibertySystem;

		if ( ! empty( $pContentType ) ) {
			$object = LibertyBase::getLibertyObject(1, $pContentType, FALSE);
			if ( ! empty( $object ) ) {
				// Note that we can't do verify access here because
				// we are using a generic object but we can at least get a
				// basic permission check here.
				return $object->hasViewPermission(FALSE);
			}
		}

		return FALSE;
	}

} # class SearchLib

if (!defined('search_relevance_sort')) {
	function search_relevance_sort($a, $b) {
		$rel = $b['relevancy'] - $a['relevancy'];
		if ($rel == 0) {
			$rel = $b['hits'] - $a['hits'];
		}
		return $rel;
	}
}

?>
