<?php 
	//Include config.php which contains db details and connects to the db)
	require_once("includes/config.php"); 
	require_once("includes/getuser.php");
	require_once("includes/isauthor.php");
	require_once("includes/limitstring.php");
	require_once("includes/ODBCDateToTextDate.php");
	require_once("includes/quote_smart.php");
	
	if(!IsAuthor($heraldID))
		{
		echo "You do not have the necessary permissions to view this page";
		exit(); 
		}

	if(!isset($_POST['surveyID']))
		{
		echo "This page requires input from another page. Please go to <a href=\"viewstudentbystudent.php\">viewstudentbystudent.php</a>";
		exit;
		}
	//extract data from viewresults.php
	$surveyID = $_POST['surveyID'];
	//$noOfCriteria = $_POST['hNoOfCriteria'];
	if($_POST['rStartDate']==0)
		{
		$startDate="NULL";
		}
	else
		{
		$startDate = date("Y-m-d", mktime(0, 0, 0, $_POST['startMonth'], $_POST['startDay'], $_POST['startYear'])); 
		}
	if($_POST['rFinishDate']==0)
		{
		$finishDate="NULL";
		}
	else
		{
		$finishDate = date("Y-m-d", mktime(0, 0, 0, $_POST['finishMonth'], $_POST['finishDay'], $_POST['finishYear']));
		}
	$showSaved = $_POST['chkSaved'];
	$showSubmitted = $_POST['chkSubmitted'];
	$instanceID = $_POST['sSchedule'];
	$showHidden = $_POST['chkShowHidden'];
	
	//work out bit of SQL to deal with status in results queries
	$strStatusQuery = "";
	$savedSubmittedText = "";
	if($showSaved=='on' && $showSubmitted!='on')
		{
		$strStatusQuery = " AND SurveyInstanceParticipants.status = 1 ";
		$savedSubmittedText = "Students who have saved their survey";
		}
	elseif($showSaved=='on' && $showSubmitted=='on')
		{
		$strStatusQuery = " AND (SurveyInstanceParticipants.status = 1 OR SurveyInstanceParticipants.status = 2) ";
		$savedSubmittedText = "Students who have saved or submitted their survey";
		}
	elseif($showSaved!='on' && $showSubmitted=='on')
		{
		$strStatusQuery = " AND SurveyInstanceParticipants.status = 2 ";
		$savedSubmittedText = "Students who have submitted their survey";
		}
	
	
	//Get survey information
	$qSurveys = "SELECT title
				FROM Surveys
				WHERE surveyID = $surveyID";
	$qResSurvey = mysqli_query($db_connection, $qSurveys);
	$rowSurvey = mysqli_fetch_array($qResSurvey);
	$surveyTitle = $rowSurvey[title];
	$surveyTitleNoSpaces = str_replace(' ', '', $surveyTitle);
	$surveyTitleNoSpaces = quote_smart($surveyTitleNoSpaces);
	$filename=$surveyTitleNoSpaces."_Results.xls";
	
	//the following is necessary to allow IE to open downloads under https
	//see http://uk.php.net/manual/en/function.session-cache-limiter.php

	header("Expires: " . gmdate("D, d M Y H:i:s", time() + 5) . " GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()-10) . " GMT");
	header("Pragma: public");
	//header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: public"); 
	header("Content-Description: File Transfer");

	session_cache_limiter("must-revalidate");
	header("Content-Type: application/vnd.ms-excel");
	header('Content-Disposition: attachment; filename="Results.xls"');

	// and after you start the session
	//session_start();
	
	//error_reporting(E_ALL);


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<?php echo "<title>$surveyTitle - Results by student</title>";?>
</head>

<body>
<?php
echo "<h1>$surveyTitle - Results by student</h1>";
echo "<a name=\"maintext\" id=\"maintext\"></a>"; 
$qStudents = "	SELECT DISTINCT SurveyInstanceParticipants.heraldID 
				FROM SurveyInstanceParticipants, SurveyInstances
				WHERE SurveyInstances.surveyID = $surveyID
				AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID".
				($strStatusQuery!=""?$strStatusQuery:"").
				($instanceID!=0?" AND SurveyInstances.surveyInstanceID = $instanceID" : "").
				($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
				($finishDate!="NULL"?" AND SurveyInstanceParticipants.date <= '$finishDate'":"");
$qResStudents = @mysqli_query($db_connection, $qStudents);
if (($qResStudents == false))
	{
	echo "problem querying SurveyInstanceParticipants" . mysqli_error();
	}
echo "<h2>Total number of students = ".mysqli_num_rows($qResStudents)."</h2>";


$tableHeader = "<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\"><tr class=\"matrixHeader\"><th>Username</th><th>Real name</th>";
$qBlocks = "	SELECT Blocks.blockID, Blocks.title, Blocks.introduction, Blocks.epilogue, SurveyBlocks.position, Blocks.instanceable, SurveyBlocks.visible
				FROM Blocks, SurveyBlocks 
				WHERE SurveyBlocks.surveyID = $surveyID
				AND Blocks.blockID = SurveyBlocks.blockID
				ORDER BY SurveyBlocks.position";
$qResBlocks = mysqli_query($db_connection, $qBlocks);
$questionNo = 1;	//counter for questions 
while($rowBlocks = mysqli_fetch_array($qResBlocks))
	{
	$blockID = $rowBlocks['blockID'];
	$blockVisible = $rowBlocks['visible'];
	$qSections = "	SELECT Sections.sectionID, Sections.title, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, BlockSections.visible 
					FROM Sections, BlockSections 
					WHERE BlockSections.blockID = $blockID
					AND Sections.sectionID = BlockSections.sectionID
					ORDER BY BlockSections.position";
	$qResSections = mysqli_query($db_connection, $qSections);
	while($rowSections = mysqli_fetch_array($qResSections))
		{
		$sectionID = $rowSections['sectionID'];
		$sectionVisible = $rowSections['visible'];
		//get questions
		$qQuestions = "SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position, SectionQuestions.visible 
					FROM Questions, SectionQuestions
					WHERE SectionQuestions.sectionID = $sectionID
					AND Questions.questionID = SectionQuestions.questionID
					ORDER BY SectionQuestions.position";
		$qResQuestions = mysqli_query($db_connection, $qQuestions);
		while($rowQuestions = mysqli_fetch_array($qResQuestions))
			{
			$questionID = $rowQuestions['questionID'];
			$questionVisible = $rowQuestions['visible'];
			$questionTypeID = $rowQuestions['questionTypeID'];
			if(($blockVisible==1 && $sectionVisible==1 && $questionVisible==1) || $showHidden=='on')
				{
				$tableHeader = $tableHeader . "<th align=\"left\">".$questionNo.". ".$rowQuestions['text']."</th>";
				if($questionTypeID==1||$questionTypeID==2||$questionTypeID==3||$questionTypeID==6)
					{
					if($rowQuestions['comments']=="true")
						{
						$tableHeader = $tableHeader . "<th>Comment</th>";
						}
					}
				$questionNo++;
				}
			}
		}
	}
$tableHeader = $tableHeader . "</tr>";

$bRowOdd = true;
$tableBody = "";
while($rowStudent=mysqli_fetch_array($qResStudents))
	{
	$studentID = $rowStudent['heraldID'];
	$questionNo = 1;	//counter for questions 
	if($bRowOdd)
		{
		$rowClass = "matrixRowOdd";
		}
	else
		{
		$rowClass = "matrixRowEven";
		}
	$tableBody = $tableBody . "<tr class=\"$rowClass\">";
	$tableBody = $tableBody . "<td>".$studentID."</td>";
	$tableBody = $tableBody . "<td>";
	//connect to heraldID and name database
	//$dbstudent_connection = mysql_connect ($dbstudent_info['host'], $dbstudent_info['username'], $dbstudent_info['password']) or die (mysql_error());
	//$db_select = mysql_select_db ($dbstudent_info['dbname'], $dbstudent_connection) or die (mysql_error());
	$dbstudent_connection = mysqli_connect ($dbstudent_info['host'], $dbstudent_info['username'], $dbstudent_info['password'], $dbstudent_info['dbname']) or die (mysqli_error());
	$qStudentName = "	SELECT LASTNAME, FORENAMES
					FROM cards
					WHERE USERNAME = '$studentID'";
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
			$tableBody = $tableBody . $rowStudentName['LASTNAME'] . ", " . $rowStudentName['FORENAMES'];
			}
		else
			{
			$tableBody = $tableBody . "Name not found";
			}
		}
	mysqli_close($dbstudent_connection);
	//$db_connection = mysql_connect ($db_info['host'], $db_info['username'], $db_info['password']) or die (mysql_error());
	//$db_select = mysql_select_db ($db_info['dbname'], $db_connection) or die (mysql_error());
	$db_connection = mysqli_connect ($db_info['host'], $db_info['username'], $db_info['password'], $db_info['dbname']) or die (mysqli_error());
	$tableBody = $tableBody . "</td>";
	$qBlocks = "	SELECT Blocks.blockID, Blocks.title, Blocks.introduction, Blocks.epilogue, SurveyBlocks.position, Blocks.instanceable, SurveyBlocks.visible
					FROM Blocks, SurveyBlocks 
					WHERE SurveyBlocks.surveyID = $surveyID
					AND Blocks.blockID = SurveyBlocks.blockID
					ORDER BY SurveyBlocks.position";
	$qResBlocks = mysqli_query($db_connection, $qBlocks);
	while($rowBlocks = mysqli_fetch_array($qResBlocks))
		{
		$blockID = $rowBlocks['blockID'];
		$blockVisible = $rowBlocks['visible'];
		$qSections = "	SELECT Sections.sectionID, Sections.title, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, BlockSections.visible 
						FROM Sections, BlockSections 
						WHERE BlockSections.blockID = $blockID
						AND Sections.sectionID = BlockSections.sectionID
						ORDER BY BlockSections.position";
		
		$qResSections = mysqli_query($db_connection, $qSections);
		while($rowSections = mysqli_fetch_array($qResSections))
			{
			$sectionID = $rowSections['sectionID'];
			$sectionVisible = $rowSections['visible'];
			//get questions
			$qQuestions = "SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position, SectionQuestions.visible
						FROM Questions, SectionQuestions
						WHERE SectionQuestions.sectionID = $sectionID
						AND Questions.questionID = SectionQuestions.questionID
						ORDER BY SectionQuestions.position";
			
			$qResQuestions = mysqli_query($db_connection, $qQuestions);
			while($rowQuestions = mysqli_fetch_array($qResQuestions))
				{
				$questionID = $rowQuestions['questionID'];
				$questionTypeID = $rowQuestions['questionTypeID'];
				$questionVisible = $rowQuestions['visible'];
				if(($blockVisible==1 && $sectionVisible==1 && $questionVisible==1) || $showHidden=='on')
					{
					//get participants comments for this item
					$qComments = "SELECT DISTINCT AnswerComments.text
								FROM Answers, AnswerComments, SurveyInstances, SurveyInstanceParticipants  
								WHERE AnswerComments.text <> ''
								AND AnswerComments.text <> 'Please comment..' ".
								($strStatusQuery!=""?$strStatusQuery:"").
								($instanceID!=0?" AND SurveyInstances.surveyInstanceID = $instanceID" : " AND SurveyInstances.surveyID = $surveyID ").
								($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
								($finishDate!="NULL"?" AND SurveyInstanceParticipants.date <= '$finishDate'":"").
								" AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
								AND Answers.heraldID = '$studentID'
								AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
								AND Answers.blockID = $blockID
								AND Answers.sectionID = $sectionID
								AND Answers.questionID = $questionID
								AND Answers.answerID = AnswerComments.answerID";
					$qResComments = mysqli_query($db_connection, $qComments);
					if($questionTypeID==1||$questionTypeID==2||$questionTypeID==3)
						{
						$qItemAnswers = " SELECT DISTINCT AnswerItems.itemID
									FROM Answers, AnswerItems, SurveyInstances, SurveyInstanceParticipants  
									WHERE Answers.heraldID = '$studentID'".
									($strStatusQuery!=""?$strStatusQuery:"").
									($instanceID!=0?" AND SurveyInstances.surveyInstanceID = $instanceID" : " AND SurveyInstances.surveyID = $surveyID ").
									($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
									($finishDate!="NULL"?" AND SurveyInstanceParticipants.date <= '$finishDate'":"").
									" AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
									AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
									AND Answers.blockID = $blockID
									AND Answers.sectionID = $sectionID
									AND Answers.questionID = $questionID
									AND Answers.answerID = AnswerItems.answerID";
						$qResItemAnswers = mysqli_query($db_connection, $qItemAnswers);
						if(mysqli_num_rows($qResItemAnswers)>0)
							{
							if($questionTypeID==2)
								{
								$tableBody = $tableBody . "<td>";
								$first_one = 1;
								}
							while($rowItemAnswers = mysqli_fetch_array($qResItemAnswers))
								{
								$itemID = $rowItemAnswers['itemID'];
								$qItems = "	SELECT Items.text
											FROM Items
											WHERE Items.itemID = $itemID";
								$qResItems = mysqli_query($db_connection, $qItems);
								if($questionTypeID==2)
									{
									while($rowItems = mysqli_fetch_array($qResItems))
										{
										if($first_one==1)
											{
											$first_one=0;
											}
										else
											{
											$tableBody = $tableBody . "|";
											}
										$tableBody = $tableBody . $rowItems['text'];
										}
									}
								else
									{
									while($rowItems = mysqli_fetch_array($qResItems))
										{
										$tableBody = $tableBody . "<td>".$rowItems['text']."</td>";
										}
									}
								}
							if($questionTypeID==2)
								{
								$tableBody = $tableBody . "</td>";
								}
							}
						else
							{
							$tableBody = $tableBody . "<td>&nbsp;</td>";
							}
						if($rowQuestions['comments']=='true')
							{
							if(mysqli_num_rows($qResComments)>0)
								{
								while($rowComments = mysqli_fetch_array($qResComments))
									{
									//handle comments with another question type separately
									$tableBody = $tableBody . "<td>".$rowComments['text']."</td>";
									}
								}
							else
								{
								$tableBody = $tableBody . "<td>&nbsp;</td>";
								}
							}
						}
					elseif($questionTypeID==6)
						{
						$qDates = " SELECT DISTINCT AnswerDates.date
								FROM Answers, AnswerDates, SurveyInstances, SurveyInstanceParticipants  
								WHERE AnswerDates.answerID = Answers.answerID ".
								($strStatusQuery!=""?$strStatusQuery:"").
								($instanceID!=0?" AND SurveyInstances.surveyInstanceID = $instanceID" : " AND SurveyInstances.surveyID = $surveyID").
								($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
								($finishDate!="NULL"?" AND SurveyInstanceParticipants.date <= '$finishDate'":"").
								" AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
								AND Answers.heraldID = '$studentID'
								AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
								AND Answers.blockID = $blockID
								AND Answers.sectionID = $sectionID
								AND Answers.questionID = $questionID";
						$qResDates = mysqli_query($db_connection, $qDates);
						if(mysqli_num_rows($qResDates)>0)
							{
							while($rowDates = mysqli_fetch_array($qResDates))
								{
								$tableBody = $tableBody . "<td>".$rowDates['date']."</td>";
								}
							}
						else
							{
							$tableBody = $tableBody . "<td>&nbsp;</td>";
							}
						if($rowQuestions['comments']=='true')
							{
							if(mysqli_num_rows($qResComments)>0)
								{
								while($rowComments = mysqli_fetch_array($qResComments))
									{
									//handle comments with another question type separately
									$tableBody = $tableBody . "<td>".$rowComments['text']."</td>";
									}
								}
							else
								{
								$tableBody = $tableBody . "<td>&nbsp;</td>";
								}
							}
							
						}
					else
						{
						if(mysqli_num_rows($qResComments)>0)
							{
							while($rowComments = mysqli_fetch_array($qResComments))
								{
								//handle comments with another question type separately
								$tableBody = $tableBody . "<td>".$rowComments['text']."</td>";
								}
							}
						else
							{
							$tableBody = $tableBody . "<td>&nbsp;</td>";
							}
						}
					$questionNo++;
					}
				}
			}
		}
	$tableBody = $tableBody . "</tr>";
	$bRowOdd = !$bRowOdd;
	}
$tableBody = $tableBody . "</table>";
echo $tableHeader;
echo $tableBody;
//echo "</table>"; 
//echo "</div>";
?>
</body>
</html>
