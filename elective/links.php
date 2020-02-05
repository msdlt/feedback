<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Useful information</title>
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
</head>
<body>
<?php
require_once("includes/html/electiveheader.html");
require_once("../includes/html/BreadCrumbsHeader.html"); 
echo "<strong>Useful information</strong>"; 	
require_once("../includes/html/BreadCrumbsFooter.html");
echo "<a name=\"maintext\" id=\"maintext\"></a>";
echo "	<h1>Useful information</h1>";
echo "<h2>Travel Guides</h2>";
echo "<table>
	<tr>
		<td><a href=\"http://news.bbc.co.uk/1/hi/world/default.stm\" target=\"countrylink\">BBC News</a></td>
		<td>For the latest news on a country plus country profiles are available.</td>
	</tr>
	<tr>
		<td><a href=\"http://www.lonelyplanet.co.uk\" target=\"countrylink\">Lonely Planet</a></td>
		<td>Includes the World Guide which is linked to from Quick Searches by country.</td>
	</tr>
	<tr>
		<td><a href=\"http://travel.roughguides.com\" target=\"countrylink\">Rough Guides Travel</a></td>
		<td>From the publishers of Rough Guides.</td>
	</tr>
	<tr>
		<td><a href=\"http://www.footprintguides.com/\" target=\"countrylink\">Footprint Travel Guides</a></td>
		<td>These cover only a selection of more popular tourist destinations.</td>
	</tr>
</table>";
echo "<h2>Travel Advice</h2>";
echo "<table>
	<tr>
		<td><a href=\"http://www.fco.gov.uk\" target=\"countrylink\">Foreign and Commonwealth Office</a></td>
		<td>Official government advice including <a href=\"http://www.fco.gov.uk/servlet/Front?pagename=OpenMarket/Xcelerate/ShowPage&c=Page&cid=1007029394365\" target=\"countrylink\">Country Profiles</a> which provide information on individual countries.</td>
	</tr>
</table>";
echo "<h2>Maps</h2>";
echo "<table>
	<tr>
		<td><a href=\"http://www.multimap.com\" target=\"countrylink\">Multimap</a></td>
		<td>Although the detail is better for the developed world, this is still a useful resource for most countries worldwide.</td>
	</tr>
</table>";
?>
<?php
	//Breadcrumb
	require_once("../includes/html/BreadCrumbsHeader.html"); 
	echo "<strong>Useful information</strong>"; 	
	require_once("../includes/html/BreadCrumbsFooter.html"); 
	//footer - including contact info.
	require_once("../includes/html/footernew.html");
	require_once("../includes/instructions/inst_electiveslinks.php"); 

?>
</body>
</html>
