<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Search electives - Quick search</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" type="text/css" href="../css/SurveyStyles.css">
<link rel="stylesheet" type="text/css" href="../css/msdstyle2.css" media="screen"/>
<link href="../css/msdprint.css" rel="stylesheet" type="text/css" media="print" />
	
<?php
require_once("../includes/config.php"); 
require_once("../includes/getuser.php");
require_once("../includes/isauthor.php");
?>
<script language="javascript" type="text/javascript">
function ValidateCountry()
	{
	if (document.getElementById("sItems").selectedIndex == 0)
		{
		alert ("Please choose a country");
		document.getElementById("sItems").focus();
		return false;
		}
	return true;
	}
function ValidateInstitution()
	{
	if (document.getElementById("tInstitution").value == "")
		{
		alert ("Please enter an name");
		document.getElementById("tInstitution").focus();
		return false;
		}
	return true;
	}
</script>
</head>
<body>
<?php
	$surveyID = 18; 
	require_once("includes/html/electiveheader.html");
	require_once("../includes/html/BreadCrumbsHeader.html"); 
	echo "<a href=\"index.php\">Search electives</a> &gt; <strong>Quick search</strong>"; 	
	require_once("../includes/html/BreadCrumbsFooter.html");
?>		
<?php
echo "<a name=\"maintext\" id=\"maintext\"></a>";
echo "<h1>Quick search</h1>";
echo "
<h2>Search electives by host country:</h2>
<div class=\"block\">
	<form id=\"frmCountry\" action=\"electiveresults.php\" method=\"post\" onSubmit=\"return ValidateCountry();\">";
echo "	<div class=\"section\">
			<table class=\"normal_3\">
				<tr>
					<td class=\"question\">Choose a host country:</td>
					<td>";
						$qItems = "	SELECT DISTINCT Items.itemID, Items.text
									FROM SurveyInstances, Items, Answers, AnswerItems, SurveyInstanceParticipants
									WHERE SurveyInstances.surveyID = 18
									AND Answers.surveyInstanceID = SurveyInstances.surveyInstanceID
									AND Answers.blockID = 15
									AND Answers.sectionID = 44
									AND Answers.questionID = 115
									AND AnswerItems.answerID = Answers.answerID
									AND Items.itemID = AnswerItems.itemID
									AND SurveyInstanceParticipants.surveyInstanceID = Answers.surveyInstanceID
									AND SurveyInstanceParticipants.heraldID = Answers.heraldID
									AND SurveyInstanceParticipants.status = 2
									ORDER BY Items.text";
						$qResItems = mysqli_query($db_connection, $qItems);
				echo "	<select id=\"sItems\" name=\"sItems\" size=\"1\">
							<option value=\"0\" selected>Choose a host country..</option>";
						while($rowItems = mysqli_fetch_array($qResItems))
							{
						echo"<option value=\"15_44_115_"."$rowItems[itemID]"."\">".$rowItems['text']."</option>";
							}
				echo "	</select>";
			echo"	</td>
					<td>
						<input type=\"submit\" value=\"Search\" id=\"bSearchByCountry\" name=\"bSearchByCountry\">
						<input type=\"hidden\" id=\"hNoOfCriteria\" name=\"hNoOfCriteria\" value=\"1\">
					</td>
				</tr>
			</table>
		</div>
	</form>
</div>";
echo "
<h2>Search electives by host institution:</h2>
<div class=\"block\">
	<form id=\"frmInstitution\" action=\"electiveresultsfreetext.php\" method=\"post\" onsubmit=\"return ValidateInstitution();\">
		<div class=\"section\">
			<table class=\"normal_3\">
				<tr>
					<td class=\"question\">Enter the name of the institution:</td>
					<td>
						<input type=\"text\" id=\"tInstitution\" name = \"tInstitution\"/>
					</td>
					<td>
						<input type=\"submit\" value=\"Search\" id=\"bSearchByInstitution\" name=\"bSearchByInstitution\">
					</td>
				</tr>
			</table>
		</div>
	</form>
</div>";
?>
<?php
	//Breadcrumb
	require_once("../includes/html/BreadCrumbsHeader.html"); 
	echo "<a href=\"index.php\">Search electives</a> &gt; <strong>Quick search</strong>"; 		
	require_once("../includes/html/BreadCrumbsFooter.html"); 
	//footer - including contact info.
	require_once("../includes/html/footernew.html");
	require_once("../includes/instructions/inst_quicksearch.php"); 

?>
</body>
</html>
