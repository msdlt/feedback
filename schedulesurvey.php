<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Administration</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="css/SurveyStyles.css">
<link rel="stylesheet" type="text/css" href="css/msdstyle2.css" media="screen"/>
<link href="css/msdprint.css" rel="stylesheet" type="text/css" media="print" />
<script language="javascript" type="text/javascript">
function goTo(URL)
		{
		window.location.href = URL;
		}
</script>
</head>

<body>
<?php
	require_once("includes/config.php"); 
	require_once("includes/getuser.php");
	require_once("includes/isauthor.php");
	require_once("includes/limitstring.php");
	require_once("includes/ODBCDateToTextDate.php");
?>
<?php

if(isset($_GET['surveyID']))
	{
	//from editsection.php or editquestion.php
	$surveyID = $_GET['surveyID'];
	}
elseif (isset($_POST['delete']))
	{
	if(isset($_POST['hSurveyID']))
		{
		$surveyID = $_POST['hSurveyID'];
		//Find out ids of all the checkboxes which were checked
		$aInstances = $_POST['checkSurveyInstances'];
		for ($i=0;$i<count($aInstances);$i++)
			{
			$delInstances = "	DELETE FROM SurveyInstances
								WHERE surveyInstanceID = $aInstances[$i]";
			$result_query = @mysqli_query($db_connection, $delInstances);
			if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
				{
				echo "problem deleting from Instancess" . mysqli_error();
				}
			}
		}
	}
else
	{
echo "<h1>Warning</h1>
	<p>This page requires data from another form.</p>";
	exit();
	}

//Get info about survey
$qSurveys = "SELECT title
			FROM Surveys
			WHERE surveyID = $surveyID";
$qResSurvey = mysqli_query($qSurveys);
$rowSurvey = mysqli_fetch_array($qResSurvey);
$surveyTitle = $rowSurvey[title];
if(IsAuthor($heraldID))
	{
	require_once("includes/html/adminheadernew.html");
	require_once("includes/html/BreadCrumbsHeader.html"); 
	echo "
	<a href=\"admin.php\">Administration</a> &gt; 
	Schedule survey: ".limitString($surveyTitle,30)."";
	require_once("includes/html/BreadCrumbsFooter.html"); 
	}
else
	{
	require_once("includes/html/header.html"); 
	echo "You do not have the necessary permissions to view this page";
	exit();
	}
echo "<a name=\"maintext\" id=\"maintext\"></a>
<h1>Schedule: $surveyTitle</h1>";
if (isset($_GET['orderBy']))
	{
	$orderBy = $_GET['orderBy'];
	$qSurveyInstances = "	SELECT surveyInstanceID, title, startDate, finishDate
							FROM SurveyInstances
							WHERE surveyID = $surveyID
							ORDER BY $orderBy";
	}
else	
	{
	$qSurveyInstances = "	SELECT surveyInstanceID, title, startDate, finishDate
							FROM SurveyInstances
							WHERE surveyID = $surveyID
							ORDER BY startDate";
	}
$qResSurveyInstances = mysqli_query($qSurveyInstances);
if (($qResSurveyInstances == false))
	{
	echo "problem querying SurveyInstances" . mysqli_error();
	}
