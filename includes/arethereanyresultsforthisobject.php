<?php

function AreThereAnyResultsForThisObject($surveyID, $blockID, $sectionID = false, $questionID = false, $itemID = false)
	{
	//This routine needs to find out whether there are any results for this object within this survey
	//This is used to decide whether the object is simpy hidden when deleted or actually deleted
	//from the relevant amny to many table (e.g. SurveyBlocks)
	global $db_connection;
	//find all surveys which contain blockID for which heraldID is an author
	$qAnyAnswers = "SELECT Answers.answerID
					FROM Answers, SurveyInstances".($itemID != false ? ", AnswerItems":"")
					." WHERE SurveyInstances.surveyID = $surveyID
					AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
					AND	Answers.blockID = $blockID"
					.($sectionID != false ? " AND Answers.sectionID = $sectionID" 
					.($questionID != false ? " AND Answers.questionID = $questionID"
					.($itemID != false ? " AND AnswerItems.itemID = $itemID
					AND AnswerItems.answerID = Answers.answerID" : ""): ""): "");
	$qResAnyAnswers = @mysql_query($qAnyAnswers, $db_connection);
	if($qResAnyAnswers == false)
		{
		echo "Problem querying Answers table in AreThereAnyResultsForThisObject";
		}
	else
		{
		if (mysql_num_rows($qResAnyAnswers)>0)
			{
			return(true);
			}
		else
			{
			return(false);
			}
		}
	}
?>