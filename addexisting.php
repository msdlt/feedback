<?php
	require_once("includes/config.php");
	require_once("includes/getuser.php"); 
	require_once("includes/isauthor.php");
	require_once("includes/issuperauthor.php");
	require_once("includes/limitstring.php");
	//require_once("includes/arethereanyresultsforthisobject.php");
	//require_once("includes/quote_smart.php");
?>
<?php
//********************************************************
//updating or creating surveys
//********************************************************
$surveyID = NULL;
$blockID = NULL;
$sectionID = NULL;
$questionID = NULL;
if (isset($_POST['bAddExisting']))
	{
	//from self - updating changed values
	if(isset($_POST['hSurveyID']))
		{
		$surveyID = $_POST['hSurveyID'];
		$addingWhat = "Block";
		$returnTo = "editsurvey.php?surveyID=$surveyID";
		if(isset($_POST['hBlockID']))
			{
			$blockID = $_POST['hBlockID'];
			$addingWhat = "Section";
			$returnTo = "editblock.php?surveyID=$surveyID&blockID=$blockID";
			if(isset($_POST['hSectionID']))
				{
				$sectionID = $_POST['hSectionID'];
				$addingWhat = "Question";
				$returnTo = "editsection.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID";
				if(isset($_POST['hQuestionID']))
					{
					$questionID = $_POST['hQuestionID'];
					$addingWhat = "Item";
					$returnTo = "editquestion.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID&questionID=$questionID";
					}
				}
			}
		}
	switch ($addingWhat)
		{
		case "Block":
			{
			$qMaxPosition = "	SELECT MAX(position) as maxPosition
								FROM SurveyBlocks
								WHERE surveyID = $surveyID";
			break;
			}
		case "Section":
			{
			$qMaxPosition = "	SELECT MAX(position) as maxPosition
								FROM BlockSections
								WHERE blockID = $blockID";
			break;
			}
		case "Question":
			{
			$qMaxPosition = "	SELECT MAX(position) as maxPosition
								FROM SectionQuestions
								WHERE sectionID = $sectionID";
			break;
			}
		case "Item":
			{
			$qMaxPosition = "	SELECT MAX(position) as maxPosition
								FROM QuestionItems
								WHERE questionID = $questionID";
			break;
			}
		}
	$result_query = @mysqli_query($db_connection, $qMaxPosition);
	if (($result_query == false))
		{
		echo "problem querying qMaxPosition" . mysqli_error($db_connection);
		}
	$rowMaxPosition = mysqli_fetch_array($result_query);
	$position = $rowMaxPosition[maxPosition];
	if ($position == "")
		{
		$position = 0;
		}
	$aObjects = $_POST['checkObjectIDs'];
	for ($i=0;$i<count($aObjects);$i++)
		{
		switch ($addingWhat)
			{
			case "Block":
				{
				$iExistingObject = "INSERT INTO SurveyBlocks
									VALUES(0,$surveyID,$aObjects[$i],$position,1)";
				break;
				}
			case "Section":
				{
				$iExistingObject = "INSERT INTO BlockSections
									VALUES(0,$blockID,$aObjects[$i],$position,1)";
				break;
				}
			case "Question":
				{
				$iExistingObject = "INSERT INTO SectionQuestions
									VALUES(0,$sectionID,$aObjects[$i],$position,1)";
				break;
				}
			case "Item":
				{
				$iExistingObject = "INSERT INTO QuestionItems
									VALUES(0,$questionID,$aObjects[$i],$position,1)";
				break;
				}
			}
		$result_query = @mysqli_query($db_connection, $iExistingObject);
		if (($result_query == false))
			{
			echo "problem insering Existing Object" . mysqli_error($db_connection);
			$bSuccess = false;
			}
		else
			{
			$position++;
			}
		}
	//send the user back to schedulesurvey.php after they have added
	header("Location: https://" . $_SERVER['HTTP_HOST']
			 . dirname($_SERVER['PHP_SELF'])
			 . "/" . $returnTo);
	exit();
	}
