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
 * @subpackage SearchStatsLib
 */
class SearchStatsLib extends BitBase {
	function SearchStatsLib() {					parent::__construct();
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
