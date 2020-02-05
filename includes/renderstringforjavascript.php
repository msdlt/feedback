<?php
/**
 * Quote a string so that it will render as a string literal in javascript without problems
 */
function renderStringForJavaScript($text)
	{
   	$text = addslashes($text); //escape ' and " and \ and NUL
	$text = str_replace("\n"," ",$text); //removes carriage returns in ie and netscape
	$text = str_replace("\r"," ",$text); //removes carriage returns in ie and netscape
	return $text;
	}
?>