<?php

class SearchStatsLib extends BitBase {
	function SearchStatsLib() {					BitBase::BitBase();
	}

	function clear_search_stats() {
		$query = "delete from `".BIT_DB_PREFIX."tiki_search_stats";
		$result = $this->query($query,array());
	}

	function list_search_stats($offset, $maxRecords, $sort_mode, $find) {

		if ($find) {
			$mid = " where (UPPER(`term`) like ?)";
			$bindvars = array("%".strtoupper( $find )."%");
		} else {
			$mid = "";
			$bindvars = array();
		}

		$query = "select * from `".BIT_DB_PREFIX."tiki_search_stats` $mid order by ".$this->convert_sortmode($sort_mode);
		$query_cant = "select count(*) from `".BIT_DB_PREFIX."tiki_search_stats` $mid";
		$result = $this->query($query,$bindvars,$maxRecords,$offset);
		$cant = $this->getOne($query_cant,$bindvars);
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
