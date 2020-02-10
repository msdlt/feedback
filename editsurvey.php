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
//********************************************************
//updating or creating surveys
//********************************************************
if ((isset($_POST['bUpdate'])&& $_POST['bUpdate']!= "")||(isset($_POST['bCreate'])&& $_POST['bCreate']!= ""))
	{
	//from self - updating changed values
	if(isset($_POST['hSurveyID']))
		{
		$surveyID = $_POST['hSurveyID'];
		$updateTitle = quote_smart($_POST['tTitle']);
		$updateIntroduction = quote_smart($_POST['tIntroduction']);
		$updateEpilogue = quote_smart($_POST['tEpilogue']);
		$updateAllowSave = quote_smart($_POST['rAllowSave']);
		$updateAllowViewByStudent = quote_smart($_POST['rAllowViewByStudent']);
		$updateLastModified = "CURDATE()";
		//**********************************************************
		//Server-side validation of data entered - 
		//**********************************************************
		/* Checking and ensure that the survey title does not already exist in the database */
		$sql_title_check = mysqli_query($db_connection, "SELECT title FROM Surveys
										WHERE title=$updateTitle" . ($surveyID !="add" ? "AND surveyID <> $surveyID" : ""));
										//that last if statement allows edited survey to have the same name as it had before
										//but prevents added surveys having the same name as an existing survey.
		$title_check = mysqli_num_rows($sql_title_check);
		if($title_check > 0 || $updateTitle == "")
			{
			$validationProblem = true;
			if($surveyID =="add")
				{
				//set submit button title
				$btnSubmitName = "bCreate";
				$btnSubmitText = "Create survey";
				$pageTitleText = "Create survey";
				}
			else
				{
				$btnSubmitName = "bUpdate";
				$btnSubmitText = "Update survey";
				$pageTitleText = "Edit survey";
				}
			}
		//**********************************************************
		//End server-side validation of data entered 
		//**********************************************************
		else
			{
			//Validated - go ahead with updating/writing
			if($surveyID =="add")
				{
				//Create new survey with values from this form
				$iSurveys = "INSERT INTO Surveys
						VALUES(0,$updateTitle,$updateIntroduction,$updateEpilogue,$updateLastModified,$updateAllowSave,$updateAllowViewByStudent)";
				$result_query = @mysqli_query($db_connection, $iSurveys);
				$surveyID = mysqli_insert_id();
				if (($result_query == false))
					{
					echo "problem insering into Surveys" . mysqli_error();
					$bSuccess = false;
					}
				}
			else
				{				
				//Update survey with values from this form
				$uSurveys = "UPDATE Surveys
						SET title = $updateTitle,
						introduction = $updateIntroduction,
						epilogue = $updateEpilogue,
						lastModified = $updateLastModified,
						allowSave = $updateAllowSave,
						allowViewByStudent = $updateAllowViewByStudent
						WHERE surveyID = $surveyID";
				$result_query = @mysqli_query($db_connection, $uSurveys);
				if (($result_query == false))
					{
					echo "problem updating Surveys" . mysqli_error();
					$bSuccess = false;
					}
				}
			//now check to see if authors have been changed
			$qAuthors = "SELECT Authors.heraldID, Authors.authorID 
				FROM Authors, SurveyAuthors
				WHERE SurveyAuthors.surveyID = $surveyID
				AND Authors.authorID = SurveyAuthors.authorID";
			$qResAuthors = mysqli_query($db_connection, $qAuthors);
			if (($qResAuthors == false))
				{
				echo "problem querying Authors" . mysqli_error();
				}
			else
				{
				$aExistingAuthors = array();
				$i = 1;
				while($rowAuthors = mysqli_fetch_array($qResAuthors))
					{
					$aExistingAuthors[$i] = $rowAuthors['heraldID'];
					$i++;
					}
				}
			$tUpdateAuthors = $_POST['hAuthors'];
			$aUpdateAuthors = explode(",",$tUpdateAuthors);
			//check if any authors in aUpdateAuthors are not in aExistingAuthors - i.e they have been added
			$aAdded = array_diff($aUpdateAuthors,$aExistingAuthors);
			//array_diff returns an arraywhich does not necessarily start from 0 - merge sorts it
			$aAdded = array_merge($aAdded);
			for($i=0;$i<count($aAdded);$i++)
				{
				//if so, check if they are in Authors and have just not been added to SurveyAuthors
				$qIsAuthor = "	SELECT authorID 
								FROM Authors
								WHERE heraldID = '$aAdded[$i]'";
				$qResIsAuthor = mysqli_query($db_connection, $qIsAuthor);
				if (mysqli_num_rows($qResIsAuthor)==0)
					{
					//if not in Authors then add them
					$iAuthor = "	INSERT INTO Authors
									VALUES(NULL,'$aAdded[$i]')";
					$iResAuthor = @mysqli_query($db_connection, $iAuthor);
					if (($iResAuthor == false) && mysqli_affected_rows($db_connection) == 0)
						{
						echo "problem inserting into Authors" . mysqli_error();
						}
					else
						{
						//get authorID from INSERT
						$iAuthorID = mysqli_insert_id();
						}
					}
				else
					{
					$rowIsAuthor = mysqli_fetch_array($qResIsAuthor);
					//get authorID from original query
					$iAuthorID = $rowIsAuthor[authorID];
					}
				//then add to SurveyAuthors
				$iSurveyAuthor = "	INSERT INTO SurveyAuthors
									VALUES(NULL, $surveyID, $iAuthorID)";
				$iResSurveyAuthor = @mysqli_query($db_connection, $iSurveyAuthor);
				if (($iResSurveyAuthor == false) && mysqli_affected_rows($db_connection) == 0)
					{
					echo "problem inserting into SurveyAuthors" . mysqli_error();
					}
				}
			//check if any authors in aExistingAuthors are not in aUpdateAuthors - i.e. they have been removed
			$aRemoved = array_diff($aExistingAuthors, $aUpdateAuthors);
			$aRemoved = array_merge($aRemoved);
			for($i=0;$i<count($aRemoved);$i++)
				{
				//if so, first get AuthorID
				$qAuthorID = "	SELECT authorID 
								FROM Authors
								WHERE heraldID = '$aRemoved[$i]'";
				$qResAuthorID = mysqli_query($db_connection, $qAuthorID);
				$rowAuthorID = mysqli_fetch_array($qResAuthorID);
				$iRemoveAuthorID = $rowAuthorID[authorID];
				//if so, check if this author is also an author for another survey
				$qIsOtherAuthor = "	SELECT surveyAuthorID 
									FROM SurveyAuthors
									WHERE authorID = $iRemoveAuthorID
									AND surveyID <> $surveyID";
				$qResIsOtherAuthor = mysqli_query($db_connection, $qIsOtherAuthor);
				if (mysqli_num_rows($qResIsOtherAuthor)==0)
					{
					//if not, remove from Authors
					$dAuthor = "DELETE FROM Authors
								WHERE authorID = $iRemoveAuthorID";
					$dResAuthor = @mysqli_query($db_connection, $dAuthor);
					if (($dResAuthor == false) && mysqli_affected_rows($db_connection) == 0)
						{
						echo "problem deleting from Authors " . mysqli_error();
						}
					}
				//remove from SurveyAuthors
				$dSurveyAuthor = "	DELETE FROM SurveyAuthors
									WHERE authorID = $iRemoveAuthorID
									AND surveyID = $surveyID";
				$dResSurveyAuthor = @mysqli_query($db_connection, $dSurveyAuthor);
				if (($dResSurveyAuthor == false) && mysqli_affected_rows($db_connection) == 0)
					{
					echo "problem deleting from SurveyAuthors " . mysqli_error();
					}
				}
			//send the user back to schedulesurvey.php after they have added
			header("Location: https://" . $_SERVER['HTTP_HOST']
                     . dirname($_SERVER['PHP_SELF'])
                     . "/" . "admin.php");
			exit();
			}
		}
	}
//********************************************************
//deleting block(s)
//********************************************************
else if (isset($_POST['bDelete'])&& $_POST['bDelete']!= "")
	{
	$surveyID = $_POST['hSurveyID'];
	//Find out ids of all the checkboxes which were checked
	$aBlocks = $_POST['checkBlockIDs'];
	for ($i=0;$i<count($aBlocks);$i++)
		{
		//find out if there are any results for this block
		if(AreThereAnyResultsForThisObject($surveyID, $aBlocks[$i])==true)
			{
			//hide the block
			$uSurveyBlocks = "	UPDATE SurveyBlocks
								SET visible = 0
								WHERE surveyID = $surveyID
								AND blockID = $aBlocks[$i]";
			$result_query = @mysqli_query($db_connection, $uSurveyBlocks);
			if (($result_query == false))
					{
					echo "problem updating SurveyBlocks" . mysqli_error();
					$bSuccess = false;
					}
			}
		else
			{
			//delete it from the survey - but don't do anything to the Block itself or any of its children - separate interface for that
			$dSurveyBlocks = "DELETE FROM SurveyBlocks
						WHERE surveyID = $surveyID
						AND blockID = $aBlocks[$i]";
			$dResSurveyBlocks = @mysqli_query($db_connection, $dSurveyBlocks);
			if (($dResSurveyBlocks == false) && mysqli_affected_rows($db_connection) == 0)
				{
				echo "problem deleting from SurveyBlocks " . mysqli_error();
				}
			}
		}
	$btnSubmitName = "bUpdate";
	$btnSubmitText = "Update survey";
	$pageTitleText = "Edit survey";
	}
//********************************************************
//reinstating block(s)
//********************************************************
else if (isset($_POST['bReinstate'])&& $_POST['bReinstate']!= "")
	{
	$surveyID = $_POST['hSurveyID'];
	//Find out ids of all the checkboxes which were checked
	$aBlocks = $_POST['reinstateBlockIDs'];
	for ($i=0;$i<count($aBlocks);$i++)
		{
		//reinstate the block
		$uSurveyBlocks = "	UPDATE SurveyBlocks
							SET visible = 1
							WHERE surveyID = $surveyID
							AND blockID = $aBlocks[$i]";
		$result_query = @mysqli_query($db_connection, $uSurveyBlocks);
		if (($result_query == false))
			{
			echo "problem updating SurveyBlocks" . mysqli_error();
			$bSuccess = false;
			}
		}
	$btnSubmitName = "bUpdate";
	$btnSubmitText = "Update survey";
	$pageTitleText = "Edit survey";
	}
//********************************************************
//re-ordering blocks block(s)
//********************************************************
else if (isset($_POST['hReOrder']) && $_POST['bReOrder'] != "")
	{
	$surveyID = $_POST['hSurveyID'];
	//Get new order of block ID's
	$tBlocks = $_POST['hReOrder'];
	$aBlocks = explode(",",$tBlocks);
	for ($i=0;$i<count($aBlocks);$i++)
		{
		$uSurveyBlocks = "	UPDATE SurveyBlocks
							SET position = $i
							WHERE surveyID = $surveyID
							AND blockID = $aBlocks[$i]";
		$result_query = @mysqli_query($db_connection, $uSurveyBlocks);
		if (($result_query == false))
			{
			echo "problem updating SurveyBlocks" . mysqli_error();
			$bSuccess = false;
			}
		}
	$btnSubmitName = "bUpdate";
	$btnSubmitText = "Update survey";
	$pageTitleText = "Edit survey";
	}
//********************************************************
//simply showing survey properties
//********************************************************
else if(isset($_GET['surveyID']))
	{
	$surveyID = $_GET['surveyID'];
	//check if editing a survey or adding a new one
	if($_GET['surveyID']=="add")
		{
		$btnSubmitName = "bCreate";
		$btnSubmitText = "Create survey";
		$pageTitleText = "Create survey";
		}
	else
		{
		$btnSubmitName = "bUpdate";
		$btnSubmitText = "Update survey";
		$pageTitleText = "Edit survey";
		}
	}
else
	{
echo "<h1>Warning</h1>
	<p>This page requires data from another form.</p>";
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
//add a new item to a list
function getAndAddItem(list, promptText, alreadyThereText, btnSubmit)
	{
	var newItem = prompt(promptText);
	var alreadyThere = false;
	if (newItem != "" && newItem != null)
		{
		for (i=0;i<list.options.length;i++)
			{
			if (list.options[i].text==newItem)
				{
				alreadyThere = true;
				}
			}
		if (alreadyThere == true)
			{
			alert(alreadyThereText);
			}
		else
			{
			//call functions in OptionTransfer.js
			addOption(list,newItem,list.options.length,false);
			sortSelect(list);
			ControlHasChanged(btnSubmit);
			}
		}
	}
function removeSelectedItems(list, btnSubmit)
	{
	if (!hasOptions(list)) { return; }
	for (var i=(list.options.length-1); i>=0; i--) 
		{ 
		var o=list.options[i]; 
		if (o.selected) 
			{ 
			if(o.text == <?php require_once("includes/getuser.php"); echo "\"$heraldID\""; ?>)
				{
				alert("You cannot remove yourself from the list of authors");
				return false;
				} 
			} 
		} 
	removeSelectedOptions(list)
	ControlHasChanged(btnSubmit);
	}
function goTo(URL)
		{
		window.location.href = URL;
		}
	//Validation of input (called by frm1 OnSubmit())
function ValidateForm(theForm)
	{
	if(!validText(document.getElementById("tTitle"),"survey title",true))
		{
		return false;
		}
	if (cookieStatus()=="on") setCookie("savedData", "true");
	dumpList(document.getElementById('lAuthors'),document.getElementById('hAuthors'),1); 
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

<body onBeforeUnload="return CheckSaved('You have made changes to the survey properties. Please save them before moving away from this page')">
<?php
//Get info about survey
if($surveyID!="add")
	{
	$qSurveys = "SELECT title, introduction, epilogue, lastModified, allowSave, allowViewByStudent
					FROM Surveys
					WHERE surveyID = $surveyID";
	$qResSurvey = mysqli_query($db_connection, $qSurveys);
	$rowSurvey = mysqli_fetch_array($qResSurvey);
	$surveyTitle = $rowSurvey['title'];
	$surveyIntroduction = $rowSurvey['introduction'];
	$surveyEpilogue = $rowSurvey['epilogue'];
	$surveyAllowSave = $rowSurvey['allowSave'];
	$surveyAllowViewByStudent = $rowSurvey['allowViewByStudent'];
	$surveyLastModified = $rowSurvey['lastModified'];
	//Get info about authors
	$qAuthors = "SELECT Authors.heraldID, Authors.authorID 
				FROM Authors, SurveyAuthors
				WHERE SurveyAuthors.surveyID = $surveyID
				AND Authors.authorID = SurveyAuthors.authorID";
	$qResAuthors = mysqli_query($db_connection, $qAuthors);
	}
elseif ($validationProblem == true)
	{
	$surveyTitle = $_POST['tTitle'];
	$surveyIntroduction = $_POST['tIntroduction']; 
	$surveyEpilogue = $_POST['tEpilogue'];
	$surveyAllowSave = $_POST['rAllowSave'];
	$surveyAllowViewByStudent = $_POST['rAllowViewByStudent'];
	}
else
	{
	$surveyTitle = "New survey";
	$surveyIntroduction = "Introduction";
	$surveyEpilogue = "Epilogue";
	$surveyAllowSave = "false";
	$surveyAllowViewByStudent = "false";
	}

if(IsAuthor($heraldID))
	{
	require_once("includes/html/adminheadernew.html");
	require_once("includes/html/BreadCrumbsHeader.html"); 
	echo "<a href=\"admin.php\">Administration</a> &gt; $pageTitleText: ".limitString($surveyTitle,30); 	
	require_once("includes/html/BreadCrumbsFooter.html"); 
	}
else
	{
	require_once("includes/html/header.html"); 
	echo "You do not have the necessary permissions to view this page";
	exit();
	}
echo "<a name=\"maintext\" id=\"maintext\"></a>";
echo "<h1>$pageTitleText: $surveyTitle</h1>
<form id=\"frmEdit\" name=\"frmEdit\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" onSubmit=\"return ValidateForm(this)\">
<h2>Survey properties:</h2>
<div class=\"questionNormal\">";
if($validationProblem==true)
	{
	echo "<span class=\"errorMessage\"><strong>Please fix the following errors:</strong><br/>";
	if($title_check > 0)
		{
		echo "This survey name is already in use in our database. Please enter a different name and try again.<br/>";
		}
	if($updateTitle == "")
		{
		echo "You must enter a name for this survey. Please enter a name and try again.<br/>";
		}
	echo "	</span><br/>";
	}
echo "
	<table class=\"normal_3\" summary=\"\">
	<tr>
		<td>Name:</td>
		<td>
			<input type=\"text\" id=\"tTitle\" name=\"tTitle\" value=\"$surveyTitle\" size=\"50\" onChange=\"ControlHasChanged('$btnSubmitName')\">
		</td>
	</tr>
	<tr>
		<td>Last modified:</td>
		<td>".ODBCDateToTextDateShort($surveyLastModified)."</td>
	</tr>
	<tr>
		<td valign=\"top\">Introduction:</td>
		<td>
			<textarea id=\"tIntroduction\" name=\"tIntroduction\" rows=\"5\" cols=\"35\" onChange=\"ControlHasChanged('$btnSubmitName')\">$surveyIntroduction</textarea>
		</td>
	</tr>
	<tr>
		<td valign=\"top\">Epilogue:</td>
		<td>
			<textarea id=\"tEpilogue\" name=\"tEpilogue\" rows=\"5\" cols=\"35\" onChange=\"ControlHasChanged('$btnSubmitName')\">$surveyEpilogue</textarea>
		</td>
	</tr>";
	
echo "<tr>
		<td valign=\"top\">Authors:</td>
		<td>
			<select id=\"lAuthors\" name=\"lAuthors\" multiple size=\"5\">";
if($surveyID!="add")
	{
	if (($qResAuthors == false))
		{
		echo "problem querying Authors" . mysqli_error();
		}
	else
		{
		while($rowAuthors = mysqli_fetch_array($qResAuthors))
			{
	echo "			<option value=\"".$rowAuthors[authorID]."\">" .$rowAuthors[heraldID]. "	</option>";
			}
		}
	}
else
	{
	echo "			<option value=\"0\">" .$heraldID. "	</option>";
	}
echo "		</select>
			<input type=\"button\" name=\"bAddAuthor\" id=\"bAddAuthor\" value=\"Add\" onClick=\"getAndAddItem(document.frmEdit.lAuthors,'Please enter the Herald ID of the author', 'That Herald ID is already an author for this survey','$btnSubmitName')\">";
echo "		<input type=\"button\" name=\"bRemoveAuthor\" id=\"bRemoveAuthor\" value=\"Remove\" onClick=\"removeSelectedItems(document.frmEdit.lAuthors,'$btnSubmitName')\">";
echo "	</td>
	</tr>
	<tr>
		<td valign=\"top\">Allow save before submit:</td>
		<td>
			<input type=\"radio\" id=\"rNoAllowSave\" name=\"rAllowSave\" value=\"false\" onclick=\"ControlHasChanged('$btnSubmitName')\"";
			if($surveyAllowSave=="false")
				{
				echo "checked";
				}
			echo "><label for=\"rNoAllowSave\">No</label>
			<input type=\"radio\" id=\"rAllowSave\" name=\"rAllowSave\" value=\"true\" onclick=\"ControlHasChanged('$btnSubmitName')\"";
			if($surveyAllowSave=="true")
				{
				echo "checked";
				}
			echo "><label for=\"rAllowSave\">Yes</label>
		</td>
	</tr>
	<tr>
		<td valign=\"top\">Allow administrators to view an individual student's feedback?:</td>
		<td>
			<input type=\"radio\" id=\"rNoAllowViewByStudent\" name=\"rAllowViewByStudent\" value=\"false\" onclick=\"ControlHasChanged('$btnSubmitName')\"";
			if($surveyAllowViewByStudent=="false")
				{
				echo "checked";
				}
			echo "><label for=\"rNoAllowViewByStudent\">No</label>
			<input type=\"radio\" id=\"rAllowViewByStudent\" name=\"rAllowViewByStudent\" value=\"true\" onclick=\"ControlHasChanged('$btnSubmitName')\"";
			if($surveyAllowViewByStudent=="true")
				{
				echo "checked";
				}
			echo "><label for=\"rAllowViewByStudent\">Yes</label>
		</td>
	</tr><tr>
		<td clospan=\"2\">";
		if($surveyID!="add")
			{
			echo "<input type=\"hidden\" value=\"\" id=\"hAuthors\" name=\"hAuthors\">";
			}
		else
			{
			echo "<input type=\"hidden\" value=\"$heraldID\" id=\"hAuthors\" name=\"hAuthors\">";
			}
			echo "<input type=\"hidden\" value=\"$surveyID\" id=\"hSurveyID\" name=\"hSurveyID\">
			<input type=\"submit\" value=\"$btnSubmitText\" id=\"$btnSubmitName\" name=\"$btnSubmitName\">
			<input type=\"button\" id=\"btnPreviewSurvey\" name=\"btnPreviewSurvey\" value=\"Preview\" onClick=\"window.open('previewsurvey.php?surveyID=$surveyID','previewWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes')\">
		</td>
	</tr>
		";
echo " </table>";
echo " </div>";
echo " </form>";
if($surveyID !="add")
	{
	//only show blocks if we're not adding a new survey
	$qBlocks = "SELECT Blocks.blockID, Blocks.title, Blocks.introduction, Blocks.epilogue, SurveyBlocks.position, SurveyBlocks.visible, Blocks.instanceable, Blocks.lastModified 
				FROM Blocks, SurveyBlocks 
				WHERE SurveyBlocks.surveyID = $surveyID
				AND SurveyBlocks.visible = 1
				AND Blocks.blockID = SurveyBlocks.blockID
				ORDER BY SurveyBlocks.position";
				
	$qResBlocks = mysqli_query($db_connection, $qBlocks);
	if (($qResBlocks == false))
		{
		echo "problem querying Blocks" . mysqli_error();
		}
	else
		{
		echo "	
		<h2>Blocks in this survey:</h2>
		<div class=\"questionNormal\">
			<form id=\"frmDelete\" name=\"frmDelete\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">
				<table summary=\"\" width=\"100%\">
					<tr>
						<td valign=\"top\" width=\"90%\">
							<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
								if(mysqli_num_rows($qResBlocks)==0)
									{
									echo "<p>This survey does not contain any blocks.</p>";
									}
								else
									{
									while($rowBlocks = mysqli_fetch_array($qResBlocks))
										{
										$blockID = $rowBlocks['blockID'];
								echo "	<tr class=\"matrixHeader\">
											<td>
												<input type=\"checkbox\" id=\"check_$blockID\" name=\"checkBlockIDs[]\" value=\"$blockID\"/>
											</td>
											<td class=\"question\">Block: ".$rowBlocks[title]."</td>
											<td>Last modified: ".ODBCDateToTextDateShort($rowBlocks['lastModified'])."</td>
											<td><input type=\"button\" id=\"editBlock_$blockID\" name=\"editBlock_$blockID\" value=\"Edit block\" onClick=\"goTo('editblock.php?surveyID=$surveyID&blockID=$blockID')\"".(IsSuperAuthor($heraldID, $blockID)==false ? "disabled" : "" )."/></td>
										</tr>";
										//get all sections		
										$qSections = "	SELECT Sections.sectionID, Sections.title, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, Sections.lastModified 
														FROM Sections, BlockSections 
														WHERE BlockSections.blockID = $blockID
														AND BlockSections.visible = 1
														AND Sections.sectionID = BlockSections.sectionID
														ORDER BY BlockSections.position";
										
										$qResSections = mysqli_query($db_connection, $qSections);
										
										if (($qResSections == false))
											{
											echo "problem querying Sections" . mysqli_error();
											}
										else
											{
											$bRowOdd = true;
											while($rowSections = mysqli_fetch_array($qResSections))
												{
												if($bRowOdd)
													{
													$rowClass = "matrixRowOdd";
													}
												else
													{
													$rowClass = "matrixRowEven";
													}
												//show section titles
								echo "			<tr class=\"$rowClass\">
													<td></td>
													<td  colspan=\"3\" valign=\"top\">Section: ".$rowSections[title]."</td>
												</tr>";
												$bRowOdd = !$bRowOdd;
												}
											}
										}
									}
							echo "
								<tr>
									<td colspan=\"4\">
										<input type=\"hidden\" value=\"$surveyID\" id=\"hSurveyID2\" name=\"hSurveyID\">
										<input type=\"button\" id=\"bAdd\" name=\"bAdd\" value=\"Add New Block\" onClick=\"goTo('editblock.php?surveyID=$surveyID&blockID=add')\"/>
										<input type=\"button\" id=\"bAddExisting\" name=\"bAddExisting\" value=\"Add Existing Block\" onClick=\"goTo('addexisting.php?surveyID=$surveyID')\"/>";
										if(mysqli_num_rows($qResBlocks)>0)
											{
											echo "&nbsp;<input type=\"submit\" id=\"bDelete\" name=\"bDelete\" value=\"Delete\" onclick=\"return checkCheckBoxes()\"/>";
											}
								echo "</td>
								</tr>
							</table>
						</td>";
					if(mysqli_num_rows($qResBlocks)>1)
						{
						echo"
						<td width=\"10%\" valign=\"top\">
							<p>To re-order, select block(s) from the list and reposition using the <strong>Move Up</strong> and
							<strong>Move Down</strong> buttons. Click <strong>Re-order</strong> to apply your changes.
							<table border=\"0\">
								<tr>
									<td>
										<select name=\"sUpDown\" size=\"7\" multiple>";
										mysqli_data_seek($qResBlocks, 0);
										while($rowBlocks = mysqli_fetch_array($qResBlocks))
											{
											$blockID = $rowBlocks['blockID'];
											$blockTitle = $rowBlocks[title];
											echo "<option value=\"$blockID\">".($blockTitle==""?"Block":limitString($blockTitle,30))."";
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
			</form>
		</div>";
		}
	if(mysqli_num_rows($qResBlocks)>0)
		{
		echo "	<script language=\"JavaScript\" type=\"text/javascript\" >
			function checkCheckBoxes()
				{
				var iNoOfBlocks = 0;
				var aBlocks = new Array();";				
		//resets the mysql_fetch_array to start at the beginning again
		mysqli_data_seek($qResBlocks, 0);
		while($rowBlocks = mysqli_fetch_array($qResBlocks))
			{
			$blockID = $rowBlocks['blockID'];
			$blockTitle = $rowBlocks[title];
		echo "	if (document.getElementById(\"check_$blockID\").checked == true)
					{
					iNoOfBlocks=iNoOfBlocks+1;
					aBlocks[iNoOfBlocks] = \"$blockTitle\";
					}";
			}
		echo "	if (iNoOfBlocks == 0)
					{
					alert(\"Please select a block to delete.\");
					return false;
					}
				else
					{
					if (iNoOfBlocks == 1)
						{
						var confirmText = \"Are you sure you want to delete \" + aBlocks[iNoOfBlocks] + \".\";
						}
					else
						{
						var confirmText = \"Are you sure you want to delete these \" + iNoOfBlocks + \" blocks?\";
						}
					var answer = confirm (confirmText);
					if (answer)
						{
						if (CheckSaved('false')=='false')
							{
							var confirmText2 = \"You have made changes to the survey properties. Please save them before deleting blocks. Click 'Cancel' to go back and save your chnages or 'OK' to continue.\";
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
//only show blocks if we're not adding a new survey
	$qHiddenBlocks = "SELECT Blocks.blockID, Blocks.title, Blocks.introduction, Blocks.epilogue, SurveyBlocks.position, SurveyBlocks.visible, Blocks.instanceable, Blocks.lastModified 
				FROM Blocks, SurveyBlocks 
				WHERE SurveyBlocks.surveyID = $surveyID
				AND SurveyBlocks.visible = 0
				AND Blocks.blockID = SurveyBlocks.blockID
				ORDER BY SurveyBlocks.position";
				
	$qResBlocks = mysqli_query($db_connection, $qHiddenBlocks);
	if (($qResBlocks == false))
		{
		echo "problem querying Blocks" . mysqli_error();
		}
	else
		{
		echo "	
		<h2>Hidden blocks in this survey:</h2>
		<div class=\"questionNormal\">
			<form id=\"frmReinstate\" name=\"frmReinstate\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">
				<table summary=\"\" width=\"100%\">
					<tr>
						<td valign=\"top\" >
							<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
								if(mysqli_num_rows($qResBlocks)==0)
									{
									echo "<p>This survey does not contain any hidden blocks.</p>";
									}
								else
									{
									while($rowBlocks = mysqli_fetch_array($qResBlocks))
										{
										$blockID = $rowBlocks['blockID'];
								echo "	<tr class=\"matrixHeaderHidden\">
											<td>
												<input type=\"checkbox\" id=\"reinstate_$blockID\" name=\"reinstateBlockIDs[]\" value=\"$blockID\"/>
											</td>
											<td class=\"question\">Hidden Block: ".$rowBlocks[title]."</td>
											<td>Last modified: ".ODBCDateToTextDateShort($rowBlocks['lastModified'])."</td>
											<td></td>
										</tr>";
										//get all sections		
										$qSections = "	SELECT Sections.sectionID, Sections.title, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, Sections.lastModified 
														FROM Sections, BlockSections 
														WHERE BlockSections.blockID = $blockID
														AND BlockSections.visible = 1
														AND Sections.sectionID = BlockSections.sectionID
														ORDER BY BlockSections.position";
										
										$qResSections = mysqli_query($db_connection, $qSections);
										
										if (($qResSections == false))
											{
											echo "problem querying Sections" . mysqli_error();
											}
										else
											{
											$bRowOdd = true;
											while($rowSections = mysqli_fetch_array($qResSections))
												{
												if($bRowOdd)
													{
													$rowClass = "matrixRowOdd";
													}
												else
													{
													$rowClass = "matrixRowEven";
													}
												//show section titles
								echo "			<tr class=\"$rowClass\">
													<td></td>
													<td  colspan=\"3\" valign=\"top\">Section: ".$rowSections[title]."</td>
												</tr>";
												$bRowOdd = !$bRowOdd;
												}
											}
										}
								echo "	<tr>";
								echo "		<td colspan=\"4\">
											<input type=\"hidden\" value=\"$surveyID\" id=\"hSurveyID3\" name=\"hSurveyID\">
											<input type=\"submit\" id=\"bReinstate\" name=\"bReinstate\" value=\"Reinstate\" onClick=\"return checkReinstateBoxes()\"/>
											</td>";
								echo "	</tr>";
									}
							echo "	
							</table>
						</td>
					<tr>
				</table>
			</form>
		</div>";
		}
	if(mysqli_num_rows($qResBlocks)>0)
		{
		echo "	<script language=\"JavaScript\" type=\"text/javascript\" >
			function checkReinstateBoxes()
				{
				var iNoOfBlocks = 0;
				var aBlocks = new Array();";				
		//resets the mysqli_fetch_array to start at the beginning again
		mysqli_data_seek($qResBlocks, 0);
		while($rowBlocks = mysqli_fetch_array($qResBlocks))
			{
			$blockID = $rowBlocks['blockID'];
			$blockTitle = $rowBlocks[title];
		echo "	if (document.getElementById(\"reinstate_$blockID\").checked == true)
					{
					iNoOfBlocks=iNoOfBlocks+1;
					aBlocks[iNoOfBlocks] = \"$blockTitle\";
					}";
			}
		echo "	if (iNoOfBlocks == 0)
					{
					alert(\"Please select a block to reinstate.\");
					return false;
					}
				else
					{
					if (iNoOfBlocks == 1)
						{
						var confirmText = \"Are you sure you want to reinstate \" + aBlocks[iNoOfBlocks] + \".\";
						}
					else
						{
						var confirmText = \"Are you sure you want to reinstate these \" + iNoOfBlocks + \" blocks?\";
						}
					var answer = confirm (confirmText);
					if (answer)
						{
						if (CheckSaved('false')=='false')
							{
							var confirmText2 = \"You have made changes to the survey properties. Please save them before reinstating blocks. Click 'Cancel' to go back and save your chnages or 'OK' to continue.\";
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
		echo "<a href=\"admin.php\">Administration</a> &gt; $pageTitleText: ".limitString($surveyTitle,30); 	
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html"); 
	require_once("includes/instructions/inst_editsurvey.php"); 

?>
</body>
</html>
