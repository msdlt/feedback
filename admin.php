<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Administration</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="css/SurveyStyles.css">
<link rel="stylesheet" type="text/css" href="css/msdstyle2.css" media="screen"/>
<link href="css/msdprint.css" rel="stylesheet" type="text/css" media="print" />
<script language="javascript" type="text/javascript">
	function goTo(URL)
		{
		window.location.href = URL;
		}
</script>
</head>

<body>
<?php
	require_once("includes/config.php"); 
	require_once("includes/getuser.php");
	require_once("includes/isauthor.php");
	require_once("includes/ODBCDateToTextDate.php");
?>
<?php
if (isset($_POST['delete']))
	{
	//Find out ids of all the checkboxes which were checked
	$aSurveys = $_POST['checkSurveyIDs'];
	for ($i=0;$i<count($aSurveys);$i++)
		{
		//first find out the authors of each survey to be deleted
		$qAuthors = "SELECT Authors.heraldID, Authors.authorID 
			FROM Authors, SurveyAuthors
			WHERE Authors.authorID = SurveyAuthors.authorID
			AND SurveyAuthors.surveyID = $aSurveys[$i]";
		$qResAuthors = mysqli_query($db_connection, $qAuthors);
		if (($qResAuthors == false))
			{
			echo "problem querying Authors" . mysqli_error();
			}
		else
			{
			while($rowAuthorID = mysqli_fetch_array($qResAuthors))
				{
				$iRemoveAuthorID = $rowAuthorID[authorID];
				//then find out whether they are authors in any other survey
				$qIsOtherAuthor = "	SELECT surveyAuthorID 
									FROM SurveyAuthors
									WHERE authorID = $iRemoveAuthorID
									AND surveyID <> $aSurveys[$i]";
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
				//delete from Survey Authors
				$dSurveyAuthor = "	DELETE FROM SurveyAuthors
									WHERE authorID = $iRemoveAuthorID
									AND surveyID = $aSurveys[$i]";
				$dResSurveyAuthor = @mysqli_query($db_connection, $dSurveyAuthor);
				if (($dResSurveyAuthor == false) && mysqli_affected_rows($db_connection) == 0)
					{
					echo "problem deleting from SurveyAuthors " . mysqli_error();
					}
				}
			}
		$delSurveys = "	DELETE FROM Surveys
						WHERE surveyID = $aSurveys[$i]";
		$result_query = @mysqli_query($db_connection, $delSurveys);
		if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
			{
			echo "problem deleting from Surveys" . mysqli_error();
			}
		//Then delete it from SurveyBlocks
		
		//Then delete it from SurveyInstances
		}
	}
//Include config.php which contains db details and connects to the db)
$qSurveys = "SELECT Surveys.surveyID, Surveys.title, Surveys.lastModified 
			FROM Surveys, SurveyAuthors, Authors
			WHERE Surveys.surveyID = SurveyAuthors.surveyID
			AND SurveyAuthors.authorID = Authors.authorID
			AND Authors.heraldID = '$heraldID'";
		
$qResSurveys = @mysqli_query($db_connection, $qSurveys);

if(IsAuthor($heraldID))
	{
	require_once("includes/html/adminheadernew.html");
	require_once("includes/html/BreadCrumbsHeader.html"); 
	echo "Administration";
	require_once("includes/html/BreadCrumbsFooter.html"); 
	}
else
	{
	require_once("includes/html/header.html"); 
	echo "You do not have the necessary permissions to view this page";
	exit();
	}
echo "<a name=\"maintext\" id=\"maintext\"></a>
	<h1>Administration</h1>
		<div class=\"block\">
		<h2>Choose a survey to edit:</h2>
		<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">
			<form id=\"frmEdit\" name=\"frmEdit\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">
			<tr class=\"matrixHeader\">
				<th class=\"question\"></td>
				<th class=\"question\">Name</td>
				<th class=\"question\">Last modified</td>
				<th class=\"question\">Schedule</td>
				<th class=\"question\">Edit</td>
				<th class=\"question\">Preview</td>
			</tr>";
					