else
	{
echo "	<div class=\"questionNormal\">
	<form id=\"frmDelete\" name=\"frmDelete\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">
	<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">
	<tr class=\"matrixHeader\">
		<th></th>
		<th>Title</th>
		<th>
			<a href=\"".$_SERVER['PHP_SELF']."?orderBy=startDate&surveyID=$surveyID\">Start Date</a>
		</th>
		<th>
			<a href=\"".$_SERVER['PHP_SELF']."?orderBy=finishDate&surveyID=$surveyID\">Finish Date</a>
		</th>
		<th></th>
	</tr>";
	$bRowOdd = true;
	while($rowSurveyInstances = mysqli_fetch_array($qResSurveyInstances))
		{
		$surveyInstanceTitle = $rowSurveyInstances[title];
		$surveyInstanceID = $rowSurveyInstances[surveyInstanceID];
		if($rowSurveyInstances[startDate]==NULL)
			{
			$surveyInstanceStartDate = "Unlimited";
			}
		else
			{
			$surveyInstanceStartDate = ODBCDateToTextDateShort($rowSurveyInstances[startDate]);
			}
		if($rowSurveyInstances[finishDate]==NULL)
			{
			$surveyInstanceFinishDate = "Unlimited";
			}
		else
			{
			$surveyInstanceFinishDate = ODBCDateToTextDateShort($rowSurveyInstances[finishDate]);
			}
		if($bRowOdd)
			{
			$rowClass = "matrixRowOdd";
			}
		else
			{
			$rowClass = "matrixRowEven";
			}
echo "	<tr class=\"$rowClass\">
			<td><input type=\"checkbox\" id=\"check_$surveyInstanceID\" name=\"checkSurveyInstances[]\" value=\"$surveyInstanceID\"/></td>
			<td>".$surveyInstanceTitle."</td>
			<td>".$surveyInstanceStartDate."</td>
			<td>".$surveyInstanceFinishDate."</td>
			<td><input type=\"button\" id=\"editsurveyinstance_$surveyInstanceID\" name=\"editsurveyinstance_$surveyInstanceID\" value=\"Edit schedule\" onClick=\"goTo('editsurveyinstance.php?surveyInstanceID=$surveyInstanceID&surveyID=$surveyID')\" /></td>
		</tr>";
		$bRowOdd = !$bRowOdd;
		}
echo "	
	</table>
	<input type=\"hidden\" id=\"hSurveyID\" name=\"hSurveyID\" value=\"$surveyID\"/>
	<input type=\"submit\" id=\"delete\" name=\"delete\" value=\"Delete\" onclick=\"return checkCheckBoxes()\"/>
	<input type=\"button\" id=\"add\" name=\"add\" value=\"Add\" onClick=\"goTo('editsurveyinstance.php?surveyInstanceID=add&surveyID=$surveyID')\"/>
	</form>
</div>";
	}
echo "	<script language=\"JavaScript\" type=\"text/javascript\" >
			function checkCheckBoxes()
				{
				var iNoOfInstances = 0;
				var aInstances = new Array();";				
//resets the mysql_fetch_array to start at the beginning again
mysqli_data_seek($qResSurveyInstances, 0);
while($rowSurveyInstances = mysqli_fetch_array($qResSurveyInstances))
	{
	$surveyInstanceID = $rowSurveyInstances[surveyInstanceID];
	$surveyInstanceTitle = $rowSurveyInstances[title];
echo "			if (document.getElementById(\"check_$surveyInstanceID\").checked == true)
					{
					iNoOfInstances=iNoOfInstances+1;
					aInstances[iNoOfInstances] = \"$surveyInstanceTitle\";
					}";
	}
echo "			if (iNoOfInstances == 0)
					{
					alert(\"Please select a schedule to delete.\");
					return false;
					}
				else
					{
					if (iNoOfInstances == 1)
						{
						var confirmText = \"Are you sure you want to delete \" + aInstances[iNoOfInstances] + \".\";
						}
					else
						{
						var confirmText = \"Are you sure you want to delete these \" + iNoOfInstances + \" instances?\";
						}
					var answer = confirm (confirmText);
					if (answer)
						{
						return true;
						}
					else
						{
						return false;
						}
					}
				}
		</script>";


?>
<?php
	//Breadcrumb
	if(IsAuthor($heraldID))
		{
		require_once("includes/html/BreadCrumbsHeader.html"); 
		echo "
		<a href=\"admin.php\">Administration</a> &gt; 
		Schedule survey: ".limitString($surveyTitle,30)."";
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html");
	require_once("includes/instructions/inst_schedulesurvey.php"); 

?>
</body>
</html>
