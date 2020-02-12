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
	if(isset($_POST['hQuestionID']))
		{
		$surveyID = $_POST['hSurveyID'];
		$blockID = $_POST['hBlockID'];
		$sectionID = $_POST['hSectionID'];
		$questionID = $_POST['hQuestionID'];
		$updateTitle = quote_smart($_POST['tTitle']);
		$updateComments = quote_smart($_POST['rComments']);
		$updateType = $_POST['sQuestionType'];
		$updateText = quote_smart($_POST['tText']);
		$updateLastModified = "CURDATE()";
		//**********************************************************
		//Server-side validation of data entered - 
		//**********************************************************
		/* Checking and ensure that the question title does not already exist in the database */
		$sql_title_check = mysqli_query("SELECT title FROM Questions
										WHERE title=$updateTitle" . ($questionID !="add" ? "AND questionID <> $questionID" : ""));
										//that last if statement allows edited question to have the same name as it had before
										//but prevents added questions having the same name as an existing question.
		$title_check = mysqli_num_rows($sql_title_check);
		if($title_check > 0 || $updateTitle == "" || $updateText=="")
			{
			$validationProblem = true;
			if($questionID =="add")
				{
				//set submit button title
				$btnSubmitName = "bCreate";
				$btnSubmitText = "Create question";
				$pageTitleText = "Create question";
				}
			else
				{
				$btnSubmitName = "bUpdate";
				$btnSubmitText = "Update question";
				$pageTitleText = "Edit question";
				}
			}
		//**********************************************************
		//End server-side validation of data entered 
		//**********************************************************
		else
			{
			//Validated - go ahead with updating/writing
			if($questionID =="add")
				{
				//Create new question with values from this form
				$iQuestions = "	INSERT INTO Questions
								VALUES(0,$updateTitle,$updateComments,$updateType,$updateText,$updateLastModified)";
				$result_query = @mysqli_query($db_connection, $iQuestions);
				$questionID = mysqli_insert_id();
				if (($result_query == false))
					{
					echo "problem inserting into Questions" . mysqli_error();
					$bSuccess = false;
					}
				//Now need to add it to SectionQuestions
				//first work out position to add it to
				$qMaxPosition = "	SELECT MAX(position) as maxPosition
									FROM SectionQuestions
									WHERE sectionID = $sectionID";
				$result_query = @mysqli_query($db_connection, $qMaxPosition);
				if (($result_query == false))
					{
					echo "problem querying qMaxPosition" . mysqli_error();
					}
				$rowMaxPosition = mysqli_fetch_array($result_query);
				$maxPosition = $rowMaxPosition[maxPosition];
				if ($maxPosition == "")
					{
					//there are no existing questions in this section
					$position = 0;
					}
				else
					{
					//there are existing questions in this section so increment
					$position = $maxPosition + 1;
					}
				$iSectionQuestions = "	INSERT INTO SectionQuestions
										VALUES(0,$sectionID,$questionID,$position,1)";
				$result_query = @mysqli_query($db_connection, $iSectionQuestions);
				if (($result_query == false))
					{
					echo "problem insering into SectionQuestions" . mysqli_error();
					$bSuccess = false;
					}
				}
			else
				{
				///Update section with values from this form
				$uQuestions = "	UPDATE Questions
								SET title = $updateTitle,
								comments = $updateComments,
								questionTypeID = $updateType,
								text = $updateText,
								lastModified = $updateLastModified
								WHERE questionID = $questionID";
				$result_query = @mysqli_query($db_connection, $uQuestions);
				if (($result_query == false))
					{
					echo "problem updating Questions" . mysqli_error();
					$bSuccess = false;
					}
				}
			//send the user back to schedulesurvey.php after they have added
			header("Location: https://" . $_SERVER['HTTP_HOST']
                     . dirname($_SERVER['PHP_SELF'])
                     . "/" . "editsection.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID");
			exit();
			}
		}
	}
