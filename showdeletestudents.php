<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php 
	//Include config.php which contains db details and connects to the db)
	require_once("includes/config.php"); 
	require_once("includes/getuser.php");
	require_once("includes/isauthor.php");
	require_once("includes/limitstring.php");
	require_once("includes/ODBCDateToTextDate.php");

	if (isset($_POST['delete']))
		{
		$surveyID = $_POST['hSurveyID'];
		$instanceID = $_POST['hSurveyInstanceID'];
		//Find out ids of all the checkboxes which were checked
		$aStudents = $_POST['checkHeraldIDs'];
		for ($i=0;$i<count($aStudents);$i++)
			{
			//first get answerIDs from Answers
			$qAnswers = "	SELECT answerID
							FROM Answers
							WHERE surveyInstanceID = $instanceID
							AND heraldID = '$aStudents[$i]'";
			
			$qResAnswers = mysqli_query($db_connection, $qAnswers);
			if (($qResAnswers == false))
				{
				echo "problem querying Answers" . mysqli_error($db_connection);
				}
			else
				{
				while($rowAnswers = mysqli_fetch_array($qResAnswers))
					{
					//delete comments, items and dates where answerID is associated with herald and surveyinstance in answers table
					//delete comments
					$delComments = "	DELETE FROM AnswerComments
										WHERE answerID = $rowAnswers[answerID]";
					$delResComments = @mysqli_query($db_connection,$delComments);
					if (($delResComments == false) && mysqli_affected_rows($db_connection) == 0)
						{
						echo "problem deleting from AnswerComments " . mysqli_error($db_connection);
						}
					//delete items
					$delItems = "		DELETE FROM AnswerItems
										WHERE answerID = $rowAnswers[answerID]";
					$delResItems = @mysqli_query($db_connection,$delItems);
					if (($delResItems == false) && mysqli_affected_rows($db_connection) == 0)
						{
						echo "problem deleting from AnswerItems " . mysqli_error($db_connection);
						}
					//delete dates
					$delDates = "		DELETE FROM AnswerDates
										WHERE answerID = $rowAnswers[answerID]";
					$delResDates = @mysqli_query($db_connection,$delDates);
					if (($delResDates == false) && mysqli_affected_rows($db_connection) == 0)
						{
						echo "problem deleting from AnswerDates " . mysqli_error($db_connection);
						}
					}
				}
			//then delete from Answers
			$delAnswers = "	DELETE FROM Answers
							WHERE surveyInstanceID = $instanceID
							AND heraldID = '$aStudents[$i]'";
			$delResAnswers = @mysqli_query($db_connection,$delAnswers);
			if (($delResAnswers == false) && mysqli_affected_rows($db_connection) == 0)
				{
				echo "problem deleting from Answers " . mysqli_error($db_connection);
				}
			//then finally delete from SurveyInstanceParticipants
			//then delete from Answers
			$delSIP = "	DELETE FROM SurveyInstanceParticipants
							WHERE surveyInstanceID = $instanceID
							AND heraldID = '$aStudents[$i]'";
			$delResSIP = @mysqli_query($db_connection, $delSIP);
			if (($delResSIP == false) && mysqli_affected_rows($db_connection) == 0)
				{
				echo "problem deleting from SurveyInstanceParticipants " . mysqli_error($db_connection);
				}
			}
		}
	elseif(isset($_POST['bShowStudents']))
		{
		//extract data from viewstudents.php
		$surveyID = $_POST['surveyID'];
		$instanceID = $_POST['sSchedule'];
		
		
		echo "<title>$surveyTitle - Students</title>";
		}
	else
		{
		echo "This page requires input from another page. Please go to <a href=\"deletestudents.php\">deletestudents.php</a>";
		exit;
		}
	//Get survey information
	$qSurveys = "SELECT title
				FROM Surveys
				WHERE surveyID = $surveyID";
	$qResSurvey = mysqli_query($db_connection, $qSurveys);
	$rowSurvey = mysqli_fetch_array($qResSurvey);
	$surveyTitle = $rowSurvey['title'];
	$qSurveyInstances = "SELECT title, startDate, finishDate
						FROM SurveyInstances
						WHERE surveyInstanceID = $instanceID";
	$qResSurveyInstance = mysqli_query($db_connection, $qSurveyInstances);
	$rowSurveyInstance = mysqli_fetch_array($qResSurveyInstance);
	$surveyInstanceTitle = $rowSurveyInstance['title'];
	$surveyInstanceStartDate = $rowSurveyInstance['startDate'];
	$surveyInstanceFinishDate = $rowSurveyInstance['finishDate'];
	
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
	echo "<a href=\"results.php\">Results</a> &gt; <a href=\"deletestudents.php\">Delete Results</a> &gt; $surveyInstanceTitle - Delete Results";
	require_once("includes/html/BreadCrumbsFooter.html"); 
	}
