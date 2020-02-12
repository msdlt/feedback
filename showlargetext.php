<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<?php 
	//Include config.php which contains db details and connects to the db)
	require_once("includes/config.php"); 
	require_once("includes/getuser.php");
	require_once("includes/isauthor.php");
	require_once("includes/limitstring.php");
	require_once("includes/ODBCDateToTextDate.php");
	require_once("includes/gettextoutput.php");
	require_once("includes/getinstancesforblockandsection.php");

	if($_SERVER['REQUEST_METHOD'] != 'POST') //see http://stackoverflow.com/questions/22004789/if-isset-post-not-working-for-internet-explorer
		{
		echo "This page requires input from another page. Please go to <a href=\"viewreports.php\">viewreports.php</a>";
		exit;
		}
		//extract data from viewstudents.php
		$surveyID = $_POST['hSurveyID'];
		$surveyInstanceID = $_POST['hSurveyInstanceID'];
		$studentHeraldID = $_GET['studentHeraldID'];
	
		//Only reuiqred on learntech.imsu.ox.ac.uk where db exists
		//connect to heraldID and name database
		//$db_select = mysql_select_db ($dbstudent_info['dbname'], $db_connection) or die (mysqli_error());
		//$dbstudent_connection = mysql_connect ($dbstudent_info['host'], $dbstudent_info['username'], $dbstudent_info['password']) or die (mysqli_error());
		//$db_select = mysql_select_db ($dbstudent_info['dbname'], $dbstudent_connection) or die (mysqli_error());
		$dbstudent_connection = mysqli_connect ($dbstudent_info['host'], $dbstudent_info['username'], $dbstudent_info['password'], $dbstudent_info['dbname']) or die (mysqli_error());
		$qStudentName = "	SELECT LASTNAME, FORENAMES
						FROM cards
						WHERE USERNAME = '$studentHeraldID'";
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
				$studentName = $rowStudentName['LASTNAME'] . ", " . $rowStudentName['FORENAMES'];
				}
			else
				{
				echo "Name not found";
				}
			}	
		mysqli_close($dbstudent_connection);
		//$db_connection = mysql_connect ($db_info['host'], $db_info['username'], $db_info['password']) or die (mysqli_error());
		//$db_select = mysql_select_db ($db_info['dbname'], $db_connection) or die (mysqli_error());
		$db_connection = mysqli_connect ($db_info['host'], $db_info['username'], $db_info['password'], $db_info['dbname']) or die (mysqli_error());
	
	//Get survey information
	$qSurveys = "SELECT title
				FROM Surveys
				WHERE surveyID = $surveyID";
	$qResSurvey = mysqli_query($db_connection, $qSurveys);
	$rowSurvey = mysqli_fetch_array($qResSurvey);
	$surveyTitle = $rowSurvey['title'];
	
	$qSurveyInstances = "SELECT title, startDate, finishDate
						FROM SurveyInstances
						WHERE surveyInstanceID = $surveyInstanceID";
	$qResSurveyInstance = mysqli_query($db_connection, $qSurveyInstances);
	$rowSurveyInstance = mysqli_fetch_array($qResSurveyInstance);
	$surveyInstanceTitle = $rowSurveyInstance['title'];
	
	echo "<title>$surveyInstanceTitle - $studentName - Reports</title>";