//*********************************************************************
//showing all objects which are not currently in the containing object
//*********************************************************************
$surveyID = NULL;
$blockID = NULL;
$sectionID = NULL;
$questionID = NULL;
if(isset($_GET['surveyID']))
	{
	$surveyID = $_GET['surveyID'];
	$pageTitleText = "Add existing block";
	$addingWhat = "Block";
	//Get info about survey
	$qSurvey = "SELECT title, introduction, epilogue, lastModified
				FROM Surveys
				WHERE surveyID = $surveyID";
	$qResSurvey = mysqli_query($db_connection, $qSurvey);
	$rowSurvey = mysqli_fetch_array($qResSurvey);
	$surveyTitle = $rowSurvey['title'];
	$IdName = "blockID";
	if(isset($_GET['blockID']))
		{
		$blockID = $_GET['blockID'];
		$pageTitleText = "Add existing section";
		$addingWhat = "Section";
		//Get info about block
		$qBlock = "SELECT title
					FROM Blocks 
					WHERE blockID = $blockID";
		$qResBlock = mysqli_query($db_connection, $qBlock);
		$rowBlock = mysqli_fetch_array($qResBlock);
		$blockTitle = $rowBlock['title'];
		$IdName = "sectionID";
		if(isset($_GET['sectionID']))
			{
			$sectionID = $_GET['sectionID'];
			$pageTitleText = "Add existing question";
			$addingWhat = "Question";
			//Get info about section
			$qSection = "SELECT title
						FROM Sections 
						WHERE sectionID = $sectionID";
			$qResSection = mysqli_query($db_connection, $qSection);
			$rowSection = mysqli_fetch_array($qResSection);
			$sectionTitle = $rowSection['title'];
			$IdName = "questionID";
			if(isset($_GET['questionID']))
				{
				$questionID = $_GET['questionID'];
				$pageTitleText = "Add existing item";
				$addingWhat = "Item";
				//Get info about question
				$qQuestion = "SELECT title
							FROM Questions 
							WHERE questionID = $questionID";
				$qResQuestion = mysqli_query($db_connection, $qQuestion);
				$rowQuestion = mysqli_fetch_array($qResQuestion);
				$questionTitle = $rowQuestion['title'];
				$IdName = "itemID";
				}
			}
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
<link href="css/msdprint.css" rel="stylesheet" type="text/css" media="print"/>
<script language="JavaScript" src="script/validate.js"></script>
<script language="JavaScript" src="script/OptionTransfer.js"></script>
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
	//Validation of input (called by frm1 OnSubmit())
function ValidateForm(theForm)
	{
	if(!validText(document.getElementById("tTitle"),"survey title",true))
		{
		return false;
		}
	if (cookieStatus()=="on") setCookie("savedData", "true");
	dumpList(document.getElementById('lAuthors'),document.getElementById('hAuthors')); 
	return true;
	}
</script>
</head>

<body>
<?php

if(IsAuthor($heraldID))
	{
	require_once("includes/html/adminheadernew.html");
	require_once("includes/html/BreadCrumbsHeader.html"); 
	echo "
	<a href=\"admin.php\">Administration</a> &gt; 
	<a href=\"editsurvey.php?surveyID=$surveyID\">Edit survey: ".limitString($surveyTitle,30)."</a>"
	.($blockID!=NULL ? " &gt; 
	<a href=\"editblock.php?surveyID=$surveyID&blockID=$blockID\">Edit block: ".limitString($blockTitle,30)."</a>"
	.($sectionID!=NULL ? " &gt; 
	<a href=\"editsection.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID\">Edit section: ".limitString($sectionTitle,30)."</a>"
	.($questionID!=NULL ? " &gt; 
	<a href=\"editquestion.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID&questionID=$questionID\">Edit question: ".limitString($questionTitle,30)."</a>"
	: "") : "") : "")
	." &gt; $pageTitleText</span>";
	require_once("includes/html/BreadCrumbsFooter.html"); 
	}
else
	{
	require_once("includes/html/header.html"); 
	echo "You do not have the necessary permissions to view this page";
	exit();
	}
echo "<a name=\"maintext\" id=\"maintext\"></a>
<h1>$pageTitleText</h1>
";
//get objects
switch ($addingWhat) 
	{
	case "Block":
		{
		//i.e. Blocks not already in Survey
		$qObjects = "SELECT Blocks.blockID, Blocks.title, Blocks.text
					FROM Blocks";
		break;
		}
	case "Section":
		{						
		$qObjects = "SELECT Sections.sectionID, Sections.title, Sections.text
					FROM Sections";
		break;
		}
	case "Question":
		{
		$qObjects = "SELECT Questions.questionID, Questions.title, Questions.text
					FROM Questions";
		break;
		}
	case "Item":
		{
		$qObjects = "SELECT Items.itemID, Items.title, Items.text
					FROM Items";
		break;
		}
	}

$qResObjects = mysqli_query($db_connection, $qObjects);
if (($qResObjects == false))
	{
	echo "problem querying Objects" . mysqli_error($db_connection);
	}
else
	{
	echo "	
	<div class=\"questionNormal\">
		<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">
			<form id=\"frmAdd\" name=\"frmAdd\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
				while($rowObjects = mysqli_fetch_array($qResObjects))
					{
					//check whether this object already existis in this parent
					switch ($addingWhat) 
						{
						case "Block":
							{
							//i.e. is this block already in this survey 
							$qAlreadyInParent = "	SELECT blockID
													FROM SurveyBlocks
													WHERE surveyID = $surveyID
													AND blockID = $rowObjects[$IdName]";
							break;
							}
						case "Section":
							{						
							$qAlreadyInParent = "	SELECT sectionID
													FROM BlockSections
													WHERE blockID = $blockID
													AND sectionID = $rowObjects[$IdName]";
							break;
							}
						case "Question":
							{
							$qAlreadyInParent = "	SELECT questionID
													FROM SectionQuestions
													WHERE sectionID = $sectionID
													AND questionID = $rowObjects[$IdName]";
							break;
							}
						case "Item":
							{
							$qAlreadyInParent = "	SELECT itemID
													FROM QuestionItems
													WHERE questionID = $questionID
													AND itemID = $rowObjects[$IdName]";
							break;
							}
						}
					$qAlreadyInParent = mysqli_query($db_connection, $qAlreadyInParent);
					if ($qAlreadyInParent == false)
						{
						echo "problem querying AlreadyInParent" . mysqli_error($db_connection);
						}
					else if(mysqli_num_rows($qAlreadyInParent) == 0)
						{
					
						$objectID = $rowObjects[$IdName];
						$objectTitle = $rowObjects['title'];
						$objectText = $rowObjects['text'];
				echo "	<tr class=\"matrixHeader\">
							<td>
								<input type=\"checkbox\" id=\"check_$objectID\" name=\"checkObjectIDs[]\" value=\"$objectID\"/>
							</td>
							<td class=\"question\">".$objectTitle."</td>
							<td class=\"question\">".$objectText."</td>
						</tr>";
						//now get children of object
						switch ($addingWhat) 
							{
							case "Block":
								{
								//i.e. Sections in Block
								$qChildrenOfObject = "	SELECT Sections.sectionID, Sections.title
														FROM BlockSections, Sections
														WHERE BlockSections.sectionID = Sections.sectionID
														AND BlockSections.blockID = $rowObjects[$IdName]";
								break;
								}
							case "Section":
								{						
								$qChildrenOfObject = "	SELECT Questions.questionID, Questions.title
														FROM SectionQuestions, Questions
														WHERE SectionQuestions.questionID = Questions.questionID
														AND SectionQuestions.sectionID = $rowObjects[$IdName]";
								break;
								}
							case "Question":
								{
								$qChildrenOfObject = "	SELECT Items.itemID, Items.title
														FROM QuestionItems, Items
														WHERE QuestionItems.itemID = Items.itemID
														AND QuestionItems.questionID = $rowObjects[$IdName]";
								break;
								}
							}
						if ($addingWhat != "Item")
							{
							$qResChildrenOfObject = mysqli_query($db_connection, $qChildrenOfObject);
							if (($qResChildrenOfObject == false))
								{
								echo "problem querying ChildrenOfObject" . mysqli_error($db_connection);
								}
							else
								{
								$bRowOdd = true;
								while($rowChildrenOfObject = mysqli_fetch_array($qResChildrenOfObject))
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
										<td colspan=\"2\" valign=\"top\">".$rowChildrenOfObject['title']."</td>
									</tr>";
									$bRowOdd = !$bRowOdd;
									}
								}
							}
						}
					}
				echo "	<tr>";
				echo "		<td colspan=\"3\">
								<input type=\"submit\" id=\"bAddExisting\" name=\"bAddExisting\" value=\"Add Existing ".$addingWhat."(s)\" onclick=\"return checkCheckBoxes()\"/>
								<input type=\"hidden\" id=\"hSurveyID\" name=\"hSurveyID\" value=\"$surveyID\">"
								.($blockID!=NULL ?"<input type=\"hidden\" id=\"hBlockID\" name=\"hBlockID\" value=\"$blockID\">"
								.($sectionID!=NULL ?"<input type=\"hidden\" id=\"hSectionID\" name=\"hSectionID\" value=\"$sectionID\">"
								.($questionID!=NULL ?"<input type=\"hidden\" id=\"hQuestionID\" name=\"hQuestionID\" value=\"$questionID\">":""):""):"");
				echo "		</td>";
				echo "	</tr>";
					
			echo "	
			</form>
		</table>
	</div>";
	}
echo "	<script language=\"JavaScript\" type=\"text/javascript\" >
		function checkCheckBoxes()
			{
			var iNoOfObjects = 0;
			var aObjects = new Array();";				
//resets the mysql_fetch_array to start at the beginning again
mysqli_data_seek($qResObjects, 0);
while($rowObjects = mysqli_fetch_array($qResObjects))
{
$objectID = $rowObjects[$IdName];
$objectTitle = $rowObjects['title'];
echo "		if (document.getElementById(\"check_$objectID\").checked == true)
				{
				iNoOfObjects=iNoOfObjects+1;
				}";
}
echo "			if (iNoOfObjects == 0)
				{
				alert(\"Please select a ". $addingWhat . " to add.\");
				return false;
				}
			else
				{
				if (iNoOfObjects == 1)
					{
					var confirmText = \"Are you sure you want to add \" + aObjects[iNoOfObjects] + \"?\";
					}
				else
					{
					var confirmText = \"Are you sure you want to add these \" + iNoOfObjects + \" ". $addingWhat . "s?\";
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
		echo "
		<a href=\"admin.php\">Administration</a> &gt; 
		<a href=\"editsurvey.php?surveyID=$surveyID\">Edit survey: ".limitString($surveyTitle,30)."</a>"
		.($blockID!=NULL ? " &gt; 
		<a href=\"editblock.php?surveyID=$surveyID&blockID=$blockID\">Edit block: ".limitString($blockTitle,30)."</a>"
		.($sectionID!=NULL ? " &gt; 
		<a href=\"editsection.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID\">Edit section: ".limitString($sectionTitle,30)."</a>"
		.($questionID!=NULL ? " &gt; 
		<a href=\"editquestion.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID&questionID=$questionID\">Edit question: ".limitString($questionTitle,30)."</a>"
		: "") : "") : "")
		." &gt; $pageTitleText</span>";
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html"); 
	require_once("includes/instructions/inst_addexisting.php"); 

?>
</body>
</html>
