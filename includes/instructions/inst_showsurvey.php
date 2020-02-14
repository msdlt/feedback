<?php
//Instructions box
echo "	<div id=\"homeRight\">
			<div class=\"loginBox\">
				<h5>Instructions</h5>
				";
if($surveyAllowSave == "true")
	{
			echo "<p>If you do not wish to complete the whole form in one go, click the <strong>Save for now</strong> button to save your answers 
					so far. However, in order for your feedback to be counted, you will need to complete all the questions and submit it finally by clicking the
					<strong>Final submission</strong> button by the closing date";
	if($rowSurveyInstances['finishDate']!=NULL)
		{
		
		echo " (".ODBCDateToTextDate($rowSurveyInstances['finishDate']).")";
		}
	echo".</p>";
	}
else
	{
			echo "<p>Please complete all questions before clicking the <strong>Final submission</strong> button.</p>";
	}
		echo "<p>You may revisit your answers at any time. However, you will not be able to make changes after the closing date";
	if($rowSurveyInstances[finishDate]!=NULL)
		{
		echo " (".ODBCDateToTextDate($rowSurveyInstances['finishDate']).")";
		}
	echo".</p>";
		echo "</div>
		</div>";
?>