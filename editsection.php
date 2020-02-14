<?php
	require_once("includes/config.php");
	require_once("includes/getuser.php"); 
	require_once("includes/isauthor.php");
	require_once("includes/issuperauthor.php");
	require_once("includes/limitstring.php");
	require_once("includes/arethereanyresultsforthisobject.php");
	require_once("includes/quote_smart.php");
	require_once("includes/ODBCDateToTextDate.php");
?>
<?php
if ((isset($_POST['bUpdate'])&& $_POST['bUpdate']!= "")||(isset($_POST['bCreate'])&& $_POST['bCreate']!= ""))
	{
	if(isset($_POST['hSectionID']))
		{
		$sectionID = $_POST['hSectionID'];
		$blockID = $_POST['hBlockID'];
		$surveyID = $_POST['hSurveyID'];
		$updateTitle = quote_smart($_POST['tTitle']);
		$updateText = quote_smart($_POST['tText']);
		$updateIntroduction = quote_smart($_POST['tIntroduction']);
		$updateEpilogue = quote_smart($_POST['tEpilogue']);
		$updateType = $_POST['sSectionType'];
		if($_POST['rInstanceable']=="true")
			{
			$updateInstanceable = 1;
			}
		else
			{
			$updateInstanceable = 0;
			}
		$updateLastModified = "CURDATE()";
		//**********************************************************
		//Server-side validation of data entered - 
		//**********************************************************
		/* Checking and ensure that the section title does not already exist in the database */
		$sql_title_check = mysqli_query("SELECT title FROM Sections
										WHERE title=$updateTitle" . ($sectionID !="add" ? "AND sectionID <> $sectionID" : ""));
										//that last if statement allows edited section to have the same name as it had before
										//but prevents added sections having the same name as an existing section.
		$title_check = mysqli_num_rows($sql_title_check);
		if($title_check > 0 || $updateTitle == "")
			{
			$validationProblem = true;
			if($sectionID =="add")
				{
				//set submit button title
				$btnSubmitName = "bCreate";
				$btnSubmitText = "Create section";
				$pageTitleText = "Create section";
				}
			else
				{
				$btnSubmitName = "bUpdate";
				$btnSubmitText = "Update section";
				$pageTitleText = "Edit section";
				}
			}
		//**********************************************************
		//End server-side validation of data entered 
		//**********************************************************
		else
			{
			//Validated - go ahead with updating/writing
			if($sectionID =="add")
				{
				//Create new section with values from this form
				$iSections = "	INSERT INTO Sections
								VALUES(0,$updateTitle,$updateText,$updateIntroduction,$updateEpilogue,$updateType, $updateLastModified,$updateInstanceable)";
				$result_query = @mysqli_query($db_connection, $iSections);
				$sectionID = mysqli_insert_id();
				if (($result_query == false))
					{
					echo "problem inserting into Sections" . mysqli_error();
					$bSuccess = false;
					}
				//Now need to add it to BlockSections
				//first work out position to add it to
				$qMaxPosition = "	SELECT MAX(position) as maxPosition
									FROM BlockSections
									WHERE blockID = $blockID";
				$result_query = @mysqli_query($db_connection, $qMaxPosition);
				if (($result_query == false))
					{
					echo "problem querying qMaxPosition" . mysqli_error();
					}
				$rowMaxPosition = mysqli_fetch_array($result_query);
				$maxPosition = $rowMaxPosition[maxPosition];
				if ($maxPosition == "")
					{
					//there are no existing sections in this block
					$position = 0;
					}
				else
					{
					//there are existing sections in this block so increment
					$position = $maxPosition + 1;
					}
				$iBlockSections = "INSERT INTO BlockSections
								VALUES(0,$blockID,$sectionID,$position,1)";
				$result_query = @mysqli_query($db_connection, $iBlockSections);
				if (($result_query == false))
					{
					echo "problem insering into BlockSections" . mysqli_error();
					$bSuccess = false;
					}
				}
			else
				{
				//Update section with values from this form
				$uSections = "UPDATE Sections
						SET title = $updateTitle,
						text = $updateText,
						introduction = $updateIntroduction,
						epilogue = $updateEpilogue,
						sectionTypeID = $updateType,
						lastModified = $updateLastModified,
						instanceable = $updateInstanceable
						WHERE sectionID = $sectionID";
				$result_query = @mysqli_query($db_connection, $uSections);
				if (($result_query == false))
					{
					echo "problem updating Sections" . mysqli_error();
					$bSuccess = false;
					}
				}
			//send the user back to schedulesurvey.php after they have added
			header("Location: https://" . $_SERVER['HTTP_HOST']
                     . dirname($_SERVER['PHP_SELF'])
                     . "/" . "editblock.php?surveyID=$surveyID&blockID=$blockID");
			exit();
			}
		}
	}
//********************************************************
//deleting question(s)
//********************************************************
else if (isset($_POST['bDelete'])&&$_POST['bDelete']!="")
	{
	$surveyID = $_POST['hSurveyID'];
	$blockID = $_POST['hBlockID'];
	$sectionID = $_POST['hSectionID'];
	//Find out ids of all the checkboxes which were checked
	$aQuestions = $_POST['checkQuestionIDs'];
	for ($i=0;$i<count($aQuestions);$i++)
		{
		//find out if there are any results for this section
		if(AreThereAnyResultsForThisObject($db_connection, $surveyID, $blockID, $sectionID, $aQuestions[$i])==true)
			{
			//hide the section
			$uSectionQuestions = "	UPDATE SectionQuestions
								SET visible = 0
								WHERE sectionID = $sectionID
								AND questionID = $aQuestions[$i]";
			$result_query = @mysqli_query($db_connection, $uSectionQuestions);
			if (($result_query == false))
					{
					echo "problem updating SectionQuestions" . mysqli_error();
					$bSuccess = false;
					}
			}
		else
			{
			//delete it from the block - but don't do anything to the question itself or any of its children - separate interface for that
			$dSectionQuestions = "	DELETE FROM SectionQuestions
									WHERE sectionID = $sectionID
									AND questionID = $aQuestions[$i]";
			$dResSectionQuestions = @mysqli_query($db_connection, $dSectionQuestions);
			if (($dResSectionQuestions == false) && mysqli_affected_rows($db_connection) == 0)
				{
				echo "problem deleting from SectionQuestions " . mysqli_error();
				}
			}
		}
	$btnSubmitName = "bUpdate";
	$btnSubmitText = "Update section";
	$pageTitleText = "Edit section";
	}
//********************************************************
//Reinstating question(s)
//********************************************************
else if (isset($_POST['bReinstate'])&&$_POST['bReinstate']!="")
	{
	$surveyID = $_POST['hSurveyID'];
	$blockID = $_POST['hBlockID'];
	$sectionID = $_POST['hSectionID'];
	//Find out ids of all the checkboxes which were checked
	$aQuestions = $_POST['reinstateQuestionIDs'];
	for ($i=0;$i<count($aQuestions);$i++)
		{
		//reinstate the section
		$uSectionQuestions = "	UPDATE SectionQuestions
							SET visible = 1
							WHERE sectionID = $sectionID
							AND questionID = $aQuestions[$i]";
		$result_query = @mysqli_query($db_connection, $uSectionQuestions);
		if (($result_query == false))
			{
			echo "problem updating SectionQuestions" . mysqli_error();
			$bSuccess = false;
			}
		}
	$btnSubmitName = "bUpdate";
	$btnSubmitText = "Update section";
	$pageTitleText = "Edit section";
	}
//********************************************************
//re-ordering question(s)
//********************************************************
else if (isset($_POST['hReOrder']) && $_POST['bReOrder'] != "")
	{
	$surveyID = $_POST['hSurveyID'];
	$blockID = $_POST['hBlockID'];
	$sectionID = $_POST['hSectionID'];
	//Get new order of section ID's
	$tQuestions = $_POST['hReOrder'];
	$aQuestions = explode(",",$tQuestions);
	for ($i=0;$i<count($aQuestions);$i++)
		{
		$uSectionQuestions = "	UPDATE SectionQuestions
								SET position = $i
								WHERE sectionID = $sectionID
								AND questionID = $aQuestions[$i]";
		$result_query = @mysqli_query($db_connection, $uSectionQuestions);
		if (($result_query == false))
			{
			echo "problem updating SectionQuestions" . mysqli_error();
			$bSuccess = false;
			}
		}
	$btnSubmitName = "bUpdate";
	$btnSubmitText = "Update section";
	$pageTitleText = "Edit section";
	}
else if (isset($_GET['surveyID'])&&isset($_GET['blockID'])&&isset($_GET['sectionID']))
	{
	$surveyID = $_GET['surveyID'];
	$blockID = $_GET['blockID'];
	$sectionID = $_GET['sectionID'];
	if($_GET['sectionID']=="add")
		{
		$btnSubmitName = "bCreate";
		$btnSubmitText = "Create section";
		$pageTitleText = "Create section";
		}
	else
		{
		$btnSubmitName = "bUpdate";
		$btnSubmitText = "Update section";
		$pageTitleText = "Edit section";
		}
	}
else
	{
echo "<h1>Warning</h1>
	<p>This page requires data from another page.</p>";
	exit();
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php echo $pageTitleText ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="css/SurveyStyles.css">
<link rel="stylesheet" type="text/css" href="css/msdstyle2.css" media="screen"/>
<link href="css/msdprint.css" rel="stylesheet" type="text/css" media="print" />
<script language="JavaScript" src="script/validate.js"></script>
<script language="JavaScript" src="script/OptionTransfer.js"></script>
<script language="javascript" type="text/javascript">
//enables OK/update button if input has changed
function ControlHasChanged(btnSubmit)
	{
	document.getElementById(btnSubmit).disabled = false;
	document.getElementById('btnPreviewSurvey').disabled = true;
	//setting a cookie which regsiters any changes since last submit button press
	if (cookieStatus()=="on") setCookie("savedData", "false"); 
	}
// function to set a cookie 
function setCookie(cookieName, cookieValue) 
	{
  	var currentCookie = cookieName + "=" + escape(cookieValue);
  	document.cookie = currentCookie;
	}
// function to get a cookie 
function getCookie(cookieName) 
	{
  	var dc = document.cookie;
  	var prefix = cookieName + "=";
  	var begin = dc.indexOf("; " + prefix);
  	if (begin == -1) 
		{
    	begin = dc.indexOf(prefix);
    	if (begin != 0) return null;
  		} 
	else
		{
		begin = begin + 2;
		}
  	var end = document.cookie.indexOf(";", begin);
  	if (end == -1)
		{
    	end = dc.length;
		}
  	return unescape(dc.substring(begin + prefix.length, end));
	}
//checks whether user has cookies turned on or off
function cookieStatus()
	{
	setCookie("testCookie", "testValue");
	var testCookieValue = getCookie("testCookie"); 
	if (testCookieValue != "testValue")
		{
		return "off";
		}
	else
		{
		return "on";
		}
	}
//exactly what it says on the tin
function CheckSaved(text)
	{
	if (cookieStatus()=="on")
		{
		var AllSaved = getCookie("savedData"); 
		if (AllSaved!="true")
			{
			return text;
			}
		else
			{
			return;
			}
		}
	else
		{
		return;
		}
	} 
function goTo(URL)
		{
		window.location.href = URL;
		}
	//Validation of input (called by frm1 OnSubmit())
function ValidateForm(theForm)
	{
	if(!validText(document.getElementById("tTitle"),"section title",true))
		{
		return false;
		}
	if (cookieStatus()=="on") setCookie("savedData", "true");
	return true;
	}
function dumpList(from,target,output)
	{
	//dumps the contents of a <select> (from) into target (usually an <input type="hidden">
	//output = 0 - dump value
	//output = 1 - dump text
	target.value = "";
	for (i=0;i<from.options.length;i++) 
		{
		if (i==from.options.length-1)
			{
			if(output==1)
				{
				target.value = target.value + from.options[i].text;
				}
			else
				{
				target.value = target.value + from.options[i].value;
				}
			}
		else
			{
			if(output==1)
				{
				target.value = target.value + from.options[i].text + ',';
				}
			else
				{
				target.value = target.value + from.options[i].value + ',';
				}
			}
		}
	}

</script>
</head>

<body onBeforeUnload="return CheckSaved('You have made changes to the section properties. Please save them before moving away from this page')">
<?php
//Get info about survey
$qSurveys = "SELECT title
			FROM Surveys
			WHERE surveyID = $surveyID";
$qResSurvey = mysqli_query($db_connection, $qSurveys);
$rowSurvey = mysqli_fetch_array($qResSurvey);
$surveyTitle = $rowSurvey['title'];
//Get info about block
$qBlocks = "SELECT title
			FROM Blocks
			WHERE blockID = $blockID";
$qResBlock = mysqli_query($db_connection, $qBlocks);
$rowBlock = mysqli_fetch_array($qResBlock);
$blockTitle = $rowBlock['title'];
//Get info about section
if($sectionID!="add")
	{
	$qSections = "	SELECT title, text, introduction, epilogue, sectionTypeID, lastModified, instanceable 
					FROM Sections
					WHERE sectionID = $sectionID";
	$qResSections = mysqli_query($db_connection, $qSections);
	$rowSection = mysqli_fetch_array($qResSections);
	$sectionTitle = $rowSection['title'];
	$sectionText = $rowSection['text'];
	$sectionIntroduction = $rowSection['introduction'];
	$sectionEpilogue = $rowSection['epilogue'];
	$sectionType = $rowSection['sectionTypeID'];
	$sectionLastModified = $rowSection['lastModified'];
	$sectionInstanceable = $rowSection['instanceable'];
	}
elseif ($validationProblem == true)
	{
	$sectionTitle = $_POST['tTitle'];
	$sectionText = $_POST['tText'];
	$sectionIntroduction = $_POST['tIntroduction']; 
	$sectionEpilogue = $_POST['tEpilogue'];
	$sectionType = $_POST['sSectionType'];
	if($_POST['rInstanceable']==true)
		{
		$sectionInstanceable = 1;
		}
	else
		{
		$sectionInstanceable = 0;
		}
	}
else
	{
	$sectionTitle = "New section";
	$sectionText = "New section";
	$sectionIntroduction = "Introduction";
	$sectionEpilogue = "Epilogue";
	$sectionType = 1;
	$sectionInstanceable = 0;
	}
$qSectionTypes = "	SELECT sectionTypeID, type
					FROM SectionTypes";
$qResSectionTypes = mysqli_query($db_connection, $qSectionTypes);					
if(IsAuthor($heraldID))
	{
	require_once("includes/html/adminheadernew.html");
	require_once("includes/html/BreadCrumbsHeader.html"); 
	echo "
	<a href=\"admin.php\">Administration</a> &gt;
	<a href=\"editsurvey.php?surveyID=$surveyID\">Edit survey: ".limitString($surveyTitle,30)."</a> &gt; 
	<a href=\"editblock.php?surveyID=$surveyID&blockID=$blockID\">Edit block: ".limitString($blockTitle,30)."</a> &gt; 
	$pageTitleText: ".limitString($sectionTitle,30);
	require_once("includes/html/BreadCrumbsFooter.html"); 
	}
else
	{
	require_once("includes/html/header.html"); 
	echo "You do not have the necessary permissions to view this page";
	exit();
	}
echo "<a name=\"maintext\" id=\"maintext\"></a>
<h1>$pageTitleText: $sectionTitle</h1>
<form id=\"frmEdit\" name=\"frmEdit\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" onSubmit=\"return ValidateForm(this)\">
<h2>Section properties:</h2>
<div class=\"questionNormal\">";
if($validationProblem==true)
	{
	echo "<span class=\"errorMessage\"><strong>Please fix the following errors:</strong><br/>";
	if($title_check > 0)
		{
		echo "This section name is already in use in our database. Please enter a different title (e.g. prefix with the name of your survey) and try again.<br/>";
		}
	if($updateTitle == "")
		{
		echo "You must enter a name for this section. Please enter a name and try again.<br/>";
		}
	echo "</span><br/>";
	}
echo"
<table class=\"normal_3\" summary=\"\">
	<tr>
		<td>Name:</td>
		<td>
			<input type=\"text\" id=\"tTitle\" name=\"tTitle\" value=\"$sectionTitle\" size=\"50\" onChange=\"ControlHasChanged('$btnSubmitName')\">
		</td>
	</tr>
	<tr>
		<td valign=\"top\">Title:</td>
		<td>
			<textarea id=\"tText\" name=\"tText\" rows=\"3\" cols=\"35\" onChange=\"ControlHasChanged('$btnSubmitName')\">$sectionText</textarea>
		</td>
	</tr>
	<tr>
		<td>Last modified:</td>
		<td>".ODBCDateToTextDateShort($sectionLastModified)."</td>
	</tr>
	<tr>
		<td valign=\"top\">Introduction:</td>
		<td>
			<textarea id=\"tIntroduction\" name=\"tIntroduction\" rows=\"5\" cols=\"35\" onChange=\"ControlHasChanged('$btnSubmitName')\">$sectionIntroduction</textarea>
		</td>
	</tr>
	<tr>
		<td valign=\"top\">Epilogue:</td>
		<td>
			<textarea id=\"tEpilogue\" name=\"tEpilogue\" rows=\"5\" cols=\"35\" onChange=\"ControlHasChanged('$btnSubmitName')\">$sectionEpilogue</textarea>
		</td>
	</tr>
	<tr>
		<td valign=\"top\">Section type:</td>
		<td>
			<select id=\"sSectionType\" name=\"sSectionType\" size=\"2\" onChange=\"ControlHasChanged('$btnSubmitName')\">";
			while($rowSectionTypes = mysqli_fetch_array($qResSectionTypes))
				{
echo "			<option ";
				if($rowSectionTypes[sectionTypeID]==$sectionType)
					{
					echo " selected ";
					}
					echo "value=\"$rowSectionTypes[sectionTypeID]\">$rowSectionTypes[type]</option>";
				}
echo "		</select> 
		</td>
	</tr>
	<tr>
		<td valign=\"top\">Is repeatable?</td>
		<td>
			<input ";
			if($sectionInstanceable==1)
				{
				echo "checked";
				}	
			echo " type=\"radio\" name=\"rInstanceable\" id=\"rInstanceableTrue\" value=\"true\" onChange=\"ControlHasChanged('$btnSubmitName')\"><label for=\"rInstanceableTrue\">Yes</label>
			<input ";
			if($sectionInstanceable==0)
				{
				echo "checked";
				} 
			echo " type=\"radio\" name=\"rInstanceable\" id=\"rInstanceableFalse\" value=\"false\" onChange=\"ControlHasChanged('$btnSubmitName')\"><label for=\"rInstanceableFalse\">No</label>
		</td>
	</tr>
	<tr>
		<td clospan=\"2\">
			<input type=\"hidden\" value=\"$surveyID\" id=\"hSurveyID\" name=\"hSurveyID\">
			<input type=\"hidden\" value=\"$blockID\" id=\"hBlockID\" name=\"hBlockID\">
			<input type=\"hidden\" value=\"$sectionID\" id=\"hSectionID\" name=\"hSectionID\">
			<input type=\"submit\" value=\"$btnSubmitText\" id=\"$btnSubmitName\" name=\"$btnSubmitName\">
			<input type=\"button\" id=\"btnPreviewSurvey\" name=\"btnPreviewSurvey\" value=\"Preview\" onClick=\"window.open('previewsurvey.php?surveyID=$surveyID','previewWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes')\">
		</td>
	</tr>
		";
echo " </table>";
echo " </div>
	</form>";
if($sectionID !="add")
	{
	//get all questions		
	$qQuestions = "SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position, Questions.lastModified 
					FROM Questions, SectionQuestions
					WHERE SectionQuestions.sectionID = $sectionID
					AND SectionQuestions.visible = 1
					AND Questions.questionID = SectionQuestions.questionID
					ORDER BY SectionQuestions.position";
		
	$qResQuestions = mysqli_query($db_connection, $qQuestions);
	
	if (($qResQuestions == false))
		{
		echo "problem querying Questions" . mysqli_error();
		}
	else
		{
		//to get question numbering right, need to calculate how many questions occur before this one in the survey
		//first calculate how many blocks before this one in the survey
		//first find out position of this block
		$qBlockPosition = "	SELECT position
							FROM SurveyBlocks
							WHERE blockID = $blockID
							AND surveyID = $surveyID";
		$qResBlockPosition = mysqli_query($db_connection, $qBlockPosition);
		$rowBlockPosition = mysqli_fetch_array($qResBlockPosition);
		$blockPosition = $rowBlockPosition[position];
		//then find out no of questions which occur in previous blocks:
		$qQuestionsInPreviousBlocks = "	SELECT SectionQuestions.questionID
										FROM SurveyBlocks, BlockSections, SectionQuestions
										WHERE SurveyBlocks.surveyID = $surveyID
										AND SurveyBlocks.position < $blockPosition
										AND BlockSections.blockID = SurveyBlocks.blockID
										AND BlockSections.visible = 1
										AND SectionQuestions.sectionID = BlockSections.sectionID
										AND SectionQuestions.visible = 1 ";
		$qResQuestionsInPreviousBlocks = mysqli_query($db_connection, $qQuestionsInPreviousBlocks);
		$questionNo = mysqli_num_rows($qResQuestionsInPreviousBlocks);
		//now find out position of this section within this block
		$qSectionPosition = "	SELECT position
								FROM BlockSections
								WHERE sectionID = $sectionID
								AND blockID = $blockID";
		$qResSectionPosition = mysqli_query($db_connection, $qSectionPosition);
		$rowSectionPosition = mysqli_fetch_array($qResSectionPosition);
		$sectionPosition = $rowSectionPosition[position];
		//then find out questions which occur before this one:
		$qPreviousQuestions = "	SELECT SectionQuestions.questionID
						FROM BlockSections, SectionQuestions
						WHERE BlockSections.position < $sectionPosition
						AND BlockSections.blockID = $blockID
						AND SectionQuestions.sectionID = BlockSections.sectionID
						AND SectionQuestions.visible = 1 ";
		
		$qResPreviousQuestions = mysqli_query($db_connection, $qPreviousQuestions);
		$questionNo = $questionNo + mysqli_num_rows($qResPreviousQuestions) + 1;
		echo "
		<h2>Questions in this section:</h2>
		<div class=\"questionNormal\">
			<form id=\"frmDelete\" name=\"frmDelete\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">
				<table summary=\"\" width=\"100%\">
					<tr>
						<td valign=\"top\">
							<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
								if(mysqli_num_rows($qResQuestions)==0)
									{
									echo "<p>This section does not contain any questions.</p>";
									}
								else
									{
									while($rowQuestions = mysqli_fetch_array($qResQuestions))
										{
										$questionID = $rowQuestions['questionID'];
										$questionTitle = $rowQuestions['text'];
							echo "		<tr class=\"matrixHeader\">
											<td>
												<input type=\"checkbox\" id=\"check_$questionID\" name=\"checkQuestionIDs[]\" value=\"$questionID\"/>
											</td>
											<td class=\"question\">".$questionNo." ".$rowQuestions['text']."</td>
											<td>Last modified: ".ODBCDateToTextDateShort($rowQuestions['lastModified'])."</td>
											<td><input type=\"button\" id=\"editQuestion_$questionID\" name=\"editQuestion_$questionID\" value=\"Edit question\" onClick=\"goTo('editquestion.php?&surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID&questionID=$questionID')\"".(IsSuperAuthor($heraldID, $blockID, $sectionID, $questionID)==false ? "disabled" : "" )." /></td>
										</tr>";
										//get all items	
										$qItems = "	SELECT Items.itemID, Items.text, QuestionItems.position
													FROM Items, QuestionItems
													WHERE QuestionItems.questionID = $questionID
													AND QuestionItems.visible = 1
													AND Items.itemID = QuestionItems.itemID
													ORDER BY QuestionItems.position";
											
										$qResItems = mysqli_query($db_connection, $qItems);
										if (($qResItems == false))
											{
											echo "problem querying Items" . mysqli_error();
											}
										else
											{
											$bRowOdd = true;
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
												echo "			
												<tr class=\"$rowClass\">
													<td></td>
													<td  colspan=\"3\" valign=\"top\">".$rowItems['text']."</td>
												</tr>";
												$bRowOdd = !$bRowOdd;
												}
											}
										$questionNo = $questionNo + 1;
										}
									}
					echo "		<tr>
									<td colspan=\"4\">
										<!-- These seem to need to be repeated - give them a different ID-->
										<input type=\"hidden\" value=\"$surveyID\" id=\"hSurveyID2\" name=\"hSurveyID\">
										<input type=\"hidden\" value=\"$blockID\" id=\"hBlockID2\" name=\"hBlockID\">
										<input type=\"hidden\" value=\"$sectionID\" id=\"hSectionID2\" name=\"hSectionID\">
										<input type=\"button\" id=\"bAdd\" name=\"bAdd\" value=\"Add New Question\" onClick=\"goTo('editquestion.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID&questionID=add')\"/>
										<input type=\"button\" id=\"bAddExisting\" name=\"bAddExisting\" value=\"Add Existing Question\" onClick=\"goTo('addexisting.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID')\"/>";
									if(mysqli_num_rows($qResQuestions)>0)
										{
										echo "&nbsp;<input type=\"submit\" id=\"bDelete\" name=\"bDelete\" value=\"Delete\" onclick=\"return checkCheckBoxes()\"/>";
										}
									echo"
									</td>
								</tr>
							</table>
						</td>";
					if(mysqli_num_rows($qResQuestions)>1)
						{
						echo "
						<td width=\"10%\" valign=\"top\">
							<p>To re-order, select question(s) from the list and reposition using the <strong>Move Up</strong> and
							<strong>Move Down</strong> buttons. Click <strong>Re-order</strong> to apply your changes.
							<table border=\"0\">
								<tr>
									<td>
										<select name=\"sUpDown\" size=\"7\" multiple>";
										mysqli_data_seek($qResQuestions, 0);
										while($rowQuestions = mysqli_fetch_array($qResQuestions))
											{
											$questionID = $rowQuestions['questionID'];
											$questionTitle = $rowQuestions['text'];
											echo "<option value=\"$questionID\">".($questionTitle==""?"Question":limitString($questionTitle,30))."";
											}
										echo "
										</select>
									</td>
									<td align=\"center\" valign=\"middle\">
										<input type=\"button\" value=\"Move Up\" onClick=\"moveOptionUp(this.form['sUpDown'])\"/>
										<BR><BR>
										<input type=\"button\" value=\"Move Down\" onClick=\"moveOptionDown(this.form['sUpDown'])\"/>
									</td>
								</tr>
								<tr>
									<td colspan=\"2\">
										<input type=\"hidden\" id=\"hReOrder\" name=\"hReOrder\" value=\"\"/>
										<input type=\"submit\" id=\"bReOrder\" name=\"bReOrder\" value=\"Re-Order\" onclick=\"dumpList(sUpDown,hReOrder,0)\"/>
									</td>
								</tr>
							</table>
						</td>";
						}
					echo "
					<tr>
				</table>
			<form>
		</div>";
		}
	if(mysqli_num_rows($qResQuestions)>0)
		{
		echo "	<script language=\"JavaScript\" type=\"text/javascript\" >
			function checkCheckBoxes()
				{
				var iNoOfQuestions = 0;
				var aQuestions = new Array();";				
		//resets the mysqli_fetch_array to start at the beginning again
		mysqli_data_seek($qResQuestions, 0);
		while($rowQuestions = mysqli_fetch_array($qResQuestions))
			{
			$questionID = $rowQuestions['questionID'];
			$questionTitle = $rowQuestions['text'];
		echo "	if (document.getElementById(\"check_$questionID\").checked == true)
					{
					iNoOfQuestions=iNoOfQuestions+1;
					aQuestions[iNoOfQuestions] = \"$questionTitle\";
					}";
			}
		echo "	if (iNoOfQuestions == 0)
					{
					alert(\"Please select a question to delete.\");
					return false;
					}
				else
					{
					if (iNoOfQuestions == 1)
						{
						var confirmText = \"Are you sure you want to delete \" + aQuestions[iNoOfQuestions] + \".\";
						}
					else
						{
						var confirmText = \"Are you sure you want to delete these \" + iNoOfQuestions + \" questions?\";
						}
					var answer = confirm (confirmText);
					if (answer)
						{
						if (CheckSaved('false')=='false')
							{
							var confirmText2 = \"You have made changes to the section properties. Please save them before deleting questions. Click 'Cancel' to go back and save your chnages or 'OK' to continue.\";
							var answer2 = confirm (confirmText2);
							if (answer2)
								{
								return true;
								}
							else
								{
								return false;
								}
							}
						else
							{
							return true;
							}
						}
					else
						{
						return false;
						}
					}
				}
		</script>";
	}
	//get all questions		
	$qHiddenQuestions = "SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position, Questions.lastModified 
					FROM Questions, SectionQuestions
					WHERE SectionQuestions.sectionID = $sectionID
					AND SectionQuestions.visible = 0
					AND Questions.questionID = SectionQuestions.questionID
					ORDER BY SectionQuestions.position";
		
	$qResQuestions = mysqli_query($db_connection, $qHiddenQuestions);
	
	if (($qResQuestions == false))
		{
		echo "problem querying Questions" . mysqli_error();
		}
	else
		{
		//no need to worry about numbering for hidden questions
		echo "
		<h2>Hidden questions in this section:</h2>
		<div class=\"questionNormal\">
			<form id=\"frmDelete\" name=\"frmDelete\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">
				<table summary=\"\" width=\"100%\">
					<tr>
						<td valign=\"top\">
							<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
								if(mysqli_num_rows($qResQuestions)==0)
									{
									echo "<p>This section does not contain any hidden questions.</p>";
									}
								else
									{
									while($rowQuestions = mysqli_fetch_array($qResQuestions))
										{
										$questionID = $rowQuestions['questionID'];
										$questionTitle = $rowQuestions['text'];
							echo "		<tr class=\"matrixHeader\">
											<td>
												<input type=\"checkbox\" id=\"reinstate_$questionID\" name=\"reinstateQuestionIDs[]\" value=\"$questionID\"/>
											</td>
											<td class=\"Hidden question\">".$rowQuestions['text']."</td>
											<td>Last modified: ".ODBCDateToTextDateShort($rowQuestions['lastModified'])."</td>
											<td>nbsp;</td>
										</tr>";
										//get all items	
										$qItems = "	SELECT Items.itemID, Items.text, QuestionItems.position
													FROM Items, QuestionItems
													WHERE QuestionItems.questionID = $questionID
													AND QuestionItems.visible = 1
													AND Items.itemID = QuestionItems.itemID
													ORDER BY QuestionItems.position";
											
										$qResItems = mysqli_query($db_connection, $qItems);
										if (($qResItems == false))
											{
											echo "problem querying Items" . mysqli_error();
											}
										else
											{
											$bRowOdd = true;
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
												echo "			
												<tr class=\"$rowClass\">
													<td></td>
													<td  colspan=\"3\" valign=\"top\">".$rowItems['text']."</td>
												</tr>";
												$bRowOdd = !$bRowOdd;
												}
											}
										$questionNo = $questionNo + 1;
										}
					echo "		<tr>
									<td colspan=\"4\">
										<!-- These seem to need to be repeated - give them a different ID-->
										<input type=\"hidden\" value=\"$surveyID\" id=\"hSurveyID3\" name=\"hSurveyID\">
										<input type=\"hidden\" value=\"$blockID\" id=\"hBlockID3\" name=\"hBlockID\">
										<input type=\"hidden\" value=\"$sectionID\" id=\"hSectionID3\" name=\"hSectionID\">
										<input type=\"submit\" id=\"bReinstate\" name=\"bReinstate\" value=\"Reinstate\" onclick=\"return checkReinstateBoxes()\"/>
									</td>
								</tr>";
								}
							echo"
							</table>
						</td>
					<tr>
				</table>
			<form>
		</div>";
		}
	if(mysqli_num_rows($qResQuestions)>0)
		{
		echo "	<script language=\"JavaScript\" type=\"text/javascript\" >
			function checkReinstateBoxes()
				{
				var iNoOfQuestions = 0;
				var aQuestions = new Array();";				
		//resets the mysqli_fetch_array to start at the beginning again
		mysqli_data_seek($qResQuestions, 0);
		while($rowQuestions = mysqli_fetch_array($qResQuestions))
			{
			$questionID = $rowQuestions['questionID'];
			$questionTitle = $rowQuestions['text'];
		echo "	if (document.getElementById(\"reinstate_$questionID\").checked == true)
					{
					iNoOfQuestions=iNoOfQuestions+1;
					aQuestions[iNoOfQuestions] = \"$questionTitle\";
					}";
			}
		echo "	if (iNoOfQuestions == 0)
					{
					alert(\"Please select a question to reinstate.\");
					return false;
					}
				else
					{
					if (iNoOfQuestions == 1)
						{
						var confirmText = \"Are you sure you want to reinstate \" + aQuestions[iNoOfQuestions] + \".\";
						}
					else
						{
						var confirmText = \"Are you sure you want to reinstate these \" + iNoOfQuestions + \" questions?\";
						}
					var answer = confirm (confirmText);
					if (answer)
						{
						if (CheckSaved('false')=='false')
							{
							var confirmText2 = \"You have made changes to the section properties. Please save them before reinstating questions. Click 'Cancel' to go back and save your chnages or 'OK' to continue.\";
							var answer2 = confirm (confirmText2);
							if (answer2)
								{
								return true;
								}
							else
								{
								return false;
								}
							}
						else
							{
							return true;
							}
						}
					else
						{
						return false;
						}
					}
				}
		</script>";
		}
	}
	
//bit of javascript to make sure that right option for start and finish dates is chosen

echo " <script language=\"JavaScript\">";
echo " 		document.getElementById('$btnSubmitName').disabled = true;";
echo " 		document.getElementById('btnPreviewSurvey').disabled = false;";
echo "      setCookie(\"savedData\", \"true\");";
echo " </script>";
?>
<?php
	//Breadcrumb
	if(IsAuthor($heraldID))
		{
		require_once("includes/html/BreadCrumbsHeader.html"); 
		echo "
		<a href=\"admin.php\">Administration</a> &gt;
		<a href=\"editsurvey.php?surveyID=$surveyID\">Edit survey: ".limitString($surveyTitle,30)."</a> &gt; 
		<a href=\"editblock.php?surveyID=$surveyID&blockID=$blockID\">Edit block: ".limitString($blockTitle,30)."</a> &gt; 
		$pageTitleText: ".limitString($sectionTitle,30);
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html"); 
	require_once("includes/instructions/inst_editsection.php"); 

?>
</body>
</html>
