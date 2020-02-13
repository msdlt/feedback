<?php

function IsSuperAuthor($heraldID, $blockID, $sectionID = false, $questionID = false, $itemID = false)
	{
	global $db_connection;
	//find all surveys which contain the given block, section, question or item ID
	if($itemID != false)
		{
		$qContainingSurveys = "SELECT SurveyBlocks.surveyID
								FROM SurveyBlocks, BlockSections, SectionQuestions, QuestionItems
								WHERE BlockSections.blockID = SurveyBlocks.blockID
								AND SectionQuestions.sectionID = BlockSections.sectionID
								AND QuestionItems.questionID = SectionQuestions.questionID
								AND QuestionItems.itemID = $itemID";
		}
	elseif($questionID != false)
		{
		$qContainingSurveys = "	SELECT SurveyBlocks.surveyID
								FROM SurveyBlocks, BlockSections, SectionQuestions
								WHERE BlockSections.blockID = SurveyBlocks.blockID
								AND SectionQuestions.sectionID = BlockSections.sectionID
								AND SectionQuestions.questionID = $questionID";
		}
	elseif($sectionID != false)
		{
		$qContainingSurveys = "	SELECT SurveyBlocks.surveyID
								FROM SurveyBlocks, BlockSections
								WHERE BlockSections.blockID = SurveyBlocks.blockID
								AND BlockSections.sectionID = $sectionID";
		}
	else
		{
		$qContainingSurveys = "	SELECT SurveyBlocks.surveyID 
								FROM SurveyBlocks
								WHERE SurveyBlocks.blockID = $blockID";
		}
		
	$qResContainingSurveys = mysqli_query($qContainingSurveys, $db_connection);
	if($qResContainingSurveys == false)
		{
		echo "Problem querying qContainingSurveys in IsSuperAuthor";
		return(false);
		}
	else
		{
		while($rowContainingSurveys = mysqli_fetch_array($qResContainingSurveys))
			{
			$surveyID = $rowContainingSurveys['surveyID'];
			$qSurveyAuthor = "	SELECT Authors.authorID 
								FROM Authors, SurveyAuthors
								WHERE SurveyAuthors.surveyID = $surveyID
								AND Authors.authorID = SurveyAuthors.authorID
								AND Authors.heraldID = '$heraldID'";
			$qResSurveyAuthor = @mysqli_query($qSurveyAuthor, $db_connection);
			if (mysqli_num_rows($qResSurveyAuthor)==0)
				{
				return(false);
				}
			}
		return(true);
		}
	}
?>