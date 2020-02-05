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

	if($_SERVER['REQUEST_METHOD'] != 'POST')
		{
		echo "This page requires input from another page. Please go to <a href=\"viewresults.php\">viewresults.php</a>";
		exit;
		}
	//extract data from viewresults.php
	$surveyID = $_POST['surveyID'];
	$noOfCriteria = $_POST['hNoOfCriteria'];
	$showHidden = $_POST['chkShowHidden'];
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
	//delete any exirting records in ParticipantItems as this is only a temporary table used 
	//to simplify queries within this .php file.
	$dParticipantItems = "DELETE FROM ParticipantItems";
	$dResParticipantItems = mysqli_query($dParticipantItems);
	//This is an array to hold the items in each question by which other questions are being analysed 
	//$aQuestionToAnalyseByItems[x][y][z]
	// x = criterion number
	// y = item number
	// z = 0 = itemID
	// z = 1 = questionText
	// z = 2 = itemText
	$aQuestionToAnalyseByItems = array();
	$aQuestionToAnalyseBy = array();
	$aQuestionsToAnalyse = array();
	for($i=1;$i<=$noOfCriteria;$i++)
		{
		$analyseByName = "qAnalyseBy_" . $i;
		$hidQuestionsName = "hidQuestions_" . $i;
		//get question to analyse by and questions to analyse for this criterion
		if(isset($_POST[$analyseByName]))
			{
			$aQuestionToAnalyseBy[$i] = explode("_",$_POST[$analyseByName]);
			$aQuestionsToAnalyse[$i] = explode(",",$_POST[$hidQuestionsName]);
			}
		//split question to analyse by inro Block, Section and Question IDs
		$QuestionToAnalyseByBlockID = intval($aQuestionToAnalyseBy[$i][0]);
		$QuestionToAnalyseBySectionID = intval($aQuestionToAnalyseBy[$i][1]);
		$QuestionToAnalyseByQuestionID = intval($aQuestionToAnalyseBy[$i][2]);
		//get the items for the $QuestionToAnalyseBy 
		$qQuestionItems = "SELECT QuestionItems.itemID, QuestionItems.position, Items.text as itemText, Questions.text as questionText
						FROM QuestionItems, Questions, Items
						WHERE Questions.questionID = $QuestionToAnalyseByQuestionID
						AND QuestionItems.questionID = Questions.questionID".
						($showHidden=='on'?" " : " AND QuestionItems.visible = 1 ").
						"AND Items.itemID = QuestionItems.itemID
						ORDER BY position";
		
		$qResQuestionItems = mysqli_query($qQuestionItems);
		$itemCounter = 0;
		while($rowQuestionItems = mysqli_fetch_array($qResQuestionItems))
			{
			$aQuestionItem = array();
			$aQuestionItem[0] = $rowQuestionItems[itemID];
			$aQuestionItem[1] = $rowQuestionItems[questionText];
			$aQuestionItem[2] = $rowQuestionItems[itemText];
			$aQuestionToAnalyseByItems[$i][$itemCounter] = $aQuestionItem;
			//get the IDs of participants who answered this item in:
			// - the specified instances survey
			// - between the dates specified
			// - and of the specified status
			$QuestionToAnalyseByItem = intval($aQuestionToAnalyseByItems[$i][$itemCounter][0]);
			$qParticipants = "	SELECT Answers.heraldID, Answers.instance,Answers.sinstance
								FROM Answers, AnswerItems, SurveyInstances, SurveyInstanceParticipants
								WHERE Answers.heraldID = SurveyInstanceParticipants.heraldID ".
								($strStatusQuery!=""?$strStatusQuery:"").
								($instanceID!=0?" AND SurveyInstances.surveyInstanceID = $instanceID" : " AND SurveyInstances.surveyID = $surveyID").
								($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
								($finishDate!="NULL"?" AND SurveyInstanceParticipants.date <= '$finishDate'":"").
								" AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
								AND Answers.blockID = $QuestionToAnalyseByBlockID
								AND Answers.sectionID = $QuestionToAnalyseBySectionID
								AND Answers.questionID = $QuestionToAnalyseByQuestionID
								AND AnswerItems.answerID = Answers.answerID
								AND AnswerItems.itemID = $QuestionToAnalyseByItem
								AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID";
			$qResParticipants = mysqli_query($qParticipants);
			$NoOfParticipants = mysqli_num_rows($qResParticipants);
			//now write those participants to a table to store them temporarily next to the item they chose
			while($rowParticipants = mysqli_fetch_array($qResParticipants))
				{
				$iParticipantItems = "	INSERT INTO ParticipantItems
										VALUES(0,'$rowParticipants[heraldID]',$QuestionToAnalyseByBlockID,$QuestionToAnalyseBySectionID,$QuestionToAnalyseByQuestionID,$QuestionToAnalyseByItem,$rowParticipants[instance],$rowParticipants[sinstance])";
				$result_query = @mysqli_query($db_connection, $iParticipantItems);
				if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
					{
					echo "problem inserting into ParticipantItems" . mysqli_error();
					$bSuccess = false;
					}
				}
			$itemCounter = $itemCounter + 1;
			}
		}
	
	//Get survey information
	$qSurveys = "SELECT title
				FROM Surveys
				WHERE surveyID = $surveyID";
	$qResSurvey = mysqli_query($qSurveys);
	$rowSurvey = mysqli_fetch_array($qResSurvey);
	$surveyTitle = $rowSurvey[title];
	$surveyTitleNoSpaces = str_replace(' ', '', $surveyTitle);
	$surveyTitleNoSpaces = quote_smart($surveyTitleNoSpaces);
	//echo"surveyTitle = " . $surveyTitle;
	//echo"surveyTitleNoSpaces = " . $surveyTitleNoSpaces;
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


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<?php echo "<title>$surveyTitle - Results</title>";?>
</head>

<body>
<?php
echo "<h1>$surveyTitle - Results</h1>";
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
$qBlocks = "SELECT Blocks.blockID, Blocks.title, Blocks.text, Blocks.introduction, Blocks.epilogue, SurveyBlocks.position, Blocks.instanceable, SurveyBlocks.visible
			FROM Blocks, SurveyBlocks 
			WHERE SurveyBlocks.surveyID = $surveyID
			AND Blocks.blockID = SurveyBlocks.blockID
			ORDER BY SurveyBlocks.position";
$qResBlocks = mysqli_query($qBlocks);
//echo "<div class=\"resultsTable\">";
//echo "<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">"; 
//echo "	<tr class=\"matrixHeader\">
//			<th>Question</th>
//			<th>Item</th>
//			<th>No. of particpants who chose this item</th>
//			<th>% of total participants who chose this item</th>
//			<th>Total no. of particpants who attempted this question</th>
//			<th>Comments</th>
//			<th>Participants who chose:</th>
//		</tr>";
//counter for questions 
$questionNo = 1;	
$bRowOdd = true;
$colspan=3;
while($rowBlocks = mysqli_fetch_array($qResBlocks))
	{
	$blockID = $rowBlocks[blockID];
	$blockTitle = $rowBlocks[text];
	$blockVisible = $rowBlocks[visible];
	if($blockTitle != "" && ($blockVisible==1 || $showHidden=='on')) echo "<h3>".$blockTitle."</h3>";
	$qSections = "	SELECT Sections.sectionID, Sections.title, Sections.text, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, BlockSections.visible 
					FROM Sections, BlockSections 
					WHERE BlockSections.blockID = $blockID
					AND Sections.sectionID = BlockSections.sectionID
					ORDER BY BlockSections.position";
	
	$qResSections = mysqli_query($qSections);

	while($rowSections = mysqli_fetch_array($qResSections))
		{
		$sectionID = $rowSections[sectionID];
		$sectionTitle = $rowSections[text];
		$sectionVisible = $rowSections[visible];
		if($blockVisible==1 && ($sectionVisible==1 || $showHidden=='on')) echo "<h4>".$sectionTitle."</h4>";
		//get questions
		$qQuestions = "SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position, SectionQuestions.visible 
					FROM Questions, SectionQuestions
					WHERE SectionQuestions.sectionID = $sectionID
					AND Questions.questionID = SectionQuestions.questionID
					ORDER BY SectionQuestions.position";
		
		$qResQuestions = mysqli_query($qQuestions);
		
		while($rowQuestions = mysqli_fetch_array($qResQuestions))
			{
			$questionID = $rowQuestions[questionID];
			$questionVisible = $rowQuestions[visible];
			$bAnalyseThisQuestion = false;
			//work out whether this question is being analysed by any of the criteria
			for($i=1;$i<=$noOfCriteria;$i++)
				{
				for($j=0; $j<count($aQuestionsToAnalyse[$i]); $j++)
					{
					$aTemp = explode("_",$aQuestionsToAnalyse[$i][$j]);
					$qBlockID = $aTemp[0];
					$qSectionID = $aTemp[1];
					$qQuestionID = $aTemp[2];
					if ($qBlockID==$blockID && $qSectionID==$sectionID && $qQuestionID==$questionID)
						{
						//we need to output this question according to the participants who chose QuestionToAnalyseBy
						$bAnalyseThisQuestion = true;
						//remember criterion in which this question is being analysed
						$criterionNumber = $i;
						}
					}
				} 
			if($bAnalyseThisQuestion == true)
				{
				//create a string to hold each row's html, this will be added to column by column as we 
				//step through $aQuestionToAnalyseByItems				
				for ($i=0;$i<count($aQuestionToAnalyseByItems[$criterionNumber]);$i++)
					{
					if(($blockVisible==1 && $sectionVisible==1 && $questionVisible==1) || $showHidden=='on')
						{
						echo "	<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">
								<tr class=\"matrixHeader\">
									<th align=\"left\" colspan=\"".$colspan."\">".$questionNo.". ".$rowQuestions[text]." - analysed by ".$aQuestionToAnalyseByItems[$criterionNumber][$i][1]." - ".$aQuestionToAnalyseByItems[$criterionNumber][$i][2]."</th>
								</tr>";
						} 
					
					//split question to analyse by inro Block, Section and Question IDs
					$QuestionToAnalyseByBlockID = intval($aQuestionToAnalyseBy[$criterionNumber][0]);
					$QuestionToAnalyseBySectionID = intval($aQuestionToAnalyseBy[$criterionNumber][1]);
					$QuestionToAnalyseByQuestionID = intval($aQuestionToAnalyseBy[$criterionNumber][2]);
					//begin outputting results by item of $aQuestionToAnalyseBy
					//get number of participants who answered this question who also answered 
					//each item in aQuestionToAnalyseByItems
					$QuestionToAnalyseByItem = intval($aQuestionToAnalyseByItems[$criterionNumber][$i][0]);
					
					$qCountAnswers = "SELECT Answers.answerID
									FROM Answers, ParticipantItems, SurveyInstances, SurveyInstanceParticipants
									WHERE ParticipantItems.blockID = $QuestionToAnalyseByBlockID
									AND ParticipantItems.sectionID = $QuestionToAnalyseBySectionID
									AND ParticipantItems.questionID = $QuestionToAnalyseByQuestionID
									AND ParticipantItems.itemID = $QuestionToAnalyseByItem
									AND Answers.questionID = $questionID ".
									($strStatusQuery!=""?$strStatusQuery:"").
									($instanceID!=0?" AND SurveyInstances.surveyInstanceID = $instanceID" : " AND SurveyInstances.surveyID = $surveyID ").
									($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
									($finishDate!="NULL"?" AND SurveyInstanceParticipants.date <= '$finishDate'":"").
									" AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
									AND Answers.heraldID = SurveyInstanceParticipants.heraldID
									AND Answers.heraldID = ParticipantItems.heraldID
									AND Answers.instance = ParticipantItems.instance
									AND Answers.sinstance = ParticipantItems.sinstance
									AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
									AND Answers.blockID = $blockID
									AND Answers.sectionID = $sectionID";
					
					$qResCountAnswers = mysqli_query($qCountAnswers);
					
					$NoOfAnswers = mysqli_num_rows($qResCountAnswers);
					//get participants comments for this item
					$qComments = "SELECT AnswerComments.text
								FROM Answers, AnswerComments, ParticipantItems, SurveyInstances, SurveyInstanceParticipants
								WHERE ParticipantItems.blockID = $QuestionToAnalyseByBlockID
								AND ParticipantItems.sectionID = $QuestionToAnalyseBySectionID
								AND ParticipantItems.questionID = $QuestionToAnalyseByQuestionID
								AND ParticipantItems.itemID = $QuestionToAnalyseByItem
								AND AnswerComments.text <> ''
								AND AnswerComments.text <> 'Please comment..'
								AND AnswerComments.text <> 'If you have answered: Strongly Agree; Disagree; or Strongly Disagree, please tell us why...'
								AND AnswerComments.text <> 'If you have answered: Excellent or Poor, please tell us why...'	".
								($strStatusQuery!=""?$strStatusQuery:"").
								($instanceID!=0?" AND SurveyInstances.surveyInstanceID = $instanceID" : " AND SurveyInstances.surveyID = $surveyID ").
								($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
								($finishDate!="NULL"?" AND SurveyInstanceParticipants.date <= '$finishDate'":"").
								"  AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
								AND Answers.heraldID = SurveyInstanceParticipants.heraldID
								AND Answers.heraldID = ParticipantItems.heraldID
								AND Answers.instance = ParticipantItems.instance
								AND Answers.sinstance = ParticipantItems.sinstance
								AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
								AND Answers.blockID = $blockID
								AND Answers.sectionID = $sectionID
								AND Answers.questionID = $questionID
								AND Answers.answerID = AnswerComments.answerID";
					$qResComments = mysqli_query($qComments);
					
					//get participants comments for this item
					$qDates = "SELECT AnswerDates.date
								FROM Answers, AnswerDates, ParticipantItems, SurveyInstances, SurveyInstanceParticipants
								WHERE ParticipantItems.blockID = $QuestionToAnalyseByBlockID
								AND ParticipantItems.sectionID = $QuestionToAnalyseBySectionID
								AND ParticipantItems.questionID = $QuestionToAnalyseByQuestionID
								AND ParticipantItems.itemID = $QuestionToAnalyseByItem
								AND Answers.answerID = AnswerDates.answerID ".
								($strStatusQuery!=""?$strStatusQuery:"").
								($instanceID!=0?" AND SurveyInstances.surveyInstanceID = $instanceID" : " AND SurveyInstances.surveyID = $surveyID").
								($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
								($finishDate!="NULL"?" AND SurveyInstanceParticipants.date <= '$finishDate'":"").
								" AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
								AND Answers.heraldID = SurveyInstanceParticipants.heraldID
								AND Answers.heraldID = ParticipantItems.heraldID
								AND Answers.instance = ParticipantItems.instance
								AND Answers.sinstance = ParticipantItems.sinstance
								AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
								AND Answers.blockID = $blockID
								AND Answers.sectionID = $sectionID
								AND Answers.questionID = $questionID";
					$qResDates = mysqli_query($qDates);
					
					//get items
					$qItems = "SELECT Items.itemID, Items.text, QuestionItems.position
							FROM Items, QuestionItems
							WHERE QuestionItems.questionID = $rowQuestions[questionID]".
							($showHidden=='on'?" " : " AND QuestionItems.visible = 1 ").
							"AND Items.itemID = QuestionItems.itemID
							ORDER BY QuestionItems.position";
										
					$qResItems = mysqli_query($qItems);
					//check whether any of the items for this question have been checked ('on')
					$itemNo = 0;
					$bRowOdd = false;
					$rowClass = "matrixRowEven";
					while($rowItems = mysqli_fetch_array($qResItems))
						{
						if($bRowOdd)
							{
							$rowClass = "matrixRowOdd";
							}
						else
							{
							$rowClass = "matrixRowEven";
							}
						//get number of participants who chose this item
						$qCountItems = "SELECT Answers.answerID
										FROM Answers, AnswerItems, ParticipantItems, SurveyInstances, SurveyInstanceParticipants
										WHERE ParticipantItems.blockID = $QuestionToAnalyseByBlockID
										AND ParticipantItems.sectionID = $QuestionToAnalyseBySectionID
										AND ParticipantItems.questionID = $QuestionToAnalyseByQuestionID
										AND ParticipantItems.itemID = $QuestionToAnalyseByItem
										AND AnswerItems.itemID = $rowItems[itemID] ".
										($strStatusQuery!=""?$strStatusQuery:"").
										($instanceID!=0?" AND SurveyInstances.surveyInstanceID = $instanceID" : " AND SurveyInstances.surveyID = $surveyID ").
										($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
										($finishDate!="NULL"?" AND SurveyInstanceParticipants.date <= '$finishDate'":"").
										" AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
										AND Answers.heraldID = SurveyInstanceParticipants.heraldID
										AND Answers.heraldID = ParticipantItems.heraldID
										AND Answers.instance = ParticipantItems.instance
										AND Answers.sinstance = ParticipantItems.sinstance
										AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
										AND Answers.blockID = $blockID
										AND Answers.sectionID = $sectionID
										AND Answers.questionID = $questionID
										AND Answers.answerID = AnswerItems.answerID";
						$qResCountItems = mysqli_query($qCountItems);
						
						$NoOfItems = mysqli_num_rows($qResCountItems);
						if($NoOfAnswers>0)
							{
							$PercentageOfAnswers = round($NoOfItems/$NoOfAnswers * 100,1); 
							}
						else
							{
							$PercentageOfAnswers = 0;
							} 
						if(($blockVisible==1 && $sectionVisible==1 && $questionVisible==1) || $showHidden=='on') echo"<tr class=\"$rowClass\"><td>$rowItems[text]</td><td>$PercentageOfAnswers</td><td>[$NoOfItems]</td></tr>";
						$itemNo++;
						$bRowOdd = !$bRowOdd;
						if($bRowOdd)
							{
							$rowClass = "matrixRowOdd";
							}
						else
							{
							$rowClass = "matrixRowEven";
							}
						}
					$dateCounter = 1;
					while($rowDates = mysqli_fetch_array($qResDates))
						{
						if(($blockVisible==1 && $sectionVisible==1 && $questionVisible==1) || $showHidden=='on')
							{
							echo "<tr class=\"$rowClass\">
								<td valign=\"top\" colspan=\"".$colspan."\">"
									 .$dateCounter. " - " .ODBCDateToTextDateShort($rowDates['date']). " <br/>";
									 $dateCounter++;
							echo " </td>
								</tr>"; 
							}
						}
					$bRowOdd = !$bRowOdd;
					if($bRowOdd)
						{
						$rowClass = "matrixRowOdd";
						}
					else
						{
						$rowClass = "matrixRowEven";
						}
					if(($blockVisible==1 && $sectionVisible==1 && $questionVisible==1) || $showHidden=='on')
						{
						echo"<tr class=\"$rowClass\"><td valign=\"top\" colspan=\"".$colspan."\">
						<div class=\"comments\" id=\"divComments_".$questionNo.$i."\" name=\"divComments_".$questionNo.$i."\">
							<strong>Comments:</strong>
							<br/>";
							$commentCount = 1;
							while($rowComments = mysqli_fetch_array($qResComments))
								{
								echo $commentCount . " -  " .$rowComments[text]. "<br/>";
								$commentCount++;
								}
						echo"</div></td></tr>";
						echo "</table>"; 
						}
					}
				} 
			else
				{
				if(($blockVisible==1 && $sectionVisible==1 && $questionVisible==1) || $showHidden=='on')
					{
					echo "	<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">
								<tr class=\"matrixHeader\">
									<th align=\"left\" colspan=\"".$colspan."\">".$questionNo.". ".$rowQuestions[text]."</th>
								</tr>";
					} 
				//get number of participants who answered this question
				$qCountAnswers = "SELECT Answers.answerID
								FROM Answers, SurveyInstances, SurveyInstanceParticipants 
								WHERE questionID = $questionID ".
								($strStatusQuery!=""?$strStatusQuery:"").
								($instanceID!=0?" AND SurveyInstances.surveyInstanceID = $instanceID" : " AND SurveyInstances.surveyID = $surveyID ").
								($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
								($finishDate!="NULL"?" AND SurveyInstanceParticipants.date <= '$finishDate'":"").
								" AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
								AND Answers.heraldID = SurveyInstanceParticipants.heraldID
								AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
								AND Answers.blockID = $blockID
								AND sectionID = $sectionID";
				$qResCountAnswers = mysqli_query($qCountAnswers);
				
				$NoOfAnswers = mysqli_num_rows($qResCountAnswers);
				
				//get participants comments for this item
				$qComments = "SELECT AnswerComments.text
							FROM Answers, AnswerComments, SurveyInstances, SurveyInstanceParticipants  
							WHERE AnswerComments.text <> ''
							AND AnswerComments.text <> 'Please comment..'
							AND AnswerComments.text <> 'If you have answered: Strongly Agree; Disagree; or Strongly Disagree, please tell us why...'
							AND AnswerComments.text <> 'If you have answered: Excellent or Poor, please tell us why...' ".
							($strStatusQuery!=""?$strStatusQuery:"").
							($instanceID!=0?" AND SurveyInstances.surveyInstanceID = $instanceID" : " AND SurveyInstances.surveyID = $surveyID ").
							($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
							($finishDate!="NULL"?" AND SurveyInstanceParticipants.date <= '$finishDate'":"").
							" AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
							AND Answers.heraldID = SurveyInstanceParticipants.heraldID
							AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
							AND Answers.blockID = $blockID
							AND Answers.sectionID = $sectionID
							AND Answers.questionID = $questionID
							AND Answers.answerID = AnswerComments.answerID";
				$qResComments = mysqli_query($qComments);
				
				//get any dates for this question
				$qDates = "SELECT AnswerDates.date
							FROM Answers, AnswerDates, SurveyInstances, SurveyInstanceParticipants  
							WHERE AnswerDates.answerID = Answers.answerID ".
							($strStatusQuery!=""?$strStatusQuery:"").
							($instanceID!=0?" AND SurveyInstances.surveyInstanceID = $instanceID" : " AND SurveyInstances.surveyID = $surveyID").
							($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
							($finishDate!="NULL"?" AND SurveyInstanceParticipants.date <= '$finishDate'":"").
							" AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
							AND Answers.heraldID = SurveyInstanceParticipants.heraldID
							AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
							AND Answers.blockID = $blockID
							AND Answers.sectionID = $sectionID
							AND Answers.questionID = $questionID";
							
				$qResDates = mysqli_query($qDates);
				
				//get items
				$qItems = "SELECT Items.itemID, Items.text, QuestionItems.position
						FROM Items, QuestionItems
						WHERE QuestionItems.questionID = $rowQuestions[questionID]".
						($showHidden=='on'?" " : " AND QuestionItems.visible = 1 ").
						"AND Items.itemID = QuestionItems.itemID
						ORDER BY QuestionItems.position";
									
				$qResItems = mysqli_query($qItems);
				//check whether any of the items for this question have been checked ('on')
				$bRowOdd = false;
				$rowClass = "matrixRowEven";
				while($rowItems = mysqli_fetch_array($qResItems))
					{
					//get number of participants who chose this item
					$qCountItems = "SELECT Answers.answerID
									FROM Answers, AnswerItems, SurveyInstances, SurveyInstanceParticipants  
									WHERE AnswerItems.itemID = $rowItems[itemID] ".
									($strStatusQuery!=""?$strStatusQuery:"").
									($instanceID!=0?" AND SurveyInstances.surveyInstanceID = $instanceID" : " AND SurveyInstances.surveyID = $surveyID ").
									($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
									($finishDate!="NULL"?" AND SurveyInstanceParticipants.date <= '$finishDate'":"").
									" AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
									AND Answers.heraldID = SurveyInstanceParticipants.heraldID
									AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
									AND Answers.blockID = $blockID
									AND Answers.sectionID = $sectionID
									AND Answers.questionID = $questionID
									AND Answers.answerID = AnswerItems.answerID";
					$qResCountItems = mysqli_query($qCountItems);
					
					$NoOfItems = mysqli_num_rows($qResCountItems);
					if($NoOfAnswers>0)
							{
							$PercentageOfAnswers = round($NoOfItems/$NoOfAnswers * 100,1); 
							}
						else
							{
							$PercentageOfAnswers = 0;
							}
					if(($blockVisible==1 && $sectionVisible==1 && $questionVisible==1) || $showHidden=='on') echo"<tr class=\"$rowClass\"><td>$rowItems[text]</td><td>$PercentageOfAnswers</td><td>[$NoOfItems]</td></tr>";
					$bRowOdd = !$bRowOdd;
					if($bRowOdd)
						{
						$rowClass = "matrixRowOdd";
						}
					else
						{
						$rowClass = "matrixRowEven";
						}
					}
				$dateCount = 1;
				while($rowDates = mysqli_fetch_array($qResDates))
					{
					if(($blockVisible==1 && $sectionVisible==1 && $questionVisible==1) || $showHidden=='on')
						{
						echo "<tr class=\"$rowClass\">
								<td colspan=\"".$colspan."\">"
									. $dateCount . " - " .ODBCDateToTextDateShort($rowDates['date']). " <br/>";
									$dateCount++;
									$bRowOdd = !$bRowOdd;
									if($bRowOdd)
										{
										$rowClass = "matrixRowOdd";
										}
									else
										{
										$rowClass = "matrixRowEven";
										}
						echo " </td>
							</tr>"; 
						}
					}
				if(($blockVisible==1 && $sectionVisible==1 && $questionVisible==1) || $showHidden=='on')
					{
					echo"<tr class=\"$rowClass\"><td valign=\"top\" colspan=\"".$colspan."\">
							<div class=\"comments\" id=\"divComments_".$questionNo.$i."\" name=\"divComments_".$questionNo.$i."\">
								<strong>Comments:</strong>
								<br/>";
								$commentCount = 1;
								while($rowComments = mysqli_fetch_array($qResComments))
									{
									echo $commentCount . " - $rowComments[text] <br/>";
									$commentCount++;
									}
					echo"</div></td></tr>";
					echo "</table>"; 
					}
				}
			if(($blockVisible==1 && $sectionVisible==1 && $questionVisible==1) || $showHidden=='on') $questionNo = $questionNo + 1;
			}
		}
	}
//echo "</table>"; 
//echo "</div>";
?>
</body>
</html>