//********************************************************
//deleting item(s)
//********************************************************
else if (isset($_POST['bDelete'])&&$_POST['bDelete']!="")
	{
	$surveyID = $_POST['hSurveyID'];
	$blockID = $_POST['hBlockID'];
	$sectionID = $_POST['hSectionID'];
	$questionID = $_POST['hQuestionID'];
	//Find out ids of all the checkboxes which were checked
	$aItems = $_POST['checkItemIDs'];
	for ($i=0;$i<count($aItems);$i++)
		{
		//find out if there are any results for this question
		if(AreThereAnyResultsForThisObject($surveyID, $blockID, $sectionID, $questionID, $aItems[$i])==true)
			{
			//hide the question
			$uQuestionItems = "	UPDATE QuestionItems
								SET visible = 0
								WHERE questionID = $questionID
								AND itemID = $aItems[$i]";
			$result_query = @mysqli_query($db_connection, $uQuestionItems);
			if (($result_query == false))
					{
					echo "problem updating QuestionItems" . mysqli_error();
					$bSuccess = false;
					}
			}
		else
			{
			//delete it from the block - but don't do anything to the question itself or any of its children - separate interface for that
			$dQuestionItems = "	DELETE FROM QuestionItems
								WHERE questionID = $questionID
								AND itemID = $aItems[$i]";
			$dResQuestionItems = @mysqli_query($db_connection, $dQuestionItems);
			if (($dResQuestionItems == false) && mysqli_affected_rows($db_connection) == 0)
				{
				echo "problem deleting from QuestionItems " . mysqli_error();
				}
			}
		}
	$btnSubmitName = "bUpdate";
	$btnSubmitText = "Update question";
	$pageTitleText = "Edit question";
	}
//********************************************************
//reinstating item(s)
//********************************************************
else if (isset($_POST['bReinstate'])&&$_POST['bReinstate']!="")
	{
	$surveyID = $_POST['hSurveyID'];
	$blockID = $_POST['hBlockID'];
	$sectionID = $_POST['hSectionID'];
	$questionID = $_POST['hQuestionID'];
	//Find out ids of all the checkboxes which were checked
	$aItems = $_POST['reinstateItemIDs'];
	for ($i=0;$i<count($aItems);$i++)
		{
		//show the question
		$uQuestionItems = "	UPDATE QuestionItems
							SET visible = 1
							WHERE questionID = $questionID
							AND itemID = $aItems[$i]";
		$result_query = @mysqli_query($db_connection, $uQuestionItems);
		if (($result_query == false))
			{
			echo "problem updating QuestionItems" . mysqli_error();
			$bSuccess = false;
			}
		}
	$btnSubmitName = "bUpdate";
	$btnSubmitText = "Update question";
	$pageTitleText = "Edit question";
	}
//********************************************************
//re-ordering item(s)
//********************************************************
else if (isset($_POST['hReOrder']) && $_POST['bReOrder'] != "")
	{
	$surveyID = $_POST['hSurveyID'];
	$blockID = $_POST['hBlockID'];
	$sectionID = $_POST['hSectionID'];
	$questionID = $_POST['hQuestionID'];
	//Get new order of section ID's
	$tItems = $_POST['hReOrder'];
	$aItems = explode(",",$tItems);
	for ($i=0;$i<count($aItems);$i++)
		{
		$uQuestionItems = "	UPDATE QuestionItems
							SET position = $i
							WHERE questionID = $questionID
							AND itemID = $aItems[$i]";
		$result_query = @mysqli_query($db_connection, $uQuestionItems);
		if (($result_query == false))
			{
			echo "problem updating QuestionItems" . mysqli_error();
			$bSuccess = false;
			}
		}
	$btnSubmitName = "bUpdate";
	$btnSubmitText = "Update question";
	$pageTitleText = "Edit question";
	}
