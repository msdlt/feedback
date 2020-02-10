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
	if(isset($_POST['hBlockID']))
		{
		$blockID = $_POST['hBlockID'];
		$surveyID = $_POST['hSurveyID'];
		$updateTitle = quote_smart($_POST['tTitle']);
		$updateText = quote_smart($_POST['tText']);
		$updateIntroduction = quote_smart($_POST['tIntroduction']);
		$updateEpilogue = quote_smart($_POST['tEpilogue']);
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
		/* Checking and ensure that the block title does not already exist in the database */
		$sql_title_check = mysqli_query($db_connection, "SELECT title FROM Blocks
										WHERE title=$updateTitle" . ($blockID !="add" ? "AND blockID <> $blockID" : ""));
										//that last if statement allows edited block to have the same name as it had before
										//but prevents added blockc having the same name as an existing block.
		$title_check = mysqli_num_rows($sql_title_check);
		if($title_check > 0 || $updateTitle == "")
			{
			$validationProblem = true;
			if($blockID =="add")
				{
				//set submit button title
				$btnSubmitName = "bCreate";
				$btnSubmitText = "Create block";
				$pageTitleText = "Create block";
				}
			else
				{
				$btnSubmitName = "bUpdate";
				$btnSubmitText = "Update block";
				$pageTitleText = "Edit block";
				}
			}
		//**********************************************************
		//End server-side validation of data entered 
		//**********************************************************
		else
			{
			//Validated - go ahead with updating/writing
			if($blockID =="add")
				{
				//Create new block with values from this form
				$iBlocks = "INSERT INTO Blocks
						VALUES(0,$updateIntroduction,$updateEpilogue,$updateLastModified,$updateTitle,$updateText,$updateInstanceable)";
				$result_query = @mysqli_query($db_connection, $iBlocks);
				$blockID = mysqli_insert_id();
				if (($result_query == false))
					{
					echo "problem insering into Blocks" . mysqli_error();
					$bSuccess = false;
					}
				//Now need to add it to SurveyBlocks
				//first work out position to add it to
				$qMaxPosition = "	SELECT MAX(position) as maxPosition
									FROM SurveyBlocks
									WHERE surveyID = $surveyID";
				$result_query = @mysqli_query($db_connection, $qMaxPosition);
				if (($result_query == false))
					{
					echo "problem querying qMaxPosition" . mysqli_error();
					}
				$rowMaxPosition = mysqli_fetch_array($result_query);
				$maxPosition = $rowMaxPosition[maxPosition];
				if ($maxPosition == "")
					{
					//there are no existing blocks in this survey
					$position = 0;
					}
				else
					{
					//there are existing blocks in this block so survey
					$position = $maxPosition + 1;
					}
				$iSurveyBlocks = "INSERT INTO SurveyBlocks
							VALUES(0,$surveyID,$blockID,$position,1)";
				$result_query = @mysqli_query($db_connection, $iSurveyBlocks);
				if (($result_query == false))
					{
					echo "problem insering into SurveyBlocks" . mysqli_error();
					$bSuccess = false;
					}
				}
			else
				{
				//Update section with values from this form
				$uBlocks = "UPDATE Blocks
						SET introduction = $updateIntroduction,
						epilogue = $updateEpilogue,
						lastModified = $updateLastModified,
						title = $updateTitle,
						text = $updateText,
						instanceable = $updateInstanceable
						WHERE blockID = $blockID";
				$result_query = @mysqli_query($db_connection, $uBlocks);
				if (($result_query == false))
					{
					echo "problem updating Blocks" . mysqli_error();
					$bSuccess = false;
					}
				}
			//send the user back to schedulesurvey.php after they have added
			header("Location: https://" . $_SERVER['HTTP_HOST']
                     . dirname($_SERVER['PHP_SELF'])
                     . "/" . "editsurvey.php?surveyID=$surveyID");
			exit();
			}
		}
	}
