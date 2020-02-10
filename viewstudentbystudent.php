<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>View results by student</title>
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

	//These are routines from http://www.mattkruse.com/javascript/optiontransfer/index.html
	if(isset($_POST['bChooseSurveys']))
		{
		echo "	<script language=\"JavaScript\" src=\"script/OptionTransfer.js\"></script>
				<script language=\"JavaScript\">
					var opt = new OptionTransfer(\"qSource\",\"qDestination\"); 
					opt.setAutoSort(true); 
					opt.saveNewRightOptions(\"hidQuestions\");
				</script>";
		}
	//error_reporting(E_ALL);
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
//enables OK/update button if input has changed
function ControlHasChanged(btnSubmit)
	{
	document.getElementById(btnSubmit).disabled = false;
	//setting a cookie which regsiters any changes since last submit button press
	}

//dynamically filling date lists
function buildsel(Month,Year,DayList,btnSubmit) 
	{
  	ControlHasChanged(btnSubmit);	
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
function toggleStartDate(value, btnSubmit)
	{
	ControlHasChanged(btnSubmit);
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
function toggleFinishDate(value,btnSubmit)
	{
	ControlHasChanged(btnSubmit);
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
function goTo(URL)
		{
		window.location.href = URL;
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

function OnbGenerateExcelResultsByStudent()
	{
	if(ValidateSecondForm()==false)
		{
		return false;
		}
	else
		{
		document.frmAnalyse.action = "showresultsbystudentexcel.php"
		document.frmAnalyse.target = "_self";	
		document.frmAnalyse.submit();
		return true;
		}
	}	

</script>
					
</head>
<body>
<?php
	if(IsAuthor($heraldID))
		{
		require_once("includes/html/adminheadernew.html");
		require_once("includes/html/BreadCrumbsHeader.html"); 
		echo "<a href=\"results.php\">Results</a> &gt; View results by student"; 	
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
echo "<h1>View results by student</h1>";
$qSurveys = "SELECT Surveys.surveyID, Surveys.title
			FROM Surveys, SurveyAuthors, Authors
			WHERE Surveys.surveyID = SurveyAuthors.surveyID
			AND SurveyAuthors.authorID = Authors.authorID
			AND Authors.heraldID = '$heraldID'
			AND Surveys.allowViewByStudent = 'true'";
		
$qResSurveys = @mysqli_query($db_connection, $qSurveys);
echo "<div class=\"block\">
		<h2>Step 1: Choose a survey</h2>		
		<form id=\"frmResults\" name=\"frmResults\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" onSubmit=\"return ValidateForm()\">";

if (($qResSurveys == false))
	{
	echo "problem querying Surveys" . mysqli_error();
	}
else
	{
	echo "<div class=\"questionNormal\">";
	echo "	<table class=\"normal_3\" summary=\"\">";
	echo "		<tr>
					<td>
						<select id=\"ddSurvey\" name=\"ddSurvey\" size=\"1\">						
							<option value=\"0\">
								Choose a survey
							</option>";
	while($rowSurveys = mysqli_fetch_array($qResSurveys))
		{
		//remember the option previously selected
		if(isset($_POST['ddSurvey']) && $_POST['ddSurvey'] == $rowSurveys['surveyID'])
			{
			echo "			<option value=\"".$rowSurveys['surveyID']."\" selected>
								$rowSurveys[title]
							</option>";
			}
		else
			{
			echo "			<option value=\"".$rowSurveys['surveyID']."\">
								$rowSurveys[title]
							</option>";
			}
		}
	echo "				</select>
					</td>
				</tr>";	
	echo "	</table>";
	echo "</div>";
	}
echo "	<input type=\"submit\" value=\"Choose survey\" id=\"bChooseSurveys\" name=\"bChooseSurveys\">	";
echo "</form>
	</div>";


if(isset($_POST['bChooseSurveys']))
	{
	echo "<div class=\"block\">
	<h2>Step 2: Limit results</h2>
	<form id=\"frmAnalyse\" name=\"frmAnalyse\" method=\"post\" onSubmit=\"return ValidateSecondForm()\">";
	
	if (($qResSurveys == false))
		{
		echo "problem querying Surveys" . mysqli_error();
		}
	else
		{
		$btnSubmitName = "bShowStudents";
		$surveyID = $_POST['ddSurvey']; //getting surveyID from POST
		echo "<div class=\"questionNormal\">";
		echo "	<table class=\"normal_3\" summary=\"\">
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
							echo "problem querying SurveyInstances" . mysqli_error();
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
								echo "<option value=\"".$rowSurveyInstances[surveyInstanceID]."\">" . $rowSurveyInstances[title] . " - " . $instanceStartDate . " to "  . $instanceFinishDate . "</option>";
								}
							echo "</select>";
							}
						echo"
						<td>
					</tr>
					<tr>
						<td class=\"question\">Start date:</td>
						<td>";
							$dayValue = intval(date("d"));
							$monthValue = intval(date("n"));
							$yearValue = intval(date("Y"));
							$yearMin = date("Y")-5;
							$yearMax = date("Y")+10;
							echo "	<input type=\"radio\" id=\"rStartDateNone\" name=\"rStartDate\" value=\"0\" onclick=\"toggleStartDate(this.value,'$btnSubmitName')\"><label for=\"rStartDateNone\">None</label>";
							echo "	<input type=\"radio\" id=\"rStartDate\" name=\"rStartDate\" value=\"1\" onclick=\"toggleStartDate(this.value,'$btnSubmitName')\">";
							echo "	<select id=\"startDay\" name=\"startDay\" size=\"1\" onChange=\"ControlHasChanged('$btnSubmitName')\">";
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
							echo "	<select id=\"startMonth\" name=\"startMonth\" size=\"1\" onChange=\"buildsel(this[this.selectedIndex].value,startYear[startYear.selectedIndex].value,startDay,'$btnSubmitName')\">";
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
							echo "	<select id=\"startYear\" name=\"startYear\" size=\"1\" onChange=\"buildsel(startMonth[startMonth.selectedIndex].value,this[this.selectedIndex].value,startDay,'$btnSubmitName')\">";
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
							$dayValue = intval(date("d"));
							$monthValue = intval(date("n"));
							$yearValue = intval(date("Y"));
							$yearMin = date("Y")-5;
							$yearMax = date("Y")+10;
							echo "	<input type=\"radio\" id=\"rFinishDateNone\" name=\"rFinishDate\" value=\"0\" onclick=\"toggleFinishDate(this.value,'$btnSubmitName')\"><label for=\"rFinishDateNone\">None</label>";
							echo "	<input type=\"radio\" id=\"rFinishDate\" name=\"rFinishDate\" value=\"1\" onclick=\"toggleFinishDate(this.value,'$btnSubmitName')\">";
							echo "	<select id=\"finishDay\" name=\"finishDay\" size=\"1\" onChange=\"ControlHasChanged('$btnSubmitName')\">";
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
							echo "	<select id=\"finishMonth\" name=\"finishMonth\" size=\"1\" onChange=\"buildsel(this[this.selectedIndex].value,finishYear[finishYear.selectedIndex].value,startDay,'$btnSubmitName')\">";
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
							echo "	<select id=\"finishYear\" name=\"finishYear\" size=\"1\" onChange=\"buildsel(finishMonth[finishMonth.selectedIndex].value,this[this.selectedIndex].value,startDay,'$btnSubmitName')\">";
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
							<input type=\"checkbox\" name=\"chkSaved\" id=\"chkSaved\"/><label for=\"chkSaved\">Saved</label><br/>
							<input type=\"checkbox\" name=\"chkSubmitted\" id=\"chkSubmitted\"/><label for=\"chkSubmitted\">Submitted</label>
							<input type=\"hidden\" id=\"surveyID\" name=\"surveyID\" value=\"$surveyID\">
						</td>
					</tr>";	
		echo "	</table>";
		echo "</div>";
		}
	echo "	<h2>Step 3: Generate Results</h2>
	<input type=\"checkbox\" id=\"chkShowHidden\" name=\"chkShowHidden\"><strong>Show results for hidden questions</strong><br/><br/>
			<input type=\"submit\" value=\"Show students\" id=\"bGenerateExcelResultsByStudent\" name=\"bGenerateExcelResultsByStudent\" onClick=\"return OnbGenerateExcelResultsByStudent();\">";
	echo "</form>
		</div>";
	//bit of javascript to make sure that right option for start and finish dates is chosen
	
	echo " <script language=\"JavaScript\">";
	echo " 		document.getElementById('rFinishDateNone').click();";
	echo " 		document.getElementById('rStartDateNone').click();";
	echo "</script>";
	}

?>

<?php
	//Breadcrumb
	if(IsAuthor($heraldID))
		{
		require_once("includes/html/BreadCrumbsHeader.html"); 
		echo "<a href=\"results.php\">Results</a> &gt; View results by student"; 	
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html"); 
	require_once("includes/instructions/inst_viewstudents.php"); 
?>
</body>
</html>
