<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Results</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" type="text/css" href="css/SurveyStyles.css">
<link rel="stylesheet" type="text/css" href="css/msdstyle2.css" media="screen"/>
<link href="css/msdprint.css" rel="stylesheet" type="text/css" media="print" />
	
<?php
require_once("includes/config.php"); 
require_once("includes/getuser.php");
require_once("includes/isauthor.php");
require_once("includes/limitstring.php");
?>	
<script type="text/javascript">
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
		echo "Results"; 	
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
echo "
<h1>Results</h1>
<table>
	<tr>
		<td>View a list of students who have saved or submitted a particular survey and schedule.</td>
		<td><input type=\"button\" name=\"bViewStudents\" id=\"bViewStudents\" onClick=\"goTo('viewstudents.php')\" value=\"View students\"/></td>
	</tr>
	<tr>
		<td>View the results of a particular survey and schedule, including simple analysis.</td>
		<td><input type=\"button\" name=\"bViewResults\" id=\"bViewResults\" onClick=\"goTo('viewresults.php')\"/ value=\"View results\"></td>
	</tr>";
	//need to work out whether the person who has logged in is an author for a survey which contains lgtext questions
	//what surveys is this author an author of?
	$qSurveys = "SELECT Surveys.surveyID, Surveys.title, Surveys.allowViewByStudent
			FROM Surveys, SurveyAuthors, Authors
			WHERE Surveys.surveyID = SurveyAuthors.surveyID
			AND SurveyAuthors.authorID = Authors.authorID
			AND Authors.heraldID = '$heraldID'";
	$qResSurveys = @mysqli_query($db_connection, $qSurveys);
	if (($qResSurveys == false))
		{
		echo "problem querying Surveys" . mysqli_error();
		}
	else
		{
		$noOfSurveysWithLgText = 0;
		$noOfSurveysWithAllowViewByStudent = 0;
		while($rowSurveys = mysqli_fetch_array($qResSurveys))
			{
			$surveyID = $rowSurveys[surveyID];
			//find out whether any of these surveys contain lgtext questions
			$qLgTextQuestions = "SELECT Questions.questionID
								FROM Questions, SectionQuestions, BlockSections, SurveyBlocks
								WHERE SurveyBlocks.surveyID = $surveyID
								AND BlockSections.blockID = SurveyBlocks.blockID
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
				$noOfSurveysWithLgText++;
				}
			if($rowSurveys['allowViewByStudent']=="true")
				{
				$noOfSurveysWithAllowViewByStudent++;
				}
			}
		if($noOfSurveysWithLgText>0)
			{
			echo"<tr>
				<td>View the reports of individual students - only for surveys which contain <strong>Report</strong> question types.</td>
				<td><input type=\"button\" name=\"bViewReports\" id=\"bViewReports\" onClick=\"goTo('viewreports.php')\"/ value=\"View Reports\"></td>
				</tr>";
			}
		if($noOfSurveysWithAllowViewByStudent>0)
			{
			echo"<tr>
				<td>View results on a student by student basis.</td>
				<td><input type=\"button\" name=\"bViewStudentByStudent\" id=\"bViewStudentByStudent\" onClick=\"goTo('viewstudentbystudent.php')\"/ value=\"View results by student\"></td>
				</tr>";
			}
		}
	echo"<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>";
	echo"<tr>
		<td>Delete one or more entries from a particular survey and schedule.</td>
		<td><input type=\"button\" name=\"bDeleteResults\" id=\"bDeleteResults\" onClick=\"goTo('deletestudents.php')\"/ value=\"Delete results\"></td>
	</tr>";
	
echo"	
</table>
<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
";

?>
<?php
	//Breadcrumb
	if(IsAuthor($heraldID))
		{
		require_once("includes/html/BreadCrumbsHeader.html"); 
		echo "Results"; 	
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html");
	require_once("includes/instructions/inst_results.php"); 

?>
</body>
</html>
