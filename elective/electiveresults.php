<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<script type="text/javascript" src="../script/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="../ckfinder/ckfinder.js"></script>
<script type="text/javascript" src="../script/jquery-1.9.1.min.js"></script>
<script language="javascript">
var isIE = navigator.appName.indexOf("Microsoft")!=-1;
function toggleAllDivs(checkbox)
	{
	if(!isIE)
		{
		var aDivs = getElementsByAttributeValue('div', 'class', 'comments');
		}
	else
		{
		var aDivs = getElementsByAttributeValue('div', 'className', 'comments');
		}
	if(checkbox.checked)
		{
		var displayType ="block";
		}
	else
		{
		var displayType ="none";
		}
	for (var i=0;i<aDivs.length;i++)
		{
		aDivs[i].style.display = displayType;
		}
	}

function getElementsByAttributeValue(tagName, attrName, attrValue) {
    
	var els = document.getElementsByTagName(tagName);
    var attsEls = new Array();
    for (var j = 0; j < els.length; j++)
		{
      	if (els[j].getAttribute(attrName) == attrValue)
			{
			attsEls[attsEls.length] = els[j];
			}
		}
    return attsEls;
  }
function bodyOnLoad(){
		CKFinder.setupCKEditor( null, 'https://learntech.imsu.ox.ac.uk/feedback/ckfinder/' );
		$('textarea').each(function(){
				var id = $(this).attr('id');
				CKEDITOR.replace( id, {
					readOnly: true,
					toolbar: [
								{ name: 'document',    items: ['Print'] },
								{ name: 'editing',   items: [ 'Find'] },
								{ name: 'insert', items: [ 'Image', 'Table', 'SpecialChar'] },
								{ name: 'basicstyles', items: [ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
								'/',
								{ name: 'paragraph',   items: [ 'NumberedList', 'BulletedList', 'Outdent', 'Indent', 'Blockquote', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'] },
								{ name: 'links', items: [ 'Link', 'Unlink'] },
								{ name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
								{ name: 'tools', items: [ 'Maximize'] }
							]
				});
			})
		}
</script>
<?php 
	//Include config.php which contains db details and connects to the db)
	require_once("../includes/config.php"); 
	require_once("../includes/getuser.php");
	require_once("../includes/isauthor.php");
	require_once("../includes/limitstring.php");
	require_once("../includes/datediff.php");
	require_once("../includes/ODBCDateToTextDate.php");

	if(isset($_POST['bSearchByCountry'])||isset($_POST['bSearchByInstitution']))
		{
		$startDate="NULL";
		}
	elseif(isset($_POST['bFullSearch']))
		{
		$lastYears = $_POST['sLastYears'];
		if($lastYears == 0)
			{
			$startDate="NULL";		
			}
		else
			{
			$startDate = date("Y-m-d",mktime(0, 0, 0, date("m"),  date("d"),  date("Y")-$lastYears));
			}
		}
	else
		{
		echo "This page requires input from another page. Please go to <a href=\"index.php\">'Search electives'</a>";
		exit();
		}
	$lonelyPlanetURL = "http://www.lonelyplanet.com/destinations/";
	$wikipediaURL = "http://";
	//extract data from viewresults.php
	$surveyID = 18;
	$noOfCriteria = $_POST['hNoOfCriteria'];
	//first create ParticipantItemsTable for this user
	//create table name
	$TableForThisUser = "ParticipantItemsTemp".$heraldID;
	//first drop ParticipantItems for this user if it exists
	$qDropTable = "DROP TABLE IF EXISTS $TableForThisUser";
	$qResDropTable = mysqli_query($db_connection, $qDropTable);
	//Then create it
	$qCreateTable = "	CREATE TABLE `$TableForThisUser` (
  						`participantItemID` int(10) unsigned NOT NULL auto_increment,
					  `heraldID` varchar(12) NOT NULL default '0',
					  `blockID` int(10) unsigned NOT NULL default '0',
					  `sectionID` int(10) unsigned NOT NULL default '0',
					  `questionID` int(10) unsigned NOT NULL default '0',
					  `itemID` int(10) unsigned NOT NULL default '0',
					  `instance` tinyint(4) NOT NULL default '0',
					  `sinstance` tinyint(4) NOT NULL default '0',
					  PRIMARY KEY  (`participantItemID`)
					) TYPE=MyISAM COMMENT='Used temporarily to store participants who have chosen a par'";
	$qResCreateTable = mysqli_query($db_connection, $qCreateTable);
	
	//delete any exirting records in ParticipantItems as this is only a temporary table used 
	//to simplify queries within this .php file.
	//$dParticipantItems = "DELETE FROM ParticipantItems";
	//$dResParticipantItems = mysql_query($dParticipantItems);
	//This is an array to hold the items in each question by which other questions are being analysed 
	//$aQuestionToAnalyseByItems[x][y][z]
	// x = criterion number
	// y = item number
	// z = 0 = itemID
	// z = 1 = questionText
	// z = 2 = itemText
	$aQuestionToAnalyseBy = array();
	$aQuestionsToAnalyse = array();
	$aItemsToAnalyse = array();
	$rubricText = "";
	for($i=1;$i<=$noOfCriteria;$i++)
		{
		//creating text to be output which explains what results show
		$rubricText = $rubricText . "<li>answered the question \"";
		if(isset($_POST['bSearchByCountry']))
			{
			$aQuestionToAnalyseBy[$i] = explode("_","15_44_115");
			$aItemsToAnalyse[$i] = explode(",",$_POST['sItems']);
			//print_r($aItemsToAnalyse[$i]);
			}
		else
			{
			$analyseByName = "qAnalyseBy_" . $i;
			$hidQuestionsName = "hidQuestions_" . $i;
			//get question to analyse by and questions to analyse for this criterion
			if(isset($_POST[$analyseByName]))
				{
				$aQuestionToAnalyseBy[$i] = explode("_",$_POST[$analyseByName]);
				$aItemsToAnalyse[$i] = explode(",",$_POST[$hidQuestionsName]);
				}
			}		
		//now need to be creating a query string which can be added to qParticipants to find out
		//which heraldIDs answered all the criteria questions with one of the items specified
		//note that in the case of multiple select questions, this should automatically be changed to 
		//be ALL of the items specified.
		$QuestionToAnalyseByBlockID = intval($aQuestionToAnalyseBy[$i][0]);
		$QuestionToAnalyseBySectionID = intval($aQuestionToAnalyseBy[$i][1]);
		$QuestionToAnalyseByQuestionID = intval($aQuestionToAnalyseBy[$i][2]);
		//get text of question for rybricText
		$qQuestion = "	SELECT text, questionTypeID
						FROM Questions
						WHERE questionID = $QuestionToAnalyseByQuestionID";
		$qResQuestion = mysqli_query($db_connection, $qQuestion);
		$rowQuestion = mysqli_fetch_array($qResQuestion);
		$rubricText = $rubricText . $rowQuestion['text'] . "\" with the following:<ul>";
		if(isset($_POST['bSearchByCountry']))
			{
			//reset rubricText
			$rubricText = "<li>who visited:<ul>";
			}
		$participantQueryString = "";
		$aItem = array();
		for($j=0;$j<count($aItemsToAnalyse[$i]);$j++)
			{
			$aItem = explode("_",$aItemsToAnalyse[$i][$j]);
			$itemID = $aItem[3];
			//get text of question for rubricText
			$qItem = "	SELECT text
						FROM Items
						WHERE itemID = $itemID";
			$qResItem = mysqli_query($db_connection, $qItem);
			$rowItem = mysqli_fetch_array($qResItem);
			if($QuestionToAnalyseByQuestionID==115)
				{
				$trimmedCountry = mysqli_real_escape_string($db_connection, trim($rowItem['text']));
				$qInfoURLs = "	SELECT lonelyPlanetURL, wikipediaURL
								FROM ElectiveLinks
								WHERE TRIM(country) = '$trimmedCountry'";
				$qResInfoURLs = mysqli_query($db_connection, $qInfoURLs);
				$rowInfoURLs = mysqli_fetch_array($qResInfoURLs);
				if($rowInfoURLs['lonelyPlanetURL']!="")
					{
					$rubricText = $rubricText . "<li><a href=\"" . $lonelyPlanetURL . $rowInfoURLs['lonelyPlanetURL'] . "\" target=\"countrylink\">" . $trimmedCountry . "</a> (Follow link to further information on this country from Lonely Planet)";
					}
				elseif($rowInfoURLs['wikipediaURL']!="")
					{
					$rubricText = $rubricText . "<li><a href=\"" . $wikipediaURL . $rowInfoURLs['wikipediaURL'] . "\" target=\"countrylink\">" . $trimmedCountry . "</a> (Follow link to further information on this country from Wikipedia)";
					}
				else
					{
					$rubricText = $rubricText . "<li>" . $trimmedCountry;
					}
				}
			//if mchoic or drdown we need to OR the items to analyse
			//if mselec, we need to AND the items to analyse
			if($j<count($aItemsToAnalyse[$i])-1)
				{
				$participantQueryString = $participantQueryString  . " AnswerItems.itemID = " . $itemID . ($rowQuestion['questionTypeID']==2?" AND ":" OR ");
				$rubricText = $rubricText . ($rowQuestion['questionTypeID']==2?" AND</li>":" OR</li>");
				}
			else
				{
				$participantQueryString = $participantQueryString  .  " AnswerItems.itemID = " . $itemID . ") ";
				$rubricText = $rubricText . "</li></ul>";
				}
			}
		if($i<$noOfCriteria)
			{
			$rubricText = $rubricText . " AND </li>";
			}
		$qParticipants = "	SELECT Answers.heraldID, Answers.instance,Answers.sinstance
						FROM Answers, AnswerItems, SurveyInstances, SurveyInstanceParticipants
						WHERE Answers.heraldID = SurveyInstanceParticipants.heraldID 
						AND SurveyInstances.surveyID = $surveyID
						AND SurveyInstanceParticipants.status = 2".
						($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
						" AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID " . 
						($participantQueryString != ""?" AND (".$participantQueryString:"").
						"AND Answers.blockID = $QuestionToAnalyseByBlockID
						AND Answers.sectionID = $QuestionToAnalyseBySectionID
						AND Answers.questionID = $QuestionToAnalyseByQuestionID
						AND AnswerItems.answerID = Answers.answerID
						AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID";
		$qResParticipants = mysqli_query($db_connection, $qParticipants);
		$NoOfParticipants = mysqli_num_rows($qResParticipants);
		//now write those participants to a table to store them temporarily next to the item they chose
		while($rowParticipants = mysqli_fetch_array($qResParticipants))
			{
			//if this is not the first criterion, check if heraldId is already in there
			if($i>1)
				{
				$qParticipantExists = "	SELECT participantItemID, blockID, sectionID, instance, sinstance
										FROM $TableForThisUser
										WHERE heraldID = '".$rowParticipants['heraldID']."'";
				$qResParticipantExists = mysqli_query($db_connection, $qParticipantExists);
				if(mysqli_num_rows($qResParticipantExists)>0) //participant is already in there
					{
					$instanceMatch = false;
					//now check whether instances match
					while($rowParticipantExists = mysqli_fetch_array($qResParticipantExists))
						{
						if($QuestionToAnalyseByBlockID == $rowParticipantExists['blockID'])
							{
							//in same block so instances must match
							if($rowParticipantExists['instance'] == $rowParticipants['instance'])
								{
								//instances match
								$instanceMatch = true;
								if($QuestionToAnalyseBySectionID == $rowParticipantExists['sectionID'])
									{
									//in same section and block so sinstance must match
									if($rowParticipantExists['sinstance'] == $rowParticipants['sinstance'])
										{
										//sintance matches
										$instanceMatch = true;
										}
									}
								}
							}
						}
					if ($instanceMatch == true)
						{
						$iParticipantItems = "	INSERT INTO $TableForThisUser
										VALUES(0,".$rowParticipants['heraldID'].",".$QuestionToAnalyseByBlockID.",".$QuestionToAnalyseBySectionID.",".$QuestionToAnalyseByQuestionID.",0,".$rowParticipants['instance'].",".$rowParticipants['sinstance'].")";
						$result_query = @mysqli_query($db_connection,$iParticipantItems);
						if (($result_query == false) || mysqli_affected_rows($db_connection) == 0)
							{
							echo "problem inserting into ParticipantItems" . mysqli_error($db_connection);
							$bSuccess = false;
							}
						}
					}
				}
			else
				{
				$iParticipantItems = "	INSERT INTO $TableForThisUser
									VALUES(0,".$rowParticipants['heraldID'].",".$QuestionToAnalyseByBlockID.",".$QuestionToAnalyseBySectionID.",".$QuestionToAnalyseByQuestionID.", 0,".$rowParticipants['instance'].",".$rowParticipants['sinstance'].")";
					$result_query = @mysqli_query($db_connection,$iParticipantItems);
					if (($result_query == false) || mysqli_affected_rows($db_connection) == 0)
						{
						echo "problem inserting into ParticipantItems" . mysqli_error($db_connection);
						$bSuccess = false;
						}
				}
			}
		}
		if ($noOfCriteria>1)
			{
			//now go through and remove any participants who are not just selected by criterion 1
			//PLUS also need to remove rows from criterion 1 where instance and sinstance do not match.
			$QuestionToAnalyseByBlockID = intval($aQuestionToAnalyseBy[1][0]);
			$QuestionToAnalyseBySectionID = intval($aQuestionToAnalyseBy[1][1]);
			$QuestionToAnalyseByQuestionID = intval($aQuestionToAnalyseBy[1][2]);
			//so first query participantItems for items matching fisrt criterion
			$qFirstCriterionParticipants = "SELECT participantItemID, instance, sinstance
											FROM $TableForThisUser
											WHERE blockID = $QuestionToAnalyseByBlockID
											AND sectionID = $QuestionToAnalyseBySectionID
											AND questionID = $QuestionToAnalyseByQuestionID";
			$qResFirstCriterionParticipants = mysqli_query($db_connection, $qFirstCriterionParticipants);
			while($rowFirstCriterionParticipants = mysqli_fetch_array($qResFirstCriterionParticipants))
				{
				$deleteItem = false;
				//then go through them one by one and check whether sintance and instance match
				$qParticipantExists = "	SELECT blockID, sectionID, instance, sinstance
										FROM $TableForThisUser
										WHERE heraldID = ".$rowFirstCriterionParticipants['heraldID']."
										AND NOT (blockID = $QuestionToAnalyseByBlockID AND sectionID = $QuestionToAnalyseBySectionID AND questionID = $QuestionToAnalyseByQuestionID)";
				$qResParticipantExists = mysqli_query($db_connection, $qParticipantExists);
				if(mysqli_num_rows($qResParticipantExists)>0) //participant is already in there
					{
					$instanceMatch = false;
					//now check whether instances match
					while($rowParticipantExists = mysqli_fetch_array($qResParticipantExists))
						{
						if($QuestionToAnalyseByBlockID == $rowParticipantExists['blockID'])
							{
							//in same block so instances must match
							if($rowParticipantExists['instance'] == $rowFirstCriterionParticipants['instance'])
								{
								//instances match
								$instanceMatch = true;
								if($QuestionToAnalyseBySectionID == $rowParticipantExists['sectionID'])
									{
									//in same section and block so sinstance must match
									if($rowParticipantExists['sinstance'] == $rowFirstCriterionParticipants['sinstance'])
										{
										//sintance matches
										$instanceMatch = true;
										}
									}
								}
							}
						}
					if ($instanceMatch == false)
						{
						$deleteItem = true;
						} 
					}
				else
					{
					//participant appears nowhere else therefore delete
					$deleteItem = true;
					}
				if($deleteItem == true)
					{
					$dParticipants = "	DELETE FROM $TableForThisUser
										WHERE participantItemID = ".$rowFirstCriterionParticipants['participantItemID'];
					$result_query = @mysqli_query($db_connection,$dParticipants);
					if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
						{
						echo "problem deleting from ParticipantItems" . mysqli_error($db_connection);
						$bSuccess = false;
						}
					}
				}
			}
		//now need to count how many students have fulfilled the search criteria
		$qNoOfParticipants = "	SELECT DISTINCT heraldID
								FROM $TableForThisUser";
		$qResNoOfParticipants = mysqli_query($db_connection, $qNoOfParticipants);
		
	//exit();
	//Get survey information
	$qSurveys = "SELECT title
				FROM Surveys
				WHERE surveyID = $surveyID";
	$qResSurvey = mysqli_query($db_connection, $qSurveys);
	$rowSurvey = mysqli_fetch_array($qResSurvey);
	$surveyTitle = $rowSurvey['title'];
	
	echo "<title>$surveyTitle - Results</title>";
?>	

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" type="text/css" href="../css/SurveyStyles.css">
<link rel="stylesheet" type="text/css" href="../css/msdstyle2.css" media="screen"/>
<link href="../css/msdprint.css" rel="stylesheet" type="text/css" media="print" />
</head>
<body onLoad="bodyOnLoad()">
<?php
require_once("includes/html/electiveheader.html");
require_once("../includes/html/BreadCrumbsHeader.html"); 
echo "<a href=\"index.php\">Search electives</a> &gt; ";
	if(isset($_POST['bSearchByCountry']))
		{
		echo "<a href=\"quicksearch.php\">Quick search</a> &gt; ";
		}
	else
		{
		echo "<a href=\"fullsearch.php\">Full search</a> &gt; ";
		} 
		echo "<strong>Search results</strong>"; 	
require_once("../includes/html/BreadCrumbsFooter.html"); 
echo "<h1>Search results</h1>";
echo "<a name=\"maintext\" id=\"maintext\"></a>"; 
echo "<h2>Total number of respondents = ".mysqli_num_rows($qResNoOfParticipants)."</h2>";
echo "<p>Results from respondents who:";
	echo "<ul>";
	if($startDate!="NULL")
		{
		echo"<li>submitted their reports after ".ODBCDateToTextDateShort($startDate)."</li>";
		}
	echo $rubricText;
	echo "</ul></p>";
echo "<input type=\"checkbox\" id=\"chkAllComments\" name = \"chkAllComments\" checked onclick=\"toggleAllDivs(this)\"/><label for=\"chkAllComments\"><strong>Show comments</strong></label>";

$qBlocks = "SELECT Blocks.blockID, Blocks.title, Blocks.text, Blocks.introduction, Blocks.epilogue, SurveyBlocks.position, Blocks.instanceable, SurveyBlocks.visible
			FROM Blocks, SurveyBlocks 
			WHERE SurveyBlocks.surveyID = $surveyID
			AND Blocks.blockID = SurveyBlocks.blockID
			ORDER BY SurveyBlocks.position";
$qResBlocks = mysqli_query($db_connection, $qBlocks);
//counter for questions 
$questionNo = 1;	
$bRowOdd = true;
while($rowBlocks = mysqli_fetch_array($qResBlocks))
	{
	$blockID = $rowBlocks['blockID'];
	if($rowBlocks['text'] != "")
		{
		//only output a block title if there is one
		echo "<h2>".$rowBlocks['text']."</h2>";
		}
	$qSections = "	SELECT Sections.sectionID, Sections.title, Sections.text, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, BlockSections.visible 
					FROM Sections, BlockSections 
					WHERE BlockSections.blockID = $blockID
					AND Sections.sectionID = BlockSections.sectionID
					ORDER BY BlockSections.position";
	
	$qResSections = mysqli_query($db_connection, $qSections);

	while($rowSections = mysqli_fetch_array($qResSections))
		{
		$sectionID = $rowSections['sectionID'];
		if($rowSections['text'] != "")
			{
			//only output a block title if there is one
			echo "<h3>".$rowSections['text']."</h3>";
			}
		//get questions
		$qQuestions = "SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position 
					FROM Questions, SectionQuestions
					WHERE SectionQuestions.sectionID = $sectionID
					AND Questions.questionID = SectionQuestions.questionID
					ORDER BY SectionQuestions.position";
		
		$qResQuestions = mysqli_query($db_connection, $qQuestions);
		
		while($rowQuestions = mysqli_fetch_array($qResQuestions))
			{
			$questionID = $rowQuestions['questionID'];
			if ($questionID==155) //startdate
				{
				//do nothing
				}
			elseif ($questionID==156) //finishdate
				{
				echo "	<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">
						<tr class=\"matrixHeader\">
							<th colspan=\"2\">" . ($questionNo-1) . " & " . $questionNo . ". Length of attachment </th>
						</tr>"; 
				}
			else
				{
				echo "	<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">
						<tr class=\"matrixHeader\">
							<th colspan=\"2\">".$questionNo.". ".$rowQuestions['text']."</th>
						</tr>"; 
				}
			//split question to analyse by inro Block, Section and Question IDs
			//$QuestionToAnalyseByBlockID = intval($aQuestionToAnalyseBy[$criterionNumber][0]);
			//$QuestionToAnalyseBySectionID = intval($aQuestionToAnalyseBy[$criterionNumber][1]);
			//$QuestionToAnalyseByQuestionID = intval($aQuestionToAnalyseBy[$criterionNumber][2]);
			//begin outputting results by item of $aQuestionToAnalyseBy
			//get number of participants who answered this question who also answered 
			//each item in aQuestionToAnalyseByItems
			//$QuestionToAnalyseByItem = intval($aQuestionToAnalyseByItems[$criterionNumber][$i][0]);
			
			$qCountAnswers = "	SELECT DISTINCT Answers.answerID
								FROM Answers, $TableForThisUser, SurveyInstances, SurveyInstanceParticipants
								WHERE Answers.questionID = $questionID 
								AND SurveyInstances.surveyID = $surveyID 
								AND SurveyInstanceParticipants.status = 2 ".
								($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
								" AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
								AND Answers.heraldID = SurveyInstanceParticipants.heraldID
								AND Answers.heraldID = $TableForThisUser.heraldID
								AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
								AND Answers.blockID = $blockID
								AND IF($TableForThisUser.blockID = $blockID,Answers.instance = $TableForThisUser.instance AND IF($TableForThisUser.sectionID = $sectionID, Answers.sinstance = $TableForThisUser.sinstance, Answers.sinstance = Answers.sinstance), Answers.instance = Answers.instance)
								AND Answers.sectionID = $sectionID";
			
			$qResCountAnswers = mysqli_query($db_connection, $qCountAnswers);
			
			$NoOfAnswers = mysqli_num_rows($qResCountAnswers);
			//get participants comments for this item
			$qComments = "	SELECT DISTINCT AnswerComments.answerCommentID, AnswerComments.text, SurveyInstanceParticipants.date
							FROM Answers, AnswerComments, $TableForThisUser, SurveyInstances, SurveyInstanceParticipants
							WHERE AnswerComments.text <> 'Please comment..' 
							AND SurveyInstances.surveyID = $surveyID 
							AND SurveyInstanceParticipants.status = 2 ".
							//($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
							"  AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
							AND Answers.heraldID = SurveyInstanceParticipants.heraldID
							AND Answers.heraldID = $TableForThisUser.heraldID
							AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
							AND Answers.blockID = $blockID
							AND Answers.sectionID = $sectionID
							AND Answers.questionID = $questionID
							AND IF($TableForThisUser.blockID = $blockID,Answers.instance = $TableForThisUser.instance AND IF($TableForThisUser.sectionID = $sectionID, Answers.sinstance = $TableForThisUser.sinstance, Answers.sinstance = Answers.sinstance), Answers.instance = Answers.instance)
							AND Answers.answerID = AnswerComments.answerID";
			$qResComments = mysqli_query($db_connection, $qComments);
			$qDates = "	SELECT DISTINCT AnswerDates.answerDateID, AnswerDates.date
						FROM Answers, AnswerDates, $TableForThisUser, SurveyInstances, SurveyInstanceParticipants
						WHERE Answers.answerID = AnswerDates.answerID
						AND SurveyInstances.surveyID = $surveyID
						AND SurveyInstanceParticipants.status = 2 ".
						($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
						" AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
						AND Answers.heraldID = SurveyInstanceParticipants.heraldID
						AND Answers.heraldID = $TableForThisUser.heraldID
						AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
						AND Answers.blockID = $blockID
						AND Answers.sectionID = $sectionID
						AND Answers.questionID = $questionID
						AND IF($TableForThisUser.blockID = $blockID,Answers.instance = $TableForThisUser.instance AND IF($TableForThisUser.sectionID = $sectionID, Answers.sinstance = $TableForThisUser.sinstance, Answers.sinstance = Answers.sinstance), Answers.instance = Answers.instance)";
			$qResDates = mysqli_query($db_connection, $qDates);
			
			//get items
			$qItems = "SELECT Items.itemID, Items.text, QuestionItems.position
					FROM Items, QuestionItems
					WHERE QuestionItems.questionID = $rowQuestions['questionID']
					AND Items.itemID = QuestionItems.itemID
					ORDER BY QuestionItems.position";
								
			$qResItems = mysqli_query($db_connection, $qItems);
			//check whether any of the items for this question have been checked ('on')
			$itemNo = 0;
			$bRowOdd = false;
			$rowClass = "matrixRowEven";
			
			//Work out whether this question is being analysed by
			$thisQuestionBeingAnalysedBy = "false";
			for($i=1;$i<=$noOfCriteria;$i++)
				{
				$QuestionToAnalyseByBlockID = intval($aQuestionToAnalyseBy[$i][0]);
				$QuestionToAnalyseBySectionID = intval($aQuestionToAnalyseBy[$i][1]);
				$QuestionToAnalyseByQuestionID = intval($aQuestionToAnalyseBy[$i][2]);
				if ($QuestionToAnalyseByBlockID == $blockID && $QuestionToAnalyseBySectionID == $sectionID && $QuestionToAnalyseByQuestionID == $questionID)
					{
					$thisQuestionBeingAnalysedBy = "true";
					}
				}
				
			while($rowItems = mysqli_fetch_array($qResItems))
				{
				$thisItemBeingAnalysedBy = "true";
				if($thisQuestionBeingAnalysedBy == "true")
					{
					//automatically don't show items in this question....
					$thisItemBeingAnalysedBy = "false";
					for($i=1;$i<=$noOfCriteria;$i++)
						{
						for($j=0;$j<count($aItemsToAnalyse[$i]);$j++)
							{
							$aItem = explode("_",$aItemsToAnalyse[$i][$j]);
							$itemID = $aItem[3];
							if($itemID == $rowItems['itemID'])
								{
								//...except those which are being searched for.
								$thisItemBeingAnalysedBy = "true";
								}
							}
						}
					}
				if($thisItemBeingAnalysedBy == "true")
					{
					//get number of participants who chose this item
					$qCountItems = "SELECT DISTINCT Answers.answerID
									FROM Answers, AnswerItems, $TableForThisUser, SurveyInstances, SurveyInstanceParticipants
									WHERE AnswerItems.itemID = ". $rowItems['itemID'] ."
									AND SurveyInstances.surveyID = $surveyID
									AND SurveyInstanceParticipants.status = 2 ".
									($startDate!="NULL"?" AND SurveyInstanceParticipants.date >= '$startDate'":"").
									" AND SurveyInstanceParticipants.surveyInstanceID = SurveyInstances.surveyInstanceID
									AND Answers.heraldID = SurveyInstanceParticipants.heraldID
									AND Answers.heraldID = $TableForThisUser.heraldID
									AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
									AND Answers.blockID = $blockID
									AND Answers.sectionID = $sectionID
									AND Answers.questionID = $questionID
									AND Answers.answerID = AnswerItems.answerID
									AND IF($TableForThisUser.blockID = $blockID,Answers.instance = $TableForThisUser.instance AND IF($TableForThisUser.sectionID = $sectionID, Answers.sinstance = $TableForThisUser.sinstance, Answers.sinstance = Answers.sinstance), Answers.instance = Answers.instance)";
					$qResCountItems = mysqli_query($db_connection, $qCountItems);
					
					$NoOfItems = mysqli_num_rows($qResCountItems);
					if($questionID==115 && $NoOfItems==0)
						{
						continue;
						}
					if($bRowOdd)
						{
						$rowClass = "matrixRowOdd";
						}
					else
						{
						$rowClass = "matrixRowEven";
						}
					if($NoOfAnswers>0)
						{
						$PercentageOfAnswers = round($NoOfItems/$NoOfAnswers * 100,1); 
						}
					else
						{
						$PercentageOfAnswers = 0;
						} 
					echo"<tr class=\"$rowClass\">";
					if($questionID==115)
						{
						$trimmedCountry = mysqli_real_escape_string(trim($rowItems['text']));
						$qInfoURLs = "	SELECT lonelyPlanetURL, wikipediaURL
										FROM ElectiveLinks
										WHERE TRIM(country) = '$trimmedCountry'";
						$qResInfoURLs = mysqli_query($db_connection, $qInfoURLs);
						$rowInfoURLs = mysqli_fetch_array($qResInfoURLs);
						if($rowInfoURLs[lonelyPlanetURL]!="")
							{
							echo "<td><a href=\"" . $lonelyPlanetURL . $rowInfoURLs['lonelyPlanetURL'] . "\" target=\"countrylink\">" . $trimmedCountry . "</a> (Follow link to further information on this country from Lonely Planet)</td>";
							}
						elseif($rowInfoURLs[wikipediaURL]!="")
							{
							echo "<td><a href=\"" . $wikipediaURL . $rowInfoURLs['wikipediaURL'] . "\" target=\"countrylink\">" . $trimmedCountry . "</a> (Follow link to further information on this country from Wikipedia)</td>";
							}
						else
							{
							echo "<td>" . $trimmedCountry . "</td>";
							}
						}
					else
						{
						echo "<td>".$rowItems['text']."</td>";
						}
					echo "<td>$PercentageOfAnswers ($NoOfItems)</td></tr>";
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
				$itemNo++;
				}
			if ($questionID==155) //startdate
				{
				$storedStartDates = array();
				$dateNo=0;
				while($rowDates = mysqli_fetch_array($qResDates))
					{
					$storedStartDates[$dateNo] = $rowDates['date'];
					$dateNo++;
					}
				}
			elseif ($questionID==156) //finishdate
				{
				$dateNo=0;
				while($rowDates = mysqli_fetch_array($qResDates))
					{
					$dateDiffArray = datediff($storedStartDates[$dateNo],$rowDates['date'],2);
					echo "<tr class=\"$rowClass\">
							<td colspan=\"$colSpan\">"
								 .ODBCDateToTextDateShort($storedStartDates[$dateNo])." - ".ODBCDateToTextDateShort($rowDates['date']). " (" . $dateDiffArray[0] . ")<br/>";
					echo " </td>
						</tr>"; 
					$dateNo++;
					}
				}
			else
				{
				while($rowDates = mysqli_fetch_array($qResDates))
					{
					echo "<tr class=\"$rowClass\">
							<td colspan=\"$colSpan\">"
								 .ODBCDateToTextDateShort($rowDates['date']). " <br/>";
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
			if(mysqli_num_rows($qResComments)>0)
				{
				echo"<tr class=\"$rowClass\"><td colspan=\"2\">
					<div class=\"comments\" id=\"divComments_".$questionNo.$i."\" name=\"divComments_".$questionNo.$i."\">
						<strong>Comments:</strong>
						<br/>";
						$commentCount = 1;
						while($rowComments = mysqli_fetch_array($qResComments))
							{
							if($rowComments['text'] != strip_tags($rowComments['text'])){
								$comment_text = str_replace('src="ckfinder/','src="https://learntech.imsu.ox.ac.uk/feedback/ckfinder/',$rowComments['text']);
								//$comment_text = $rowComments['text'];
								echo "<textarea class=\"ckeditorclass\" rows=\"30\" id=\"divCommentsTextArea_".$questionNo.$i."_". $commentCount . "\">" . $commentCount . " (" .ODBCDateToTextDateShort($rowComments[date]).")"." - $comment_text </textarea>";
								}
							else{
								echo "<hr>" . $commentCount . " (" .ODBCDateToTextDateShort($rowComments['date']).")"." - ".$rowComments['text']." <br/>";
								}
							$commentCount++;
							}
				echo"</div></td></tr>";
				}
			if ($questionID!=155) //startdate
				{
				echo "</table>"; 
				}
			$questionNo = $questionNo + 1;
			}
		}
	}
//finally drop ParticipantItems for this user
$qDropTable = "DROP TABLE IF EXISTS $TableForThisUser";
$qResDropTable = mysqli_query($db_connection, $qDropTable);
?>
<?php
	//Breadcrumb
	if(IsAuthor($heraldID))
		{
		require_once("../includes/html/BreadCrumbsHeader.html"); 
		echo "<a href=\"index.php\">Search electives</a> &gt; ";
		if(isset($_POST['bSearchByCountry']))
			{
			echo "<a href=\"quicksearch.php\">Quick search</a> &gt; ";
			}
		else
			{
			echo "<a href=\"fullsearch.php\">Full search</a> &gt; ";
			} 
		echo "<strong>Search results</strong>"; 	
		require_once("../includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("../includes/html/footernew.html"); 
	require_once("../includes/instructions/inst_electiveresults.php"); 
?>
</body>
</html>
