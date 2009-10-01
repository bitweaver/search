<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/searchstats_lib.php,v 1.8 2009/10/01 13:45:47 wjames5 Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * $Id: searchstats_lib.php,v 1.8 2009/10/01 13:45:47 wjames5 Exp $
 * @author  Luis Argerich (lrargerich@yahoo.com)
 * @package search
 */

/**
 * @package search
 * @subpackage SearchStatsLib
 */
class SearchStatsLib extends BitBase {
	function SearchStatsLib() {					BitBase::BitBase();
	}

	function clear_search_stats() {
		$query = "DELETE FROM `".BIT_DB_PREFIX."search_stats";
		$result = $this->mDb->query($query,array());
	}

	function list_search_stats($offset, $max_records, $sort_mode, $find) {

		if ($find) {
			$mid = " WHERE (UPPER(`term`) LIKE ?)";
			$bindvars = array("%".strtoupper( $find )."%");
		} else {
			$mid = "";
			$bindvars = array();
		}

		$query = "SELECT * FROM `".BIT_DB_PREFIX."search_stats` $mid ORDER BY ".$this->mDb->convertSortmode($sort_mode);
		$query_cant = "SELECT COUNT(*) FROM `".BIT_DB_PREFIX."search_stats` $mid";
		$result = $this->mDb->query($query,$bindvars,$max_records,$offset);
		$cant = $this->mDb->getOne($query_cant,$bindvars);
		$ret = array();

		while ($res = $result->fetchRow()) {
			$ret[] = $res;
		}

		$retval = array();
		$retval["data"] = $ret;
		$retval["cant"] = $cant;
		return $retval;
	}
}

$searchstatslib = new SearchStatsLib();

?>