//********************************************************
//deleting section(s)
//********************************************************
else if (isset($_POST['bDelete'])&&$_POST['bDelete']!="")
	{
	$surveyID = $_POST['hSurveyID'];
	$blockID = $_POST['hBlockID'];
	//Find out ids of all the checkboxes which were checked
	$aSections = $_POST['checkSectionIDs'];
	for ($i=0;$i<count($aSections);$i++)
		{
		//find out if there are any results for this section
		if(AreThereAnyResultsForThisObject($surveyID, $blockID, $aSections[$i])==true)
			{
			//hide the section
			$uBlockSections = "	UPDATE BlockSections
								SET visible = 0
								WHERE blockID = $blockID
								AND sectionID = $aSections[$i]";
			$result_query = @mysqli_query($db_connection, $uBlockSections);
			if (($result_query == false))
					{
					echo "problem updating BlockSections" . mysqli_error();
					$bSuccess = false;
					}
			}
		else
			{
			//delete it from the block - but don't do anything to the section itself or any of its children - separate interface for that
			$dBlockSections = "	DELETE FROM BlockSections
								WHERE blockID = $blockID
								AND sectionID = $aSections[$i]";
			$dResBlockSections = @mysqli_query($db_connection, $dBlockSections);
			if (($dResSurveyBlocks == false) && mysqli_affected_rows($db_connection) == 0)
				{
				echo "problem deleting from BlockSections " . mysqli_error();
				}
			}
		}
	$btnSubmitName = "bUpdate";
	$btnSubmitText = "Update block";
	$pageTitleText = "Edit block";
	}
//********************************************************
//Reinstating section(s)
//********************************************************
else if (isset($_POST['bReinstate'])&&$_POST['bReinstate']!="")
	{
	$surveyID = $_POST['hSurveyID'];
	$blockID = $_POST['hBlockID'];
	//Find out ids of all the checkboxes which were checked
	$aSections = $_POST['reinstateSectionIDs'];
	for ($i=0;$i<count($aSections);$i++)
		{
		//hide the section
		$uBlockSections = "	UPDATE BlockSections
							SET visible = 1
							WHERE blockID = $blockID
							AND sectionID = $aSections[$i]";
		$result_query = @mysqli_query($db_connection, $uBlockSections);
		if (($result_query == false))
			{
			echo "problem updating BlockSections" . mysqli_error();
			$bSuccess = false;
			}
		}
	$btnSubmitName = "bUpdate";
	$btnSubmitText = "Update block";
	$pageTitleText = "Edit block";
	}
//********************************************************
//re-ordering section(s)
//********************************************************
else if (isset($_POST['hReOrder']) && $_POST['bReOrder'] != "")
	{
	$surveyID = $_POST['hSurveyID'];
	$blockID = $_POST['hBlockID'];
	//Get new order of section ID's
	$tSections = $_POST['hReOrder'];
	$aSections = explode(",",$tSections);
	for ($i=0;$i<count($aSections);$i++)
		{
		$uBlockSections = "	UPDATE BlockSections
							SET position = $i
							WHERE blockID = $blockID
							AND sectionID = $aSections[$i]";
		$result_query = @mysqli_query($db_connection, $uBlockSections);
		if (($result_query == false))
			{
			echo "problem updating BlockSections" . mysqli_error();
			$bSuccess = false;
			}
		}
	$btnSubmitName = "bUpdate";
	$btnSubmitText = "Update block";
	$pageTitleText = "Edit block";
	}
