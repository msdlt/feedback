<?php
require_once("includes/config.php"); 
require_once("includes/getuser.php");
require_once("includes/isauthor.php");
//require_once("includes/quote_smart.php");
require_once("includes/ODBCDateToTextDate.php");
if ((isset($_POST['surveyInstanceID']) && $_POST['surveyInstanceID']!="")||(isset($_GET['surveyInstanceID']) && $_GET['surveyInstanceID']!=""))
	{
	//pass surveyInstanceID on
	$surveyInstanceID = $_GET['surveyInstanceID'];
	//find out which survey this is an instance of
	$qSurvey = "	SELECT surveyID, title 
					FROM SurveyInstances
					WHERE surveyInstanceID = $surveyInstanceID";
	$qResSurvey = mysqli_query($db_connection, $qSurvey);
	if (($qResSurvey == false))
		{
		echo "problem querying SurveyInstances" . mysqli_error($db_connection);
		}
	else
		{
		$rowSurvey = mysqli_fetch_array($qResSurvey);
		$surveyID = $rowSurvey['surveyID'];
		$surveyInstanceTitle = $rowSurvey['title'];
		}
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
<?php 
	$qSurveys = "SELECT title, introduction, epilogue, allowSave
				FROM Surveys
				WHERE surveyID = $surveyID";
	$qResSurvey = mysqli_query($db_connection, $qSurveys);
	$rowSurvey = mysqli_fetch_array($qResSurvey);
	$surveyTitle = $rowSurvey['title'];
	$surveyIntroduction = $rowSurvey['introduction'];
	$surveyEpilogue = $rowSurvey['epilogue'];
	$surveyAllowSave = $rowSurvey['allowSave'];
	echo "<title>$surveyInstanceTitle</title>";
?>	
	<link rel="stylesheet" type="text/css" href="css/SurveyStyles.css"></link>
	<link rel="stylesheet" type="text/css" href="css/msdstyle2.css" media="screen"/>
	<link  rel="stylesheet" type="text/css" href="css/msdprint.css" media="print" />
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

</head>
<body>
<?php
	//*******************************************************************************************************************************
	//BEGIN SHOWING SURVEY
	//*******************************************************************************************************************************
	function getInstancesForBlock($blockID)
		{
		global $db_connection; //makes these available within the function
		global $surveyInstanceID; 
		global $heraldID;
		
		if(isset($_POST[hCurrentInstance . "_" . $blockID]))
			{
			$noOfInstances = $_POST[hCurrentInstance . "_" . $blockID];	
			}
		else
			{
			//find out how many instances of answers in this block there are for this user
			$qAnswerInstances = "	SELECT MAX(instance) as maxInstance, COUNT(instance) as countInstance
									FROM Answers
									WHERE surveyInstanceID = $surveyInstanceID
									AND blockID = $blockID
									AND heraldID = '$heraldID'";
			$qResAnswerInstances = mysqli_query($db_connection, $qAnswerInstances);
			$rowAnswerInstances = mysqli_fetch_array($qResAnswerInstances);
			if ($rowAnswerInstances[countInstance] > 0 && $rowAnswerInstances[maxInstance] > 0)
				{			
				//User has already submitted entire survey - how many instances did they create? 
				$noOfInstances = $rowAnswerInstances[maxInstance];
				}
			else
				{
				//First time use - only one instance
				$noOfInstances = 1;
				}
			}
		return $noOfInstances;
		}
	function getInstancesForSection($blockID, $sectionID, $inst)
		{
		global $db_connection; //makes these available within the function
		global $surveyInstanceID; 
		global $heraldID;
		
		if(isset($_POST[hCurrentSectionInstance . "_" . $blockID . "_" . $sectionID . "_i" . $inst]))
			{
			$noOfSectionInstances = $_POST[hCurrentSectionInstance . "_" . $blockID . "_" . $sectionID."_i".$inst];	
			}
		else
			{
			//find out how many instances of answers in this block there are for this user
			$qAnswerInstances = "	SELECT MAX(sinstance) as maxInstance, COUNT(sinstance) as countInstance
									FROM Answers
									WHERE surveyInstanceID = $surveyInstanceID
									AND blockID = $blockID
									AND sectionID = $sectionID
									AND instance = $inst
									AND heraldID = '$heraldID'";
			$qResAnswerInstances = mysqli_query($db_connection, $qAnswerInstances);
			$rowAnswerInstances = mysqli_fetch_array($qResAnswerInstances);
			if ($rowAnswerInstances[countInstance] > 0 && $rowAnswerInstances[maxInstance] > 0)
				{			
				//User has already submitted entire survey - how many instances did they create? 
				$noOfSectionInstances = $rowAnswerInstances[maxInstance];
				}
			else
				{
				//First time use - only one instance
				$noOfSectionInstances = 1;
				}
			}
		return $noOfSectionInstances;
		}
	
	
	echo "<h1>$surveyInstanceTitle </h1>";
	//get blocks
	$qBlocks = "SELECT Blocks.blockID, Blocks.title, Blocks.text, Blocks.introduction, Blocks.epilogue, SurveyBlocks.position, Blocks.instanceable
				FROM Blocks, SurveyBlocks 
				WHERE SurveyBlocks.surveyID = $surveyID
				AND SurveyBlocks.visible = 1
				AND Blocks.blockID = SurveyBlocks.blockID
				ORDER BY SurveyBlocks.position";
	
	$qResBlocks = mysqli_query($db_connection, $qBlocks);
	//counter for questions 
	
	
	$questionNo = 1;
	//loop through sections
	echo "<table>";
	while($rowBlocks = mysqli_fetch_array($qResBlocks))
		{
		$blockIsInstanceable = false;
		$blockID = $rowBlocks['blockID'];
		if($rowBlocks['instanceable']==1)
			{
			$blockIsInstanceable = true;
			$noOfInstances = getInstancesForBlock($blockID);
			}
		else
			{
			$blockIsInstanceable = false;
			$noOfInstances = 1;
			}
		//get sections
		$qSections = "SELECT Sections.sectionID, Sections.title, Sections.text, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, Sections.instanceable 
					FROM Sections, BlockSections 
					WHERE BlockSections.blockID = $blockID
					AND BlockSections.visible = 1
					AND Sections.sectionID = BlockSections.sectionID
					ORDER BY BlockSections.position";
		
		$qResSections = mysqli_query($db_connection, $qSections);
		for($inst=1;$inst<=$noOfInstances;$inst++)
			{
			//begin block content
			if($rowBlocks['text'] != "")
				{
				//only output a block title if there is one
				echo "<tr><td colspan=\"3\"><h2>".$rowBlocks['text'];
				if ($rowBlocks['instanceable']==1)
					{
					echo ": ".$inst;
					} 
				echo "</h2></td></tr>";
				}
			if(mysqli_num_rows($qResSections)>0)
				{
				mysqli_data_seek($qResSections, 0);
				}
			while($rowSections = mysqli_fetch_array($qResSections))
				{			
				$sectionIsInstanceable = false;
				$sectionID = $rowSections['sectionID'];
				if($rowSections['instanceable']==1)
					{
					$sectionIsInstanceable = true;
					$noOfSectionInstances = getInstancesForSection($blockID,$sectionID,$inst);
					}
				else
					{
					$sectionIsInstanceable = false;
					$noOfSectionInstances = 1;
					}
				//get questions
				$qQuestions = "SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position 
							FROM Questions, SectionQuestions
							WHERE SectionQuestions.sectionID = $sectionID
							AND SectionQuestions.visible = 1
							AND Questions.questionID = SectionQuestions.questionID
							ORDER BY SectionQuestions.position";
				
				$qResQuestions = mysqli_query($db_connection, $qQuestions);
				for($sinst=1;$sinst<=$noOfSectionInstances;$sinst++)
					{
					//begin section content
					if($rowSections['text'] != "")
						{
						//only output a section title if there is one
						echo "<tr><td colspan=\"3\"><h3>".$rowSections['text'];
						if ($rowSections[instanceable]==1)
							{
							echo ": ".$sinst;
							} 
						echo "</h3></td></tr>";
						}
					if(mysqli_num_rows($qResQuestions)>0)
						{
						mysqli_data_seek($qResQuestions, 0);
						}
					//if the section's 'normal'
					//loop through questions
					while($rowQuestions = mysqli_fetch_array($qResQuestions))
						{
						$questionID = $rowQuestions['questionID'];
						if($rowQuestions['comments']=="true" || $rowQuestions['questionTypeID'] == 4 || $rowQuestions['questionTypeID'] == 5)
							{
							//find out if this question already has comments/text from this user
							$qCommentAnswered = "	SELECT AnswerComments.text
												FROM AnswerComments, Answers
												WHERE Answers.surveyInstanceID = $surveyInstanceID
												AND Answers.blockID = $blockID
												AND Answers.sectionID = $sectionID
												AND Answers.questionID = $questionID
												AND Answers.heraldID = '$heraldID'
												AND Answers.instance = $inst
												AND Answers.sinstance = $sinst
												AND AnswerComments.answerID = Answers.answerID";
							$qResCommentAnswered = mysqli_query($db_connection, $qCommentAnswered);
							}
						
						//find out if this question has already been answered by this user
						$qItemAnswered = "	SELECT AnswerItems.itemID
											FROM AnswerItems, Answers
											WHERE Answers.surveyInstanceID = $surveyInstanceID
											AND Answers.blockID = $blockID
											AND Answers.sectionID = $sectionID
											AND Answers.questionID = $questionID
											AND Answers.heraldID = '$heraldID'
											AND Answers.instance = $inst
											AND Answers.sinstance = $sinst
											AND AnswerItems.AnswerID = Answers.answerID";
						$qResItemAnswered = mysqli_query($db_connection, $qItemAnswered);
						
						echo "<tr><td>$questionNo</td><td>".$rowQuestions['text']."</td>";
						
						switch ($rowQuestions['questionTypeID'])
							{
							case 1: //MCHOIC
								{
								echo "<td>";
								if(mysqli_num_rows($qResItemAnswered)==1)
									{
									$rowItemAnswered = mysqli_fetch_array($qResItemAnswered);
									//get item text for chosen item
									$qItems = "SELECT text
											FROM Items
											WHERE itemID = ".$rowItemAnswered['itemID'];
									$qResItems = mysqli_query($db_connection, $qItems);
									$rowItems = mysqli_fetch_array($qResItems);
									echo $rowItems['text'];
									}
								echo "</td></tr>";
								if($rowQuestions['comments']=="true")
									{
									echo"<tr><td></td><td colspan=\"2\">";
									if(mysqli_num_rows($qResCommentAnswered)==1)
										{
										//if so, write the text into the textarea
										$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
										echo $rowCommentAnswered['text'];
										}
									else
										{
										echo "No comment";
										}
									echo"</td></tr>";
									}
								break;
								} 
							case 2: //MSELEC
								{
								echo "<td>";
								if(mysqli_num_rows($qResItemAnswered)>0)
									{
									$firstItem = true;
									while($rowItemAnswered = mysqli_fetch_array($qResItemAnswered))
										{
										$qItems = "SELECT text
											FROM Items
											WHERE itemID = ".$rowItemAnswered['itemID'];
										$qResItems = mysqli_query($db_connection, $qItems);
										$rowItems = mysqli_fetch_array($qResItems);
										if($firstItem == true)
											{
											$firstItem = false;
											}
										else
											{
											echo"; ";
											}
										echo $rowItems['text'];
										}
									}
								echo "</td></tr>";
								if($rowQuestions['comments']=="true")
									{
									echo"<tr><td></td><td colspan=\"2\">";
									if(mysqli_num_rows($qResCommentAnswered)==1)
										{
										//if so, write the text into the textarea
										$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
										echo $rowCommentAnswered['text'];
										}
									else
										{
										echo "No comment";
										}
									echo"</td></tr>";
									}
								break;
								} 
							case 3: //DRDOWN
								{
								echo "<td>";
								if(mysqli_num_rows($qResItemAnswered)==1)
									{
									$rowItemAnswered = mysqli_fetch_array($qResItemAnswered);
									//get item text for chosen item
									$qItems = "SELECT text
											FROM Items
											WHERE itemID = ".$rowItemAnswered['itemID'];
									$qResItems = mysqli_query($db_connection, $qItems);
									$rowItems = mysqli_fetch_array($qResItems);
									echo $rowItems['text'];
									}
								echo "</td></tr>";
								if($rowQuestions['comments']=="true")
									{
									echo"<tr><td></td><td colspan=\"2\">";
									if(mysqli_num_rows($qResCommentAnswered)==1)
										{
										//if so, write the text into the textarea
										$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
										echo $rowCommentAnswered['text'];
										}
									else
										{
										echo "Please comment..";
										}
									echo"</td></tr>";
									}
								break;
								} 
							case 4: //TEXT
								{
								echo "<td></td></tr>";
								if(mysqli_num_rows($qResCommentAnswered)==1)
									{
									echo"<tr><td></td><td colspan=\"2\">";
									//if so, write the text into the textarea
									$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
									echo $rowCommentAnswered['text'];
									echo"</td></tr>";
									}
								break;
								}
							case 5: //lgtext
								{
								echo "<td></td></tr>";
								//NOTE: No branching possible on a text question
								if(mysqli_num_rows($qResCommentAnswered)==1)
									{
									echo"<tr><td></td><td colspan=\"2\">";
									//if so, write the text into the textarea
									$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
									echo $rowCommentAnswered['text'];
									echo"</td></tr>";
									}
								break;
								}
							case 6: //date
								{
								echo "<td>";
								//find out if this question already has a date from this user
								$qDateAnswered = "	SELECT AnswerDates.date
													FROM AnswerDates, Answers
													WHERE Answers.surveyInstanceID = $surveyInstanceID
													AND Answers.blockID = $blockID
													AND Answers.sectionID = $sectionID
													AND Answers.questionID = $questionID
													AND Answers.heraldID = '$heraldID'
													AND Answers.instance = $inst
													AND Answers.sinstance = $sinst
													AND AnswerDates.answerID = Answers.answerID";
								$qResDateAnswered = mysqli_query($db_connection, $qDateAnswered);
								if(mysqli_num_rows($qResDateAnswered)==1)
									{
									//if so, write the text into the textarea
									$rowDateAnswered = mysqli_fetch_array($qResDateAnswered);
									echo $rowDateAnswered['date'];
									}
								echo "</td></tr>";
								if($rowQuestions['comments']=="true")
									{
									echo"<tr><td></td><td colspan=\"2\">";
									if(mysqli_num_rows($qResCommentAnswered)==1)
										{
										//if so, write the text into the textarea
										$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
										echo $rowCommentAnswered['text'];
										}
									else
										{
										echo "Please comment..";
										}
									echo"</td></tr>";
									}
								break;
								} 
							}
						//increment question number
						$questionNo = $questionNo + 1;
						}
					}
				}	
			}
		}
	echo "</table>";
?>
</body>
</html>