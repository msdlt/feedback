<?php
function ODBCDateToTextDate($ODBCDate)
	{
	//expects date as e.g. 2005-12-31
	//outputs date as 31st Dec 2005
	$aDate = explode("-",$ODBCDate);
	$strFinishDate = date('jS M Y',mktime(0, 0, 0, $aDate[1], $aDate[2], $aDate[0]));
	return($strFinishDate);
	}
function ODBCDateToTextDateShort($ODBCDate)
	{
	//expects date as e.g. 2005-12-31
	//outputs date as 31st Dec 2005
	$aDate = explode("-",$ODBCDate);
	$strFinishDate = date('d/m/y',mktime(0, 0, 0, $aDate[1], $aDate[2], $aDate[0]));
	return($strFinishDate);
	}
?>