else if (isset($_GET['blockID'])&&isset($_GET['surveyID']))
	{
	$surveyID = $_GET['surveyID'];
	$blockID = $_GET['blockID'];
	if($_GET['blockID']=="add")
		{
		$btnSubmitName = "bCreate";
		$btnSubmitText = "Create block";
		$pageTitleText = "Create block";
		}
	else
		{
		$btnSubmitName = "bUpdate";
		$btnSubmitText = "Update block";
		$pageTitleText = "Edit block";
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
	if(!validText(document.getElementById("tTitle"),"block title",true))
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

<body onBeforeUnload="return CheckSaved('You have made changes to the block properties. Please save them before moving away from this page')">
<?php
//get information about survey
$qSurveys = "SELECT title
			FROM Surveys
			WHERE surveyID = $surveyID";
$qResSurvey = mysqli_query($db_connection, $qSurveys);
$rowSurvey = mysqli_fetch_array($qResSurvey);
$surveyTitle = $rowSurvey[title];
//Get info about block
if($blockID!="add")
	{
	$qBlocks = "	SELECT title, text, introduction, epilogue, instanceable, lastModified 
					FROM Blocks
					WHERE blockID = $blockID";
	$qResBlocks = mysqli_query($db_connection, $qBlocks);
	$rowBlock = mysqli_fetch_array($qResBlocks);
	$blockTitle = $rowBlock[title];
	$blockText = $rowBlock['text'];
	$blockIntroduction = $rowBlock[introduction];
	$blockEpilogue = $rowBlock[epilogue];
	$blockInstanceable = $rowBlock[instanceable];
	$blockLastModified = $rowBlock['lastModified'];
	}
elseif ($validationProblem == true)
	{
	$blockTitle = $_POST['tTitle'];
	$blockText = $_POST['tText'];
	$blockIntroduction = $_POST['tIntroduction']; 
	$blockEpilogue = $_POST['tEpilogue'];
	if($_POST['rInstanceable']==true)
		{
		$blockInstanceable = 1;
		}
	else
		{
		$blockInstanceable = 0;
		}
	}
else
	{
	$blockTitle = "New block";
	$blockText = "New block";
	$blockIntroduction = "Introduction";
	$blockEpilogue = "Epilogue";
	$blockInstanceable = 0;
	}
if(IsAuthor($heraldID))
	{
	require_once("includes/html/adminheadernew.html");
	require_once("includes/html/BreadCrumbsHeader.html"); 
	echo "
	<a href=\"admin.php\">Administration</a> &gt;
	<a href=\"editsurvey.php?surveyID=$surveyID\">Edit survey: ".limitString($surveyTitle,30)."</a> &gt; 
	$pageTitleText: ".limitString($blockTitle,30);
	require_once("includes/html/BreadCrumbsFooter.html"); 
	}
else
	{
	require_once("includes/html/header.html");
	echo "You do not have the necessary permissions to view this page";
	exit(); 
	}
echo "<a name=\"maintext\" id=\"maintext\"></a>
<h1>$pageTitleText: $blockTitle</h1>
<form id=\"frmEdit\" name=\"frmEdit\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" onSubmit=\"return ValidateForm(this)\">
<h2>Block properties:</h2>
<div class=\"questionNormal\">";
if($validationProblem==true)
	{
	echo "<span class=\"errorMessage\"><strong>Please fix the following errors:</strong><br/>";
	if($title_check > 0)
		{
		echo "This block name is already in use in our database. Please enter a different name (e.g. prefix with the name of your survey) and try again.<br/>";
		}
	if($updateTitle == "")
		{
		echo "You must enter a title for this block. Please enter a title and try again.<br/>";
		}
	echo "</span><br/>";
	}
echo"
<table class=\"normal_3\" summary=\"\">
	<tr>
		<td>Name:</td>
		<td>
			<input type=\"text\" id=\"tTitle\" name=\"tTitle\" value=\"$blockTitle\" size=\"50\" onChange=\"ControlHasChanged('$btnSubmitName')\">
		</td>
	</tr>
	<tr>
		<td valign=\"top\">Title:</td>
		<td>
			<textarea id=\"tText\" name=\"tText\" rows=\"3\" cols=\"35\" onChange=\"ControlHasChanged('$btnSubmitName')\">$blockText</textarea>
		</td>
	</tr>
	<tr>
		<td>Last modified:</td>
		<td>".ODBCDateToTextDateShort($blockLastModified)."</td>
	</tr>
	<tr>
		<td valign=\"top\">Introduction:</td>
		<td>
			<textarea id=\"tIntroduction\" name=\"tIntroduction\" rows=\"5\" cols=\"35\" onChange=\"ControlHasChanged('$btnSubmitName')\">$blockIntroduction</textarea>
		</td>
	</tr>
	<tr>
		<td valign=\"top\">Epilogue:</td>
		<td>
			<textarea id=\"tEpilogue\" name=\"tEpilogue\" rows=\"5\" cols=\"35\" onChange=\"ControlHasChanged('$btnSubmitName')\">$blockEpilogue</textarea>
		</td>
	</tr>
	<tr>
		<td valign=\"top\">Is repeatable?</td>
		<td>
			<input ";
			if($blockInstanceable==1)
				{
				echo "checked";
				}	
			echo " type=\"radio\" name=\"rInstanceable\" id=\"rInstanceableTrue\" value=\"true\" onChange=\"ControlHasChanged('$btnSubmitName')\"><label for=\"rInstanceableTrue\">Yes</label>
			<input ";
			if($blockInstanceable==0)
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
			<input type=\"submit\" value=\"$btnSubmitText\" id=\"$btnSubmitName\" name=\"$btnSubmitName\">
			<input type=\"button\" id=\"btnPreviewSurvey\" name=\"btnPreviewSurvey\" value=\"Preview\" onClick=\"window.open('previewsurvey.php?surveyID=$surveyID','previewWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes')\">
		</td>
	</tr>
		";
echo " </table>";
echo " </div>";

if($blockID !="add")
	{
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
		//to get question numbering right, need to calculate how many questions occur before this one in the survey
		//first find out position of this block in the survey
		$qBlockPosition = "	SELECT position
							FROM SurveyBlocks
							WHERE blockID = $blockID
							AND surveyID = $surveyID";
		
		$qResBlockPosition = mysqli_query($db_connection, $qBlockPosition);
		$rowBlockPosition = mysqli_fetch_array($qResBlockPosition);
		$blockPosition = $rowBlockPosition[position];
		//then find out sections which occur before this one:
		$qPreviousQuestions = "	SELECT SectionQuestions.questionID
						FROM SurveyBlocks, Blocks, BlockSections, Sections, SectionQuestions
						WHERE SurveyBlocks.position < $blockPosition
						AND SurveyBlocks.surveyID = $surveyID
						AND Blocks.blockID = SurveyBlocks.blockID
						AND BlockSections.blockID = Blocks.blockID
						AND BlockSections.visible = 1
						AND Sections.sectionID = BlockSections.sectionID
						AND SectionQuestions.sectionID = Sections.sectionID";
		
		$qResPreviousQuestions = mysqli_query($db_connection, $qPreviousQuestions);
		$questionNo = mysqli_num_rows($qResPreviousQuestions) + 1;
echo "	<h2>Sections in this block:</h2>
		<div class=\"questionNormal\">
			<form id=\"frmDelete\" name=\"frmDelete\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">
				<table summary=\"\" width=\"100%\">
					<tr>
						<td valign=\"top\">
							<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
							if(mysqli_num_rows($qResSections)==0)
								{
								echo "<p>This block does not contain any sections.</p>";
								}
							else
								{
								while($rowSections = mysqli_fetch_array($qResSections))
									{
									$sectionID = $rowSections['sectionID'];
							echo "	<tr class=\"matrixHeader\">
										<td>
											<input type=\"checkbox\" id=\"check_$sectionID\" name=\"checkSectionIDs[]\" value=\"$sectionID\"/>
										</td>
										<td class=\"question\">Section: ".$rowSections[title]."</td>
										<td>Last modified: ".ODBCDateToTextDateShort($rowSections['lastModified'])."</td>
										<td><input type=\"button\" id=\"editSection_$sectionID\" name=\"editSection_$sectionID\" value=\"Edit section\" onClick=\"goTo('editsection.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID')\"".(IsSuperAuthor($heraldID, $blockID, $sectionID)==false ? "disabled" : "" )." /></td>
									</tr>";
									//get all questions		
									$qQuestions = "SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position 
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
										$bRowOdd = true;
										while($rowQuestions = mysqli_fetch_array($qResQuestions))
											{
											if($bRowOdd)
												{
												$rowClass = "matrixRowOdd";
												}
											else
												{
												$rowClass = "matrixRowEven";
												}
										echo "<tr class=\"$rowClass\">
												<td></td>
												<td  colspan=\"3\" valign=\"top\">".$questionNo." ".$rowQuestions['text']."</td>
											</tr>";
											$questionNo = $questionNo + 1;
											$bRowOdd = !$bRowOdd;
											}
										}
									}
								}
								echo "
								<tr>
									<td colspan=\"4\">
										<!-- These seem to need to be repeated - give them a different ID-->
										<input type=\"hidden\" value=\"$surveyID\" id=\"hSurveyID2\" name=\"hSurveyID\">
										<input type=\"hidden\" value=\"$blockID\" id=\"hBlockID2\" name=\"hBlockID\">
										<input type=\"button\" id=\"bAdd\" name=\"bAdd\" value=\"Add New Section\" onClick=\"goTo('editsection.php?surveyID=$surveyID&blockID=$blockID&sectionID=add')\"/>
										<input type=\"button\" id=\"bAddExisting\" name=\"bAddExisting\" value=\"Add Existing Section\" onClick=\"goTo('addexisting.php?surveyID=$surveyID&blockID=$blockID')\"/>";
								if(mysqli_num_rows($qResSections)>0)
									{
									echo "&nbsp;<input type=\"submit\" id=\"bDelete\" name=\"bDelete\" value=\"Delete\" onclick=\"return checkCheckBoxes()\"/>";
									}
							echo "	</td>
								</tr>
							</table>
						</td>";
					if(mysqli_num_rows($qResSections)>1)
						{
						echo"
						<td width=\"10%\" valign=\"top\">
							<p>To re-order, select section(s) from the list and reposition using the <strong>Move Up</strong> and
							<strong>Move Down</strong> buttons. Click <strong>Re-order</strong> to apply your changes.
							<table border=\"0\">
								<tr>
									<td>
										<select name=\"sUpDown\" size=\"7\" multiple>";
										mysqli_data_seek($qResSections, 0);
										while($rowSections = mysqli_fetch_array($qResSections))
											{
											$sectionID = $rowSections['sectionID'];
											$sectionTitle = $rowSections[title];
											echo "<option value=\"$sectionID\">".($sectionTitle==""?"Section":limitString($sectionTitle,30))."";
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
					</tr>
				</table>
			</form>		
		</div>";
		}
	if(mysqli_num_rows($qResSections)>0)
		{
		echo "	<script language=\"JavaScript\" type=\"text/javascript\" >
			function checkCheckBoxes()
				{
				var iNoOfSections = 0;
				var aSections = new Array();";				
		//resets the mysql_fetch_array to start at the beginning again
		mysqli_data_seek($qResSections, 0);
		while($rowSections = mysqli_fetch_array($qResSections))
			{
			$sectionID = $rowSections['sectionID'];
			$sectionTitle = $rowSections[title];
		echo "	if (document.getElementById(\"check_$sectionID\").checked == true)
					{
					iNoOfSections=iNoOfSections+1;
					aSections[iNoOfSections] = \"$sectionTitle\";
					}";
			}
		echo "	if (iNoOfSections == 0)
					{
					alert(\"Please select a section to delete.\");
					return false;
					}
				else
					{
					if (iNoOfSections == 1)
						{
						var confirmText = \"Are you sure you want to delete \" + aSections[iNoOfSections] + \".\";
						}
					else
						{
						var confirmText = \"Are you sure you want to delete these \" + iNoOfSections + \" sections?\";
						}
					var answer = confirm (confirmText);
					if (answer)
						{
						if (CheckSaved('false')=='false')
							{
							var confirmText2 = \"You have made changes to the block properties. Please save them before deleting sections. Click 'Cancel' to go back and save your chnages or 'OK' to continue.\";
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
	$qHiddenSections = "	SELECT Sections.sectionID, Sections.title, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, Sections.lastModified 
							FROM Sections, BlockSections 
							WHERE BlockSections.blockID = $blockID
							AND BlockSections.visible = 0
							AND Sections.sectionID = BlockSections.sectionID
							ORDER BY BlockSections.position";
			
	$qResSections = mysqli_query($db_connection, $qHiddenSections);
	if (($qResSections == false))
		{
		echo "problem querying Sections" . mysqli_error();
		}
	else
		{
		//No need to get question numbering right in hidden sections
echo "	<h2>Hidden sections in this block:</h2>
		<div class=\"questionNormal\">
			<form id=\"frmDelete\" name=\"frmDelete\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">
				<table summary=\"\" width=\"100%\">
					<tr>
						<td valign=\"top\">
							<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
							if(mysqli_num_rows($qResSections)==0)
								{
								echo "<p>This block does not contain any hidden sections.</p>";
								}
							else
								{
								while($rowSections = mysqli_fetch_array($qResSections))
									{
									$sectionID = $rowSections['sectionID'];
							echo "	<tr class=\"matrixHeaderHidden\">
										<td>
											<input type=\"checkbox\" id=\"reinstate_$sectionID\" name=\"reinstateSectionIDs[]\" value=\"$sectionID\"/>
										</td>
										<td class=\"question\">Hidden Section: ".$rowSections[title]."</td>
										<td>Last modified: ".ODBCDateToTextDateShort($rowSections['lastModified'])."</td>
										<td>&nbsp;</td>
									</tr>";
									//get all questions		
									$qQuestions = "SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position 
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
										$bRowOdd = true;
										while($rowQuestions = mysqli_fetch_array($qResQuestions))
											{
											if($bRowOdd)
												{
												$rowClass = "matrixRowOdd";
												}
											else
												{
												$rowClass = "matrixRowEven";
												}
										echo "<tr class=\"$rowClass\">
												<td></td>
												<td  colspan=\"3\" valign=\"top\">Question: ".$rowQuestions['text']."</td>
											</tr>";
											$bRowOdd = !$bRowOdd;
											}
										}
									}
								echo "	<tr>";
								echo "		<td colspan=\"4\">
												<!-- These seem to need to be repeated - give them a different ID-->
												<input type=\"hidden\" value=\"$surveyID\" id=\"hSurveyID3\" name=\"hSurveyID\">
												<input type=\"hidden\" value=\"$blockID\" id=\"hBlockID3\" name=\"hBlockID\">
												<input type=\"submit\" id=\"bReinstate\" name=\"bReinstate\" value=\"Reinstate\" onclick=\"return checkReinstateBoxes()\"/>
											</td>";
								echo "	</tr>";
								}
							echo "
							</table>
						</td>
					</tr>
				</table>
			</form>		
		</div>";
		}
	if(mysqli_num_rows($qResSections)>0)
		{
	echo "	<script language=\"JavaScript\" type=\"text/javascript\" >
			function checkReinstateBoxes()
				{
				var iNoOfSections = 0;
				var aSections = new Array();";				
		//resets the mysql_fetch_array to start at the beginning again
		mysqli_data_seek($qResSections, 0);
		while($rowSections = mysqli_fetch_array($qResSections))
			{
			$sectionID = $rowSections['sectionID'];
			$sectionTitle = $rowSections[title];
		echo "	if (document.getElementById(\"reinstate_$sectionID\").checked == true)
					{
					iNoOfSections=iNoOfSections+1;
					aSections[iNoOfSections] = \"$sectionTitle\";
					}";
			}
		echo "	if (iNoOfSections == 0)
					{
					alert(\"Please select a section to reinstate.\");
					return false;
					}
				else
					{
					if (iNoOfSections == 1)
						{
						var confirmText = \"Are you sure you want to reinstate \" + aSections[iNoOfSections] + \".\";
						}
					else
						{
						var confirmText = \"Are you sure you want to reinstate these \" + iNoOfSections + \" sections?\";
						}
					var answer = confirm (confirmText);
					if (answer)
						{
						if (CheckSaved('false')=='false')
							{
							var confirmText2 = \"You have made changes to the block properties. Please save them before reinstating sections. Click 'Cancel' to go back and save your chnages or 'OK' to continue.\";
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
		echo "
		<a href=\"admin.php\">Administration</a> &gt;
		<a href=\"editsurvey.php?surveyID=$surveyID\">Edit survey: ".limitString($surveyTitle,30)."</a> &gt; 
		$pageTitleText: ".limitString($blockTitle,30);
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html"); 
	require_once("includes/instructions/inst_editblock.php"); 
?>
</body>
</html>