?>	

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" type="text/css" href="css/SurveyStyles.css">
<link rel="stylesheet" type="text/css" href="css/msdstyle2.css" media="screen"/>
<link href="css/msdprint.css" rel="stylesheet" type="text/css" media="print" />
<script type="text/javascript" src="script/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="ckfinder/ckfinder.js"></script>
<script type="text/javascript" src="script/jquery-1.9.1.min.js"></script>
<script>
function bodyOnLoad(){
		CKFinder.setupCKEditor( null, 'https://learntech.imsu.ox.ac.uk/feedback/ckfinder/' );
		$('textarea').each(function(){
				var id = $(this).attr('id');
				CKEDITOR.replace( id, {
					readOnly: true,
					height: '500px',
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
</head>

<body onLoad="bodyOnLoad()">
<?php
if(IsAuthor($heraldID))
	{
	require_once("includes/html/adminheadernew.html");
	require_once("includes/html/BreadCrumbsHeader.html"); 
	echo "<a href=\"results.php\">Results</a> &gt; <a href=\"viewreports.php\">View Reports</a> &gt; <a href=\"showlargetextstudents.php\">Student Reports</a> &gt; $surveyInstanceTitle - $studentName - Reports";
	require_once("includes/html/BreadCrumbsFooter.html"); 
	}
else
	{
	require_once("includes/html/header.html"); 
	echo "You do not have the necessary permissions to view this page";
	exit(); 
	}
echo "<h1>$surveyInstanceTitle - $studentName - Reports</h1>";
echo "<a name=\"maintext\" id=\"maintext\"></a>";
echo "<form id=\"frmSurvey\" name=\"frmSurvey\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" onSubmit=\"return ValidateForm()\">";

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
		//is this block branched to?
		$qThisObjectIsBranchedTo = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
										FROM BranchDestinations";										
		$qResThisObjectIsBranchedTo = mysqli_query($db_connection, $qThisObjectIsBranchedTo);
		$ThisObjectIsBranchedTo = false;
		while($rowThisObjectIsBranchedTo = mysqli_fetch_array($qResThisObjectIsBranchedTo))
			{
			if($rowThisObjectIsBranchedTo['blockID'] == $blockID && 
			$rowThisObjectIsBranchedTo['sectionID'] == NULL && 
			$rowThisObjectIsBranchedTo['questionID'] == NULL)
				{
				$ThisObjectIsBranchedTo = true;
				}
			}
		//does this block contain a lgtext question?
		//find out whether this blocks contain lgtext questions
		$bThisBlockContainsLgText = false;
		$qLgTextQuestions = "SELECT Questions.questionID
							FROM Questions, SectionQuestions, BlockSections
							WHERE BlockSections.blockID = ". $blockID ."
							AND SectionQuestions.sectionID = BlockSections.sectionID
							AND Questions.questionID = SectionQuestions.questionID
							AND Questions.questionTypeID = 5";
		$qResLgTextQuestions = @mysqli_query($db_connection, $qLgTextQuestions);
		if (($qResLgTextQuestions == false))
			{
			echo "problem querying Questions" . mysqli_error();
			}
		else
			{
			if(mysqli_num_rows($qResLgTextQuestions)>0)
				{
				$bThisBlockContainsLgText = true;
				}
			}
		//get sections
		$qSections = "SELECT Sections.sectionID, Sections.title, Sections.text, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, Sections.instanceable 
					FROM Sections, BlockSections 
					WHERE BlockSections.blockID = ". $blockID ."
					AND BlockSections.visible = 1
					AND Sections.sectionID = BlockSections.sectionID
					ORDER BY BlockSections.position";
		
		$qResSections = mysqli_query($db_connection, $qSections);
		for($inst=1;$inst<=$noOfInstances;$inst++)
			{
			echo "<div class=\"block\" id=\"div".$blockID."_i".$inst."\"".($ThisObjectIsBranchedTo?"style=\"display:none\"":(!$bThisBlockContainsLgText?"style=\"display:none\"":"")).">";
			//begin block content
			if($rowBlocks['text'] != "")
				{
				//only output a block title if there is one
				echo "<h2>".$rowBlocks['text'];
				if ($rowBlocks['instanceable']==1)
					{
					echo ": ".$inst;
					} 
				echo "</h2>";
				}
			echo "<div class=\"blockText\">$rowBlocks[introduction]</div>"; 
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
				//is this block branched to?
				$qThisObjectIsBranchedTo = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
												FROM BranchDestinations";										
				$qResThisObjectIsBranchedTo = mysqli_query($db_connection, $qThisObjectIsBranchedTo);
				$ThisObjectIsBranchedTo = false;
				while($rowThisObjectIsBranchedTo = mysqli_fetch_array($qResThisObjectIsBranchedTo))
					{
					if($rowThisObjectIsBranchedTo['blockID'] == $blockID && 
					$rowThisObjectIsBranchedTo['sectionID'] == $sectionID && 
					$rowThisObjectIsBranchedTo['questionID'] == NULL)
						{
						$ThisObjectIsBranchedTo = true;
						}
					}
				//find out whether this section contain lgtext questions
				$bThisSectionContainsLgText = false;
				$qLgTextQuestions = "SELECT Questions.questionID
									FROM Questions, SectionQuestions, BlockSections
									WHERE SectionQuestions.sectionID = ". $sectionID ."
									AND Questions.questionID = SectionQuestions.questionID
									AND Questions.questionTypeID = 5";
				$qResLgTextQuestions = @mysqli_query($db_connection, $db_connection, $qLgTextQuestions);
				if (($qResLgTextQuestions == false))
					{
					echo "problem querying Questions" . mysqli_error();
					}
				else
					{
					if(mysqli_num_rows($qResLgTextQuestions)>0)
						{
						$bThisSectionContainsLgText = true;
						}
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
					echo "<div class=\"section\" id=\"div".$blockID."_".$sectionID."_i".$inst."_si".$sinst."\"".($ThisObjectIsBranchedTo?"style=\"display:none\"":(!$bThisSectionContainsLgText?"style=\"display:none\"":"")).">";
				
					//begin section content
					if($rowSections['text'] != "")
						{
						//only output a section title if there is one
						echo "<h3>". $rowSections['text'];
						if ($rowSections[instanceable]==1)
							{
							echo ": ".$sinst;
							} 
						echo "</h3>";
						}
					echo "<div class=\"sectionText\">$rowSections[introduction]</div>"; 
					if(mysqli_num_rows($qResQuestions)>0)
						{
						mysqli_data_seek($qResQuestions, 0);
						}
					//check section type
					switch ($rowSections[sectionTypeID]) 
						{
						case 1:
							{
							//if the section's 'normal'
							//loop through questions
							while($rowQuestions = mysqli_fetch_array($qResQuestions))
								{
								$questionID = $rowQuestions['questionID'];
								
								//is this question branched to?
								$qThisObjectIsBranchedTo = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
																FROM BranchDestinations";										
								$qResThisObjectIsBranchedTo = mysqli_query($db_connection, $qThisObjectIsBranchedTo);
								$ThisObjectIsBranchedTo = false;
								while($rowThisObjectIsBranchedTo = mysqli_fetch_array($qResThisObjectIsBranchedTo))
									{
									if($rowThisObjectIsBranchedTo['blockID'] == $blockID && 
									$rowThisObjectIsBranchedTo['sectionID'] == $sectionID && 
									$rowThisObjectIsBranchedTo['questionID'] == $questionID)
										{
										$ThisObjectIsBranchedTo = true;
										}
									}
								
								echo "<div class=\"questionNormal\" id=\"div".$blockID."_".$sectionID."_".$questionID."_i".$inst."_si".$sinst."\"".($ThisObjectIsBranchedTo?"style=\"display:none\"":"").">";
								if (!$addingInstance && !$deletingInstance)
									{
									if($rowQuestions[comments]=="true" || $rowQuestions['questionTypeID'] == 4 || $rowQuestions['questionTypeID'] == 5)
										{
										//find out if this question already has comments/text from this user
										$qCommentAnswered = "	SELECT AnswerComments.text
															FROM AnswerComments, Answers
															WHERE Answers.surveyInstanceID = $surveyInstanceID
															AND Answers.blockID = $blockID
															AND Answers.sectionID = $sectionID
															AND Answers.questionID = $questionID
															AND Answers.heraldID = '$studentHeraldID'
															AND Answers.instance = $inst
															AND Answers.sinstance = $sinst
															AND AnswerComments.answerID = Answers.answerID";
										$qResCommentAnswered = mysqli_query($db_connection, $qCommentAnswered);
										}
									}
								if($rowQuestions['questionTypeID']==5)
									{
									//NOTE: No branching possible on a text question
									$textName = $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
									$textID = $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
									echo "<table class=\"normal_4\">";
									echo "	<tr>";
									echo " 		<td class=\"question\">$questionNo ".$rowQuestions['text']."</td>";
									echo "	</tr>";
									echo "	<tr>
												<td>
													<textarea class=\"ckeditorclass\" rows=\"50\" id=\"".$textID. "\">";
														if(mysqli_num_rows($qResCommentAnswered)==1)
															{
															//if so, write the text into the textarea
															$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
															echo stripslashes($rowCommentAnswered['text']);
															}
												echo "</textarea>";
											echo "</td>
											</tr>";
									echo "</table>";
									}
								echo "</div>";
								//increment question number
								$questionNo = $questionNo + 1;
								}
							break;
							}
						}
					//end section content
					echo "</div>";
					}
				}	
			echo "</div>";	
			}
		}
	echo "</form>";
?>			
<?php
	//Breadcrumb
	if(IsAuthor($heraldID))
		{
		require_once("includes/html/BreadCrumbsHeader.html"); 
		echo "<a href=\"results.php\">Results</a> &gt; <a href=\"viewreports.php\">View Reports</a> &gt; <a href=\"showlargetextstudents.php\">Student Reports</a> &gt; $surveyInstanceTitle - $studentName - Reports";
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html");
	require_once("includes/instructions/inst_showlargetext.php"); 

?>


</body>
</html>
