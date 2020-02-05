<?php
require_once("includes/config.php"); 
require_once("includes/getuser.php");
require_once("includes/isauthor.php");
require_once("includes/quote_smart.php");
require_once("includes/ODBCDateToTextDate.php");
require_once("includes/gettextoutput.php");
require_once("includes/getinstancesforblockandsection.php");
if (isset($_GET['surveyInstanceID']) && $_GET['surveyInstanceID']!="")
	{
	//pass surveyInstanceID on
	$surveyInstanceID = $_GET['surveyInstanceID'];
	}
else
	{
	//if not don't give any URL parameter
	header("Location: index.php");
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<link rel="stylesheet" type="text/css" href="css/SurveyStyles.css"></link>
	<link rel="stylesheet" type="text/css" href="css/msdstyle2.css" media="screen"/>
	<link  rel="stylesheet" type="text/css" href="css/msdprint.css" media="print" />
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>
<body onload="window.print()">
<?php
echo getTextOutput($surveyInstanceID,$heraldID);	
echo "<h3>Printed: ". date('H:i, d/m/y') . ".";
?>
<INPUT type="button" value="Close Window" onClick="window.close()"> 
</body>
</html>