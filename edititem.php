<?php
	require_once("includes/config.php");
	require_once("includes/getuser.php"); 
	require_once("includes/isauthor.php");
	require_once("includes/issuperauthor.php");
	require_once("includes/limitstring.php");
	require_once("includes/quote_smart.php");
	require_once("includes/ODBCDateToTextDate.php");
?>
<?php
function addItem($text, $title, $lastModified, $questionID)
	{
	global $db_connection; //makes this available within the function
	//Create new question with values from this form
	$iItems = "	INSERT INTO Items
					VALUES(0,$text,$title,$lastModified)";
	$result_query = @mysqli_query($iItems,$db_connection);
	$itemID = mysqli_insert_id();
	if (($result_query == false))
		{
		echo "problem inserting into Items" . mysqli_error();
		$bSuccess = false;
		}
	//Now need to add it to QuestionItems
	//first work out position to add it to
	$qMaxPosition = "	SELECT MAX(position) as maxPosition
						FROM QuestionItems
						WHERE questionID = $questionID";
	$result_query = @mysqli_query($qMaxPosition,$db_connection);
	if (($result_query == false))
		{
		echo "problem querying qMaxPosition" . mysqli_error();
		}
	$rowMaxPosition = mysqli_fetch_array($result_query);
	$maxPosition = $rowMaxPosition[maxPosition];
	if ($maxPosition == "")
		{
		//there are no existing items in this question
		$position = 0;
		}
	else
		{
		//there are existing items in this question so increment
		$position = $maxPosition + 1;
		}
	$iQuestionItems = "	INSERT INTO QuestionItems
						VALUES(0,$questionID,$itemID,$position,1)";
	$result_query = @mysqli_query($db_connection, $iQuestionItems);
	if (($result_query == false))
		{
		echo "problem insering into QuestionItems" . mysqli_error();
		$bSuccess = false;
		}
	}




