<?php
	require_once("includes/config.php"); 
	require_once("includes/getuser.php");
	require_once("includes/isauthor.php");
	require_once("includes/quote_smart.php");
	require_once("includes/limitstring.php");
?>
<?php
$validationProblem = false;
if (isset($_POST['bUpdate'])||isset($_POST['bCreate']))
	{
	//from self - updating changed values
	if(isset($_POST['hSurveyID']))
		{
		$surveyID = $_POST['hSurveyID'];
		$surveyInstanceID = $_POST['hSurveyInstanceID'];
		$updateTitle = quote_smart($_POST['tTitle']);
		if($_POST['rStartDate']==0)
			{
			$startDate="NULL";
			$updateStartDate="NULL";
			}
		else
			{
			$startDate = date("Y-m-d", mktime(0, 0, 0, $_POST['startMonth'], $_POST['startDay'], $_POST['startYear'])); 
			$updateStartDate = quote_smart($startDate);
			}
		if($_POST['rFinishDate']==0)
			{
			$finishDate="NULL";
			$updateFinishDate="NULL";
			}
		else
			{
			$finishDate = date("Y-m-d", mktime(0, 0, 0, $_POST['finishMonth'], $_POST['finishDay'], $_POST['finishYear']));
			$updateFinishDate = quote_smart($finishDate);
			}
		$updateLastModified = "CURDATE()";
		
		//**************************************************************************************
		//Server-side validation of input
		//**************************************************************************************
		
		/* Checking and ensure that the schedule title does not already exist in the database */
		$sql_title_check = mysqli_query($db_connection, "SELECT title FROM SurveyInstances
										WHERE title=$updateTitle" . ($surveyInstanceID !="add" ? "AND surveyInstanceID <> $surveyInstanceID" : ""));
		$title_check = mysqli_num_rows($sql_title_check);
		
		//first check that dates are 'valid'
		$dateProblem = false;
		//checking that finishDate > startDate
		if ($startDate!="NULL")
			{
			if (!checkDate($_POST['startMonth'], $_POST['startDay'], $_POST['startYear']))
				{
				$dateProblem = true;
				$dateProblemText = "The start date you have chosen is not valid. Please choose a different date and try again.";
				}
			else if ($finishDate!="NULL")
				{
				if (!checkDate($_POST['finishMonth'], $_POST['finishDay'], $_POST['finishYear']))
					{
					$dateProblem = true;
					$dateProblemText = "The finish date you have chosen is not valid. Please choose a different date and try again.";
					}
				else if($startDate>=$finishDate)
					{
					$dateProblem = true;
					$dateProblemText = "The finish date must be later than the start date. Please choose a different date and try again.";
					}
				}
			}
		if($title_check > 0 || $updateTitle == "" || $dateProblem)
			{
			$validationProblem = true;
			//show button again
			$btnSubmitName = "bCreate";
			$btnSubmitText = "Create schedule";
			}
			//**************************************************************************************
			//End of server-side validation of input
			//**************************************************************************************		
		else
			{
			//everything's OK therefore write to database
			if($surveyInstanceID =="add")
				{
				$iSurveyInstances = "INSERT INTO SurveyInstances
									VALUES(0,$surveyID,$updateTitle,$updateStartDate,$updateFinishDate)";
				$result_query = @mysqli_query($db_connection, $iSurveyInstances);
				$surveyInstanceID = mysqli_insert_id();
				if ($result_query == false)
					{
					echo "problem inserting into SurveyInstances" . mysqli_error();
					$bSuccess = false;
					}
				}
			else
				{
				//Update survey with values from this form
				$uSurveyInstances = "UPDATE SurveyInstances
						SET surveyID = $surveyID,
						title = $updateTitle,
						startDate = $updateStartDate,
						finishDate = $updateFinishDate
						WHERE surveyInstanceID = $surveyInstanceID";
				$result_query = @mysqli_query($db_connection, $uSurveyInstances);
				if ($result_query == false)
					{
					echo "problem updating SurveyInstances" . mysqli_error();
					$bSuccess = false;
					}
				}
			//send the user back to schedulesurvey.php after they have added
			header("Location: https://" . $_SERVER['HTTP_HOST']
                     . dirname($_SERVER['PHP_SELF'])
                     . "/" . "schedulesurvey.php?surveyID=".$surveyID);
			exit();
			}
		}
	}
else if(isset($_GET['surveyID']))
	{
	//check if editing a schedule or adding a new one
	if($_GET['surveyInstanceID']=="add")
		{
		$surveyID = $_GET['surveyID'];
		$surveyInstanceID = $_GET['surveyInstanceID'];
		$btnSubmitName = "bCreate";
		$btnSubmitText = "Create schedule";
		}
	else
		{
		//from schedulesurvey.php
		$surveyID = $_GET['surveyID'];
		$surveyInstanceID = $_GET['surveyInstanceID'];
		$btnSubmitName = "bUpdate";
		$btnSubmitText = "Update schedule";
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
<title>Administration</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="css/SurveyStyles.css">
<link rel="stylesheet" type="text/css" href="css/msdstyle2.css" media="screen"/>
<link href="css/msdprint.css" rel="stylesheet" type="text/css" media="print" />
<script language="JavaScript" src="script/validate.js"></script>
<script language="javascript" type="text/javascript">
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
//enables OK/update button if input has changed
function ControlHasChanged(btnSubmit)
	{
	document.getElementById(btnSubmit).disabled = false;
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
function ValidateForm(theForm)
	{
	if(!validText(document.getElementById("tTitle"),"survey title",true))
		{
		return false;
		}
	if (cookieStatus()=="on") setCookie("savedData", "true");
	if (CheckDates() ==false) return false;
	return true;
	}
//checks that finish date is later than start date
function CheckDates()
	{
	if (document.getElementById("rStartDate").checked)
		{
		dateObs1 = new Date(document.getElementById("startYear").value,document.getElementById("startMonth").value,document.getElementById("startDay").value);
		}
	else
		{
		dateObs1 = null;
		}
	if (document.getElementById("rFinishDate").checked)
		{
		dateObs2 = new Date(document.getElementById("finishYear").value,document.getElementById("finishMonth").value,document.getElementById("finishDay").value);
		}
	else
		{
		dateObs2 = null;
		}
	if (dateObs1!=null && dateObs2!=null && dateObs2.getTime() <= dateObs1.getTime()) /*..if it does, make sure it is later than the first date*/
		{
		alert("dateObs1 = " + dateObs1 + "dateObs2 = " + dateObs2);
		alert("The finish date must be later than the start date");
		return false;
		}
	}

</script>
</head>

<body onBeforeUnload="return CheckSaved('You have made changes to the schedule properties. Please save them before moving away from this page')">
<?php
//Get info about survey
$qSurveys = "	SELECT title, introduction, epilogue, lastModified
				FROM Surveys
				WHERE surveyID = $surveyID";
$qResSurvey = mysqli_query($db_connection, $qSurveys);
$rowSurvey = mysqli_fetch_array($qResSurvey);
$surveyTitle = $rowSurvey[title];
if($surveyInstanceID!="add")
	{
	$qSurveyInstances = "	SELECT title, startDate, finishDate
							FROM SurveyInstances
							WHERE surveyInstanceID = $surveyInstanceID";
	$qResSurveyInstance = mysqli_query($db_connection, $qSurveyInstances);
	$rowSurveyInstance = mysqli_fetch_array($qResSurveyInstance);
	$surveyInstanceTitle = $rowSurveyInstance[title];
	$surveyInstanceStartDate = $rowSurveyInstance[startDate];
	$surveyInstanceFinishDate = $rowSurveyInstance[finishDate];
	}
elseif ($validationProblem == true)
	{
	$surveyInstanceTitle = $_POST['tTitle'];
	$surveyInstanceStartDate = date("Y-m-d", mktime(0, 0, 0, $_POST['startMonth'], $_POST['startDay'], $_POST['startYear'])); 
	$surveyInstanceFinishDate = date("Y-m-d", mktime(0, 0, 0, $_POST['finishMonth'], $_POST['finishDay'], $_POST['finishYear']));
	}
else
	{
	$surveyInstanceTitle = "New schedule";
	$surveyInstanceStartDate = date("Y-m-d");
	$surveyInstanceFinishDate = date("Y-m-d");
	}

if(IsAuthor($heraldID))
	{
	require_once("includes/html/adminheadernew.html");
	require_once("includes/html/BreadCrumbsHeader.html"); 
	echo "
	<a href=\"admin.php\">Administration</a> &gt; 
	<a href=\"schedulesurvey.php?surveyID=$surveyID\">Schedule survey: ".limitString($surveyTitle,30)."</a> &gt; 
	Schedule: ".limitString($surveyInstanceTitle,30)."";
	require_once("includes/html/BreadCrumbsFooter.html"); 
	}
else
	{
	require_once("includes/html/header.html"); 
	echo "You do not have the necessary permissions to view this page";
	exit();
	}
echo "<a name=\"maintext\" id=\"maintext\"></a>";
echo "<h1>Schedule: $surveyInstanceTitle</h1>";
echo "<form id=\"frmEdit\" name=\"frmEdit\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" onSubmit=\"return ValidateForm(this)\">
<div class=\"block\">
<h2>Survey schedule properties:</h2>";
if($validationProblem==true)
	{
	echo "<strong>Please fix the following errors:</strong><br/>";
	if($title_check > 0)
		{
		echo "This schedule title is already in use in our database. Please submit a different title and try again.<br/>";
		}
	if($updateTitle == "")
		{
		echo "You must enter a title for this schedule. Please enter a title and try again.<br/>";
		}
	if($dateProblem)
		{
		echo $dateProblemText . "<br/>";
		}
	}
echo "<table class=\"normal_3\" summary=\"\">
	<tr>
		<td>Survey title:</td>
		<td>
			<input type=\"text\" id=\"tTitle\" name=\"tTitle\" value=\"$surveyInstanceTitle\" size=\"50\" onChange=\"ControlHasChanged('$btnSubmitName')\">
		</td>
	</tr>
	<tr>
		<td valign=\"top\">Start date:</td>
		<td>";
			$aDate = explode("-",$surveyInstanceStartDate);
			$dayValue = intval($aDate[2]);//date("d",$surveyInstanceStartDate);
			$monthValue = intval($aDate[1]);//date("m",$surveyInstanceStartDate);
			$yearValue = intval($aDate[0]);//date("Y",$surveyInstanceStartDate);
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
		<td valign=\"top\">Finish date:</td>
		<td>
			";
			$aDate = explode("-",$surveyInstanceFinishDate);
			$dayValue = intval($aDate[2]);//date("d",$surveyInstanceStartDate);
			$monthValue = intval($aDate[1]);//date("m",$surveyInstanceStartDate);
			$yearValue = intval($aDate[0]);//date("Y",$surveyInstanceStartDate);
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
		<td colspan=\"2\">
			<input type=\"hidden\" value=\"$surveyID\" id=\"hSurveyID\" name=\"hSurveyID\">
			<input type=\"hidden\" value=\"$surveyInstanceID\" id=\"hSurveyInstanceID\" name=\"hSurveyInstanceID\">";
	echo "	<input type=\"submit\" value=\"$btnSubmitText\" id=\"$btnSubmitName\" name=\"$btnSubmitName\">";
echo"
		</td>
	</tr>";
echo " </table>";
echo " </div>";
echo " </form>";

//bit of javascript to make sure that right option for start and finish dates is chosen

echo " <script language=\"JavaScript\">";
	if($surveyInstanceFinishDate==NULL)
		{
echo " 		document.getElementById('rFinishDateNone').click();";
		}
	else
		{
echo " 		document.getElementById('rFinishDate').click();";
		}
	if($surveyInstanceStartDate==NULL)
		{
echo " 		document.getElementById('rStartDateNone').click();";
		}
	else
		{
echo " 		document.getElementById('rStartDate').click();";
		}
	echo " 	document.getElementById('$btnSubmitName').disabled = true;";
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
		<a href=\"schedulesurvey.php?surveyID=$surveyID\">Schedule survey: ".limitString($surveyTitle,30)."</a> &gt; 
		Schedule: ".limitString($surveyInstanceTitle,30)."";
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html");
	require_once("includes/instructions/inst_editsurveyinstance.php"); 
?>
</body>
</html>
