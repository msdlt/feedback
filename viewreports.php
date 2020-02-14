<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>View Reports</title>
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
		if (document.getElementById("sSchedule").selectedIndex == 0)
			{
			alert ("Please choose a schedule");
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

function goTo(URL)
		{
		window.location.href = URL;
		}
</script>
					
</head>
<body>
<?php
	if(IsAuthor($heraldID))
		{
		require_once("includes/html/adminheadernew.html");
		require_once("includes/html/BreadCrumbsHeader.html"); 
		echo "<a href=\"results.php\">Results</a> &gt; View Reports"; 	
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
echo "<h1>View Reports</h1>";
$qSurveys = "SELECT Surveys.surveyID, Surveys.title
			FROM Surveys, SurveyAuthors, Authors
			WHERE Surveys.surveyID = SurveyAuthors.surveyID
			AND SurveyAuthors.authorID = Authors.authorID
			AND Authors.heraldID = '$heraldID'";
		
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
	echo "</div>";
	}
echo "	<input type=\"submit\" value=\"Choose survey\" id=\"bChooseSurveys\" name=\"bChooseSurveys\">	";
echo "</form>
	</div>";


if(isset($_POST['bChooseSurveys']))
	{
	echo "<div class=\"block\">
	<h2>Step 2: Limit results</h2>
	<form id=\"frmAnalyse\" name=\"frmAnalyse\" action=\"showlargetextstudents.php\" method=\"post\" onSubmit=\"return ValidateSecondForm()\">";
	
	if (($qResSurveys == false))
		{
		echo "problem querying Surveys" . mysqli_error();
		}
	else
		{
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
								$instanceStartDate = $rowSurveyInstances['startDate'];
								if($instanceStartDate==NULL)
									{
									$instanceStartDate = "Unlimited";
									}
								else
									{
									$instanceStartDate = ODBCDateToTextDateShort($instanceStartDate);
									}
								$instanceFinishDate = $rowSurveyInstances['finishDate'];
								if($instanceFinishDate==NULL)
									{
									$instanceFinishDate = "Unlimited";
									}
								else
									{
									$instanceFinishDate = ODBCDateToTextDateShort($instanceFinishDate);
									}
								echo "<option value=\"".$rowSurveyInstances['surveyInstanceID']."\">" . $rowSurveyInstances['title'] . " - " . $instanceStartDate . " to "  . $instanceFinishDate . "</option>";
								}
							echo "</select>";
							}
						echo"
						<input type=\"hidden\" id=\"surveyID\" name=\"surveyID\" value=\"$surveyID\">
						<td>
					</tr>";					
		echo "	</table>";
		echo "</div>";
		}
	echo "	<h2>Step 3: Generate Results</h2>
			<input type=\"submit\" value=\"Show reports\" id=\"bShowReports\" name=\"bShowReports\">	";
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
		echo "<a href=\"results.php\">Results</a> &gt; View Reports"; 	
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html"); 
	require_once("includes/instructions/inst_viewreports.php"); 
?>
</body>
</html>