else if (isset($_GET['surveyID'])&&isset($_GET['blockID'])&&isset($_GET['sectionID'])&&isset($_GET['questionID']))
	{
	$surveyID = $_GET['surveyID'];
	$blockID = $_GET['blockID'];
	$sectionID = $_GET['sectionID'];
	$questionID = $_GET['questionID'];
	if($_GET['questionID']=="add")
		{
		$btnSubmitName = "bCreate";
		$btnSubmitText = "Create question";
		$pageTitleText = "Create question";
		}
	else
		{
		$btnSubmitName = "bUpdate";
		$btnSubmitText = "Update question";
		$pageTitleText = "Edit question";
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
<link href="css/msdprint.css" rel="stylesheet" type="text/css" media="print"/>
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
function QuestionTypeHasChanged(list,btnSubmit)
	{
	//disables and enables controls in response to a change in questionType
	var textSelected = false;
	var dateSelected = false;
	for (var i=(list.options.length-1); i>=0; i--) 
		{ 
		var o=list.options[i]; 
		if (o.selected) 
			{ 
			if(o.value == 4 || o.value == 5)
				{
				textSelected = true;
				}
			else if (o.value == 6)
				{
				dateSelected = true;
				}
			} 
		}
	if(textSelected==true)
		{
		document.getElementById("rNoComments").disabled=true;
		document.getElementById("rComments").disabled=true;
		document.getElementById("divItems").style.display="none";
		}
	else if (dateSelected==true)
		{
		document.getElementById("rNoComments").disabled=false;
		document.getElementById("rComments").disabled=false;
		document.getElementById("divItems").style.display="none";
		}
	else
		{
		document.getElementById("rNoComments").disabled=false;
		document.getElementById("rComments").disabled=false;
		document.getElementById("divItems").style.display="block";
		}		
 	ControlHasChanged(btnSubmit);
	}

</script>
</head>

<body onBeforeUnload="return CheckSaved('You have made changes to the question properties. Please save them before moving away from this page')">
<?php
//Get info about survey
$qSurveys = "SELECT title
			FROM Surveys
			WHERE surveyID = $surveyID";
$qResSurvey = mysqli_query($qSurveys);
$rowSurvey = mysqli_fetch_array($qResSurvey);
$surveyTitle = $rowSurvey['title'];
//Get info about block
$qBlocks = "SELECT title
			FROM Blocks
			WHERE blockID = $blockID";
$qResBlock = mysqli_query($qBlocks);
$rowBlock = mysqli_fetch_array($qResBlock);
$blockTitle = $rowBlock['title'];
//Get info about section
$qSections = "	SELECT title
				FROM Sections
				WHERE sectionID = $sectionID";
$qResSections = mysqli_query($qSections);
$rowSection = mysqli_fetch_array($qResSections);
$sectionTitle = $rowSection['title'];
//get info about question
//Get info about section
if($questionID!="add")
	{
	$qQuestions = "	SELECT title, text, comments, questionTypeID, lastModified 
					FROM Questions
					WHERE questionID = $questionID";
	$qResQuestions = mysqli_query($qQuestions);
	$rowQuestion = mysqli_fetch_array($qResQuestions);
	$questionTitle = $rowQuestion[title];
	$questionText = $rowQuestion['text'];
	$questionComments = $rowQuestion['comments'];
	$questionType = $rowQuestion['questionTypeID'];
	$questionLastModified = $rowQuestion['lastModified'];
	}
elseif ($validationProblem == true)
	{
	$questionTitle = $_POST['tTitle'];
	$questionComments = $_POST['rComments'];
	$questionType = $_POST['sQuestionType'];
	$questionText = $_POST['tText'];
	}
else
	{
	$questionTitle = "New question";
	$questionComments = "false";
	$questionType = 1;
	$questionText = "New question";
	}
//get question types
$qQuestionTypes = "	SELECT questionTypeID, type
					FROM QuestionTypes";
$qResQuestionTypes = mysqli_query($qQuestionTypes);		
				
if(IsAuthor($heraldID))
	{
	require_once("includes/html/adminheadernew.html");
	require_once("includes/html/BreadCrumbsHeader.html"); 
	echo "
	<a href=\"admin.php\">Administration</a> &gt;
	<a href=\"editsurvey.php?surveyID=$surveyID\">Edit survey: ".limitString($surveyTitle,30)."</a> &gt; 
	<a href=\"editblock.php?surveyID=$surveyID&blockID=$blockID\">Edit block: ".limitString($blockTitle,30)."</a> &gt; 
	<a href=\"editsection.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID\">Edit section: ".limitString($sectionTitle,30)."</a> &gt; 
	$pageTitleText: ".limitString($questionTitle,30);
	require_once("includes/html/BreadCrumbsFooter.html"); 
	}
else
	{
	require_once("includes/html/header.html");
	echo "You do not have the necessary permissions to view this page";
	exit();
	}
echo "<a name=\"maintext\" id=\"maintext\"></a>
<h1>$pageTitleText: $questionTitle</h1>
<form id=\"frmEdit\" name=\"frmEdit\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" onSubmit=\"return ValidateForm(this)\">
<h2>Question properties:</h2>
<div class=\"questionNormal\">";
if($validationProblem==true)
	{
	echo "<span class=\"errorMessage\"><strong>Please fix the following errors:</strong><br/>";
	if($title_check > 0)
		{
		echo "This question name is already in use in our database. Please submit a different name (e.g. prefix with the name of your survey) and try again.";
		}
	if($updateTitle == "")
		{
		echo "You must enter a name for this question. Please enter a name and try again.";
		}
	if($updateText == "")
		{
		echo "You must enter some text for this question. Please enter some text and try again.";
		}
	echo "	</span><br/>";
	}
echo"
<table class=\"normal_3\" summary=\"\">
	<tr>
		<td>Name:</td>
		<td>
			<input type=\"text\" id=\"tTitle\" name=\"tTitle\" value=\"$questionTitle\" size=\"50\" onChange=\"ControlHasChanged('$btnSubmitName')\">
		</td>
	</tr>
	<tr>
		<td>Last modified:</td>
		<td>".ODBCDateToTextDateShort($questionLastModified)."</td>
	</tr>
	<tr>
		<td valign=\"top\">Question text:</td>
		<td>
			<textarea id=\"tText\" name=\"tText\" rows=\"5\" cols=\"35\" onChange=\"ControlHasChanged('$btnSubmitName')\">$questionText</textarea>
		</td>
	</tr>
	<tr>
		<td valign=\"top\">Question type:</td>
		<td>
			<select id=\"sQuestionType\" name=\"sQuestionType\" size=\"5\" onChange=\"QuestionTypeHasChanged(this,'$btnSubmitName')\">";
			while($rowQuestionTypes = mysqli_fetch_array($qResQuestionTypes))
				{
echo "			<option ";
				if($rowQuestionTypes['questionTypeID']==$questionType)
					{
					echo " selected ";
					}
					echo "value=\"$rowQuestionTypes['questionTypeID']\">$rowQuestionTypes[type]</option>";
				}
echo "		</select> 
		</td>
	</tr>
	<tr>
		<td valign=\"top\">Allow comments:</td>
		<td>
			<input type=\"radio\" id=\"rNoComments\" name=\"rComments\" value=\"false\" onclick=\"ControlHasChanged('$btnSubmitName')\"><label for=\"rNoComments\">No</label>
			<input type=\"radio\" id=\"rComments\" name=\"rComments\" value=\"true\" onclick=\"ControlHasChanged('$btnSubmitName')\"><label for=\"rComments\">Yes</label>
		</td>
	</tr>
	<tr>
		<td clospan=\"2\">
			<input type=\"hidden\" value=\"$surveyID\" id=\"hSurveyID\" name=\"hSurveyID\">
			<input type=\"hidden\" value=\"$blockID\" id=\"hBlockID\" name=\"hBlockID\">
			<input type=\"hidden\" value=\"$sectionID\" id=\"hSectionID\" name=\"hSectionID\">
			<input type=\"hidden\" value=\"$questionID\" id=\"hQuestionID\" name=\"hQuestionID\">
			<input type=\"submit\" value=\"$btnSubmitText\" id=\"$btnSubmitName\" name=\"$btnSubmitName\">
			<input type=\"button\" id=\"btnPreviewSurvey\" name=\"btnPreviewSurvey\" value=\"Preview\" onClick=\"window.open('previewsurvey.php?surveyID=$surveyID','previewWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes')\">
		</td>
	</tr>
		";
echo " </table>";
echo " </div>";

if($questionID !="add")
	{
	$qItems = "	SELECT Items.itemID, Items.text, QuestionItems.position
				FROM Items, QuestionItems
				WHERE QuestionItems.questionID = $questionID
				AND QuestionItems.visible = 1
				AND Items.itemID = QuestionItems.itemID
				ORDER BY QuestionItems.position";
	$qResItems = mysqli_query($qItems);
	if (($qResItems == false))
		{
		echo "problem querying Items" . mysqli_error();
		}
	else
		{
		echo "
		<h2>Items in this question:</h2>
		<div id=\"divItems\" class=\"questionNormal\">
			<form id=\"frmDelete\" name=\"frmDelete\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">
				<table summary=\"\" width=\"100%\">
					<tr>
						<td valign=\"top\">
							<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
								$bRowOdd = true;
								if(mysqli_num_rows($qResItems)==0)
									{
									echo "<p>This question does not contain any items.</p>";
									}
								else
									{
									while($rowItems = mysqli_fetch_array($qResItems))
										{
										$itemID = $rowItems[itemID];
										$itemTitle = $rowItems['text'];
										$qBranchesFromItem = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
																FROM Branches, BranchDestinations
																WHERE Branches.surveyID = $surveyID
																AND Branches.blockID = $blockID
																AND Branches.sectionID = $sectionID
																AND Branches.questionID = $questionID
																AND Branches.itemID = $itemID
																AND BranchDestinations.branchID = Branches.branchID";
										$qResBranchesFromItem = mysqli_query($qBranchesFromItem);
										if(mysqli_num_rows($qResBranchesFromItem)>0)
											{
											$itemIsInvolvedInBranching = true;
											}
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
											<td width = \"5px\">
												<input type=\"checkbox\" id=\"check_$itemID\" name=\"checkItemIDs[]\" value=\"$itemID\"/>
											</td>
											<td>".$rowItems['text']."</td>";
										if($itemIsInvolvedInBranching)
											{
											echo"<td width = \"5px\"><input type=\"button\" id=\"editBranch_$itemID\" name=\"editBranch_$itemID\" value=\"Edit branch(es)\" onClick=\"goTo('editbranch.php?&surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID&questionID=$questionID&itemID=$itemID&method=edit')\" /></td>";
											}
										else
											{
											echo"<td width = \"5px\"><input type=\"button\" id=\"addBranch_$itemID\" name=\"addBranch_$itemID\" value=\"Create branch(es)\" onClick=\"goTo('editbranch.php?&surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID&questionID=$questionID&itemID=$itemID&method=add')\" /></td>";
											}
											echo"<td width = \"5px\"><input type=\"button\" id=\"editItem_$itemID\" name=\"editItem_$itemID\" value=\"Edit item\" onClick=\"goTo('edititem.php?&surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID&questionID=$questionID&itemID=$itemID')\"".(IsSuperAuthor($heraldID, $blockID, $sectionID, $questionID, $itemID)==false ? "disabled" : "" )." /></td>
										</tr>";
										$bRowOdd = !$bRowOdd;
										}
									}
					echo "		<tr>
									<td colspan=\"3\">
										<input type=\"hidden\" value=\"$surveyID\" id=\"hSurveyID2\" name=\"hSurveyID\">
										<input type=\"hidden\" value=\"$blockID\" id=\"hBlockID2\" name=\"hBlockID\">
										<input type=\"hidden\" value=\"$sectionID\" id=\"hSectionID2\" name=\"hSectionID\">
										<input type=\"hidden\" value=\"$questionID\" id=\"hQuestionID2\" name=\"hQuestionID\">
										<input type=\"button\" id=\"bAdd\" name=\"bAdd\" value=\"Add New Item\" onClick=\"goTo('edititem.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID&questionID=$questionID&itemID=add')\"/>
										<input type=\"button\" id=\"bAddExisting\" name=\"bAddExisting\" value=\"Add Existing Item\" onClick=\"goTo('addexisting.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID&questionID=$questionID')\"/>
										<input type=\"button\" id=\"bUpload\" name=\"bUpload\" value=\"Upload Items\" onClick=\"goTo('edititem.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID&questionID=$questionID&itemID=upload')\"/>";
									if(mysqli_num_rows($qResItems)>0)
										{
										echo "&nbsp;<input type=\"submit\" id=\"bDelete\" name=\"bDelete\" value=\"Delete\" onclick=\"return checkCheckBoxes()\"/>";
										}
									echo "
									</td>
								</tr>
							</table>
						</td>";
					if(mysqli_num_rows($qResItems)>1)
						{
						echo "	
						<td width=\"10%\" valign=\"top\">
							<p>To re-order, select item(s) from the list and reposition using the <strong>Move Up</strong> and
							<strong>Move Down</strong> buttons. Click <strong>Re-order</strong> to apply your changes.
							<table border=\"0\">
								<tr>
									<td>
										<select name=\"sUpDown\" size=\"7\" multiple>";
										mysqli_data_seek($qResItems, 0);
										while($rowItems = mysqli_fetch_array($qResItems))
											{
											$itemID = $rowItems['itemID'];
											$itemTitle = $rowItems['text'];
											echo "<option value=\"$itemID\">".($itemTitle==""?"Item":limitString($itemTitle,30))."";
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
					echo"						
					<tr>
				</table>
			<form>
		</div>";
		}
	if(mysqli_num_rows($qResItems)>0)
		{
		echo "	<script language=\"JavaScript\" type=\"text/javascript\" >
			function checkCheckBoxes()
				{
				var iNoOfItems = 0;
				var aItems = new Array();";				
		//resets the mysql_fetch_array to start at the beginning again
		mysqli_data_seek($qResItems, 0);
		while($rowItems = mysqli_fetch_array($qResItems))
			{
			$itemID = $rowItems['itemID'];
			$itemTitle = $rowItems['text'];
		echo "	if (document.getElementById(\"check_$itemID\").checked == true)
					{
					iNoOfItems=iNoOfItems+1;
					aItems[iNoOfItems] = \"$itemTitle\";
					}";
			}
		echo "	if (iNoOfItems == 0)
					{
					alert(\"Please select an item to delete.\");
					return false;
					}
				else
					{
					if (iNoOfItems == 1)
						{
						var confirmText = \"Are you sure you want to delete \" + aItems[iNoOfItems] + \".\";
						}
					else
						{
						var confirmText = \"Are you sure you want to delete these \" + iNoOfItems + \" items?\";
						}
					var answer = confirm (confirmText);
					if (answer)
						{
						if (CheckSaved('false')=='false')
							{
							var confirmText2 = \"You have made changes to the question properties. Please save them before deleting question items. Click 'Cancel' to go back and save your chnages or 'OK' to continue.\";
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
	$qHiddenItems = "	SELECT Items.itemID, Items.text, QuestionItems.position
				FROM Items, QuestionItems
				WHERE QuestionItems.questionID = $questionID
				AND QuestionItems.visible = 0
				AND Items.itemID = QuestionItems.itemID
				ORDER BY QuestionItems.position";
	$qResItems = mysqli_query($qHiddenItems);
	if (($qResItems == false))
		{
		echo "problem querying Items" . mysqli_error();
		}
	else
		{
		echo "
		<h2>Hidden items in this question:</h2>
		<div id=\"divItems\" class=\"questionNormal\">
			<form id=\"frmDelete\" name=\"frmDelete\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">
				<table summary=\"\">
					<tr>
						<td valign=\"top\">
							<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
								$bRowOdd = true;
								if(mysqli_num_rows($qResItems)==0)
									{
									echo "<p>This question does not contain any hidden items.</p>";
									}
								else
									{
									while($rowItems = mysqli_fetch_array($qResItems))
										{
										$itemID = $rowItems['itemID'];
										$itemTitle = $rowItems['text'];
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
											<td width = \"5px\">
												<input type=\"checkbox\" id=\"reinstate_$itemID\" name=\"reinstateItemIDs[]\" value=\"$itemID\"/>
											</td>
											<td>".$rowItems['text']."</td>
											<td width = \"5px\">&nbsp;</td>
										</tr>";
										$bRowOdd = !$bRowOdd;
										}
					echo "		<tr>
									<td colspan=\"3\">
										<input type=\"hidden\" value=\"$surveyID\" id=\"hSurveyID2\" name=\"hSurveyID\">
										<input type=\"hidden\" value=\"$blockID\" id=\"hBlockID2\" name=\"hBlockID\">
										<input type=\"hidden\" value=\"$sectionID\" id=\"hSectionID2\" name=\"hSectionID\">
										<input type=\"hidden\" value=\"$questionID\" id=\"hQuestionID2\" name=\"hQuestionID\">
										<input type=\"submit\" id=\"bReinstate\" name=\"bReinstate\" value=\"Reinstate\" onclick=\"return checkReinstateBoxes()\"/>
									</td>
								</tr>";
								}
					echo " </table>
						</td>
					<tr>
				</table>
			<form>
		</div>";
		}
	if(mysqli_num_rows($qResItems)>0)
		{
		echo "	<script language=\"JavaScript\" type=\"text/javascript\" >
			function checkReinstateBoxes()
				{
				var iNoOfItems = 0;
				var aItems = new Array();";				
		//resets the mysql_fetch_array to start at the beginning again
		mysqli_data_seek($qResItems, 0);
		while($rowItems = mysqli_fetch_array($qResItems))
			{
			$itemID = $rowItems['itemID'];
			$itemTitle = $rowItems['text'];
		echo "	if (document.getElementById(\"reinstate_$itemID\").checked == true)
					{
					iNoOfItems=iNoOfItems+1;
					aItems[iNoOfItems] = \"$itemTitle\";
					}";
			}
		echo "	if (iNoOfItems == 0)
					{
					alert(\"Please select an item to reinstate.\");
					return false;
					}
				else
					{
					if (iNoOfItems == 1)
						{
						var confirmText = \"Are you sure you want to reinstate \" + aItems[iNoOfItems] + \".\";
						}
					else
						{
						var confirmText = \"Are you sure you want to reinstate these \" + iNoOfItems + \" items?\";
						}
					var answer = confirm (confirmText);
					if (answer)
						{
						if (CheckSaved('false')=='false')
							{
							var confirmText2 = \"You have made changes to the question properties. Please save them before reinstating question items. Click 'Cancel' to go back and save your chnages or 'OK' to continue.\";
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
else
	{
	//this bit of code is really only there so that when QuestionTypeHasChanged
	//is called, divItems exists even if we are just adding a new question.
	echo "<div id=\"divItems\"></div>";
	}

//bit of javascript to make sure that right option for start and finish dates is chosen

echo " <script language=\"JavaScript\">";
	if($questionComments=="true")
		{
echo " 	document.getElementById('rComments').click();";
		}
		else
		{
echo " 	document.getElementById('rNoComments').click();";
		}
echo "		QuestionTypeHasChanged(document.getElementById('sQuestionType'),'$btnSubmitName');";
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
		<a href=\"editsection.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID\">Edit section: ".limitString($sectionTitle,30)."</a> &gt; 
		$pageTitleText: ".limitString($questionTitle,30);
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html"); 
	require_once("includes/instructions/inst_editquestion.php");
?>
</body>
</html>
