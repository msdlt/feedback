<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>View results</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" type="text/css" href="css/SurveyStyles.css">
<link rel="stylesheet" type="text/css" href="css/msdstyle2.css" media="screen"/>
<link href="css/msdprint.css" rel="stylesheet" type="text/css" media="print" />
	
<?php
require_once("includes/config.php"); 
require_once("includes/getuser.php");
require_once("includes/isauthor.php");
require_once("includes/limitstring.php");
require_once("includes/ODBCDateToTextDate.php");

?>
<script language="javascript" type="text/javascript">
function ValidateForm()
	{
	if (document.getElementById("ddSurvey").selectedIndex == 0)
		{
		alert ("Please choose a survey");
		return false;
		}
	return true;
	}
function ValidateSecondForm()
	{
	if (ValidateCriteria()==false) return false;
	if (CheckDates() ==false) return false;
	if(!document.getElementById('chkSaved').checked && !document.getElementById('chkSubmitted').checked)
		{
		alert ("You must check at least one of the submission status boxes");
		document.getElementById('chkSaved').focus()
		return false;
		}
	return true;
	}
</script>
<script language="javascript" type="text/javascript">

//dynamically filling date lists
function buildsel(Month,Year,DayList) 
	{
	//Calculate no of days in month
	days = daysInMonth(Month,Year);
	// get reference for list that is being updated
  	sel=DayList;
	//remember currently seslected day
	selectedDay=sel.selectedIndex;
	/* delete entries for list that is being updated */
  	for (i=0; i<sel.length; i++)
		{
    	sel.options[i] = null;
		}
  	/* add new entries to list that is being updated */
  	if (selectedDay>days-1)
		{
		selectedDay=days-1;
		}
	for (i=0; i<days; i++)
		{
		sel.options[i] = new Option(i+1,i+1);
		}
	sel.options[selectedDay].selected=true;
	}
//function to return no of days in a month
function daysInMonth(Month, Year)
	{
	if (Month==1)
		{
		return 31;
		}
	else if (Month==2)
		{
		if ((Year-1980)%4 == 0)
			{
			return 29;
			}
		else
			{
			return 28;
			}
		}
	if (Month==3) return 31;
	if (Month==4) return 30;
	if (Month==5) return 31;
	if (Month==6) return 30;
	if (Month==7) return 31;
	if (Month==8) return 31;
	if (Month==9) return 30;
	if (Month==10) return 31;
	if (Month==11) return 30;
	if (Month==12) return 31;
	}
//function toggleStartDate(value, btnSubmit)
function toggleStartDate(value)
	{
	if (value==0)
		{
		document.getElementById("startDay").disabled = true;
		document.getElementById("startMonth").disabled = true;
		document.getElementById("startYear").disabled = true;
		}
	else
		{
		document.getElementById("startDay").disabled = false;
		document.getElementById("startMonth").disabled = false;
		document.getElementById("startYear").disabled = false;
		}
	}
//function toggleFinishDate(value,btnSubmit)
function toggleFinishDate(value)
	{
	if (value==0)
		{
		document.getElementById("finishDay").disabled = true;
		document.getElementById("finishMonth").disabled = true;
		document.getElementById("finishYear").disabled = true;
		}
	else
		{
		document.getElementById("finishDay").disabled = false;
		document.getElementById("finishMonth").disabled = false;
		document.getElementById("finishYear").disabled = false;
		}
	}
//Validation of input (called by frm1 OnSubmit())
//checks that finish date is later than start date
function CheckDates()
	{
	//note have had to use DOM syntax rather than getElementById as IE (not Firefox) kept on saying that document.getElementById("rFinishDate").checked was true when it should have been false
	if (document.frmAnalyse.rStartDate[1].checked)
		{
		dateObs1 = new Date(document.getElementById("startYear").value,document.getElementById("startMonth").value,document.getElementById("startDay").value);
		}
	else
		{
		dateObs1 = "null";
		}
	if (document.frmAnalyse.rFinishDate[1].checked)
		{
		dateObs2 = new Date(document.getElementById("finishYear").value,document.getElementById("finishMonth").value,document.getElementById("finishDay").value);
		}
	else
		{
		dateObs2 = "null";
		}
	if (dateObs1!="null" && dateObs2!="null")
		{
		if(dateObs2.getTime() <= dateObs1.getTime()) /*..if it does, make sure it is later than the first date*/
			{
			alert("The finish date must be later than the start date");
			return false;
			}
		}
	}