if ((isset($_POST['bUpdate'])&& $_POST['bUpdate']!= "")||(isset($_POST['bCreate'])&& $_POST['bCreate']!= "")||(isset($_POST['bUpload'])&& $_POST['bUpload']!= ""))
	{
	if(isset($_POST['hItemID']))
		{
		$surveyID = $_POST['hSurveyID'];
		$blockID = $_POST['hBlockID'];
		$sectionID = $_POST['hSectionID'];
		$questionID = $_POST['hQuestionID'];
		$itemID = $_POST['hItemID'];
		if($_POST['rSeparator']==1)
			{
			$strSeparator = ",";
			}
		else
			{
			$strSeparator = chr(9);
			}
		if($itemID=="upload")
			{
			$aItemRows = explode(chr(10),$_POST['tText']);
			}
		else
			{
			$aItemRows[0] = $_POST['tTitle'] . $strSeparator . $_POST['tText'];
			}
		$validationProblem = false;
		$i=0;
		while($i<count($aItemRows) && $validationProblem==false)
			{	
			//on this first run through the data, we are simply validating it
			//it's worth doing this to prevent half the data being written before finding an error
			$aItemComponents = explode($strSeparator,$aItemRows[$i]);
			$updateText = quote_smart($aItemComponents[1]);
			$updateTitle = quote_smart($aItemComponents[0]);
			//**********************************************************
			//Server-side validation of data entered - 
			//**********************************************************
			/* Checking and ensure that the item title does not already exist in the database */
			$sql_title_check = mysqli_query("SELECT title FROM Items
											WHERE title=$updateTitle " . ($itemID !="add" && $itemID !="upload"?"AND itemID <> $itemID ":""));
											//that last if statement allows edited item to have the same name as it had before
											//but prevents added items having the same name as an existing item.
			$title_check = mysqli_num_rows($sql_title_check);
			if($title_check > 0 || $updateTitle == "" || $updateText=="" || count($aItemComponents)!=2)
				{
				$validationProblem = true;
				}
			//**********************************************************
			//End server-side validation of data entered 
			//**********************************************************
			$i++;
			}
		if($validationProblem != true)
			{
			$i=0;
			while($i<count($aItemRows) && $validationProblem==false)
				{	
				//on this second run through, now that initial validation is complete, we will actually write it to the database
				$aItemComponents = explode($strSeparator,$aItemRows[$i]);
				$updateText = quote_smart($aItemComponents[1]);
				$updateTitle = quote_smart($aItemComponents[0]);
				$updateLastModified = "CURDATE()";
				//Still need to check for repeat item titles as there could be repeats within the data pasted in
				/* Checking and ensure that the item title does not already exist in the database */
				$sql_title_check = mysqli_query("SELECT title FROM Items
												WHERE title=$updateTitle " . ($itemID !="add" && $itemID !="upload"?"AND itemID <> $itemID ":""));
												//that last if statement allows edited item to have the same name as it had before
												//but prevents added items having the same name as an existing item.
				$title_check = mysqli_num_rows($sql_title_check);
				if($title_check > 0)
					{
					$validationProblem = true;
					}
				//Validated - go ahead with updating/writing
				if($itemID =="add" || $itemID=="upload")
					{
					addItem($updateText, $updateTitle, $updateLastModified, $questionID);
					}
				else
					{
					///Update section with values from this form
					$uItems = "	UPDATE Items
								SET text = $updateText,
								title = $updateTitle,
								lastModified = $updateLastModified
								WHERE itemID = $itemID";
					$result_query = @mysqli_query($uItems,$db_connection);
					if (($result_query == false))
						{
						echo "problem updating Items" . mysqli_error();
						$bSuccess = false;
						}
					}
				$i++;
				}
			//send the user back to schedulesurvey.php after they have added
			header("Location: https://" . $_SERVER['HTTP_HOST']
					 . dirname($_SERVER['PHP_SELF'])
					 . "/" . "editquestion.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID&questionID=$questionID");
			exit();
			}
		}
	}
else if (isset($_GET['surveyID'])&&isset($_GET['blockID'])&&isset($_GET['sectionID'])&&isset($_GET['questionID'])&&isset($_GET['itemID']))
	{
	$surveyID = $_GET['surveyID'];
	$blockID = $_GET['blockID'];
	$sectionID = $_GET['sectionID'];
	$questionID = $_GET['questionID'];
	$itemID = $_GET['itemID'];
	}
else
	{
echo "<h1>Warning</h1>
	<p>This page requires data from another page.</p>";
	exit();
	}
if($itemID=="add")
	{
	$btnSubmitName = "bCreate";
	$btnSubmitText = "Create item";
	$pageTitleText = "Create item";
	}
elseif($itemID=="upload")
	{
	$btnSubmitName = "bUpload";
	$btnSubmitText = "Upload items";
	$pageTitleText = "Upload items";
	}
else
	{
	$btnSubmitName = "bUpdate";
	$btnSubmitText = "Update item";
	$pageTitleText = "Edit item";
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
<?php	
	if($itemID!="upload")
		{
		echo"if(!validText(document.getElementById(\"tTitle\"),\"item name\",true))return false;";
		}
	elseif($itemID=="upload")
		{
		echo"if(!document.getElementById(\"rSeparatorTab\").checked && !document.getElementById(\"rSeparatorComma\").checked)
			{
			alert(\"Please choose a separator type\");
			document.getElementById(\"rSeparatorTab\").focus();
			return false;
			}";
		}
?>
	if(!validText(document.getElementById("tText"),"item text",true))return false;
	if (cookieStatus()=="on") setCookie("savedData", "true");
	return true;
	}
</script>
</head>

<body onBeforeUnload="return CheckSaved('You have made changes to the item properties. Please save them before moving away from this page')">
<?php
//Get info about survey
$qSurveys = "SELECT title
			FROM Surveys
			WHERE surveyID = $surveyID";
$qResSurvey = mysqli_query($qSurveys);
$rowSurvey = mysqli_fetch_array($qResSurvey);
$surveyTitle = $rowSurvey[title];
//Get info about block
$qBlocks = "SELECT title
			FROM Blocks
			WHERE blockID = $blockID";
$qResBlock = mysqli_query($qBlocks);
$rowBlock = mysqli_fetch_array($qResBlock);
$blockTitle = $rowBlock[title];
//Get info about section
$qSections = "	SELECT title
				FROM Sections
				WHERE sectionID = $sectionID";
$qResSections = mysqli_query($qSections);
$rowSection = mysqli_fetch_array($qResSections);
$sectionTitle = $rowSection[title];
//get info about question
$qQuestions = "	SELECT title
				FROM Questions
				WHERE questionID = $questionID";
$qResQuestions = mysqli_query($qQuestions);
$rowQuestion = mysqli_fetch_array($qResQuestions);
$questionTitle = $rowQuestion[title];
//Get info about item
if($itemID!="add" && $itemID!="upload")
	{
	$qItems = "	SELECT title, text, lastModified 
				FROM Items
				WHERE itemID = $itemID";
	$qResItems = mysqli_query($qItems);
	$rowItem = mysqli_fetch_array($qResItems);
	$itemTitle = $rowItem[title];
	$itemText = $rowItem[text];
	$itemLastModified = $rowItem[lastModified];
	}
elseif($validationProblem == true)
	{
	$itemTitle = $_POST['tTitle'];
	$itemText = $_POST['tText'];
	}
else
	{
	$itemTitle = "New item";
	$itemText = "New item";
	}

				
if(IsAuthor($heraldID))
	{
	require_once("includes/html/adminheadernew.html");
	require_once("includes/html/BreadCrumbsHeader.html"); 
	echo "
	<a href=\"admin.php\">Administration</a> &gt;
	<a href=\"editsurvey.php?surveyID=$surveyID\">Edit survey: ".limitString($surveyTitle,30)."</a> &gt; 
	<a href=\"editblock.php?surveyID=$surveyID&blockID=$blockID\">Edit block: ".limitString($blockTitle,30)."</a> &gt; 
	<a href=\"editsection.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID\">Edit section: ".limitString($sectionTitle,30)."</a> &gt; 
	<a href=\"editquestion.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID&questionID=$questionID\">Edit question: ".limitString($questionTitle,30)."</a> &gt; 
	$pageTitleText: ".limitString($itemTitle,30);
	require_once("includes/html/BreadCrumbsFooter.html"); 
	}
else
	{
	require_once("includes/html/header.html"); 
	echo "You do not have the necessary permissions to view this page";
	exit();
	}
echo "<a name=\"maintext\" id=\"maintext\"></a>
<h1>$pageTitleText: $questionTitle</h1>
<form id=\"frmEdit\" name=\"frmEdit\" action=\"$PHP_SELF\" method=\"post\" onSubmit=\"return ValidateForm(this)\">
<h2>Item properties:</h2>
<div class=\"questionNormal\">";
if($validationProblem==true)
	{
	echo "<span class=\"errorMessage\"><strong>Please fix the following errors:</strong><br/>";
	if($title_check > 0)
		{
		echo "This item name ('$updateTitle') is already in use in our database. Please enter a different name (e.g. prefix with the name of your survey) and try again.<br/>";
		}
	if($updateTitle == "")
		{
		echo "You must enter a name for this item. Please enter a name and try again.<br/>";
		}
	if($updateText == "")
		{
		echo "You must enter some text for this item. Please enter some text and try again.<br/>";
		}
	if(count($aItemComponents)!=2)
		{
		echo "There is a problem with the data you are uploading at around line $i, please check that you have specified your separator correctly and that each item begins on a new line.<br/>";
		}
	echo "</span><br/>";
	}
echo"
<table class=\"normal_3\" summary=\"\">";
if($itemID=="upload")
	{
	//uploading items
	echo "
	<tr>
		<td valign=\"top\">Paste items to be uploaded here:</td>
		<td>
			<textarea id=\"tText\" name=\"tText\" rows=\"10\" cols=\"35\" onChange=\"ControlHasChanged('$btnSubmitName')\">$itemText</textarea>
		</td>
	</tr>
	<tr>
		<td>Separators:</td>
		<td>
			<input type=\"radio\" id=\"rSeparatorTab\" name=\"rSeparator\" value=\"0\" size=\"50\"><label for=\"rSeparatorTab\">Tab</label>
			<input type=\"radio\" id=\"rSeparatorComma\" name=\"rSeparator\" value=\"1\" size=\"50\"><label for=\"rSeparatorComma\">Comma</label>
		</td>
	</tr>";
	}
else
	{
	echo "
	<tr>
		<td>Name:</td>
		<td>
			<input type=\"text\" id=\"tTitle\" name=\"tTitle\" value=\"$itemTitle\" size=\"50\" onChange=\"ControlHasChanged('$btnSubmitName')\">
		</td>
	</tr>
	<tr>
		<td>Last modified:</td>
		<td>".ODBCDateToTextDateShort($itemLastModified)."</td>
	</tr>
	<tr>
		<td valign=\"top\">Item text:</td>
		<td>
			<textarea id=\"tText\" name=\"tText\" rows=\"5\" cols=\"35\" onChange=\"ControlHasChanged('$btnSubmitName')\">$itemText</textarea>
		</td>
	</tr>";
	}
	echo"
	<tr>
		<td clospan=\"2\">
			<input type=\"hidden\" value=\"$surveyID\" id=\"hSurveyID\" name=\"hSurveyID\">
			<input type=\"hidden\" value=\"$blockID\" id=\"hBlockID\" name=\"hBlockID\">
			<input type=\"hidden\" value=\"$sectionID\" id=\"hSectionID\" name=\"hSectionID\">
			<input type=\"hidden\" value=\"$questionID\" id=\"hQuestionID\" name=\"hQuestionID\">
			<input type=\"hidden\" value=\"$itemID\" id=\"hItemID\" name=\"hItemID\">
			<input type=\"submit\" value=\"$btnSubmitText\" id=\"$btnSubmitName\" name=\"$btnSubmitName\">
			<input type=\"button\" id=\"btnPreviewSurvey\" name=\"btnPreviewSurvey\" value=\"Preview\" onClick=\"window.open('previewsurvey.php?surveyID=$surveyID','previewWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes')\">
		</td>
	</tr>
		";
echo " </table>";
echo " </div>";

//bit of javascript to make sure that right option for start and finish dates is chosen

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
		<a href=\"editblock.php?surveyID=$surveyID&blockID=$blockID\">Edit block: ".limitString($blockTitle,30)."</a> &gt; 
		<a href=\"editsection.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID\">Edit section: ".limitString($sectionTitle,30)."</a> &gt; 
		<a href=\"editquestion.php?surveyID=$surveyID&blockID=$blockID&sectionID=$sectionID&questionID=$questionID\">Edit question: ".limitString($questionTitle,30)."</a> &gt; 
		$pageTitleText: ".limitString($itemTitle,30);
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	//footer - including contact info.
	require_once("includes/html/footernew.html"); 
	require_once("includes/instructions/inst_edititem.php"); 

?>

</body>
</html>
