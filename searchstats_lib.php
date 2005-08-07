<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_search/searchstats_lib.php,v 1.3 2005/08/07 17:44:39 squareing Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: searchstats_lib.php,v 1.3 2005/08/07 17:44:39 squareing Exp $
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
		$query = "delete from `".BIT_DB_PREFIX."tiki_search_stats";
		$result = $this->mDb->query($query,array());
	}

	function list_search_stats($offset, $maxRecords, $sort_mode, $find) {

		if ($find) {
			$mid = " where (UPPER(`term`) like ?)";
			$bindvars = array("%".strtoupper( $find )."%");
		} else {
			$mid = "";
			$bindvars = array();
		}

		$query = "select * from `".BIT_DB_PREFIX."tiki_search_stats` $mid order by ".$this->mDb->convert_sortmode($sort_mode);
		$query_cant = "select count(*) from `".BIT_DB_PREFIX."tiki_search_stats` $mid";
		$result = $this->mDb->query($query,$bindvars,$maxRecords,$offset);
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
