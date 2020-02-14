<?php
//************************************************************************************
// Returns a string which contains the stored data for a surveyInstanceID and
// heraldID combination
//************************************************************************************
function getTextOutput($surveyInstanceID, $heraldID, $db_connection)
	{
	if (isset($surveyInstanceID))
		{
		//find out which survey this is an instance of
		$qSurvey = "	SELECT surveyID, title 
						FROM SurveyInstances
						WHERE surveyInstanceID = $surveyInstanceID";
		$qResSurvey = mysqli_query($db_connection, $qSurvey);
		if (($qResSurvey == false))
			{
			return("problem querying SurveyInstances" . mysqli_error($db_connection));
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
		return("No survey instance supplied");
		}
	$textOutput = "";
	$textOutput = $textOutput . "<h1>$surveyInstanceTitle </h1>";
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
	$textOutput = $textOutput . "<table>";
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
				$textOutput = $textOutput . "<tr><td colspan=\"3\"><h2>".$rowBlocks['text'];
				if ($rowBlocks['instanceable']==1)
					{
					$textOutput = $textOutput . ": ".$inst;
					} 
				$textOutput = $textOutput . "</h2></td></tr>";
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
						$textOutput = $textOutput . "<tr><td colspan=\"3\"><h3>".$rowSections['text'];
						if ($rowSections['instanceable']==1)
							{
							$textOutput = $textOutput . ": ".$sinst;
							} 
						$textOutput = $textOutput . "</h3></td></tr>";
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
						
						$textOutput = $textOutput . "<tr><td>$questionNo</td><td>".$rowQuestions['text']."</td>";
						
						switch ($rowQuestions['questionTypeID'])
							{
							case 1: //MCHOIC
								{
								$textOutput = $textOutput . "<td>";
								if(mysqli_num_rows($qResItemAnswered)==1)
									{
									$rowItemAnswered = mysqli_fetch_array($qResItemAnswered);
									//get item text for chosen item
									$qItems = "SELECT text
											FROM Items
											WHERE itemID = ".$rowItemAnswered['itemID'];
									$qResItems = mysqli_query($db_connection, $qItems);
									$rowItems = mysqli_fetch_array($qResItems);
									$textOutput = $textOutput . $rowItems['text'];
									}
								$textOutput = $textOutput . "</td></tr>";
								if($rowQuestions['comments']=="true")
									{
									if(mysqli_num_rows($qResCommentAnswered)==1)
										{
										$textOutput = $textOutput ."<tr><td></td><td colspan=\"2\">";
										//if so, write the text into the textarea
										$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
										$textOutput = $textOutput . $rowCommentAnswered['text'];
										$textOutput = $textOutput ."</td></tr>";
										}
									}
								break;
								} 
							case 2: //MSELEC
								{
								$textOutput = $textOutput . "<td>";
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
											$textOutput = $textOutput ."; ";
											}
										$textOutput = $textOutput .$rowItems['text'];
										}
									}
								$textOutput = $textOutput . "</td></tr>";
								if($rowQuestions['comments']=="true")
									{
									if(mysqli_num_rows($qResCommentAnswered)==1)
										{
										//if so, write the text into the textarea
										$textOutput = $textOutput ."<tr><td></td><td colspan=\"2\">";
										$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
										$textOutput = $textOutput . $rowCommentAnswered['text'];
										$textOutput = $textOutput ."</td></tr>";
										}
									}
								break;
								} 
							case 3: //DRDOWN
								{
								$textOutput = $textOutput . "<td>";
								if(mysqli_num_rows($qResItemAnswered)==1)
									{
									$rowItemAnswered = mysqli_fetch_array($qResItemAnswered);
									//get item text for chosen item
									$qItems = "SELECT text
											FROM Items
											WHERE itemID = ".$rowItemAnswered['itemID'];
									$qResItems = mysqli_query($db_connection, $qItems);
									$rowItems = mysqli_fetch_array($qResItems);
									$textOutput = $textOutput . $rowItems['text'];
									}
								$textOutput = $textOutput . "</td></tr>";
								if($rowQuestions['comments']=="true")
									{
									if(mysqli_num_rows($qResCommentAnswered)==1)
										{
										//if so, write the text into the textarea
										$textOutput = $textOutput ."<tr><td></td><td colspan=\"2\">";
										$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
										$textOutput = $textOutput . $rowCommentAnswered['text'];
										$textOutput = $textOutput ."</td></tr>";
										}
									}
								break;
								} 
							case 4: //TEXT
								{
								$textOutput = $textOutput . "<td></td></tr>";
								if(mysqli_num_rows($qResCommentAnswered)==1)
									{
									$textOutput = $textOutput ."<tr><td></td><td colspan=\"2\">";
									//if so, write the text into the textarea
									$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
									$textOutput = $textOutput . $rowCommentAnswered['text'];
									$textOutput = $textOutput ."</td></tr>";
									}
								break;
								}
							case 5: //lgtext
								{
								$textOutput = $textOutput . "<td></td></tr>";
								//NOTE: No branching possible on a text question
								if(mysqli_num_rows($qResCommentAnswered)==1)
									{
									$textOutput = $textOutput ."<tr><td></td><td colspan=\"2\">";
									//if so, write the text into the textarea
									$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
									$textOutput = $textOutput . $rowCommentAnswered['text'];
									$textOutput = $textOutput ."</td></tr>";
									}
								break;
								}
							case 6: //date
								{
								$textOutput = $textOutput . "<td>";
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
									$textOutput = $textOutput . $rowDateAnswered['date'];
									}
								$textOutput = $textOutput . "</td></tr>";
								if($rowQuestions['comments']=="true")
									{
									if(mysqli_num_rows($qResCommentAnswered)==1)
										{
										//if so, write the text into the textarea
										$textOutput = $textOutput ."<tr><td></td><td colspan=\"2\">";
										$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
										$textOutput = $textOutput . $rowCommentAnswered['text'];
										$textOutput = $textOutput ."</td></tr>";
										}
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
	$textOutput = $textOutput . "</table>";
	return $textOutput;
	}
?>