function OnbAddCriterion()
	{
	if(ValidateCriteria()==false)
		{
		return false;
		}
	else
		{
		document.frmAnalyse.action = "<?php $_SERVER['PHP_SELF'] ?>"
		document.frmAnalyse.target = "_self";	
		document.frmAnalyse.submit();
		return true;
		}
	}

function OnbGenerateCompactResults()
	{
	if(ValidateSecondForm()==false)
		{
		return false;
		}
	else
		{
		document.frmAnalyse.action = "showresults.php"
		document.frmAnalyse.target = "_self";	
		document.frmAnalyse.submit();
		return true;
		}
	}
function OnbGenerateFullResults()
	{
	if(ValidateSecondForm()==false)
		{
		return false;
		}
	else
		{
		document.frmAnalyse.action = "showresultslong.php"
		document.frmAnalyse.target = "_self";	
		document.frmAnalyse.submit();
		return true;
		}
	}
function OnbGenerateExcelResults()
	{
	if(ValidateSecondForm()==false)
		{
		return false;
		}
	else
		{
		document.frmAnalyse.action = "showresultsexcel.php"
		document.frmAnalyse.target = "_self";	
		document.frmAnalyse.submit();
		return true;
		}
	}
function OnbGenerateCompactExcelResults()
	{
	if(ValidateSecondForm()==false)
		{
		return false;
		}
	else
		{
		document.frmAnalyse.action = "showresultsexcelcompact.php"
		document.frmAnalyse.target = "_self";	
		document.frmAnalyse.submit();
		return true;
		}
	}
</script>

