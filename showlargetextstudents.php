<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<script type="text/javascript">
function goTo(URL)
	{
	window.location.href = URL;
	}
function OnbShowReports(studentHeraldID)
	{
	//by using javascript to submit the form, we can pass in the student heraldID
	document.frmShowText.action = "showlargetext.php?studentHeraldID=" + studentHeraldID;
	document.frmShowText.target = "_self";	
	document.frmShowText.bViewLgText.click();
	document.frmShowText.submit();
	return true;
	}
</script>
<?php 
	//Include config.php which contains db details and connects to the db)
	require_once("includes/config.php"); 
	require_once("includes/getuser.php");
	require_once("includes/isauthor.php");
	require_once("includes/limitstring.php");
	require_once("includes/ODBCDateToTextDate.php");

	if($_SERVER['REQUEST_METHOD'] != 'POST')
		{
		echo "This page requires input from another page. Please go to <a href=\"viewreports.php\">viewreports.php</a>";
		exit;
		}
	//extract data from viewstudents.php
	$surveyID = $_POST['surveyID'];
	$instanceID = $_POST['sSchedule'];
	
	//Get survey information
	$qSurveys = "SELECT title
				FROM Surveys
				WHERE surveyID = $surveyID";
	$qResSurvey = mysqli_query($db_connection, $qSurveys);
	$rowSurvey = mysqli_fetch_array($qResSurvey);
	$surveyTitle = $rowSurvey[title];
	
	$qSurveyInstances = "SELECT title, startDate, finishDate
						FROM SurveyInstances
						WHERE surveyInstanceID = $instanceID";
	$qResSurveyInstance = mysqli_query($db_connection, $qSurveyInstances);
	$rowSurveyInstance = mysqli_fetch_array($qResSurveyInstance);
	$surveyInstanceTitle = $rowSurveyInstance[title];
	$surveyInstanceStartDate = $rowSurveyInstance[startDate];
	$surveyInstanceFinishDate = $rowSurveyInstance[finishDate];
	
	echo "<title>$surveyInstanceTitle - Student Reports</title>";
?>	

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" type="text/css" href="css/SurveyStyles.css">
<link rel="stylesheet" type="text/css" href="css/msdstyle2.css" media="screen"/>
<link href="css/msdprint.css" rel="stylesheet" type="text/css" media="print" />
</head>

<body>
<?php
if(IsAuthor($heraldID))
	{
	require_once("includes/html/adminheadernew.html");
	require_once("includes/html/BreadCrumbsHeader.html"); 
	echo "<a href=\"results.php\">Results</a> &gt; <a href=\"viewreports.php\">View Reports</a> &gt; $surveyInstanceTitle - Student Reports";
	require_once("includes/html/BreadCrumbsFooter.html"); 
	}
else
	{
	require_once("includes/html/header.html"); 
	echo "You do not have the necessary permissions to view this page";
	exit(); 
	}
echo "<h1>$surveyInstanceTitle - Student Reports</h1>";
echo "<a name=\"maintext\" id=\"maintext\"></a>";
$qStudents = "	SELECT DISTINCT SurveyInstanceParticipants.heraldID 
				FROM SurveyInstanceParticipants, SurveyInstances
				WHERE SurveyInstances.surveyID = $surveyID
				AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
				AND SurveyInstances.surveyInstanceID = $instanceID
				ORDER BY SurveyInstanceParticipants.heraldID";
$qResStudents = @mysqli_query($db_connection, $qStudents);
if (($qResStudents == false))
	{
	echo "problem querying SurveyInstanceParticipants" . mysqli_error();
	}
else
	{
	echo "<h2>Total number of students = ".mysqli_num_rows($qResStudents)."</h2>";
	echo "<ul>";
	if(mysqli_num_rows($qResStudents)>0)
		{
		//Prevents one student's answers being associated with them
		echo "<form name=\"frmShowText\" id=\"frmShowText\" method=\"post\">";
		echo "<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
		echo "	<tr class=\"matrixHeader\">
					<td  class=\"question\">heraldID</td>
					<td class=\"question\">Name</td>
					<td class=\"question\"></td>";
		echo "	</tr>";
		$bRowOdd = true;
		while($rowStudents = mysqli_fetch_array($qResStudents))
			{
			if($bRowOdd)
				{
				$rowClass = "matrixRowOdd";
				}
			else
				{
				$rowClass = "matrixRowEven";
				}
		echo "	<tr class=\"$rowClass\">
					<td>$rowStudents[heraldID]</td>
					<td>";
					//Only reuiqred on learntech.imsu.ox.ac.uk where db exists
					//connect to heraldID and name database
					//$dbstudent_connection = mysql_connect ($dbstudent_info[host], $dbstudent_info[username], $dbstudent_info[password]) or die (mysqli_error());
					//$db_select = mysql_select_db ($dbstudent_info[dbname], $dbstudent_connection) or die (mysqli_error());
					$dbstudent_connection = mysqli_connect ($dbstudent_info[host], $dbstudent_info[username], $dbstudent_info[password], $dbstudent_info[dbname]) or die (mysqli_error());
					$qStudentName = "	SELECT LASTNAME, FORENAMES
								FROM cards
								WHERE USERNAME = '$rowStudents[heraldID]'";
					$qResStudentName = @mysqli_query($dbstudent_connection, $qStudentName);
					if (($qResStudentName == false))
						{
						echo "problem querying cards" . mysqli_error();
						}
					else
						{
						if (mysqli_num_rows($qResStudentName)==1)
							{
							$rowStudentName = mysqli_fetch_array($qResStudentName);
							echo $rowStudentName[LASTNAME] . ", " . $rowStudentName[FORENAMES];
							}
						else
							{
							echo "Name not found";
							}
						}	
					mysqli_close($dbstudent_connection);
					//$db_connection = mysql_connect ($db_info[host], $db_info[username], $db_info[password]) or die (mysqli_error());
					//$db_select = mysql_select_db ($db_info[dbname], $db_connection) or die (mysqli_error());
					$db_connection = mysqli_connect ($db_info[host], $db_info[username], $db_info[password], $db_info[dbname]) or die (mysqli_error());
					echo"</td>
					<td align=\"right\">
						<input type=\"submit\" name=\"bViewLgText\" id=\"bViewLgText_$rowStudents[heraldID]\" value=\"View\" onclick=\"return OnbShowReports('".$rowStudents[heraldID]."');\">
						<input type=\"hidden\" name=\"hSurveyID\" id=\"hSurveyID\" value=\"$surveyID\">
						<input type=\"hidden\" name=\"hSurveyInstanceID\" id=\"hSurveyInstanceID\" value=\"$instanceID\">
					</td>";
			echo "	</tr>";
			$bRowOdd = !$bRowOdd;
			}
		echo"</table>
			</form>";
		}
	}
?>
<?php
	//Breadcrumb
	if(IsAuthor($heraldID))
		{
		require_once("includes/html/BreadCrumbsHeader.html"); 
		echo "<a href=\"results.php\">Results</a> &gt; <a href=\"viewreports.php\">View Reports</a> &gt; $surveyInstanceTitle - Student Reports";
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html");
	require_once("includes/instructions/inst_showlargetextstudents.php"); 

?>
</body>
</html>