else
	{
	require_once("includes/html/header.html"); 
	echo "You do not have the necessary permissions to view this page";
	exit(); 
	}
echo "<h1>$surveyInstanceTitle - Delete Results</h1>";
echo "<a name=\"maintext\" id=\"maintext\"></a>";
$qStudents = "	SELECT DISTINCT SurveyInstanceParticipants.heraldID 
				FROM SurveyInstanceParticipants, SurveyInstances
				WHERE SurveyInstances.surveyID = $surveyID
				AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID ".
				($instanceID!=0?" AND SurveyInstances.surveyInstanceID = $instanceID" : "");
$qResStudents = @mysqli_query($db_connection, $qStudents);
if (($qResStudents == false))
	{
	echo "problem querying SurveyInstanceParticipants" . mysqli_error($db_connection);
	}
else
	{
	echo "<h2>Total number of students = ".mysqli_num_rows($qResStudents)."</h2>";
	if(mysqli_num_rows($qResStudents)>0)
		{
		//Prevents one student's answers being associated with them
		echo "<form id=\"frmDelete\" name=\"frmDelete\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">
				<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
		echo "	<tr class=\"matrixHeader\">
					<td  class=\"question\">&nbsp;</td>
					<td  class=\"question\">heraldID</td>
					<td class=\"question\">Name</td>
				</tr>";
		$bRowOdd = true;
		while($rowStudents = mysqli_fetch_array($qResStudents))
			{
			$studentHeraldID = $rowStudents['heraldID'];
			if($bRowOdd)
				{
				$rowClass = "matrixRowOdd";
				}
			else
				{
				$rowClass = "matrixRowEven";
				}
		echo "	<tr class=\"$rowClass\">
					<td>
						<input type=\"checkbox\" id=\"check_$studentHeraldID\" name=\"checkHeraldIDs[]\" value=\"$studentHeraldID\"/>
					</td>
					<td>".$rowStudents['heraldID']."</td>
					<td>";
					//connect to heraldID and name database
					//$dbstudent_connection = mysql_connect ($dbstudent_info['host'], $dbstudent_info['username'], $dbstudent_info['password']) or die (mysql_error());
					//$db_select = mysql_select_db ($dbstudent_info['dbname'], $dbstudent_connection) or die (mysql_error());
					$dbstudent_connection = mysqli_connect ($dbstudent_info['host'], $dbstudent_info['username'], $dbstudent_info['password'], $dbstudent_info['dbname']) or die (mysqli_error());
					$qStudentName = "	SELECT LASTNAME, FORENAMES
									FROM cards
									WHERE USERNAME = '".$rowStudents['heraldID']."'";
					$qResStudentName = @mysqli_query($qStudentName, $dbstudent_connection);
					if (($qResStudentName == false))
						{
						echo "problem querying cards" . mysqli_error($dbstudent_connection);
						}
					else
						{
						if (mysqli_num_rows($qResStudentName)==1)
							{
							$rowStudentName = mysqli_fetch_array($qResStudentName);
							echo $rowStudentName['LASTNAME'] . ", " . $rowStudentName['FORENAMES'];
							}
						else
							{
							echo "Name not found";
							}
						}	
					mysqli_close($dbstudent_connection);
					//$db_connection = mysqli_connect ($db_info['host'], $db_info['username'], $db_info['password']) or die (mysqli_error());
					//$db_select = mysqli_select_db ($db_info['dbname'], $db_connection) or die (mysqli_error($db_connection));
					$db_connection = mysqli_connect ($db_info['host'], $db_info['username'], $db_info['password'], $db_info['dbname']) or die (mysqli_error());
					echo"</td>
				</tr>";
			$bRowOdd = !$bRowOdd;
			}
		echo "<tr>
				<td colspan=\"3\">
					<input type=\"hidden\" id=\"hSurveyInstanceID\" name=\"hSurveyInstanceID\" value = \"$instanceID\"/>
					<input type=\"hidden\" id=\"hSurveyID\" name=\"hSurveyID\" value = \"$surveyID\"/>
					<input type=\"submit\" id=\"delete\" name=\"delete\" value=\"Delete\" onclick=\"return checkCheckBoxes()\"/>
				</td>
			</tr>";
		echo"	
			</table>
		</form>";
		}
	}
