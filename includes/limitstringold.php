<?php
function limitString($str,$len)
	{
	if(strlen($str) > $len)
		{
		return(substr($str,0,$len) . "...");
		}
	else
		{
		return($str);
		} 
	}
?>