if (($qResSurveys == false))
	{
	echo "problem querying Surveys" . mysqli_error();
	}
else
	{
	$bRowOdd = true;
	while($rowSurveys = mysqli_fetch_array($qResSurveys))
		{
		$surveyID = $rowSurveys['surveyID'];
		if($bRowOdd)
			{
			$rowClass = "matrixRowOdd";
			}
		else
			{
			$rowClass = "matrixRowEven";
			}
		echo "<tr class=\"$rowClass\">
			<td>
				<input type=\"checkbox\" id=\"check_$surveyID\" name=\"checkSurveyIDs[]\" value=\"$surveyID\"/>
			</td>
			<td>$rowSurveys[title]</td>
			<td>".ODBCDateToTextDateShort($rowSurveys['lastModified'])."</td>
			<td><input type=\"button\" id=\"scheduleSurvey_".$surveyID."\" name=\"scheduleSurvey_".$surveyID."\" value=\"Schedules\" onClick=\"goTo('schedulesurvey.php?surveyID=$surveyID')\"></td>
			<td><input type=\"button\" id=\"editSurvey_".$surveyID."\" name=\"editSurvey_".$surveyID."\" value=\"Edit\" onClick=\"goTo('editsurvey.php?surveyID=$surveyID')\"></td>
			<td><input type=\"button\" id=\"previewSurvey_".$surveyID."\" name=\"previewSurvey_".$surveyID."\" value=\"Preview\" onClick=\"window.open('previewsurvey.php?surveyID=$surveyID','previewWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes')\"></td>
		</tr>";
		$bRowOdd = !$bRowOdd;
		}
	echo "	<tr>";
	echo "		<td colspan=\"4\">
					<input type=\"submit\" id=\"delete\" name=\"delete\" value=\"Delete\" onclick=\"return checkCheckBoxes()\"/>
					<input type=\"button\" id=\"add\" name=\"add\" value=\"Add\" onClick=\"goTo('editsurvey.php?surveyID=add')\"/>
				</td>";
	echo "	</tr>";
	echo "</form>";
	}	
echo " 		</table>";	
echo "	</div>"	;

echo "	<script language=\"JavaScript\" type=\"text/javascript\" >
			function checkCheckBoxes()
				{
				var iNoOfSurveys = 0;
				var aSurveys = new Array();";				
//resets the mysql_fetch_array to start at the beginning again
mysqli_data_seek($qResSurveys, 0);
while($rowSurveys = mysqli_fetch_array($qResSurveys))
	{
	$surveyID = $rowSurveys['surveyID'];
	$surveyTitle = $rowSurveys[title];
echo "			if (document.getElementById(\"check_$surveyID\").checked == true)
					{
					iNoOfSurveys=iNoOfSurveys+1;
					aSurveys[iNoOfSurveys] = \"$surveyTitle\";
					}";
	}
echo "			if (iNoOfSurveys == 0)
					{
					alert(\"Please select a survey to delete.\");
					return false;
					}
				else
					{
					if (iNoOfSurveys == 1)
						{
						var confirmText = \"Are you sure you want to delete \" + aSurveys[iNoOfSurveys] + \".\";
						}
					else
						{
						var confirmText = \"Are you sure you want to delete these \" + iNoOfSurveys + \" surveys?\";
						}
					var answer = confirm (confirmText);
					if (answer)
						{
						return true;
						}
					else
						{
						return false;
						}
					}
				}
		</script>";

?>
<?php
	//Breadcrumb
	if(IsAuthor($heraldID))
		{
		require_once("includes/html/BreadCrumbsHeader.html"); 
		echo "Administration";
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html"); 
	require_once("includes/instructions/inst_admin.php"); 
?>
</body>
</html>
