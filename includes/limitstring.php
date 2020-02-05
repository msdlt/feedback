<?php
function limitString($str,$len)
	{
	if(strlen(strip_tags($str)) > $len)
		{
		return(substr(strip_tags($str),0,$len) . "...");
		}
	else
		{
		return(strip_tags($str));
		} 
	}
?>