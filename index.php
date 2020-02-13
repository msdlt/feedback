<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Clinical Feedback Questionnaires</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" type="text/css" href="css/SurveyStyles.css">
<link rel="stylesheet" type="text/css" href="css/msdstyle2.css" media="screen"/>
<link href="css/msdprint.css" rel="stylesheet" type="text/css" media="print" />
</head>

<body>
<?php
require_once("includes/config.php"); 
require_once("includes/getuser.php");
require_once("includes/isauthor.php");

//check that we are coming from one of the accepted referring pages (defined in config.php)
//$referer = getenv('http_referer');
//$refererIsOK = false;
//if(!empty($referer))
//	{
//	for($i=0;$i<count($aPermittedReferers);$i++)
//		{
//		if (strpos($referer,$aPermittedReferers[$i])!=FALSE)
//			{
//			$refererIsOK = true;
//			} 
//		}
//	}
$refererIsOK = true;
if ($refererIsOK!=true && !IsAuthor($heraldID))
	{
	//ie. authors don't have to come from a certain referrer
	echo "Please access your survey through Weblearn";
	exit();
	}

//first find all survey instances that are current
if(IsAuthor($heraldID))
	{
	require_once("includes/html/adminheadernew.html");
	require_once("includes/html/BreadCrumbsHeader.html"); 
	echo "<strong>Surveys</strong>"; 	
	require_once("includes/html/BreadCrumbsFooter.html"); 
	}
else
	{
	require_once("includes/html/header.html"); 
	echo "You do not have the necessary permissions to view this page";
	exit(); 
	}
echo "<a name=\"maintext\" id=\"maintext\"></a>";
$qSurveyInstances = "SELECT surveyInstanceID, surveyID, title, startDate, finishDate 
			FROM SurveyInstances
			WHERE (startDate <= CURDATE()
			AND finishDate >= CURDATE())
			OR (startDate IS NULL
			AND finishDate >= CURDATE())
			OR (startDate <= CURDATE()
			AND finishDate IS NULL)
			OR (startDate IS NULL
			AND finishDate IS NULL)
			ORDER BY title";

$qResSurveyInstances = @mysqli_query($db_connection, $qSurveyInstances);
echo "<h1>Surveys</h1>";
echo " 	<div class=\"block\">";
if(IsAuthor($heraldID))
	{
	//ie. only show full list of available surveys to authors
	echo "		<h2> Choose a survey: </h3>";
	echo "		<div class=\"sectionMatrix\">";
	echo "			<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
	echo "			<tr class=\"matrixHeader\">";
	echo "				<th>Survey</th>";
	echo "				<th>Available from</th>";
	echo "				<th>Deadline</th>";
	echo "			</tr>";
						
	if (($qResSurveyInstances == false))
		{
		echo "problem querying SurveyInstances" . mysqli_error($db_connection);
		}
	else
		{
		//toggler for rows - so that appropriate style can be set
		$bRowOdd = true;
		while($rowSurveyInstances = mysqli_fetch_array($qResSurveyInstances))
			{
			if($bRowOdd)
				{
				echo "<tr class=\"matrixRowOdd\">";
				}
			else
				{
				echo "<tr class=\"matrixRowEven\">";
				}
			echo "	<td>";
			echo "		<a href=\"showsurvey.php?surveyInstanceID=$rowSurveyInstances[surveyInstanceID]\">$rowSurveyInstances[title]</a>";
			echo "	</td>";
			echo "	<td>";
			if($rowSurveyInstances['startDate']==NULL)
				{
				echo "Unlimited";
				}
			else
				{
				echo "$rowSurveyInstances[startDate]";
				}
			echo "	</td>";
			echo "	<td>";
			if($rowSurveyInstances['finishDate']==NULL)
				{
				echo "Unlimited";
				}
			else
				{
				echo $rowSurveyInstances['finishDate'];
				}
			echo "	</td>";
			echo "</tr>";
			$bRowOdd = !$bRowOdd;
			}
		}	
	echo " 		</table>";	
	echo "	</div>"	;
	}
echo "</div>"	;		

$qPastSurveyInstances = "	SELECT DISTINCTROW SurveyInstances.surveyInstanceID, SurveyInstances.surveyID, SurveyInstances.title, SurveyInstances.startDate, SurveyInstances.finishDate 
						FROM SurveyInstances, Answers, SurveyInstanceParticipants
						WHERE SurveyInstances.surveyInstanceID = Answers.surveyInstanceID
						AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
						AND Answers.heraldID = '$heraldID'
						AND SurveyInstanceParticipants.heraldID = '$heraldID'
						AND SurveyInstanceParticipants.status = 2
						ORDER BY SurveyInstances.title";

$qResPastSurveyInstances = @mysqli_query($db_connection, $qPastSurveyInstances);

if (($qResPastSurveyInstances == false))
	{
	echo "problem querying SurveyInstances" . mysqli_error($db_connection);
	}
elseif(mysqli_num_rows($qResPastSurveyInstances)>0)
	{
	echo "<h1>Completed Surveys</h1>";
	echo " 	<div class=\"block\">";
	echo "		<h2> Choose a completed survey: </h3>";
	echo "		<div class=\"sectionMatrix\">";
	echo "			<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
	echo "			<tr class=\"matrixHeader\">";
	echo "				<th>Survey</th>";
	echo "				<th></th>";
	echo "				<th>Deadline</th>";
	echo "				<th>Print</th>";
	echo "			</tr>";
	//toggler for rows - so that appropriate style can be set
	$bRowOdd = true;
	while($rowPastSurveyInstances = mysqli_fetch_array($qResPastSurveyInstances))
		{
		if($bRowOdd)
			{
			echo "<tr class=\"matrixRowOdd\">";
			}
		else
			{
			echo "<tr class=\"matrixRowEven\">";
			}
		echo "	<td>";
		echo "		<a href=\"showsurvey.php?surveyInstanceID=$rowPastSurveyInstances[surveyInstanceID]\">$rowPastSurveyInstances[title]</a>";
		echo "	</td>";
		echo "	<td>";
		echo "		";
		echo "	</td>";
		echo "	<td>";
		echo "		$rowPastSurveyInstances[finishDate]";
		echo "	</td>";
		echo "	<td>";
		echo "		<input type=\"button\" name=\"printSurvey\" id=\"printSurvey\" value=\"Print submitted survey\" onClick=\"window.open('printsurvey.php?surveyInstanceID=$rowPastSurveyInstances[surveyInstanceID]','printWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes')\">";
		echo "	</td>";
		echo "</tr>";
		$bRowOdd = !$bRowOdd;
		}
	echo " 		</table>";	
	echo "	</div>"	;	
	echo "	</div>"	;		
	}	

//Breadcrumb
if(IsAuthor($heraldID))
	{
	require_once("includes/html/BreadCrumbsHeader.html"); 
	echo "<strong>Surveys</strong>"; 	
	require_once("includes/html/BreadCrumbsFooter.html"); 
	}
//footer - including contact info.
require_once("includes/html/footernew.html"); 
//Instructions box
echo "	<div id=\"homeRight\">
			<div class=\"loginBox\">
				<h5>Instructions</h5>
				<p>Click on a survey name in <strong>Surveys</strong> to complete that survey.</p>
				<p>You may view surveys you have completed by clicking on the survey name in <strong>Completed surveys</strong>.</p>";
		echo "</div>
		</div>";
?>
</body>
</html>
