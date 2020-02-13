<?php
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

?>