echo "	<script language=\"JavaScript\" type=\"text/javascript\" >
			function checkCheckBoxes()
				{
				var iNoOfStudents = 0;
				var aStudents = new Array();";				
//resets the mysql_fetch_array to start at the beginning again
idata_seek($qResStudents, 0);
while($rowStudents = mysqli_fetch_array($qResStudents))
	{
	$studentHeraldID = $rowStudents['heraldID'];
	//connect to heraldID and name database
	//$dbstudent_connection = mysqli_connect ($dbstudent_info['host'], $dbstudent_info['username'], $dbstudent_info['password']) or die (mysqli_error());
	//$db_select = mysqli_select_db ($dbstudent_info['dbname'], $dbstudent_connection) or die (mysqlii_error($dbstudent_connection));
	$dbstudent_connection= mysqli_connect ($dbstudent_info['host'], $dbstudent_info['username'], $dbstudent_info['password'], $dbstudent_info['dbname']) or die (mysqli_error());
	$qStudentName = "	SELECT LASTNAME, FORENAMES
					FROM cards
					WHERE USERNAME = '".$rowStudents['heraldID']."'";
	$qResStudentName = @mysqli_query($qStudentName, $dbstudent_connection);
	if (($qResStudentName == false))
		{
		echo "problem querying cards" . mysqli_error($dbstudent_connection);
		}
	else
		{
		if (mysqli_num_rows($qResStudentName)==1)
			{
			$rowStudentName = mysqli_fetch_array($qResStudentName);
			$studentName = $rowStudentName['LASTNAME'] . ", " . $rowStudentName['FORENAMES'];
			}
		else
			{
			$studentName =  "Name not found";
			}
		}	
mysqli_close($dbstudent_connection);
//$db_connection = mysqli_connect ($db_info['host'], $db_info['username'], $db_info['password']) or die (mysqli_error());
//$db_select = mysqli_select_db ($db_info['dbname'], $db_connection) or die (mysqli_error($db_connection));
$db_connection = mysqli_connect ($db_info['host'], $db_info['username'], $db_info['password'], $db_info['dbname']) or die (mysqli_error());	
echo "			if (document.getElementById(\"check_$studentHeraldID\").checked == true)
					{
					iNoOfStudents=iNoOfStudents+1;
					aStudents[iNoOfStudents] = \"$studentHeraldID - $studentName\";
					}";
	}
echo "			if (iNoOfStudents == 0)
					{
					alert(\"Please select a student whose results you want to delete.\");
					return false;
					}
				else
					{
					if (iNoOfStudents == 1)
						{
						var confirmText = \"Are you sure you want to permanently delete all the results for \" + aStudents[iNoOfStudents] + \" for schedule $surveyInstanceTitle?\";
						}
					else
						{
						var confirmText = \"Are you sure you want to permanently delete all the results for these \" + iNoOfStudents + \" students for schedule $surveyInstanceTitle?\";
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
		echo "<a href=\"results.php\">Results</a> &gt; <a href=\"deletestudents.php\">Delete Results</a> &gt; $surveyInstanceTitle - Delete Results";
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html");
	require_once("includes/instructions/inst_showdeletestudents.php"); 

?>
</body>
</html>
