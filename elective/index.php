<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>View results</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" type="text/css" href="../css/SurveyStyles.css">
<link rel="stylesheet" type="text/css" href="../css/msdstyle2.css" media="screen"/>
<link href="css/msdprint.css" rel="stylesheet" type="text/css" media="print" />
	
<?php
require_once("../includes/config.php"); 
require_once("../includes/getuser.php");
require_once("../includes/isauthor.php");
require_once("../includes/limitstring.php");
require_once("../includes/ODBCDateToTextDate.php");
?>
<script type="text/javascript">
function goTo(URL)
	{
	window.location.href = URL;
	}
</script>			
</head>
<body>
<?php
require_once("includes/html/electiveheader.html");
require_once("../includes/html/BreadCrumbsHeader.html"); 
echo "<strong>Search electives</strong>"; 	
require_once("../includes/html/BreadCrumbsFooter.html");
echo "<a name=\"maintext\" id=\"maintext\"></a>";
echo "<h1>Search electives</h1>";
echo "<table>
	<tr>
		<td>Search for electives by country or host centre:</td>
		<td><input type=\"button\" name=\"bQuickSearch\" id=\"bQuickSearch\" onClick=\"goTo('quicksearch.php')\" value=\"Quick Search\"/></td>
	</tr>
	<tr>
		<td>Search for electives by any combination of criteria (except host institution):</td>
		<td><input type=\"button\" name=\"bFullSearch\" id=\"bFullSearch\" onClick=\"goTo('fullsearch.php')\"/ value=\"Full Search\"></td>
	</tr>
</table>";
?>
<?php
	//Breadcrumb
	require_once("../includes/html/BreadCrumbsHeader.html"); 
	echo "<strong>Search electives</strong>"; 	
	require_once("../includes/html/BreadCrumbsFooter.html"); 
	//footer - including contact info.
	require_once("../includes/html/footernew.html");
	require_once("../includes/instructions/inst_electivesindex.php"); 

?>
</body>
</html>