<?php
	if(isset($_POST['bChooseSurveys'])||isset($_POST['bAddCriterion'])||isset($_POST['bDeleteCriterion']))
		{
		if(isset($_POST['bChooseSurveys']))
			{
			$noOfCriteria = 1;
			$surveyID = $_POST['ddSurvey']; //getting surveyID from POST
			}
		elseif(isset($_POST['bAddCriterion']))
			{
			//must have just added a criterion
			$noOfCriteria = $_POST['hNoOfCriteria'] + 1;
			$surveyID = $_POST['surveyID']; //getting surveyID from POST
			}
		elseif(isset($_POST['bDeleteCriterion']))
			{
			//must have just deleted a criterion
			$noOfCriteria = $_POST['hNoOfCriteria'] - 1;
			$surveyID = $_POST['surveyID']; //getting surveyID from POST
			}
		if(!isset($_POST['rStartDate']) || $_POST['rStartDate']==0)
			{
			$startDate="NULL";
			}
		else
			{
			$startDate = date("Y-m-d", mktime(0, 0, 0, $_POST['startMonth'], $_POST['startDay'], $_POST['startYear'])); 
			}
		if(!isset($_POST['rFinishDate']) || $_POST['rFinishDate']==0)
			{
			$finishDate="NULL";
			}
		else
			{
			$finishDate = date("Y-m-d", mktime(0, 0, 0, $_POST['finishMonth'], $_POST['finishDay'], $_POST['finishYear']));
			}
		if(isset($_POST['chkSaved'])) {
			$showSaved = $_POST['chkSaved'];
		}
		if(isset($_POST['chkSubmitted'])) {
			$showSubmitted = $_POST['chkSubmitted'];
		}
		if(isset($_POST['sSchedule'])) {
			$instanceID = $_POST['sSchedule'];
		}
		echo "	<script language=\"JavaScript\" src=\"script/OptionTransfer.js\"></script>";
		//write script to handle option transfers
		echo "<script language=\"JavaScript\">";
		for($i=1;$i<=$noOfCriteria;$i++)
			{
			echo "	var opt_$i = new OptionTransfer(\"qSource_$i\",\"qDestination_$i\"); 
					opt_$i.setAutoSort(true); 
					opt_$i.saveNewRightOptions(\"hidQuestions_$i\");";
			}
		echo "function ValidateCriteria()
				{";
		for($i=1;$i<=$noOfCriteria;$i++)
			{
			echo "if (document.getElementById(\"qAnalyseBy_$i\").selectedIndex != 0)
					{
					if (document.getElementById(\"qDestination_$i\").length == 0)
						{
						alert ('Please choose one or more questions to analyse in analysis criterion $i.');
						return false;
						}
					}
				if (document.getElementById(\"qDestination_$i\").length != 0)
					{
					if (document.getElementById(\"qAnalyseBy_$i\").selectedIndex == 0)
						{
						alert ('Please choose a question by which to analyse in analysis criterion $i.');
						return false;
						}
					}
				";
			}
			echo "
				}
			";
		echo "</script>";
		echo"</head>";
		$bodyTag = "<body onLoad=\"";
		for($i=1;$i<=$noOfCriteria;$i++)
			{
			$bodyTag = $bodyTag . "opt_$i.init(document.frmAnalyse);";
			}
		for($i=1;$i<=$noOfCriteria;$i++)
			{
			$bodyTag = $bodyTag . "opt_$i.transferRight();";
			}
		$bodyTag = $bodyTag . "\">"; 
		echo $bodyTag;
		}
	else
		{
		echo "<body>";
		}
	if(IsAuthor($heraldID))
		{
		require_once("includes/html/adminheadernew.html");
		require_once("includes/html/BreadCrumbsHeader.html"); 
		echo "<a href=\"results.php\">Results</a> &gt; <strong>View Results</strong>"; 	
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	else
		{
		require_once("includes/html/header.html");
		echo "You do not have the necessary permissions to view this page";
		exit();  
		}
?>		
<?php
echo "<a name=\"maintext\" id=\"maintext\"></a>";
echo "<h1>View Results</h1>";
$qSurveys = "SELECT Surveys.surveyID, Surveys.title
			FROM Surveys, SurveyAuthors, Authors
			WHERE Surveys.surveyID = SurveyAuthors.surveyID
			AND SurveyAuthors.authorID = Authors.authorID
			AND Authors.heraldID = '$heraldID'
			ORDER BY Surveys.title";
$qResSurveys = @mysqli_query($db_connection, $qSurveys);
echo "<div class=\"block\">
		<h2>Step 1: Choose a survey</h2>		
		<form id=\"frmResults\" name=\"frmResults\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" onSubmit=\"return ValidateForm()\">";

if (($qResSurveys == false))
	{
	echo "problem querying Surveys" . mysqli_error($db_connection);
	}
else
	{
	echo "	<table class=\"normal_3\" summary=\"\">";
	echo "		<tr>
					<td class=\"question\">Choose a survey:</td>
					<td>
						<select id=\"ddSurvey\" name=\"ddSurvey\" size=\"1\">						
							<option value=\"0\">
								Choose a survey
							</option>";
	while($rowSurveys = mysqli_fetch_array($qResSurveys))
		{
		//remember the option previously selected
		if((isset($_POST['ddSurvey']) && $_POST['ddSurvey'] == $rowSurveys['surveyID'])||(isset($_POST['surveyID']) && $_POST['surveyID'] == $rowSurveys['surveyID']))
			{
			echo "			<option value=\"".$rowSurveys['surveyID']."\" selected>
								".$rowSurveys['title']."
							</option>";
			}
		else
			{
			echo "			<option value=\"".$rowSurveys['surveyID']."\">
								".$rowSurveys['title']."
							</option>";
			}
		}
	echo "				</select>
					</td>
				</tr>";	
	echo "	</table>";
	}
echo "	<input type=\"submit\" value=\"Choose survey\" id=\"bChooseSurveys\" name=\"bChooseSurveys\">	";
echo "</form>
	</div>";
if(isset($_POST['bChooseSurveys'])||isset($_POST['bAddCriterion'])||isset($_POST['bDeleteCriterion']))
	{
	//get blocks - returning SurveyBlocks.visible will allow us to choose whether to include 'deleted' blocks for which there are results
	$qBlocks = "SELECT Blocks.blockID, Blocks.title, Blocks.introduction, Blocks.epilogue, SurveyBlocks.position, Blocks.instanceable, SurveyBlocks.visible
				FROM Blocks, SurveyBlocks 
				WHERE SurveyBlocks.surveyID = $surveyID
				AND Blocks.blockID = SurveyBlocks.blockID
				ORDER BY SurveyBlocks.position";
	$qResBlocks = mysqli_query($db_connection, $qBlocks);
	$questionNo = 1;
	$aQuestions = array();
	$aAllowAnalyseByThisQuestion = array(); //are these qestions allowed as ones to be analysed by
	$aTextandID = array();
	while($rowBlocks = mysqli_fetch_array($qResBlocks))
		{
		$blockID = $rowBlocks['blockID'];
		$blockVisible = $rowBlocks['visible'];
		//$qResSections = mysqli_query($db_connection, $qSections);
		//get sections
		$qSections = "SELECT Sections.sectionID, Sections.title, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, BlockSections.visible 
					FROM Sections, BlockSections 
					WHERE BlockSections.blockID = $blockID
					AND Sections.sectionID = BlockSections.sectionID
					ORDER BY BlockSections.position";
		
		$qResSections = mysqli_query($db_connection, $qSections);
		//counter for questions 
		while($rowSections = mysqli_fetch_array($qResSections))
			{
			$sectionID = $rowSections['sectionID'];
			$sectionVisible = $rowSections['visible'];
			//get questions 
			$qQuestions = "SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position, SectionQuestions.visible
						FROM Questions, SectionQuestions
						WHERE SectionQuestions.sectionID = $sectionID
						AND Questions.questionID = SectionQuestions.questionID
						ORDER BY SectionQuestions.position";
			
			$qResQuestions = mysqli_query($db_connection, $qQuestions);
			
			while($rowQuestions = mysqli_fetch_array($qResQuestions))
				{
				$questionID = $rowQuestions['questionID'];
				$questionVisible = $rowQuestions['visible'];
				unset($aTextandID);
				$drdownOptionValue = $blockID . "_" . $sectionID . "_" . $questionID;
				$questionText = limitString($rowQuestions['text'],30);
				$questionNoForOutput = $questionNo;
				if ($questionNoForOutput < 10)
					{
					$questionNoForOutput = "0" . $questionNoForOutput;
					}
				$aTextandID[0] = $questionNoForOutput . " " . $questionText;
				$aTextandID[1] = $drdownOptionValue;
				$aQuestions[$questionNo] = $aTextandID;
				//only populate the drop-down with questions on which a choice could be based i.e not text/questionTypeID = 4
				if ($rowQuestions['questionTypeID'] == 1 || $rowQuestions['questionTypeID'] == 2 || $rowQuestions['questionTypeID'] == 3)
					{
					$aAllowAnalyseByThisQuestion[$questionNo] = true;
					}
				else
					{
					$aAllowAnalyseByThisQuestion[$questionNo] = false;
					}
				//increment question number
				$questionNo = $questionNo + 1;
				}
			}
		}
	echo "
	<div class=\"block\">
	<h2>Step 2: Limit results and choose analysis criteria</h2>
	<form id=\"frmAnalyse\" name=\"frmAnalyse\" method=\"post\">";
		echo"
			<table class=\"normal_3\">
				<tr>
						<td class=\"question\" align=\"left\">Choose a schedule:</td>
						<td>";
						//get schedules for this survey
						$qSurveyInstances = "SELECT surveyInstanceID, title, startDate, finishDate
											FROM SurveyInstances
											WHERE surveyID = $surveyID";
						$qResSurveyInstances = mysqli_query($db_connection, $qSurveyInstances);
						if ($qResSurveyInstances == false)
							{
							echo "problem querying SurveyInstances" . mysqli_error($db_connection);
							}
						else
							{
							echo"<select id=\"sSchedule\" name=\"sSchedule\" size=\"1\">";
							echo "	<option value=\"0\">Choose a schedule</option>";
							while($rowSurveyInstances = mysqli_fetch_array($qResSurveyInstances))
								{
								$instanceStartDate = $rowSurveyInstances[startDate];
								if($instanceStartDate==NULL)
									{
									$instanceStartDate = "Unlimited";
									}
								else
									{
									$instanceStartDate = ODBCDateToTextDateShort($instanceStartDate);
									}
								$instanceFinishDate = $rowSurveyInstances[finishDate];
								if($instanceFinishDate==NULL)
									{
									$instanceFinishDate = "Unlimited";
									}
								else
									{
									$instanceFinishDate = ODBCDateToTextDateShort($instanceFinishDate);
									}
								echo "<option value=\"".$rowSurveyInstances['surveyInstanceID']."\"". ($instanceID == $rowSurveyInstances['surveyInstanceID']?"selected":"").">" . $rowSurveyInstances['title'] . " - " . $instanceStartDate . " to "  . $instanceFinishDate . "</option>";
								}
							echo "</select>";
							}
						echo"
						<td>
					</tr>
					<tr>
						<td class=\"question\">Start date:</td>
						<td>";
							if(isset($_POST['startMonth']))
								{
								$dayValue = $_POST['startDay'];
								$monthValue = $_POST['startMonth'];
								$yearValue = $_POST['startYear'];
								}
							else
								{
								$dayValue = intval(date("d"));
								$monthValue = intval(date("n"));
								$yearValue = intval(date("Y"));
								}
							$yearMin = date("Y")-5;
							$yearMax = date("Y")+10;
							//echo "	<input type=\"radio\" id=\"rStartDateNone\" name=\"rStartDate\" value=\"0\" onclick=\"toggleStartDate(this.value,'".$btnSubmitName."')\"".((isset($_POST['rStartDate']) && $_POST['rStartDate']==0)?"checked": "")."><label for=\"rStartDateNone\">None</label>";
							//echo "	<input type=\"radio\" id=\"rStartDate\" name=\"rStartDate\" value=\"1\" onclick=\"toggleStartDate(this.value,'".$btnSubmitName."')\"".((isset($_POST['rStartDate']) && $_POST['rStartDate']==1)?"checked": "").">";
							echo "	<input type=\"radio\" id=\"rStartDateNone\" name=\"rStartDate\" value=\"0\" onclick=\"toggleStartDate(this.value)\"".((isset($_POST['rStartDate']) && $_POST['rStartDate']==0)?"checked": "")."><label for=\"rStartDateNone\">None</label>";
							echo "	<input type=\"radio\" id=\"rStartDate\" name=\"rStartDate\" value=\"1\" onclick=\"toggleStartDate(this.value)\"".((isset($_POST['rStartDate']) && $_POST['rStartDate']==1)?"checked": "").">";
							
							echo "	<select id=\"startDay\" name=\"startDay\" size=\"1\">";
									for($i=1;$i<=31;$i++)
										{
							echo "		<option ";
										if($i==$dayValue)
											{
											echo " selected ";
											}
											echo "value=\"$i\">$i</option>";
										}
							echo "	</select> / ";
							echo "	<select id=\"startMonth\" name=\"startMonth\" size=\"1\" onChange=\"buildsel(this[this.selectedIndex].value,startYear[startYear.selectedIndex].value,startDay)\">";
									for($i=1;$i<=12;$i++)
										{
										echo "		<option ";
										if($i==$monthValue)
											{
											echo " selected ";
											}
											echo "value=\"$i\">$i</option>";
										}
							echo "		</select> / ";
							echo "	<select id=\"startYear\" name=\"startYear\" size=\"1\" onChange=\"buildsel(startMonth[startMonth.selectedIndex].value,this[this.selectedIndex].value,startDay)\">";
									for($i=$yearMin;$i<=$yearMax;$i++)
										{
										echo "		<option ";
										if($i==$yearValue)
											{
											echo " selected ";
											}
											echo "value=\"$i\">$i</option>";
										}
							echo "		</select>";
				echo"	</td>
					</tr>
					<tr>
						<td class=\"question\">Finish date:</td>
						<td>
							";
							if(isset($_POST['finishMonth']))
								{
								$dayValue = $_POST['finishDay'];
								$monthValue = $_POST['finishMonth'];
								$yearValue = $_POST['finishYear'];
								}
							else
								{
								$dayValue = intval(date("d"));
								$monthValue = intval(date("n"));
								$yearValue = intval(date("Y"));
								}
							$yearMin = date("Y")-5;
							$yearMax = date("Y")+10;
							echo "	<input type=\"radio\" id=\"rFinishDateNone\" name=\"rFinishDate\" value=\"0\" onclick=\"toggleFinishDate(this.value)\"".((isset($_POST['rFinishDate']) && $_POST['rFinishDate']==0)?"checked": "")."><label for=\"rFinishDateNone\">None</label>";
							echo "	<input type=\"radio\" id=\"rFinishDate\" name=\"rFinishDate\" value=\"1\" onclick=\"toggleFinishDate(this.value)\"".((isset($_POST['rFinishDate']) && $_POST['rFinishDate']==1)?"checked": "").">";
							echo "	<select id=\"finishDay\" name=\"finishDay\" size=\"1\">";
									for($i=1;$i<=31;$i++)
										{
										echo "		<option ";
										if($i==$dayValue)
											{
											echo " selected ";
											}
											echo "value=\"$i\">$i</option>";
										}
							echo "	</select> / ";
							echo "	<select id=\"finishMonth\" name=\"finishMonth\" size=\"1\" onChange=\"buildsel(this[this.selectedIndex].value,finishYear[finishYear.selectedIndex].value,startDay)\">";
									for($i=1;$i<=12;$i++)
										{
										echo "		<option ";
										if($i==$monthValue)
											{
											echo " selected ";
											}
											echo "value=\"$i\">$i</option>";
										}
							echo "		</select> / ";
							echo "	<select id=\"finishYear\" name=\"finishYear\" size=\"1\" onChange=\"buildsel(finishMonth[finishMonth.selectedIndex].value,this[this.selectedIndex].value,startDay)\">";
									for($i=$yearMin;$i<=$yearMax;$i++)
										{
										echo "		<option ";
										if($i==$yearValue)
											{
											echo " selected ";
											}
											echo "value=\"$i\">$i</option>";
										}
							echo "		</select>";
				echo "	</td>
					</tr>
					<tr>
						<td class=\"question\" valign=\"top\">Submission status:</td>
						<td>
							<input type=\"checkbox\" name=\"chkSaved\" id=\"chkSaved\"".((isset($_POST['chkSaved'])&&$_POST['chkSaved']=='on')?"checked":"")."/><label for=\"chkSaved\">Saved</label><br/>
							<input type=\"checkbox\" name=\"chkSubmitted\" id=\"chkSubmitted\"".((isset($_POST['chkSubmitted'])&&$_POST['chkSubmitted']=='on')?"checked":"")."/><label for=\"chkSubmitted\">Submitted</label>
							<input type=\"hidden\" id=\"surveyID\" name=\"surveyID\" value=\"$surveyID\">
							<input type=\"hidden\" id=\"hNoOfCriteria\" name=\"hNoOfCriteria\" value=\"$noOfCriteria\">
						</td>
					</tr>";		
	echo "	</table>";//set up list boxes to choose questions
		for($i=1;$i<=$noOfCriteria;$i++)
				{
			echo"<div class=\"section\">
					<h3>Analysis criterion $i</h3>
					<table class=\"normal_3\">
						<tr>
							<td class=\"question\">
								Question against which to analyse: 
							</td>
							<td colspan=\"2\" class=\"question\">
								<select id=\"qAnalyseBy_$i\" name=\"qAnalyseBy_$i\" size=\"1\">";
				echo "				<option value=\"0\">Choose a question</option>";
				for($j = 1;$j<=count($aQuestions);$j++)
					{
					if ($aAllowAnalyseByThisQuestion[$j] == true)
						{
						$analyseByName = "qAnalyseBy_" . $i;
						if($_POST[$analyseByName] == $aQuestions[$j][1])
							{
							echo "<option value=\"".$aQuestions[$j][1]."\" selected>" . $aQuestions[$j][0] . "	</option>";
							}
						else
							{
							echo "<option value=\"".$aQuestions[$j][1]."\">" . $aQuestions[$j][0] . "	</option>";
							}
						}
					}
				
				echo "			</select>
							</td>";
				echo "	</tr>";
				//if this is not the first criterion, then we need to remove from qSource those questions which are already being analysed by previous criteria
				if($i>1)
					{
					$aAllowAnalysisOfThisQuestion = array(); //are these qestions allowed as ones to be analysed
					//populate this array with trues
					for($j=1;$j<=count($aQuestions);$j++)
						{
						$aAllowAnalysisOfThisQuestion[$j]=true;
						}
					//fisrt go through each of the previous criteria
					for($j=1;$j<$i;$j++)
						{
						$nameOfHidQuestions = "hidQuestions" . "_$j";
						$aQuestionsToAnalyse = explode(",",$_POST[$nameOfHidQuestions]);
						//iterate through all questions
						for($j=1;$j<=count($aQuestions);$j++)
							{
							//iterate through questions already being analysed in a previous criterion
							for($k=0;$k<count($aQuestionsToAnalyse);$k++)
								{
								if($aQuestions[$j][1]==$aQuestionsToAnalyse[$k])
									{
									//it's already being used in a previous criterion so can't be used again
									$aAllowAnalysisOfThisQuestion[$j]=false;
									}
								}
							}
						}
					}
				//now need to check whether this question has already been selected if this is not the last (or only)criterion
				$aThisQuestionPreviouslySelected = array(); //Array to hold those questions which were selected for analysis before
				//populate this array with falses
				for($j=1;$j<=count($aQuestions);$j++)
					{
					$aThisQuestionPreviouslySelected[$j]=false;
					}
				//First get list of questions selected in this criterion before
				$nameOfHidQuestions = "hidQuestions" . "_$i";
				$aQuestionsToAnalyse = explode(",",$_POST[$nameOfHidQuestions]);
				//now run through questions, checking which was previously selected
				for($j=1;$j<=count($aQuestions);$j++)
					{
					//now check whether this option is listed in the list
					for($k=0;$k<count($aQuestionsToAnalyse);$k++)
						{
						if($aQuestions[$j][1] == $aQuestionsToAnalyse[$k])
							{
							$aThisQuestionPreviouslySelected[$j]=true;
							}
						}
					}
				echo "	<tr>
							<td class=\"question\">
								Questions available for analysis:<br/>
								<select id=\"qSource_$i\" name=\"qSource_$i\" multiple size=\"10\" onDblClick=\"opt_$i.transferRight()\">";
				for($j=1;$j<count($aQuestions)+1;$j++)
					{
					//if this is not the first criterion, then we need to remove from qSource those questions which are already being analysed by previous criteria
					if($i>1)
						{
						if($aAllowAnalysisOfThisQuestion[$j]==true)
							{
							echo "			<option value=\"".$aQuestions[$j][1]."\"".($aThisQuestionPreviouslySelected[$j]==true?" selected":"").">" . $aQuestions[$j][0] . "	</option>";
							}
						}
					else
						{
						echo "			<option value=\"".$aQuestions[$j][1]."\"".($aThisQuestionPreviouslySelected[$j]==true?" selected":"").">" . $aQuestions[$j][0] . "	</option>";
						}
					}
				echo "			</select>
							</td>
							<td valign=\"middle\" align=\"center\">";
								//if this is not the last criterion, disable the buttons which allow its contents to be changed
								//only the laast criterion can be changed at any one time - this prevents
								//problems with the list of questions which can be analysed in subsequent criteria having to change
								//in response to chnaged in questions being analysed in this criterion
								echo"<input type=\"button\" name=\"right\" value=\"&gt;&gt;\" onclick=\"opt_$i.transferRight()\"".($i<$noOfCriteria ? " disabled":"")."><br/><br/>
								<input type=\"button\" name=\"right\" value=\"All &gt;&gt;\" onclick=\"opt_$i.transferAllRight()\"".($i<$noOfCriteria ? " disabled":"")."><br/><br/>
								<input type=\"button\" name=\"left\" value=\"&lt;&lt;\" onclick=\"opt_$i.transferLeft()\"".($i<$noOfCriteria ? " disabled":"")."><br/><br/>
								<input type=\"button\" name=\"left\" value=\"All &lt;&lt;\" onclick=\"opt_$i.transferAllLeft()\"".($i<$noOfCriteria ? " disabled":"").">
							</td>
							<td class=\"question\">
								Questions to be analysed:<br/>
								<select id=\"qDestination_$i\" name=\"qDestination_$i\" multiple size=\"10\" onDblClick=\"opt_$i.transferLeft()\">
								</select>
								<input type=\"hidden\" id=\"hidQuestions_$i\" name=\"hidQuestions_$i\">
							</td>
						</tr>";
				if($i==$noOfCriteria)
							{
							echo"<tr>
								<td colspan=\"3\">
									<input type=\"submit\" value=\"Add analysis criterion\" id=\"bAddCriterion\" name=\"bAddCriterion\" onClick=\"return OnbAddCriterion();\">
									<input type=\"submit\" value=\"Delete criterion\" id=\"bDeleteCriterion\" name=\"bDeleteCriterion\" onClick=\"return OnbAddCriterion();\">
								</td>
							</tr>";
							}
				echo"</table>
				</div>";
				}
			
	echo "	<input type=\"hidden\" id=\"surveyID\" name=\"surveyID\" value=\"$surveyID\">
			<h2>Step 3: Generate Results</h2>
			<br/><input type=\"checkbox\" id=\"chkShowHidden\" name=\"chkShowHidden\"><strong>Show results for hidden questions</strong><br/><br/>
			<input type=\"submit\" value=\"Generate compact results\" id=\"bGenerateCompactResults\" name=\"bGenerateCompactResults\" onClick=\"return OnbGenerateCompactResults();\"><br/><br/>
			<input type=\"submit\" value=\"Generate full results\" id=\"bGenerateFullResults\" name=\"bGenerateFullResults\" onClick=\"return OnbGenerateFullResults();\"><br/><br/>
			<input type=\"submit\" value=\"Generate full Excel results\" id=\"bGenerateExcelResults\" name=\"bGenerateExcelResults\" onClick=\"return OnbGenerateExcelResults();\"><br/><br/>
			<input type=\"submit\" value=\"Generate compact Excel results\" id=\"bGenerateCompactExcelResults\" name=\"bGenerateCompactExcelResults\" onClick=\"return OnbGenerateCompactExcelResults();\"><br/><br/>
		</form>
	</div>";
	//bit of javascript to make sure that right option for start and finish dates is chosen
	echo " <script language=\"JavaScript\">";
			if(isset($_POST['rFinishDate']))
				{
				if($_POST['rFinishDate']==0)
					{
					echo "document.getElementById('rFinishDateNone').click();";
					}
				else
					{
					echo "document.getElementById('rFinishDate').click();";
					}
				}
			else
				{
				echo "document.getElementById('rFinishDateNone').click();";
				}
			if(isset($_POST['rStartDate']))
				{
				if($_POST['rStartDate']==0)
					{
					echo "document.getElementById('rStartDateNone').click();";
					}
				else
					{
					echo "document.getElementById('rStartDate').click();";
					}
				}
			else
				{
				echo "document.getElementById('rStartDateNone').click();";
				}
	echo "</script>";
	}
?>
<?php
	//Breadcrumb
	if(IsAuthor($heraldID))
		{
		require_once("includes/html/BreadCrumbsHeader.html"); 
		echo "<a href=\"results.php\">Results</a> &gt; <strong>View Results</strong>"; 	
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html");
	require_once("includes/instructions/inst_viewresults.php"); 

?>
</body>
</html>
