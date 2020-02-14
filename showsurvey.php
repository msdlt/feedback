<?php
require_once("includes/config.php"); 
require_once("includes/getuser.php");
require_once("includes/isauthor.php");
require_once("includes/quote_smart.php");
require_once("includes/ODBCDateToTextDate.php");
//require_once("includes/gettextoutput.php");
require_once("includes/getinstancesforblockandsection.php");
require_once("includes/renderstringforjavascript.php");

//error_reporting(E_ALL);

//check that we are coming from one of the accepted referring pages (defined in config.php)
//$referer = getenv('http_referer');
//$refererIsOK = false;
//if(!empty($referer))
//	{
//	for($i=0;$i<count($aPermittedReferers);$i++)
//		{
//		if (strpos($referer,$aPermittedReferers[$i])!=FALSE)
//			{
//			$refererIsOK = true;
//			} 
//		}
//	}
$refererIsOK = true;
if ($refererIsOK==true || IsAuthor($heraldID))
	{
	//ie. authors don't have to come from a certain referrer
	//check that we have a surveyInstanceID
	if ((isset($_POST['surveyInstanceID']) && $_POST['surveyInstanceID']!="")||(isset($_GET['surveyInstanceID']) && $_GET['surveyInstanceID']!=""))
		{
		//pass surveyInstanceID on
		if(isset($_GET['surveyInstanceID']))
			{
			$surveyInstanceID = $_GET['surveyInstanceID'];
			}
		else
			{
			$surveyInstanceID=$_POST['surveyInstanceID'];
			}
		//find out which survey this is an instance of
		$qSurvey = "	SELECT surveyID, title, startDate, finishDate  
						FROM SurveyInstances
						WHERE surveyInstanceID = $surveyInstanceID";
		$qResSurvey = mysqli_query($db_connection, $qSurvey);
		if (($qResSurvey == false))
			{
			echo "problem querying SurveyInstances" . mysqli_error($db_connection);
			}
		else
			{
			$rowSurvey = mysqli_fetch_array($qResSurvey);
			$surveyID = $rowSurvey['surveyID'];
			$surveyInstanceTitle = $rowSurvey['title'];
			if($rowSurvey['startDate'] == "")
				{
				$instanceStartDate = "null";
				}
			else
				{
				$instanceStartDate = $rowSurvey['startDate'];
				}
			if($rowSurvey['finishDate'] == "")
				{
				$instanceFinishDate = "null";
				}
			else
				{
				$instanceFinishDate = $rowSurvey['finishDate'];
				}
			
			//****************************************************************************************************************
			//First read the blocks, sections, questions and items into arrays so that we do not need to repeatedly query them
			//[] = block
			//[][] = section
			//[][][] = question
			//[][][][] = item
			//****************************************************************************************************************
			$aItems = array();
			$qBlocks = "	SELECT Blocks.blockID, Blocks.text, Blocks.introduction, Blocks.epilogue, SurveyBlocks.position, Blocks.instanceable
							FROM Blocks, SurveyBlocks 
							WHERE SurveyBlocks.surveyID = $surveyID
							AND SurveyBlocks.visible = 1
							AND Blocks.blockID = SurveyBlocks.blockID
							ORDER BY SurveyBlocks.position";
			$qResBlocks = mysqli_query($db_connection, $qBlocks);
			$iBlock = 1;
			while($rowBlocks = mysqli_fetch_array($qResBlocks))
				{
				$aItems[$iBlock][0]["blockID"] = $rowBlocks['blockID'];
				$aItems[$iBlock][0]["text"] = $rowBlocks['text'];
				$aItems[$iBlock][0]["introduction"] = $rowBlocks['introduction'];
				$aItems[$iBlock][0]["epilogue"] = $rowBlocks['epilogue'];
				$aItems[$iBlock][0]["position"] = $rowBlocks['position'];
				$aItems[$iBlock][0]["instanceable"] = $rowBlocks['instanceable'];
				$qSections = "	SELECT Sections.sectionID, Sections.text, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, Sections.instanceable 
								FROM Sections, BlockSections 
								WHERE BlockSections.blockID = ".$rowBlocks['blockID']."
								AND BlockSections.visible = 1
								AND Sections.sectionID = BlockSections.sectionID
								ORDER BY BlockSections.position";
				$qResSections = mysqli_query($db_connection, $qSections);
				$iSection = 1;
				while($rowSections = mysqli_fetch_array($qResSections))
					{
					$aItems[$iBlock][$iSection][0]["sectionID"] = $rowSections['sectionID'];
					$aItems[$iBlock][$iSection][0]["text"] = $rowSections['text'];
					$aItems[$iBlock][$iSection][0]["introduction"] = $rowSections['introduction'];
					$aItems[$iBlock][$iSection][0]["epilogue"] = $rowSections['epilogue'];
					$aItems[$iBlock][$iSection][0]["position"] = $rowSections['position'];
					$aItems[$iBlock][$iSection][0]["sectionTypeID"] = $rowSections['sectionTypeID'];
					$aItems[$iBlock][$iSection][0]["instanceable"] = $rowSections['instanceable'];
					$qQuestions = "	SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position 
									FROM Questions, SectionQuestions
									WHERE SectionQuestions.sectionID = ".$rowSections['sectionID']."
									AND SectionQuestions.visible = 1
									AND Questions.questionID = SectionQuestions.questionID
									ORDER BY SectionQuestions.position";
					$qResQuestions = mysqli_query($db_connection, $qQuestions);
					$iQuestion = 1;
					while($rowQuestions = mysqli_fetch_array($qResQuestions))
						{
						$aItems[$iBlock][$iSection][$iQuestion][0]["questionID"] = $rowQuestions['questionID'];
						$aItems[$iBlock][$iSection][$iQuestion][0]["comments"] = $rowQuestions['comments'];
						$aItems[$iBlock][$iSection][$iQuestion][0]["questionTypeID"] = $rowQuestions['questionTypeID'];
						$aItems[$iBlock][$iSection][$iQuestion][0]["text"] = $rowQuestions['text'];
						$aItems[$iBlock][$iSection][$iQuestion][0]["position"] = $rowQuestions['position'];
						$qItems = "	SELECT Items.itemID, Items.text, QuestionItems.position
									FROM Items, QuestionItems
									WHERE QuestionItems.questionID = ".$rowQuestions['questionID']."
									AND QuestionItems.visible = 1
									AND Items.itemID = QuestionItems.itemID
									ORDER BY QuestionItems.position";
						$qResItems = mysqli_query($db_connection, $qItems);
						$iItem = 1;
						while($rowItems = mysqli_fetch_array($qResItems))
							{
							$aItems[$iBlock][$iSection][$iQuestion][$iItem]["itemID"] = $rowItems['itemID'];
							$aItems[$iBlock][$iSection][$iQuestion][$iItem]["text"] = $rowItems['text'];
							$aItems[$iBlock][$iSection][$iQuestion][$iItem]["position"] = $rowItems['position'];
							$iItem++;
							}						
						$iQuestion++;
						}
					$iSection ++;
					}
				$iBlock ++;
				}
			//print_r($aItems);
			}
		}
	else
		{
		//if not don't give any URL parameter - just pass them back to the index page.
		header("Location: index.php");
		}
	}
else
	{
	echo "Please access your survey through Weblearn";
	exit();
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<!--<style type="text/css" media="all">
	@import "script/widgEditor/css/widgEditor.css";
</style>
<script language="javascript" src="script/widgEditor/scripts/widgEditor.js"></script>-->
<!--<script type="text/javascript" src="script/fckeditor/fckeditor.js"></script>-->
<!--<script type="text/javascript" src="script/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>-->
<script type="text/javascript" src="script/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="ckfinder/ckfinder.js"></script>
<script language="javascript" type="text/javascript">
	function getElementsByClass(searchClass,node,tag) 
		{
		//from http://www.dustindiaz.com/getelementsbyclass/
		var classElements = new Array();
		if ( node == null ) node = document;
		if ( tag == null ) tag = '*';
		var els = node.getElementsByTagName(tag);
		var elsLen = els.length;
		var pattern = new RegExp("(^|\\s)"+searchClass+"(\\s|$)");
		for (i = 0, j = 0; i < elsLen; i++) 
			{
			if ( pattern.test(els[i].className) ) 
				{
				classElements[j] = els[i];
				j++;
				}
			}
			return classElements;
		}
		
	function bodyOnLoad(){
		CKFinder.setupCKEditor( null, 'https://learntech.imsu.ox.ac.uk/feedback/ckfinder/' );
		}
	
	//tinyMCE.init({
        //mode : "specific_textareas",
		//theme : "advanced",
		//theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,|,bullist,numlist,|,outdent,indent,|,undo,redo",
        //theme_advanced_buttons2 : "",
        //theme_advanced_buttons3 : "",
        //theme_advanced_buttons4 : "",
        //theme_advanced_toolbar_location : "top",
		//theme_advanced_toolbar_align : "left",
		//width : "100%",
        //editor_selector : "richtext"
	//});
	
	//chnages all textareas with the given class to fckeditors on load
	//function changeTextAreasToFCK()
      	//{
		//var allTextAreas = getElementsByClass('richtext',document,'textarea');
		//for (var i=0; i < allTextAreas.length; i++) 
			//{
			//var oFCKeditor = new FCKeditor( allTextAreas[i].name ) ;
			//oFCKeditor.BasePath = "script/fckeditor/" ;
			//oFCKeditor.ToolbarSet = 'ElectiveReport' ;
			//oFCKeditor.ReplaceTextarea() ;
			//}
		//}
		
	function displayErrorMesage(qNumber, qText)
		{
		alert('You have not completed all of the questions. Please answer Q' + qNumber + ' - "' + qText + '" before clicking Submit again.')
		}
	//dynamically filling date lists
	function buildsel(Month,Year,DayList) 
		{
		//Calculate no of days in month
		days = daysInMonth(Month,Year);
		// get reference for list that is being updated
		sel=DayList;
		//remember currently selected day
		selectedDay=sel.selectedIndex;
		/* delete entries for list that is being updated */
		for (i=0; i<sel.length; i++)
			{
			sel.options[i] = null;
			}
		/* add new entries to list that is being updated */
		if (selectedDay>days)
			{
			selectedDay=days;
			}
		sel.options[0] = new Option('dd',0);
		for (i=1; i<=days; i++)
			{
			sel.options[i] = new Option(i,i);
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
function goTo(URL)
		{
		window.location.href = URL;
		}
</script>
<?php 
	
	//used to track whether any data are 
	$iNoOfMissingAnswers = 0;
	//**************************************************************
	//DEALING WITH BRANCHES
	//**************************************************************
	//******************************************************************
	//Functions to show and hide branches
	//******************************************************************	
	echo "	<script language=\"javascript\" type=\"text/javascript\">";
	echo "		function showAndHideForMCHOIC(sShow,sHide)
					{
					if(sShow!=\"\")
						{
						aShow = sShow.split(\"+\");
						showDivs(aShow);
						}
					if(sHide!=\"\")
						{
						aHide = sHide.split(\"+\");
						hideDivs(aHide);
						}
					}
				function showAndHideForMSELEC(CheckBox,sItems)
					{
					if(sItems!=\"\")
						{
						aItems = sItems.split(\"+\");
						if(CheckBox.checked == true)
							{
							showDivs(aItems);
							}
						else
							{
							hideDivs(aItems);
							}	
						}	
					}
				function showAndHideForDRDOWN(selectedItem,branchItem,sItems)
					{
					if(sItems!=\"\")
						{
						aItemsArray = sItems.split(\"|\");
						}
					if(branchItem!=\"\")
						{
						abranchItems = branchItem.split(\"|\");
						var i = 0;
						while (i<abranchItems.length)
							{
							aItems = aItemsArray[i].split(\"+\");
							if(selectedItem == abranchItems[i])
								{
								showDivs(aItems);
								}
							else
								{
								hideDivs(aItems);
								}		
							i++;
							}
						}
					}
				function showDivs(aShow)
					{
					for(var j=0;j<aShow.length;j++)
						{
						if (document.getElementById(aShow[j]))
							{
							document.getElementById(aShow[j]).style.display=\"block\";
							}
						}
					}
				function hideDivs(aHide)
					{
					for(var j=0;j<aHide.length;j++)
						{
						if (document.getElementById(aHide[j]))
							{
							document.getElementById(aHide[j]).style.display=\"none\";
							}
						}
					}";
	//**************************************************************
	//END DEALING WITH BRANCHES
	//**************************************************************
	echo "	</script>";
	
	//***********************************************************************
	// CHECKING THAT ALL QUESTIONS HAVE BEEN ANSWERED - CLIENT-SIDE VALIDATION
	//***********************************************************************
	echo "<script language=\"javascript\" type=\"text/javascript\">
				//create a function which is called by the Add button before the form is submitted to change the value of hAdded to true;
				function sethAddedToTrue()
					{
					document.getElementById('hAdded').value = true;
					}
				//create a function which is called by the Delete button before the form is submitted to change the value of hDeleted to true;
				function sethDeletedToTrue()
					{
					document.getElementById('hDeleted').value = true;
					}
				//create a function which is called by the SaveSurvey button before the form is submitted to change the value of hSaved to true;
				function sethSavedToTrue()
					{
					alert(\"Your answers are being saved. Don't forget to return to this survey and submit your final answers.\");
					document.getElementById('hSaved').value = true;
					}
				function ValidateForm()
					{";
					//$qBlocks = "	SELECT Blocks.blockID, Blocks.title, Blocks.introduction, Blocks.epilogue, SurveyBlocks.position, Blocks.instanceable
									//FROM Blocks, SurveyBlocks 
									//WHERE SurveyBlocks.surveyID = $surveyID
									//AND SurveyBlocks.visible = 1
									//AND Blocks.blockID = SurveyBlocks.blockID
									//ORDER BY SurveyBlocks.position";
					//$qResBlocks = mysql_query($qBlocks);
					$questionNo = 1;
					echo "var aQuestions = new Array();";
					for ($iBlock=1; $iBlock<=count($aItems);$iBlock++)
					//while($rowBlocks = mysql_fetch_array($qResBlocks))
						{
						//$blockID = $rowBlocks['blockID'];
						$blockID = $aItems[$iBlock][0]["blockID"];
						$blockIsInstanceable = false;
						//if($rowBlocks[instanceable]==1)
						if($aItems[$iBlock][0]["instanceable"]==1)
							{
							$blockIsInstanceable = true;
							if (isset($_POST[bAddInstance . "_" . $blockID]))
								{
								$noOfCurrentInstances = $_POST[hCurrentInstance . "_" . $blockID];
								$noOfInstances = $noOfCurrentInstances + 1;
								}
							elseif (isset($_POST[bDeleteInstance . "_" . $blockID]))
								{
								$noOfCurrentInstances = $_POST[hCurrentInstance . "_" . $blockID];
								$noOfInstances = $noOfCurrentInstances - 1;
								}
							else
								{
								$noOfInstances = getInstancesForBlock($blockID);
								}
							}
						else
							{
							$blockIsInstanceable = false;
							$noOfInstances = 1;
							}
						//$qSections = "	SELECT Sections.sectionID, Sections.title, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, Sections.instanceable 
										//FROM Sections, BlockSections 
										//WHERE BlockSections.blockID = $blockID
										//AND BlockSections.visible = 1
										//AND Sections.sectionID = BlockSections.sectionID
										//ORDER BY BlockSections.position";
						//$qResSections = mysql_query($qSections);
						for($inst=1;$inst<=$noOfInstances;$inst++)
							{
							//if(mysql_num_rows($qResSections)>0)
								//{
								//mysql_data_seek($qResSections, 0);
								//}
							for($iSection=1; $iSection<=count($aItems[$iBlock])-1;$iSection++)
							//while($rowSections = mysql_fetch_array($qResSections))
								{
								//$sectionID = $rowSections['sectionID'];
								$sectionID = $aItems[$iBlock][$iSection][0]["sectionID"];
								$sectionIsInstanceable = false;
								//if($rowSections[instanceable]==1)
								if($aItems[$iBlock][$iSection][0]["instanceable"]==1)
									{
									$sectionIsInstanceable = true;
									if (isset($_POST[bAddSectionInstance . "_" . $blockID . "_" . $sectionID."_i".$inst]))
										{
										$noOfCurrentSectionInstances = $_POST[hCurrentSectionInstance . "_" . $blockID . "_" . $sectionID."_i".$inst];
										$noOfSectionInstances = $noOfCurrentSectionInstances + 1;
										}
									elseif (isset($_POST[bDeleteSectionInstance . "_" . $blockID . "_" . $sectionID."_i".$inst]))
										{
										$noOfCurrentSectionInstances = $_POST[hCurrentSectionInstance . "_" . $blockID . "_" . $sectionID."_i".$inst];
										$noOfSectionInstances = $noOfCurrentSectionInstances - 1;
										}
									else
										{
										$noOfSectionInstances = getInstancesForSection($blockID,$sectionID,$inst);
										}
									}
								else
									{
									$sectionIsInstanceable = false;
									$noOfSectionInstances = 1;
									}
								//$qQuestions = "	SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position 
												//FROM Questions, SectionQuestions
												//WHERE SectionQuestions.sectionID = $sectionID
												//AND SectionQuestions.visible = 1
												//AND Questions.questionID = SectionQuestions.questionID
												//ORDER BY SectionQuestions.position";
								$qResQuestions = mysqli_query($db_connection, $qQuestions);
								for($sinst=1;$sinst<=$noOfSectionInstances;$sinst++)
									{
									//if(mysql_num_rows($qResQuestions)>0)
										//{
										//mysql_data_seek($qResQuestions, 0);
										//}
									for($iQuestion=1; $iQuestion<=count($aItems[$iBlock][$iSection])-1;$iQuestion++)
									//while($rowQuestions = mysql_fetch_array($qResQuestions))
										{
										//$questionID = $rowQuestions['questionID'];
										$questionID = $aItems[$iBlock][$iSection][$iQuestion][0]["questionID"];
										echo "aQuestions[".$questionNo."]=new Array(8);\n";
										echo "aQuestions[".$questionNo."][0]='".$blockID."_".$sectionID."_".$questionID."_i".$inst."_si".$sinst."';\n";//questionID
										echo "aQuestions[".$questionNo."][1]=".$questionNo.";\n";
										//now we need to escape any charcaters which might cause the javascript to throw an error
										$escapedText = renderStringForJavaScript($aItems[$iBlock][$iSection][$iQuestion][0]["text"]);
										echo "aQuestions[".$questionNo."][2]='".$escapedText."';\n";	
										//echo "aQuestions[".$questionNo."][3]=".$rowQuestions['questionTypeID'].";\n";
										echo "aQuestions[".$questionNo."][3]=".$aItems[$iBlock][$iSection][$iQuestion][0]["questionTypeID"].";\n";
										//$qItems = "	SELECT Items.itemID, Items.text, QuestionItems.position
													//FROM Items, QuestionItems
													//WHERE QuestionItems.questionID = $questionID
													//AND QuestionItems.visible = 1
													//AND Items.itemID = QuestionItems.itemID
													//ORDER BY QuestionItems.position";
										//$qResItems = mysql_query($qItems);
										//$itemArraySize = mysql_num_rows($qResItems) + 1;
										$itemArraySize = count($aItems[$iBlock][$iSection][$iQuestion]);
										if($itemArraySize>0)
											{
											echo "aQuestions[".$questionNo."][4]=new Array(".$itemArraySize.");\n";
											//$itemNo = 1;
											for($iItem=1; $iItem<=count($aItems[$iBlock][$iSection][$iQuestion])-1;$iItem++)
											//while($rowItems = mysql_fetch_array($qResItems))
												{
												//$itemID = $rowItems[itemID];
												$itemID = $aItems[$iBlock][$iSection][$iQuestion][$iItem]["itemID"];
												echo "aQuestions[".$questionNo."][4][".$iItem."]='".$blockID."_".$sectionID."_".$questionID."_".$itemID."_i".$inst."_si".$sinst."';\n";
												//$itemNo++;
												}
											}
										echo "aQuestions[".$questionNo."][5]='div".$blockID."_i".$inst."';\n";
										echo "aQuestions[".$questionNo."][6]='div".$blockID."_".$sectionID."_i".$inst."_si".$sinst."';\n";
										//don't asign a question div for matrix questoins, these canot be branched to individually
										if ($aItems[$iBlock][$iSection][0]["sectionTypeID"] == 1)									
											{
											echo "aQuestions[".$questionNo."][7]='div".$blockID."_".$sectionID."_".$questionID."_i".$inst."_si".$sinst."';\n";
											}
										else
											{
											echo "aQuestions[".$questionNo."][7]=null;\n";
											}
										$questionNo++;
										}
									}
								}
							}
						}
					
	echo "			if (document.getElementById('hAdded').value == 'true')
						{
						//don't validate if we're just adding an instance
						return true;
						}
					if (document.getElementById('hDeleted').value == 'true')
						{
						//don't validate if we're just adding an instance
						return true;
						}
					else if (document.getElementById('hSaved').value == 'true')
						{
						//don't validate if the user is saving rather than submitting their data
						return true;
						}
					else
						{
						for(i=1;i<aQuestions.length;i++)
							{
							//check whether question is visible to the user
							var questionIsVisible = false;
							if(document.getElementById(aQuestions[i][5]).style.display!=\"none\" && document.getElementById(aQuestions[i][6]).style.display!=\"none\")
								{
								if (aQuestions[i][7] != null)
									{
									if(document.getElementById(aQuestions[i][7]).style.display!=\"none\")
										{
										questionIsVisible = true;
										}
									}
								else
									{
									questionIsVisible = true;
									}
								}
							if(questionIsVisible)
								{
								if(aQuestions[i][3]==1) // || aQuestions[i][3]==2) Don't requirte an answer to a multiple choice
									{
									var itemCount = 0;
									for(j=1;j<=aQuestions[i][4].length-1;j++)
										{
										if(document.getElementById(aQuestions[i][4][j]).checked)
											{
											itemCount++;
											}
										}
									if(itemCount == 0)
										{
										alert('Please answer the question: ' + aQuestions[i][1] + ': \"' + aQuestions[i][2] + '\" before clicking the \"Submit\" button.');
										document.getElementById(aQuestions[i][4][1]).focus();
										return(false);
										}
									}
								else if(aQuestions[i][3]==3)
									{
									if(document.getElementById(aQuestions[i][0]).options[0].selected == true)
										{
										alert('Please answer the question: ' + aQuestions[i][1] + ': \"' + aQuestions[i][2] + '\" before clicking the \"Submit\" button.');
										document.getElementById(aQuestions[i][0]).focus();
										return(false);
										}
									}
								else if(aQuestions[i][3]==6)
									{
									var dayID = 'day_' + aQuestions[i][0];
									var monthID = 'month_' + aQuestions[i][0];
									var yearID = 'year_' + aQuestions[i][0];
									if(document.getElementById(dayID).options[0].selected == true || document.getElementById(monthID).options[0].selected == true || document.getElementById(yearID).options[0].selected == true)
										{
										alert('Please answer the question: ' + aQuestions[i][1] + ': \"' + aQuestions[i][2] + '\" before clicking the \"Submit\" button.');
										document.getElementById(dayID).focus();
										return(false);
										}
									}
								}
							}
						return(true);
						}
					";
					
	echo "			}";
	echo "		</script>";
	//***********************************************************************
	// END CHECKING THAT ALL QUESTIONS HAVE BEEN ANSWERED
	//***********************************************************************
	
	
	$qSurveys = "SELECT title, introduction, epilogue, allowSave, allowViewByStudent
				FROM Surveys
				WHERE surveyID = $surveyID";
	$qResSurvey = mysqli_query($db_connection, $qSurveys);
	$rowSurvey = mysqli_fetch_array($qResSurvey);
	$surveyTitle = $rowSurvey['title'];
	$surveyIntroduction = $rowSurvey['introduction'];
	$surveyEpilogue = $rowSurvey['epilogue'];
	$surveyAllowSave = $rowSurvey['allowSave'];
	$allowViewByStudent = $rowSurvey['allowViewByStudent'];
	
	echo "<title>$surveyInstanceTitle</title>";
?>	


	<link rel="stylesheet" type="text/css" href="css/SurveyStyles.css"></link>
<?php
//if(IsAuthor($heraldID))
//	{
	echo"<link rel=\"stylesheet\" type=\"text/css\" href=\"css/msdstyle2.css\" media=\"screen\"/>";
//	}
//else
//	{
//	echo"<link rel=\"stylesheet\" type=\"text/css\" href=\"css/msdstyle2student.css\" media=\"screen\"/>";
//	}
?>
	<link  rel="stylesheet" type="text/css" href="css/msdprint.css" media="print" />
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

</head>

<!--<body onload="changeTextAreasToFCK()">-->
<body onLoad="bodyOnLoad()">
<?php
//*******************************************************************************************************************************
//BEGIN WRITING SURVEY RESULTS
//*******************************************************************************************************************************
$allAnswered = true;
if (isset($_POST['bSubmitSurvey'])||isset($_POST['bSaveSurvey']))
	{
	//***********************************************************************
	// Writes $text to AnswerComments table for $answerID
	//***********************************************************************
	function writeComment($answerID,$text,&$bSuccess,&$textOutput)
		{
		global $db_connection; //makes this available within the function
		$text = quote_smart($db_connection, $text);
		//now need to check whether the record exists in the AnswerComments table
		$qAnswerComments = "SELECT answerCommentID
						FROM AnswerComments
						WHERE answerID = $answerID";

		$qResAnswerComments = mysqli_query($db_connection, $qAnswerComments);
		$textOutput = $textOutput ."<tr><td></td><td colspan=\"2\">";
		//if so, write the text into the textarea
		$textOutput = $textOutput . $text;
		$textOutput = $textOutput ."</td></tr>";
		if (mysqli_num_rows($qResAnswerComments)==1)
			//if it does, update it
			{
			$rowAnswerComments = mysqli_fetch_array($qResAnswerComments);
			$uAnswerComments = "UPDATE AnswerComments
							SET answerID = $answerID,
							text = $text
							WHERE answerCommentID = $rowAnswerComments[answerCommentID]";
			$result_query = @mysqli_query($db_connection,$uAnswerComments);
			//note: don't check if mysqli_affected_rows($db_connection) == 0 for
			//updates as my_sql returns 0 if the updated values are the same as 
			//those already there.
			if (($result_query == false))
				{
				echo "problem updating AnswerComments" . mysqli_error($db_connection);
				$bSuccess = false;
				}
			}
		else if (mysqli_num_rows($qResAnswerComments)==0)
			{//if not, insert it
				$iAnswerComments = "INSERT INTO AnswerComments
					VALUES(0,$text,$answerID)";
			$result_query = @mysqli_query($db_connection, $iAnswerComments);
			if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
				{
				echo "problem inserting into AnswerComments" . mysqli_error($db_connection);
				$bSuccess = false;
				}
			}
		else
			{
			//generate an error message
			echo "There appears to be more than one entry for this answerID in AnswerComments.";
			$bSuccess = false;
			}
		}
	//***********************************************************************
	// Writes $date to AnswerDates table for $answerID
	//***********************************************************************
	function writeDate($answerID,$date,&$bSuccess,&$textOutput)
		{
		global $db_connection; //makes this available within the function
		$date = quote_smart($db_connection, $date);
		//now need to check whether the record exists in the AnswerComments table
		$qAnswerDates = "SELECT answerDateID
						FROM AnswerDates
						WHERE answerID = $answerID";

		$qResAnswerDates = mysqli_query($db_connection, $qAnswerDates);
		$textOutput = $textOutput . "<td>";
		$textOutput = $textOutput . $date;
		$textOutput = $textOutput . "</td></tr>";
		if (mysqli_num_rows($qResAnswerDates)==1)
			//if it does, update it
			{
			$rowAnswerDates = mysqli_fetch_array($qResAnswerDates);
			$uAnswerDates = "UPDATE AnswerDates
							SET answerID = $answerID,
							date = $date
							WHERE answerDateID = $rowAnswerDates[answerDateID]";
			$result_query = @mysqli_query($db_connection, $uAnswerDates);
			//note: don't check if mysqli_affected_rows($db_connection) == 0 for
			//updates as my_sql returns 0 if the updated values are the same as 
			//those already there.
			if (($result_query == false))
				{
				echo "problem updating AnswerDates" . mysqli_error($db_connection);
				$bSuccess = false;
				}
			}
		else if (mysqli_num_rows($qResAnswerDates)==0)
			{//if not, insert it
				$iAnswerDates = "INSERT INTO AnswerDates
					VALUES(0,$date,$answerID)";
			$result_query = @mysqli_query($db_connection, $iAnswerDates);
			if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
				{
				echo "problem inserting into AnswerDates" . mysqli_error($db_connection);
				$bSuccess = false;
				}
			}
		else
			{
			//generate an error message
			echo "There appears to be more than one entry for this answerID in AnswerDates.";
			$bSuccess = false;
			}
		}
	//***********************************************************************
	// Writes $itemID to AnswerItems table for $answerID
	//***********************************************************************
	function writeItem($answerID,$itemID,&$bSuccess,&$textOutput)
		{
		//echo "(".$answerID." ".$itemID."<br/>";
		global $db_connection; //makes this available within the function
		//now need to check whether the record exists in the AnswerItems table
		//ensure $answerID and itemID are ints
		$answerID=intval($answerID);
		$itemID=intval($itemID);
		$qAnswerItems = "SELECT answerItemID
						FROM AnswerItems
						WHERE answerID = $answerID";
		//print_r($qAnswerItems);
		$qResAnswerItems = mysqli_query($db_connection, $qAnswerItems);
		//print_r($qResAnswerItems);
		$qItems = "	SELECT text
					FROM Items
					WHERE itemID = $itemID";
		$qResItems = mysqli_query($db_connection, $qItems);
		$rowItems = mysqli_fetch_array($qResItems);
		$textOutput = $textOutput . "<td>";
		$textOutput = $textOutput .$rowItems['text'];
		$textOutput = $textOutput . "</td></tr>";
		if (mysqli_num_rows($qResAnswerItems)==1)
			//if it does, update it
			{
			//$rowAnswerItems = mysql_fetch_array($qResAnswerItems,$db_connection);
			$rowAnswerItems = mysqli_fetch_array($qResAnswerItems);
			//print_r($rowAnswerItems);
			//print_r($rowAnswerItems);
			$answerItemID = intval($rowAnswerItems[0]);//seemed to be coming in as a string?!!
			$uAnswerItems = "UPDATE AnswerItems
							SET answerID = $answerID,
							itemID = $itemID
							WHERE answerItemID = $answerItemID";
			//print_r($uAnswerItems);
			$result_query = @mysqli_query($db_connection, $uAnswerItems);
			if (($result_query == false))
				{
				echo "problem updating AnswerItems: " . mysqli_error($db_connection);
				$bSuccess = false;
				}
			}
		else if (mysqli_num_rows($qResAnswerItems)==0)
			{//if not, insert it
				$iAnswerItems = "INSERT INTO AnswerItems
					VALUES(0,$itemID,$answerID)";
			$result_query = @mysqli_query($db_connection, $iAnswerItems);
			if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
				{
				echo "problem inserting into AnswerItems: " . mysqli_error($db_connection);
				$bSuccess = false;
				}
			}
		else
			{
			//generate an error message
			echo "There appears to be more than one entry for this answerID in AnswerItems.";
			$bSuccess = false;
			}
		}
	function writeCheckBoxItem($answerID,$aItems,$questionID, $sectionID, $blockID, $inst, $sinst, &$bSuccess,&$textOutput)
		{
		global $db_connection; //makes this available within the function
		//now go through and check for any existing items for this answerID
		$qAnswerItems = "SELECT answerItemID
						FROM AnswerItems
						WHERE answerID = $answerID";
		$qResAnswerItems = mysqli_query($db_connection, $qAnswerItems);
		if (mysqli_num_rows($qResAnswerItems)>0)
			//if any records exist, delete them
			{
			while($rowAnswerItems = mysqli_fetch_array($qResAnswerItems))
				{
				$answerItemID = $rowAnswerItems['answerItemID'];
				$delAnswerItems = "DELETE FROM AnswerItems
								WHERE answerItemID = $answerItemID";
				$result_query = @mysqli_query($db_connection, $delAnswerItems);
				if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
					{
					echo "problem deleting from Answers" . mysqli_error($db_connection);
					$bSuccess = false;
					}
				}
			}
		$textOutput = $textOutput . "<td>";
		
		//get item ids and text for this question
		$qItems = "	SELECT Items.itemID, Items.text
					FROM Items, QuestionItems, SectionQuestions, BlockSections
					WHERE BlockSections.blockID = $blockID
					AND BlockSections.sectionID = SectionQuestions.sectionID
					AND SectionQuestions.sectionID = $sectionID
					AND SectionQuestions.questionID = QuestionItems.questionID
					AND QuestionItems.questionID = $questionID
					AND Items.itemID = QuestionItems.itemID";
					
		$qResItems = mysqli_query($db_connection, $qItems);
		while($rowItems = mysqli_fetch_array($qResItems))
			{
			$mselecName = $blockID . "_" . $sectionID . "_" . $questionID. "_" . $rowItems['itemID'] . "_i" . $inst . "_si" . $sinst;
			//echo $mselecName;
			if(isset($_POST[$mselecName]) && $_POST[$mselecName]=="on")
				{
				$textOutput = $textOutput . " " . $rowItems["text"];
				$itemID = $rowItems['itemID'];
				$iAnswerItems = "INSERT INTO AnswerItems
								VALUES(0,$itemID,$answerID)";
				$result_query = @mysqli_query($db_connection, $iAnswerItems);
				if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
					{
					echo "problem inserting into AnswerItems: " . mysqli_error($db_connection);
					$bSuccess = false;
					}
				}
			}
		
		
		//run through each item and insert new answerItems
		//for($iItem=1; $iItem<=count($aItems[$iBlock][$iSection][$iQuestion])-1;$iItem++)
			//{
			//$itemID = $aItems[$iBlock][$iSection][$iQuestion][$iItem]["itemID"];
			//$mselecName = $blockID . "_" . $sectionID . "_" . $questionID. "_" . $itemID . "_i" . $inst . "_si" . $sinst;
			//echo $mselecName;
			//if(isset($_POST[$mselecName]) && $_POST[$mselecName]=="on")
				//{
				//$textOutput = $textOutput . " " . $aItems[$iBlock][$iSection][$iQuestion][$iItem]["text"];
				//$iAnswerItems = "INSERT INTO AnswerItems
				//				VALUES(0,$itemID,$answerID)";
				//$result_query = @mysql_query($iAnswerItems,$db_connection);
				//if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
				//	{
				//	echo "problem inserting into AnswerItems: " . mysqli_error($db_connection);
				//	$bSuccess = false;
				//	}
				//}
			//}
		$textOutput = $textOutput . "</td></tr>";
		}
	//***************************************************************************
	//CHECKING THAT ALL QUESTIONS HAVE BEEN ANSWERED - SERVER-SIDE VALIDATION 
	//ESSENTIALLY A PHP-REWRITE OF THE CLIENT-SIDE VALIDATION
	//***************************************************************************
	if($_POST['hSaved']=="false")
		{
		$questionNo = 1;
		$aQuestions = array();
		for ($iBlock=1; $iBlock<=count($aItems);$iBlock++)
			{
			$blockID = $aItems[$iBlock][0]["blockID"];
			if($aItems[$iBlock][0]["instanceable"]==1)
				{
				$noOfInstances = $_POST[hCurrentInstance . "_" . $blockID];
				}
			else
				{
				$noOfInstances = 1;
				}
			for($inst=1;$inst<=$noOfInstances;$inst++)
				{
				for($iSection=1; $iSection<=count($aItems[$iBlock])-1;$iSection++)
					{
					$sectionID = $aItems[$iBlock][$iSection][0]["sectionID"];
					if($aItems[$iBlock][$iSection][0]["instanceable"]==1)
						{
						$noOfSectionInstances = $_POST[hCurrentSectionInstance . "_" . $blockID . "_" . $sectionID . "_i" . $inst];
						}
					else
						{
						$noOfSectionInstances = 1;	
						}
					for($sinst=1;$sinst<=$noOfSectionInstances;$sinst++)
						{
						for($iQuestion=1; $iQuestion<=count($aItems[$iBlock][$iSection])-1;$iQuestion++)
							{
							$questionID = $aItems[$iBlock][$iSection][$iQuestion][0]["questionID"];
							$aQuestions[$questionNo]= array();
							$aQuestions[$questionNo][0]= $blockID."_".$sectionID."_".$questionID."_i".$inst."_si".$sinst;//questionID
							$aQuestions[$questionNo][1]= $questionNo;
							$aQuestions[$questionNo][2]= $aItems[$iBlock][$iSection][$iQuestion][0]["text"];	
							$aQuestions[$questionNo][3]= $aItems[$iBlock][$iSection][$iQuestion][0]["questionTypeID"];
							$itemArraySize = count($aItems[$iBlock][$iSection][$iQuestion])-1;
							if($itemArraySize>0)
								{
								$aQuestions[$questionNo][4]= array();
								for($iItem=1; $iItem<=count($aItems[$iBlock][$iSection][$iQuestion])-1;$iItem++)
									{
									$itemID = $aItems[$iBlock][$iSection][$iQuestion][$iItem]["itemID"];
									$aQuestions[$questionNo][4][$iItem]=$blockID."_".$sectionID."_".$questionID."_".$itemID."_i".$inst."_si".$sinst;
									}
								}
							$aQuestions[$questionNo][5]= ""; //will be used to hold data on whether the question has been answered correctly;
							//is this block, section or question branched to?
							$qBranchDestinations = "SELECT BranchDestinations.branchID, Branches.blockID, Branches.sectionID, Branches.questionID, Branches.itemID, Questions.questionTypeID
													FROM BranchDestinations, Branches, Questions
													WHERE ((BranchDestinations.blockID = $blockID
													AND BranchDestinations.sectionID IS NULL
													AND BranchDestinations.questionID IS NULL)
													OR (BranchDestinations.blockID = $blockID											
													AND BranchDestinations.sectionID = $sectionID
													AND BranchDestinations.questionID IS NULL)
													OR (BranchDestinations.blockID = $blockID											
													AND BranchDestinations.sectionID = $sectionID
													AND BranchDestinations.questionID = $questionID))
													AND BranchDestinations.branchID = Branches.branchID
													AND Questions.questionID = Branches.questionID
													AND Branches.surveyID = $surveyID
													";
							$qResBranchDestinations = mysqli_query($db_connection, $qBranchDestinations);
							if (mysqli_num_rows($qResBranchDestinations)==0)
								{
								$aQuestions[$questionNo][6] = NULL;
								}
							else
								{
								$rowBranchDestinations = mysqli_fetch_array($qResBranchDestinations);
								//if it's in the same block as this question, then the branching item must have the same instance number
								if($rowBranchDestinations['blockID'] == $blockID)
									{
									if($rowBranchDestinations['sectionID'] == $sectionID)
										{
										//if it's in the same section as this question, then the branching item must have the same section instance number
										$aQuestions[$questionNo][6] = $rowBranchDestinations['blockID'] . "_" . $rowBranchDestinations['sectionID'] . "_" . $rowBranchDestinations['questionID'] . "_" . $rowBranchDestinations[itemID] . "_i" . $inst. "_si" . $sinst;
										}
									else
										{
										//otherwise it must have an section instance of 1 - sections which are instanceable can only branch to questions within their own section
										$aQuestions[$questionNo][6] = $rowBranchDestinations['blockID'] . "_" . $rowBranchDestinations['sectionID'] . "_" . $rowBranchDestinations['questionID'] . "_" . $rowBranchDestinations[itemID] . "_i" . $inst. "_si1";
										}
									}
								else
									{						
									//otherwise it must have an instance of 1 - block which are instanceable can only branch to questions within their own block
									if($rowBranchDestinations['sectionID'] == $sectionID)
										{
										//if it's in the same section as this question, then the branching item must have the same section instance number
										$aQuestions[$questionNo][6] = $rowBranchDestinations['blockID'] . "_" . $rowBranchDestinations['sectionID'] . "_" . $rowBranchDestinations['questionID'] . "_" . $rowBranchDestinations[itemID] . "_i1". "_si" . $sinst;
										}
									else
										{
										//otherwise it must have an section instance of 1 - sections which are instanceable can only branch to questions within their own section
										$aQuestions[$questionNo][6] = $rowBranchDestinations['blockID'] . "_" . $rowBranchDestinations['sectionID'] . "_" . $rowBranchDestinations['questionID'] . "_" . $rowBranchDestinations[itemID] . "_i1". "_si1";
										}
									}
								$aQuestions[$questionNo][7] = $rowBranchDestinations['questionTypeID'];
								}
							$questionNo++;
							}
						}
					}
				}
			}
		for($i=1;$i<=count($aQuestions);$i++)
			{
			$questionAnswered = true;
			if($aQuestions[$i][3]==1)
				{
				//MCHOIC
				if($_POST[$aQuestions[$i][0]]=="")
					{
					$questionAnswered = false;
					}
				}
			/*else if($aQuestions[$i][3]==2) don't check mchooic - user allowed not to answer
				{
				//MSELEC
				$itemCount = 0;
				for($j=1;$j<=count($aQuestions[$i][4]);$j++)
					{
					if($_POST[$aQuestions[$i][4][$j]]=="on")
						{
						$itemCount++;
						}
					}
				if($itemCount==0)
					{
					$questionAnswered = false;
					}
				}*/
			else if($aQuestions[$i][3]==3)
				{
				//DRDOWN
				$itemCount = 0;
				for($j=1;$j<=count($aQuestions[$i][4]);$j++)
					{
					if($_POST[$aQuestions[$i][0]] == $aQuestions[$i][4][$j])
						{
						$itemCount++;
						}
					}
				if($itemCount==0)
					{
					$questionAnswered = false;
					}
				}
			else if($aQuestions[$i][3]==6)
				{
				//date
				$itemCount = 0;
				$dayName = "day_".$aQuestions[$i][0];
				$monthName = "month_".$aQuestions[$i][0];
				$yearName = "year_".$aQuestions[$i][0];
				if($_POST[$dayName] == 0 || $_POST[$monthName] == 0 || $_POST[$yearName] == 0)
					{
					$questionAnswered = false;
					}
				}
			if($questionAnswered == false)
				{
				//check whether the question was visible
				if ($aQuestions[$i][6] == NULL)
					{
					//question is not branched to therefore..
					$aQuestions[$i][5]= false;
					$allAnswered = false;
					}
				else
					{
					//question is branched to - was it visible?
					$expItemID = explode("_",$aQuestions[$i][6]);
					unset($expItemID[3]);
					$expQuestionID = array_values($expItemID); 
					$branchingQuestionID = implode("_",$expQuestionID); 
					if($aQuestions[$i][7]==1||$aQuestions[$i][7]==3)
						{
						//for MCHOIC and DRDOWN questions, the POST value will be contained by the question
						if($_POST[$branchingQuestionID]!="")
							{
							if ($_POST[$branchingQuestionID] != $aQuestions[$i][6])
								{
								//question was invisible therefore user not required to answer it. Question will only have been visible if $_POST[$branchingQuestionID] == $aQuestions[$i][6]
								$aQuestions[$i][5]=true;
								}
							}
						else
							{
							//question was visible therefore user required to answer it. Question will only have been visible if $_POST[$branchingQuestionID] == $aQuestions[$i][6]
							$aQuestions[$i][5]=false;
							$allAnswered = false;
							}
						}
					elseif($aQuestions[$i][7]==2)
						{
						if($_POST[$aQuestions[$i][6]]!="on")
							{
							//question was invisible therefore user not required to answer it..
							$aQuestions[$i][5] =true;
							}
						else
							{
							//question was visible therefore should have been answered
							$aQuestions[$i][5]=false;
							$allAnswered = false;
							}
						}
					}
				}
			else
				{
				$aQuestions[$i][5] = true;
				}
			}
		}
	if($allAnswered==true || $_POST['hSaved']=="true")
		{
		$textOutput = "";
		$textOutput = $textOutput . "<h1>$surveyInstanceTitle </h1>";
		//***************************************************************************
		//NOW WRITE DATA TO DATABASE
		//***************************************************************************	
		$bOverallSuccess = true;
		$textOutput = $textOutput . "<table>";
		$questionNo = 1;
		for ($iBlock=1; $iBlock<=count($aItems);$iBlock++)
			{
			$blockID = $aItems[$iBlock][0]["blockID"];
			if($aItems[$iBlock][0]["instanceable"]==1)	
				{
				$noOfInstances = $_POST[hCurrentInstance . "_" . $blockID];
				}
			else
				{
				$noOfInstances = 1;
				}
			for($inst=1;$inst<=$noOfInstances;$inst++)
				{
				if($aItems[$iBlock][0]["text"] != "")
					{
					//only output a block title if there is one
					$textOutput = $textOutput . "<tr><td colspan=\"3\"><h2>".$aItems[$iBlock][0]["text"];
					if ($aItems[$iBlock][0]["instanceable"]==1)
						{
						$textOutput = $textOutput . ": ".$inst;
						} 
					$textOutput = $textOutput . "</h2></td></tr>";
					}
				for($iSection=1; $iSection<=count($aItems[$iBlock])-1;$iSection++)
					{
					$sectionID = $aItems[$iBlock][$iSection][0]["sectionID"];
					if($aItems[$iBlock][$iSection][0]["instanceable"]==1)		
						{
						$noOfSectionInstances = $_POST[hCurrentSectionInstance . "_" . $blockID . "_" . $sectionID."_i".$inst];
						}
					else
						{
						$noOfSectionInstances = 1;
						}
					for($sinst=1;$sinst<=$noOfSectionInstances;$sinst++)
						{
						//begin section content
						if($aItems[$iBlock][$iSection][0]["text"] != "")
							{
							//only output a section title if there is one
							$textOutput = $textOutput . "<tr><td colspan=\"3\"><h3>".$aItems[$iBlock][$iSection][0]["text"];
							if ($aItems[$iBlock][$iSection][0]["instanceable"]==1)
								{
								$textOutput = $textOutput . ": ".$sinst;
								} 
							$textOutput = $textOutput . "</h3></td></tr>";
							}
						for($iQuestion=1; $iQuestion<=count($aItems[$iBlock][$iSection])-1;$iQuestion++)
							{
							$questionID = $aItems[$iBlock][$iSection][$iQuestion][0]["questionID"];
							//first check whether this participant has already entered anything for this survey/section/question
							$qAnswers = "SELECT answerID
									FROM Answers
									WHERE surveyInstanceID = $surveyInstanceID
									AND blockID = $blockID
									AND sectionID = $sectionID
									AND questionID = $questionID
									AND instance = $inst
									AND sinstance = $sinst
									AND heraldID = '$heraldID'";
						
							$qResAnswers = mysqli_query($db_connection, $qAnswers);
							$textOutput = $textOutput . "<tr><td>$questionNo</td><td>".$aItems[$iBlock][$iSection][$iQuestion][0]["text"]."</td>";
							
							//******************************************************************************************************
							//Beginning of transaction - prevents anything being written unless everything else to do with
							//that question has executed successfully
							//******************************************************************************************************
							
							// make sure we flag the database to not commit after each executed query
							$query        = "SET AUTOCOMMIT=0";
							$result_query = @mysqli_query($db_connection, $query); //$db_connection is from config.php
				
							$query        = "BEGIN";
							$result_query = @mysqli_query($db_connection, $query);
											
							$bSuccess = true;//used to track whether any problems have occurred to prevent transaction being committed within this question loop
							switch ($aItems[$iBlock][$iSection][$iQuestion][0]["questionTypeID"])
								{
								case 1:
									{
									$mchoicName = $blockID . "_". $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
									$mchoicCommentName = "comments_" . $blockID . "_". $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
									if(isset($_POST[$mchoicName]))
										{
										//identify which item it is referring to:
										$expMchoicID = explode("_",$_POST[$mchoicName]); //The POST_Var contains $mchoicValue which is a string with the itemID at the end (e.g 1_1_1_2)
										//get the item ID
										$expItemID = intval($expMchoicID[3]);
										//get the block instance
										$expInstance = intval(substr($expMchoicID[4], 1));
										//get the section instance
										$expSectionInstance = intval(substr($expMchoicID[5], 2));
										//now start writing this to the database
										if(mysqli_num_rows($qResAnswers)==1)
											{	//update it
											$rowAnswers = mysqli_fetch_array($qResAnswers);
											$uAnswers = "UPDATE Answers
														SET surveyInstanceID = $surveyInstanceID,
															blockID = $blockID,
															sectionID = $sectionID,
															questionID = $questionID,
															heraldID = '$heraldID',
															date = NOW(),
															instance = $expInstance,
															sinstance = $expSectionInstance
														WHERE answerID = $rowAnswers[answerID]";
											$result_query = @mysqli_query($db_connection, $uAnswers);
											if (($result_query == false))
												{
												echo "problem updating Answers" . mysqli_error($db_connection);
												$bSuccess = false;
												}
											writeItem($rowAnswers[answerID], $expItemID, $bSuccess, $textOutput);
											if(isset($_POST[$mchoicCommentName]))
												{
												writeComment($rowAnswers[answerID], $_POST[$mchoicCommentName], $bSuccess, $textOutput);
												}
											}
										else if (mysqli_num_rows($qResAnswers)==0)
											{ //insert it
											$iAnswers = "INSERT INTO Answers
														VALUES(0,$surveyInstanceID,$blockID,$sectionID,$questionID,'$heraldID',NOW(),$expInstance,$expSectionInstance)";
											$result_query = @mysqli_query($db_connection, $iAnswers);
											if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
												{
												echo "problem inserting into Answers" . mysqli_error($db_connection);
												$bSuccess = false;
												}
											//remember this so we can refer to it after other things have been inserted into other files.
											$iInsertedAnswerID = mysqli_insert_id($db_connection);
											//echo "iInsertedAnswerID = " . $iInsertedAnswerID;
											writeItem($iInsertedAnswerID, $expItemID, $bSuccess, $textOutput);
											if(isset($_POST[$mchoicCommentName]))
												{
												writeComment($iInsertedAnswerID, $_POST[$mchoicCommentName], $bSuccess, $textOutput);
												}
											}
										else
											{
											//generate an error message
											echo "There appears to be more than one entry for this Participant/Survey/Section/Question/Combination.";
											$bSuccess = false;
											}
										}
									break;
									}
								case 2:
									{
									//get items
									$qItems = "SELECT Items.itemID, Items.text, QuestionItems.position
											FROM Items, QuestionItems
											WHERE QuestionItems.questionID = $questionID
											AND QuestionItems.visible = 1
											AND Items.itemID = QuestionItems.itemID
											ORDER BY QuestionItems.position";
														
									$qResItems = mysqli_query($db_connection, $qItems);
									//check whether any of the items for this question have been checked ('on')
									$iNoOfCheckedItems = 0;
									for($iItem=1; $iItem<=count($aItems[$iBlock][$iSection][$iQuestion])-1;$iItem++)
										{
										$itemID = $aItems[$iBlock][$iSection][$iQuestion][$iItem]["itemID"];
										$mselecName = $blockID . "_" . $sectionID . "_" . $questionID. "_" . $itemID . "_i" . $inst . "_si" . $sinst; 
										if(isset($_POST[$mselecName]) && $_POST[$mselecName]=="on")
											{
											$iNoOfCheckedItems = $iNoOfCheckedItems + 1;
											}
										}
									// If one of the check boxes for this question has been checked
									//print_r($aItems);
									//if ($iNoOfCheckedItems > 0)
										//{
										$mselecCommentName = "comments_" . $blockID . "_" .$sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										if(mysqli_num_rows($qResAnswers)==1)
											{	//update it
											$rowAnswers = mysqli_fetch_array($qResAnswers);
											$answerID = $rowAnswers['answerID'];
											$uAnswers = "UPDATE Answers
														SET surveyInstanceID = $surveyInstanceID,
															blockID = $blockID,
															sectionID = $sectionID,
															questionID = $questionID,
															heraldID = '$heraldID',
															date = NOW(),
															instance = $inst,
															sinstance = $sinst
														WHERE answerID = $answerID";
											$result_query = @mysqli_query($db_connection, $uAnswers);
											if (($result_query == false))
												{
												echo "problem updating Answers" . mysqli_error($db_connection);
												$bSuccess = false;
												}
											writeCheckBoxItem($answerID,$aItems,$questionID,$sectionID,$blockID,$inst,$sinst,$bSuccess, $textOutput);
											if(isset($_POST[$mselecCommentName]))
												{
												writeComment($answerID, $_POST[$mselecCommentName],$bSuccess, $textOutput);
												}
											}
										else if (mysqli_num_rows($qResAnswers)==0)
											{ //insert it
											$iAnswers = "INSERT INTO Answers
														VALUES(0,$surveyInstanceID,$blockID,$sectionID,$questionID,'$heraldID',NOW(),$inst,$sinst)";
											$result_query = @mysqli_query($db_connection, $iAnswers);
											if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
												{
												echo "problem inserting into Answers" . mysqli_error($db_connection);
												$bSuccess = false;
												}
											//rememnber this so we can refer to it after other things have been inserted into other files.
											$iInsertedAnswerID = mysqli_insert_id($db_connection);
											writeCheckBoxItem($iInsertedAnswerID,$aItems,$questionID,$sectionID,$blockID,$inst,$sinst,$bSuccess, $textOutput);
											if(isset($_POST[$mselecCommentName]))
												{
												writeComment($iInsertedAnswerID, $_POST[$mselecCommentName],$bSuccess, $textOutput);
												}
											}
										else
											{
											//generate an error message
											echo "There appears to be more than one entry for this Participant/Survey/Section/Question/Combination.";
											$bSuccess = false;
											}
										//}						
									break;
									}
								case 3:
									{
									$drdownName = $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
									$drdownCommentName = "comments_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst. "_si" . $sinst;
									if(isset($_POST[$drdownName]))
										{
										//identify which item it is referring to:
										$expdrdownValue = explode("_",$_POST[$drdownName]); //The POST_Var contains $drdownValue which is a string with the itemID at the end (e.g 1_1_1_2)
										//get the item ID
										$expItemID = intval($expdrdownValue[3]);
										if($expItemID != 0) //ie only write non-zero (default) values
											{
											//get the block instance
											$expInstance = intval(substr($expdrdownValue[4], 1));
											//get the section instance
											$expSectionInstance = intval(substr($expdrdownValue[5], 2));
											//now start writing this to the database
											if(mysqli_num_rows($qResAnswers)==1)
												{	//update it
												$rowAnswers = mysqli_fetch_array($qResAnswers);
												$uAnswers = "UPDATE Answers
															SET surveyInstanceID = $surveyInstanceID,
																blockID = $blockID,
																sectionID = $sectionID,
																questionID = $questionID,
																heraldID = '$heraldID',
																date = NOW(),
																instance = $expInstance,
																sinstance = $expSectionInstance
															WHERE answerID = $rowAnswers[answerID]";
												$result_query = @mysqli_query($db_connection, $uAnswers);
												if (($result_query == false))
													{
													echo "problem updating Answers" . mysqli_error($db_connection);
													$bSuccess = false;
													}
												//echo "rowAnswers[answerID] = " . $rowAnswers['answerID'];
												writeItem($rowAnswers['answerID'], $expItemID, $bSuccess, $textOutput);
												if(isset($_POST[$drdownCommentName]))
													{
													writeComment($rowAnswers['answerID'], $_POST[$drdownCommentName], $bSuccess, $textOutput);
													}
												}
											else if (mysqli_num_rows($qResAnswers)==0)
												{ //insert it
												$iAnswers = "INSERT INTO Answers
															VALUES(0,$surveyInstanceID,$blockID,$sectionID,$questionID,'$heraldID',NOW(),$expInstance,$expSectionInstance)";
												$result_query = @mysqli_query($db_connection, $iAnswers);
												if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
													{
													echo "problem inserting into Answers" . mysqli_error($db_connection);
													$bSuccess = false;
													}
												//rememnber this so we can refer to it after other things have been inserted into other files.
												$iInsertedAnswerID = mysqli_insert_id($db_connection);
												//echo "iInsertedAnswerID = " . $iInsertedAnswerID;
												writeItem($iInsertedAnswerID, $expItemID, $bSuccess, $textOutput);
												if(isset($_POST[$drdownCommentName]))
													{
													writeComment($iInsertedAnswerID, $_POST[$drdownCommentName], $bSuccess, $textOutput);
													}
												}
											else
												{
												//generate an error message
												echo "There appears to be more than one entry for this Participant/Survey/Section/Question/Combination.";
												$bSuccess = false;
												}
											}
										}
									break;
									}
								case 6:
									{
									$dayName = "day_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
									$monthName = "month_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
									$yearName = "year_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
									if(isset($_POST[$dayName]) && isset($_POST[$monthName]) && isset($_POST[$yearName]))
										{
										$date = date("Y-m-d", mktime(0, 0, 0, $_POST[$monthName], $_POST[$dayName], $_POST[$yearName])); 
										if(mysqli_num_rows($qResAnswers)==1)
											{	//update it
											$rowAnswers = mysqli_fetch_array($qResAnswers);
											$uAnswers = "UPDATE Answers
														SET surveyInstanceID = $surveyInstanceID,
															blockID = $blockID,
															sectionID = $sectionID,
															questionID = $questionID,
															heraldID = '$heraldID',
															date = NOW(),
															instance = $inst,
															sinstance = $sinst
														WHERE answerID = $rowAnswers[answerID]";
											$result_query = @mysqli_query($db_connection, $uAnswers);
											if (($result_query == false))
												{
												echo "problem updating Answers" . mysqli_error($db_connection);
												$bSuccess = false;
												}
											writeDate($rowAnswers[answerID], $date, $bSuccess, $textOutput);
											}
										else if (mysqli_num_rows($qResAnswers)==0)
											{ //insert it
											$iAnswers = "INSERT INTO Answers
														VALUES(0,$surveyInstanceID,$blockID,$sectionID,$questionID,'$heraldID',NOW(),$inst,$sinst)";
											$result_query = @mysqli_query($db_connection, $iAnswers);
											if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
												{
												echo "problem inserting into Answers" . mysqli_error($db_connection);
												$bSuccess = false;
												}
											//rememnber this so we can refer to it after other things have been inserted into other files.
											$iInsertedAnswerID = mysqli_insert_id($db_connection);
											writeDate($iInsertedAnswerID, $date, $bSuccess, $textOutput);
											}
										else
											{
											//generate an error message
											echo "There appears to be more than one entry for this Participant/Survey/Section/Question/Combination.";
											$bSuccess = false;
											}
										}
									break;
									}
								default:
									{
									$textName = $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
									$textOutput = $textOutput . "<td></td></tr>";
									if(isset($_POST[$textName]))
										{
										if($_POST[$textName] != "") //ie don't bother writing empty strings
											{
											if(mysqli_num_rows($qResAnswers)==1)
												{	//update it
												$rowAnswers = mysqli_fetch_array($qResAnswers);
												$uAnswers = "UPDATE Answers
															SET surveyInstanceID = $surveyInstanceID,
																blockID = $blockID,
																sectionID = $sectionID,
																questionID = $questionID,
																heraldID = '$heraldID',
																date = NOW(),
																instance = $inst,
																sinstance = $sinst
															WHERE answerID = $rowAnswers[answerID]";
												$result_query = @mysqli_query($db_connection, $uAnswers);
												if (($result_query == false))
													{
													echo "problem updating Answers" . mysqli_error($db_connection);
													$bSuccess = false;
													}
												writeComment($rowAnswers[answerID], $_POST[$textName], $bSuccess, $textOutput);
												}
											else if (mysqli_num_rows($qResAnswers)==0)
												{ //insert it
												$iAnswers = "INSERT INTO Answers
															VALUES(0,$surveyInstanceID,$blockID,$sectionID,$questionID,'$heraldID',NOW(),$inst,$sinst)";
												$result_query = @mysqli_query($db_connection, $iAnswers);
												if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
													{
													echo "problem inserting into Answers" . mysqli_error($db_connection);
													$bSuccess = false;
													}
												//rememnber this so we can refer to it after other things have been inserted into other files.
												$iInsertedAnswerID = mysqli_insert_id($db_connection);
												writeComment($iInsertedAnswerID, $_POST[$textName], $bSuccess, $textOutput);
												}
											else
												{
												//generate an error message
												echo "There appears to be more than one entry for this Participant/Survey/Section/Question/Combination.";
												$bSuccess = false;
												}
											}
										}
									break;
									}
								
								}
							
							//******************************************************************************************************
							//End of transaction 
							//******************************************************************************************************
							if ($bSuccess == true)
								{
								$query = "COMMIT";
								$result_query = @mysqli_query($db_connection, $query);
								} // end if success is true
							else
								{
								$bOverallSuccess = false;
								} // end else - the success flag is false				
							$questionNo++;
							//End of Question loop
							}
						}
					//End of Section loop
					}
				//end of inst loop
				}
			//End of Block loop
			}
		$textOutput = $textOutput . "</table>";
		//now change SurveyInstanceParticipants to reflect status
		//work out the status
		if($_POST['hSaved']=="true")
			{
			//ie only saved
			$status = 1;
			}
		else
			{
			//ie submitted 
			$status = 2;
			}
		//find out if this user already has an entry for this surveyInstance
		$qSurveyInstanceParticipants = "SELECT surveyInstanceParticipantID
										FROM SurveyInstanceParticipants
										WHERE surveyInstanceID = $surveyInstanceID
										AND heraldID = '$heraldID'";
		$qResSurveyInstanceParticipants = mysqli_query($db_connection, $qSurveyInstanceParticipants);
		if (($qResSurveyInstanceParticipants == false))
			{
			echo "problem querying SurveyInstanceParticipants" . mysqli_error($db_connection);
			}
		else
			{
			if(mysqli_num_rows($qResSurveyInstanceParticipants)==0)
				{
				//insert
				$iSurveyInstanceParticipants = "INSERT INTO SurveyInstanceParticipants
												VALUES(0,'$heraldID',$surveyInstanceID,$status,NOW())";
				$result_query = @mysqli_query($db_connection, $iSurveyInstanceParticipants);
				if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
					{
					echo "problem inserting into SurveyInstanceParticipants" . mysqli_error($db_connection);
					}
				}
			else if (mysqli_num_rows($qResSurveyInstanceParticipants)==1)
				{
				//update
				$rowSurveyInstanceParticipant = mysqli_fetch_array($qResSurveyInstanceParticipants);
				$uSurveyInstanceParticipants = "UPDATE SurveyInstanceParticipants
												SET heraldID = '$heraldID',
												surveyInstanceID = $surveyInstanceID,
												status = $status,
												date = NOW()
												WHERE surveyInstanceParticipantID = $rowSurveyInstanceParticipant[surveyInstanceParticipantID]";
				$result_query = @mysqli_query($db_connection, $uSurveyInstanceParticipants);
				if (($result_query == false))
					{
					echo "problem updating SurveyInstanceParticipants" . mysqli_error($db_connection);
					}
				}
			else
				{
				//generate an error message
					echo "There appears to be more than one entry for this Participant/SurveyInstance Combination.";
				}
			}
		}
	//*************************************
	// WRITE TO LOG TABLE - XML with XHTML contents
	//*************************************
	$textToWrite = quote_smart($db_connection, $textOutput);
	$iSubmissionLog = "INSERT INTO SubmissionLog
						VALUES(0,'$heraldID',NOW(),$status,$textToWrite)";
	$result_query = @mysqli_query($db_connection, $iSubmissionLog);
	if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
		{
		echo "problem inserting into SubmissionLog" . mysqli_error($db_connection);
		}
	}

//*******************************************************************************************************************************
//END WRITING SURVEY RESULTS
//*******************************************************************************************************************************


//*******************************************************************************************************************************
//BEGIN SHOWING SURVEY
//*******************************************************************************************************************************
	
	//now check whether this user has already answered any question in this survey 
	//instance - if not, there's no need to look for their answers when showing the form
	//find out if this user already has an entry for this surveyInstance
	$qAnyAnswers = "SELECT surveyInstanceParticipantID, status
					FROM SurveyInstanceParticipants
					WHERE surveyInstanceID = $surveyInstanceID
					AND heraldID = '$heraldID'";
	$qResAnyAnswers = mysqli_query($db_connection, $qAnyAnswers);
	$answersExist = "false";
	$hasSubmitted = "false";
	if (mysqli_num_rows($qResAnyAnswers)>0)
		{
		$answersExist = "true";
		$rowAnyAnswers = mysqli_fetch_array($qResAnyAnswers);
		if($rowAnyAnswers['status']==2)
			{
			$hasSubmitted = "true";
			}
		}
	if(IsAuthor($heraldID))
		{
		require_once("includes/html/adminheadernew.html");
		require_once("includes/html/BreadCrumbsHeader.html"); 
		echo "<a href=\"index.php\">Surveys</a> &gt; <strong>$surveyInstanceTitle</strong>"; 	
		require_once("includes/html/BreadCrumbsFooter.html"); 
		}
	else
		{
		if($allowViewByStudent=="true")
			{
			require_once("includes/html/chooserheader.html");
			}	
		else
			{
			require_once("includes/html/header.html");
			} 
		}
	echo "<a name=\"maintext\" id=\"maintext\"></a>";
	if (isset($_POST['bSubmitSurvey']))
		{
		if($allAnswered==true)
			{
			if ($bOverallSuccess == true)
				{
					echo "<h1>$surveyInstanceTitle</h1>";	
					echo $surveyEpilogue;
					echo "<p>If you would like to print out a copy of your answers, please do so now by clicking the <strong>Print submitted survey</strong> button below:</p>";
					echo "<input type=\"button\" name=\"printSurvey\" id=\"printSurvey\" value=\"Print submitted survey\" onClick=\"window.open('printsurvey.php?surveyInstanceID=$surveyInstanceID','printWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes')\">";
					echo "<br/><br/>Return to <a href=\"showsurvey.php?surveyInstanceID=$surveyInstanceID\">$surveyInstanceTitle</a>"; 
					require_once("includes/html/footernew.html"); 
					exit();
				} // end if success is true
			else
				{
					echo "	<h1>Error</h1>
							<p>We are sorry but we are having technical difficulties, and there has been a problem recording 
							at least some of your feedback.</p>
							<p>Please contact weblearn@medsci.ox.ac.uk and quote the following error message: Overallsuccess failed.</p>";
					exit();
				} // end else - the success flag is false				
			}
		else
			{
			echo "<span class=\"errorMessage\">You did not complete all the questions. Questions that you omitted are indicated below</span>";
			}
		}
	else
		{	
		echo "<div class=\"surveyText\">
		<noscript>
			<p>
				<strong>This feedback system uses javascript but your browser does not currently 
				support this. It is not possible to submit the form unless you have javascript enabled.</strong>
			</p>
			<p>	
				You will need to enable javascript. In Internet Explorer, check that the security level 
				for this site is set to Medium or lower (choose <strong>Internet Options..</strong> from the <strong>Tools</strong> menu, 
				click the <strong>Security</strong> tab and set the <strong>Security Level for this zone</strong> to <strong>Medium</strong>).
				Once you have done this, click <strong>Refresh</strong> to reload this page.
			</p><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
		</noscript>
		<h1>$surveyInstanceTitle</h1>";
		if($instanceStartDate=="null")
			{
			$unixStartDate = "null";
			}
		else
			{
			$unixStartDate = strtotime($instanceStartDate); 
			}
		if($instanceFinishDate=="null")
			{
			$unixFinishDate = "null";
			}
		else
			{
			$unixFinishDate = strtotime($instanceFinishDate)+86400;//add no. of seconds in a day to take you to the end of the finish day
			}
		//warning to students if survey is not anonymous
		if($allowViewByStudent=="true")
			{
			echo "<h2><span class=\"errorMessage\">Warning: Your entries will not be anonymous</span></h2>";
			}	
		//warning to students if deadline has passed
		if (!(($unixStartDate <= time() && $unixFinishDate >= time()) || ($unixStartDate == "null" && $unixFinishDate >= time()) || ($unixStartDate <= time() && $unixFinishDate == "null") || ($unixStartDate == "null" && $unixFinishDate == "null")))
			{
			echo "<h2><span class=\"errorMessage\">Warning: This survey is not live. You will not be able to save any entries or changes.</span></h2>";
			}	
		echo "$surveyIntroduction
		</div>";
		} 
	echo "<form id=\"frmSurvey\" name=\"frmSurvey\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" onSubmit=\"return ValidateForm()\">";

	//get blocks
	//$qBlocks = "SELECT Blocks.blockID, Blocks.title, Blocks.text, Blocks.introduction, Blocks.epilogue, SurveyBlocks.position, Blocks.instanceable
				//FROM Blocks, SurveyBlocks 
				//WHERE SurveyBlocks.surveyID = $surveyID
				//AND SurveyBlocks.visible = 1
				//AND Blocks.blockID = SurveyBlocks.blockID
				//ORDER BY SurveyBlocks.position";
	
	//$qResBlocks = mysql_query($qBlocks);
	//counter for questions 
	$questionNo = 1;
	//loop through sections
	
	//need to work out globally whether the form is being refreshed after adding data or is being opened anew
	//create a hidden form field to hold boolean
	echo "<input type=\"hidden\" name=\"hAdded\" id=\"hAdded\" value=\"false\">";
	echo "<input type=\"hidden\" name=\"hDeleted\" id=\"hDeleted\" value=\"false\">";
	echo "<input type=\"hidden\" name=\"hSaved\" id=\"hSaved\" value=\"false\">";
	$addingInstance = false;
	$deletingInstance = false;
	if ((isset($_POST['hAdded']) && $_POST['hAdded'] == "true") || $allAnswered==false)
		{
		$addingInstance = true;
		}
	if ((isset($_POST['hDeleted']) && $_POST['hDeleted'] == "true"))
		{
		$deletingInstance = true;
		}
	for ($iBlock=1; $iBlock<=count($aItems);$iBlock++)
		{
		$blockID = $aItems[$iBlock][0]["blockID"];
		$blockIsInstanceable = false;
		if($aItems[$iBlock][0]["instanceable"]==1)	
	
	//while($rowBlocks = mysql_fetch_array($qResBlocks))
		//{
		//$blockIsInstanceable = false;
		//$blockID = $rowBlocks['blockID'];
		//if($rowBlocks[instanceable]==1)
			{
			$blockIsInstanceable = true;
			if (isset($_POST[bAddInstance . "_" . $blockID]))
				{
				$noOfCurrentInstances = $_POST[hCurrentInstance . "_" . $blockID];
				$noOfInstances = $noOfCurrentInstances + 1;
				}
			elseif (isset($_POST[bDeleteInstance . "_" . $blockID]))
				{
				$noOfCurrentInstances = $_POST[hCurrentInstance . "_" . $blockID];
				$noOfInstances = $noOfCurrentInstances - 1;
				}
			else
				{
				$noOfInstances = getInstancesForBlock($blockID);
				}
			}
		else
			{
			$blockIsInstanceable = false;
			$noOfInstances = 1;
			}
		//is this block branched to?
		$qThisObjectIsBranchedTo = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
										FROM BranchDestinations";										
		$qResThisObjectIsBranchedTo = mysqli_query($db_connection, $qThisObjectIsBranchedTo);
		$ThisObjectIsBranchedTo = false;
		while($rowThisObjectIsBranchedTo = mysqli_fetch_array($qResThisObjectIsBranchedTo))
			{
			if($rowThisObjectIsBranchedTo['blockID'] == $blockID && 
			$rowThisObjectIsBranchedTo['sectionID'] == NULL && 
			$rowThisObjectIsBranchedTo['questionID'] == NULL)
				{
				$ThisObjectIsBranchedTo = true;
				}
			}
		//get sections
		//$qSections = "SELECT Sections.sectionID, Sections.title, Sections.text, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, Sections.instanceable 
					//FROM Sections, BlockSections 
					//WHERE BlockSections.blockID = $blockID
					//AND BlockSections.visible = 1
					//AND Sections.sectionID = BlockSections.sectionID
					//ORDER BY BlockSections.position";
		
		//$qResSections = mysql_query($qSections);
		for($inst=1;$inst<=$noOfInstances;$inst++)
			{
			echo "<div class=\"block\" id=\"div".$blockID."_i".$inst."\"".($ThisObjectIsBranchedTo?"style=\"display:none\"":"").">";
			//begin block content
			if($aItems[$iBlock][0]["text"] != "")
				{
				//only output a block title if there is one
				echo "<h2>".$aItems[$iBlock][0]["text"];
				if ($aItems[$iBlock][0]["instanceable"]==1)
					{
					echo ": ".$inst;
					} 
				echo "</h2>";
				}
			echo "<div class=\"blockText\">".$aItems[$iBlock][0]["introduction"]."</div>"; 
			//if(mysql_num_rows($qResSections)>0)
				//{
				//mysql_data_seek($qResSections, 0);
				//}
			for($iSection=1; $iSection<=count($aItems[$iBlock])-1;$iSection++)
				{
				$sectionID = $aItems[$iBlock][$iSection][0]["sectionID"];
				$sectionIsInstanceable = false;
				if($aItems[$iBlock][$iSection][0]["instanceable"]==1)		
			//while($rowSections = mysql_fetch_array($qResSections))
				//{			
				//$sectionIsInstanceable = false;
				//$sectionID = $rowSections['sectionID'];
				//if($rowSections[instanceable]==1)
					{
					$sectionIsInstanceable = true;
					if (isset($_POST[bAddSectionInstance . "_" . $blockID . "_" . $sectionID."_i".$inst]))
						{
						$noOfCurrentSectionInstances = $_POST[hCurrentSectionInstance . "_" . $blockID . "_" . $sectionID."_i".$inst];
						$noOfSectionInstances = $noOfCurrentSectionInstances + 1;
						}
					elseif (isset($_POST[bDeleteSectionInstance . "_" . $blockID . "_" . $sectionID."_i".$inst]))
						{
						$noOfCurrentSectionInstances = $_POST[hCurrentSectionInstance . "_" . $blockID . "_" . $sectionID."_i".$inst];
						$noOfSectionInstances = $noOfCurrentSectionInstances - 1;
						}
					else
						{
						$noOfSectionInstances = getInstancesForSection($blockID,$sectionID,$inst);
						}
					}
				else
					{
					$sectionIsInstanceable = false;
					$noOfSectionInstances = 1;
					}
				//is this block branched to?
				$qThisObjectIsBranchedTo = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
												FROM BranchDestinations";										
				$qResThisObjectIsBranchedTo = mysqli_query($db_connection, $qThisObjectIsBranchedTo);
				$ThisObjectIsBranchedTo = false;
				while($rowThisObjectIsBranchedTo = mysqli_fetch_array($qResThisObjectIsBranchedTo))
					{
					if($rowThisObjectIsBranchedTo['blockID'] == $blockID && 
					$rowThisObjectIsBranchedTo['sectionID'] == $sectionID && 
					$rowThisObjectIsBranchedTo['questionID'] == NULL)
						{
						$ThisObjectIsBranchedTo = true;
						}
					}
				
				//get questions
				//$qQuestions = "SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position 
							//FROM Questions, SectionQuestions
							//WHERE SectionQuestions.sectionID = $sectionID
							//AND SectionQuestions.visible = 1
							//AND Questions.questionID = SectionQuestions.questionID
							//ORDER BY SectionQuestions.position";
				
				//$qResQuestions = mysql_query($qQuestions);
				for($sinst=1;$sinst<=$noOfSectionInstances;$sinst++)
					{
					echo "<div class=\"section\" id=\"div".$blockID."_".$sectionID."_i".$inst."_si".$sinst."\"".($ThisObjectIsBranchedTo?"style=\"display:none\"":"").">";
				
					//begin section content
					if($aItems[$iBlock][$iSection][0]["text"] != "")
						{
						//only output a section title if there is one
						echo "<h3>". $aItems[$iBlock][$iSection][0]["text"];
						if ($aItems[$iBlock][$iSection][0]["instanceable"]==1)
							{
							echo ": ".$sinst;
							} 
						echo "</h3>";
						}
					echo "<div class=\"sectionText\">".$aItems[$iBlock][$iSection][0]["introduction"]."</div>"; 
					//if(mysql_num_rows($qResQuestions)>0)
						//{
						//mysql_data_seek($qResQuestions, 0);
						//}
					//check section type
					switch ($aItems[$iBlock][$iSection][0]["sectionTypeID"]) 
						{
						case 1:
							{
							//if the section's 'normal'
							//loop through questions
							//while($rowQuestions = mysql_fetch_array($qResQuestions))
								//{
								//$questionID = $rowQuestions['questionID'];
							for($iQuestion=1; $iQuestion<=count($aItems[$iBlock][$iSection])-1;$iQuestion++)
								{
								$questionID = $aItems[$iBlock][$iSection][$iQuestion][0]["questionID"];
								//is this question branched to?
								$qThisObjectIsBranchedTo = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
																FROM BranchDestinations";										
								$qResThisObjectIsBranchedTo = mysqli_query($db_connection, $qThisObjectIsBranchedTo);
								$ThisObjectIsBranchedTo = false;
								while($rowThisObjectIsBranchedTo = mysqli_fetch_array($qResThisObjectIsBranchedTo))
									{
									if($rowThisObjectIsBranchedTo['blockID'] == $blockID && 
									$rowThisObjectIsBranchedTo['sectionID'] == $sectionID && 
									$rowThisObjectIsBranchedTo['questionID'] == $questionID)
										{
										$ThisObjectIsBranchedTo = true;
										}
									}
								
								echo "<div class=\"questionNormal\" id=\"div".$blockID."_".$sectionID."_".$questionID."_i".$inst."_si".$sinst."\"".($ThisObjectIsBranchedTo?"style=\"display:none\"":"").">";
								if($allAnswered==false&&$aQuestions[$questionNo][5]==false)
									{
									echo "<span class=\"errorMessage\">This question was not answered</span>";
									}
								if (!$addingInstance && !$deletingInstance && $answersExist=="true")
									{
									if($aItems[$iBlock][$iSection][$iQuestion][0]["comments"]=="true" || $aItems[$iBlock][$iSection][$iQuestion][0]["questionTypeID"] == 4 || $aItems[$iBlock][$iSection][$iQuestion][0]["questionTypeID"] == 5)
										{
										//find out if this question already has comments/text from this user
										$qCommentAnswered = "	SELECT AnswerComments.text
															FROM AnswerComments, Answers
															WHERE Answers.surveyInstanceID = $surveyInstanceID
															AND Answers.blockID = $blockID
															AND Answers.sectionID = $sectionID
															AND Answers.questionID = $questionID
															AND Answers.heraldID = '$heraldID'
															AND Answers.instance = $inst
															AND Answers.sinstance = $sinst
															AND AnswerComments.answerID = Answers.answerID";
										$qResCommentAnswered = mysqli_query($db_connection, $qCommentAnswered);
										}
									}
								
								//get items
								//$qItems = "SELECT Items.itemID, Items.text, QuestionItems.position
										//FROM Items, QuestionItems
										//WHERE QuestionItems.questionID = $rowQuestions['questionID']
										//AND QuestionItems.visible = 1
										//AND Items.itemID = QuestionItems.itemID
										//ORDER BY QuestionItems.position";
							
								//$qResItems = mysql_query($qItems);
								
								switch ($aItems[$iBlock][$iSection][$iQuestion][0]["questionTypeID"])
									{
									case 1: //MCHOIC
										{
										//$recordCount = mysql_num_rows($qResItems);
										$recordCount = count($aItems[$iBlock][$iSection][$iQuestion])-1;
										$mchoicName = $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										echo "<table class=\"normal_1\">";
										echo "	<tr>";
										echo "<td colspan=\"$recordCount\" class=\"question\">$questionNo ".$aItems[$iBlock][$iSection][$iQuestion][0]["text"]."</td>";
										echo " </tr>
												<tr>";
										//loop through items
										for($iItem=1; $iItem<=count($aItems[$iBlock][$iSection][$iQuestion])-1;$iItem++)
											{
											$itemID = $aItems[$iBlock][$iSection][$iQuestion][$iItem]["itemID"];
										//while($rowItems = mysql_fetch_array($qResItems))
											//{
											//$itemID = $rowItems[itemID];
											$mchoicID = $blockID . "_" . $sectionID . "_" . $questionID . "_" . $itemID."_i".$inst . "_si" . $sinst;
											$mchoicValue = $blockID . "_" . $sectionID . "_" . $questionID . "_" . $itemID."_i".$inst . "_si" . $sinst;
											$mchoicCommentName = "comments_" . $blockID . "_" . $sectionID . "_" . $questionID."_i".$inst . "_si" . $sinst;
											$mchoicCommentID = "comments_" . $blockID . "_" . $sectionID . "_" . $questionID."_i".$inst . "_si" . $sinst;
											$itemIsInvolvedInBranching = false;
											//Branching
											$qBranchesFromItem = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
																	FROM Branches, BranchDestinations
																	WHERE Branches.surveyID = $surveyID
																	AND Branches.blockID = $blockID
																	AND Branches.sectionID = $sectionID
																	AND Branches.questionID = $questionID
																	AND Branches.itemID = $itemID
																	AND BranchDestinations.branchID = Branches.branchID";
											$qResBranchesFromItem = mysqli_query($db_connection, $qBranchesFromItem);
											$show = "";
											if(mysqli_num_rows($qResBranchesFromItem)>0)
												{
												$itemIsInvolvedInBranching = true;
												$i = 1;
												//if a branch goes to sections or questions within this block then we need to show them only within this instance of the block
												//if a branch goes to sections or questions within another block then show them within all instances of the block
												while($rowBranchesFromItem = mysqli_fetch_array($qResBranchesFromItem))
													{
													if($rowBranchesFromItem['blockID'] == $blockID)
														{ 
														//if a branch goes to sections or questions within this block then we need to show them only within this instance of the block
														if($rowBranchesFromItem['sectionID'] == $sectionID)
															{
															//if a branch goes to sections or questions within this section then we need to show them only within this instance of the section
															if($i > 1)
																{
																$show = $show . "+";
																}
															$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $inst . "_si" . $sinst: "_i" . $inst . "_si" . $sinst): "_i" . $inst . "_si" . $sinst);
															$i++;
															}
														else
															{
															//find out how many branches are there of the section being branched to
															$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromItem['blockID'],$rowBranchesFromItem['sectionID'],$inst);
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$show = $show . "+";
																	}
																//$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $inst . "_si" . $bsinst : "_i" . $inst . "_si" . $bsinst): "_i" . $inst . "_si" . $bsinst);
																$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $inst . "_si" . $bsinst : "_i" . $inst . "_si" . $bsinst): "_i" . $inst );
																$i++;
																}
															}
														}
													else //i.e. not branching within this block
														{ 
														//find out how many branches are there of the block being branched to
														$noOfBranchInstances = getInstancesForBlock($rowBranchesFromItem['blockID']);
														//find out how many branches are there of the section being branched to
														$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromItem['blockID'],$rowBranchesFromItem['sectionID'],$inst);
														for($binst=1;$binst<=$noOfBranchInstances;$binst++)
															{
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$show = $show . "+";
																	}
																//$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $binst . "_si" . $bsinst : "_i" . $binst . "_si" . $bsinst): "_i" . $binst . "_si" . $bsinst);
																$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $binst . "_si" . $bsinst : "_i" . $binst . "_si" . $bsinst): "_i" . $binst );
																$i++;
																}
															}
														}
													}
												}
											$qBranchesFromQuestion = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
																		FROM Branches, BranchDestinations
																		WHERE Branches.surveyID = $surveyID
																		AND Branches.blockID = $blockID
																		AND Branches.sectionID = $sectionID
																		AND Branches.questionID = $questionID
																		AND Branches.itemID <> $itemID
																		AND BranchDestinations.branchID = Branches.branchID";
											$qResBranchesFromQuestion = mysqli_query($db_connection, $qBranchesFromQuestion);
											$hide = "";
											if(mysqli_num_rows($qResBranchesFromQuestion)>0)
												{
												$itemIsInvolvedInBranching = true;
												$i = 1;
												while($rowBranchesFromQuestion = mysqli_fetch_array($qResBranchesFromQuestion))
													{
													if($rowBranchesFromQuestion['blockID'] == $blockID)
														{
														if($rowBranchesFromQuestion['sectionID'] == $sectionID)
															{
															if($i > 1)
																{
																$hide = $hide . "+";
																}
															//$hide = $hide . "div" . $rowBranchesFromQuestion['blockID'] . ($rowBranchesFromQuestion['sectionID'] != NULL ? "_" . $rowBranchesFromQuestion['sectionID'] . ($rowBranchesFromQuestion['questionID'] != NULL ? "_" . $rowBranchesFromQuestion['questionID'] . "_i" . $inst . "_si" . $sinst : "_i" . $inst . "_si" . $sinst): "_i" . $inst . "_si" . $sinst);
															$hide = $hide . "div" . $rowBranchesFromQuestion['blockID'] . ($rowBranchesFromQuestion['sectionID'] != NULL ? "_" . $rowBranchesFromQuestion['sectionID'] . ($rowBranchesFromQuestion['questionID'] != NULL ? "_" . $rowBranchesFromQuestion['questionID'] . "_i" . $inst . "_si" . $sinst : "_i" . $inst . "_si" . $sinst): "_i" . $inst);
															$i++;
															}
														else
															{
															//find out how many branches are there of the section being branched to
															$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromQuestion['blockID'],$rowBranchesFromQuestion['sectionID'],$inst);
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$hide = $hide . "+";
																	}
																//$hide = $hide . "div" . $rowBranchesFromQuestion['blockID'] . ($rowBranchesFromQuestion['sectionID'] != NULL ? "_" . $rowBranchesFromQuestion['sectionID'] . ($rowBranchesFromQuestion['questionID'] != NULL ? "_" . $rowBranchesFromQuestion['questionID'] . "_i" . $inst . "_si" . $bsinst : "_i" . $inst . "_si" . $bsinst): "_i" . $inst . "_si" . $bsinst);
																$hide = $hide . "div" . $rowBranchesFromQuestion['blockID'] . ($rowBranchesFromQuestion['sectionID'] != NULL ? "_" . $rowBranchesFromQuestion['sectionID'] . ($rowBranchesFromQuestion['questionID'] != NULL ? "_" . $rowBranchesFromQuestion['questionID'] . "_i" . $inst . "_si" . $bsinst : "_i" . $inst . "_si" . $bsinst): "_i" . $inst);
																$i++;
																}
															}
														}
													else
														{
														$noOfBranchInstances = getInstancesForBlock($rowBranchesFromQuestion['blockID']);
														$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromQuestion['blockID'],$rowBranchesFromQuestion['sectionID'],$inst);
														for($binst=1;$binst<=$noOfBranchInstances;$binst++)
															{
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$hide = $hide . "+";
																	}
																//$hide = $hide . "div" . $rowBranchesFromQuestion['blockID'] . ($rowBranchesFromQuestion['sectionID'] != NULL ? "_" . $rowBranchesFromQuestion['sectionID'] . ($rowBranchesFromQuestion['questionID'] != NULL ? "_" . $rowBranchesFromQuestion['questionID'] . "_i" . $binst . "_si" . $bsinst : "_i" . $binst . "_si" . $bsinst): "_i" . $binst . "_si" . $bsinst);
																$hide = $hide . "div" . $rowBranchesFromQuestion['blockID'] . ($rowBranchesFromQuestion['sectionID'] != NULL ? "_" . $rowBranchesFromQuestion['sectionID'] . ($rowBranchesFromQuestion['questionID'] != NULL ? "_" . $rowBranchesFromQuestion['questionID'] . "_i" . $binst . "_si" . $bsinst : "_i" . $binst . "_si" . $bsinst): "_i" . $binst );
																$i++;
																}
															}
														}
													}
												}
											if($itemIsInvolvedInBranching == true)
												{
												echo "<td><input type=\"radio\" value=\"$mchoicValue\" id=\"$mchoicID\" name=\"$mchoicName\" onclick=\"javascript:showAndHideForMCHOIC('$show','$hide');\"/>".$aItems[$iBlock][$iSection][$iQuestion][$iItem]["text"]."</td>";
												}
											else
												{
												echo "<td><input type=\"radio\" value=\"$mchoicValue\" id=\"$mchoicID\" name=\"$mchoicName\"/>".$aItems[$iBlock][$iSection][$iQuestion][$iItem]["text"]."</td>";
												}
											}
										echo "</tr>";
																	
										if($aItems[$iBlock][$iSection][$iQuestion][0]["comments"]=="true")
											{
											echo "	<tr>
														<td colspan=\"$recordCount\">
															<textarea class=\"comments\" id=\"$mchoicCommentID\" name=\"$mchoicCommentName\">";
															if ($addingInstance || $deletingInstance)
																{
																echo stripslashes($_POST[$mchoicCommentName]); 
																}
															elseif($answersExist=="true")
																{
																//if so, write the text into the textarea
																$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
																echo stripslashes($rowCommentAnswered['text']);
																}
															else
																{
																echo "Please comment..";
																}
														echo "</textarea>
														</td>
													</tr>";
											}
										echo "</table>";
										break;
										} 
									case 2: //MSELEC
										{
										echo "<table class=\"normal_2\">";
										echo "	<tr>";
										echo "<td class=\"question\">$questionNo ".$aItems[$iBlock][$iSection][$iQuestion][0]["text"]."</td>";
										echo "  </tr>";
										//loop through items
										for($iItem=1; $iItem<=count($aItems[$iBlock][$iSection][$iQuestion])-1;$iItem++)
											{
											$itemID = $aItems[$iBlock][$iSection][$iQuestion][$iItem]["itemID"];
										//while($rowItems = mysql_fetch_array($qResItems))
											//{
											//$itemID = $rowItems[itemID];
											$mselecName = $blockID . "_" . $sectionID . "_" . $questionID. "_" . $itemID . "_i" . $inst . "_si" . $sinst;
											$mselecID = $blockID . "_" . $sectionID . "_" . $questionID . "_" . $itemID . "_i" . $inst . "_si" . $sinst;
											$mselecCommentName = "comments_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
											$mselecCommentID = "comments_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
											$itemIsInvolvedInBranching = false;
											//Branching
											$qBranchesFromItem = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
																	FROM Branches, BranchDestinations
																	WHERE Branches.surveyID = $surveyID
																	AND Branches.blockID = $blockID
																	AND Branches.sectionID = $sectionID
																	AND Branches.questionID = $questionID
																	AND Branches.itemID = $itemID
																	AND BranchDestinations.branchID = Branches.branchID";
											$qResBranchesFromItem = mysqli_query($db_connection, $qBranchesFromItem);
											$show = "";
											if(mysqli_num_rows($qResBranchesFromItem)>0)
												{
												$itemIsInvolvedInBranching = true;
												$i = 1;
												while($rowBranchesFromItem = mysqli_fetch_array($qResBranchesFromItem))
													{
													if($rowBranchesFromItem['blockID'] == $blockID)
														{
														if($rowBranchesFromItem['sectionID'] == $sectionID)
															{
															if($i > 1)
																{
																$show = $show . "+";
																}
															$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $inst . "_si" . $sinst : "_i" . $inst . "_si" . $sinst): "_i" . $inst . "_si" . $sinst);
															$i++;
															}
														else
															{
															$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromItem['blockID'],$rowBranchesFromItem['sectionID'],$inst);
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$show = $show . "+";
																	}
																$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $inst . "_si" . $bsinst : "_i" . $inst . "_si" . $bsinst): "_i" . $inst . "_si" . $bsinst);
																$i++;
																}
															}
														}
													else
														{
														$noOfBranchInstances = getInstancesForBlock($rowBranchesFromItem['blockID']);
														$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromItem['blockID'],$rowBranchesFromItem['sectionID'],$inst);
														for($binst=1;$binst<=$noOfBranchInstances;$binst++)
															{
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$show = $show . "+";
																	}
																$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $binst . "_si" . $bsinst : "_i" . $binst . "_si" . $bsinst): "_i" . $binst . "_si" . $bsinst);
																$i++;
																}
															}
														}
													}
												}
											echo "<tr>
													<td>";
											if($itemIsInvolvedInBranching == true)
												{
												echo "<input type=\"checkbox\" id=\"$mselecID\" name=\"$mselecName\" onclick=\"javascript:showAndHideForMSELEC(this,'$show');\"/>".$aItems[$iBlock][$iSection][$iQuestion][$iItem]["text"];
												}
											else
												{
												echo "<input type=\"checkbox\" id=\"$mselecID\" name=\"$mselecName\"/>".$aItems[$iBlock][$iSection][$iQuestion][$iItem]["text"];
												}
											echo "	</td>
												</tr>";
											}
										if($aItems[$iBlock][$iSection][$iQuestion][0]["comments"]=="true")
											{
											echo "	<tr>
														<td colspan=\"$recordCount\">
															<textarea class=\"comments\" id=\"$mselecCommentID\" name=\"$mselecCommentName\">";
															if ($addingInstance || $deletingInstance)
																{
																echo stripslashes($_POST[$mselecCommentName]); 
																}
															elseif($answersExist=="true")
																{
																//if so, write the text into the textarea
																$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
																echo stripslashes($rowCommentAnswered['text']);
																}
															else
																{
																echo "Please comment..";
																}
													echo "</textarea>
														</td>
													</tr>";
											}
										echo "</table>";
										break;
										} 
									case 3: //DRDOWN
										{
										echo "<table class=\"normal_3\">";
										echo "	<tr>";
										$drdownName = $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										echo "<td class=\"question\">$questionNo ".$aItems[$iBlock][$iSection][$iQuestion][0]["text"];
										$drdownID = $blockID . "_" . $sectionID . "_" . $questionID. "_i" . $inst . "_si" . $sinst;
										$drdownCommentName = "comments_" . $blockID . "_" . $sectionID . "_" . $questionID. "_i" . $inst . "_si" . $sinst;
										$drdownCommentID = "comments_" . $blockID . "_" . $sectionID . "_" . $questionID. "_i" . $inst . "_si" . $sinst;
										$itemIsInvolvedInBranching = false;
										$branchItem = "";
										$show = "";
										$j=1;
										for($iItem=1; $iItem<=count($aItems[$iBlock][$iSection][$iQuestion])-1;$iItem++)
											{
											$itemID = $aItems[$iBlock][$iSection][$iQuestion][$iItem]["itemID"];
										//while($rowItems = mysql_fetch_array($qResItems))
											//{
											//$itemID = $rowItems[itemID];
											//Branching
											$qBranchesFromItem = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
																	FROM Branches, BranchDestinations
																	WHERE Branches.surveyID = $surveyID
																	AND Branches.blockID = $blockID
																	AND Branches.sectionID = $sectionID
																	AND Branches.questionID = $questionID
																	AND Branches.itemID = $itemID
																	AND BranchDestinations.branchID = Branches.branchID";
											$qResBranchesFromItem = mysqli_query($db_connection, $qBranchesFromItem);
											if(mysqli_num_rows($qResBranchesFromItem)>0)
												{
												$itemIsInvolvedInBranching = true;
												if($j > 1)
													{
													$show = $show . "|";
													$branchItem = $branchItem . "|";
													}
												$branchItem = $branchItem . $blockID . "_" . $sectionID . "_" . $questionID . "_" . $itemID . "_i" . $inst . "_si" . $sinst;
												$i = 1;
												while($rowBranchesFromItem = mysqli_fetch_array($qResBranchesFromItem))
													{
													if($rowBranchesFromItem['blockID'] == $blockID)
														{
														if($rowBranchesFromItem['sectionID'] == $sectionID)
															{
															if($i > 1)
																{
																$show = $show . "+";
																}
															$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $inst . "_si" . $sinst : "_i" . $inst . "_si" . $sinst): "_i" . $inst . "_si" . $sinst);
															$i++;
															}
														else
															{
															$noOfBranchSectionInstances = getInstancesForBlock($rowBranchesFromItem['blockID'],$rowBranchesFromItem['sectionID']);
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$show = $show . "+";
																	}
																$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $inst . "_si" . $bsinst : "_i" . $inst . "_si" . $bsinst): "_i" . $inst . "_si" . $bsinst);
																$i++;
																}
															}
														}
													else
														{
														$noOfBranchInstances = getInstancesForBlock($rowBranchesFromItem['blockID']);
														$noOfBranchSectionInstances = getInstancesForBlock($rowBranchesFromItem['blockID'],$rowBranchesFromItem['sectionID']);
														for($binst=1;$binst<=$noOfBranchInstances;$binst++)
															{
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$show = $show . "+";
																	}
																$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $binst . "_si" . $bsinst : "_i" . $binst . "_si" . $bsinst): "_i" . $binst . "_si" . $bsinst);
																$i++;
																}
															}
														}
													}
												}
											$j++;
											}
										if ($itemIsInvolvedInBranching == true)
											{
											echo "		<select id=\"$drdownID\" name=\"$drdownName\" onChange=\"javascript:showAndHideForDRDOWN(this[this.selectedIndex].value,'$branchItem','$show');\" size=\"1\">";
											}
										else
											{
											echo "		<select id=\"$drdownID\" name=\"$drdownName\" size=\"1\">";
											}
										
										echo "			<option value=\"0\">".$aItems[$iBlock][$iSection][$iQuestion][0]["text"]."</option>";
										//loop through items
										//if(mysql_num_rows($qResItems)>0)
											//{
											//mysql_data_seek($qResItems, 0);
											//}
										for($iItem=1; $iItem<=count($aItems[$iBlock][$iSection][$iQuestion])-1;$iItem++)
											{
											$itemID = $aItems[$iBlock][$iSection][$iQuestion][$iItem]["itemID"];
										//while($rowItems = mysql_fetch_array($qResItems))
											//{
											$drdownOptionValue = $blockID . "_" . $sectionID . "_" . $questionID . "_" . $itemID . "_i" . $inst . "_si" . $sinst;
											echo "			<option value=\"$drdownOptionValue\">".$aItems[$iBlock][$iSection][$iQuestion][$iItem]["text"]."</option>";
											}
										echo "			</select>
													</td>
												</tr>";
										
										if($aItems[$iBlock][$iSection][$iQuestion][0]["comments"]=="true")
											{
											echo "	<tr>
														<td colspan=\"$recordCount\">
															<textarea class=\"comments\" id=\"$drdownCommentID\" name=\"$drdownCommentName\">";
															if ($addingInstance || $deletingInstance)
																{
																echo stripslashes($_POST[$drdownCommentName]); 
																}
															elseif($answersExist=="true")
																{
																//if so, write the text into the textarea
																$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
																echo stripslashes($rowCommentAnswered['text']);
																}
															else
																{
																echo "Please comment..";
																}
													echo "</textarea>
														</td>
													</tr>";
											}
										echo "</table>";
										break;
										} 
									case 4: //TEXT
										{
										//NOTE: No branching possible on a text question
										$textName = $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										$textID = $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										echo "<table class=\"normal_4\">";
										echo "	<tr>";
										echo " 		<td class=\"question\">$questionNo ".$aItems[$iBlock][$iSection][$iQuestion][0]["text"]."</td>";
										echo "	</tr>";
										echo "	<tr>
													<td colspan=\"$recordCount\">
														<textarea class=\"comments\" id=\"$textID\" name=\"$textName\" rows=\"5\">";
														if ($addingInstance || $deletingInstance)
															{
															echo stripslashes($_POST[$textName]); 
															}
														elseif($answersExist=="true")
															{
															//if so, write the text into the textarea
															$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
															echo stripslashes($rowCommentAnswered['text']);
															}
												echo "</textarea>
													</td>
												</tr>";
										echo "</table>";
										break;
										}
									case 5: //lgtext
										{
										//NOTE: No branching possible on a text question
										$textName = $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										$textID = $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										echo "<table class=\"normal_4\">";
										echo "	<tr>";
										echo " 		<td class=\"question\">$questionNo ".$aItems[$iBlock][$iSection][$iQuestion][0]["text"]."</td>";
										echo "	</tr>";
										echo "	<tr>
													<td colspan=\"$recordCount\">
														<!--<textarea class=\"richtext\" id=\"$textID\" name=\"$textName\" rows=\"30\">-->
														<textarea class=\"ckeditor\" id=\"$textID\" name=\"$textName\" rows=\"30\">";
														if ($addingInstance || $deletingInstance)
															{
															echo stripslashes($_POST[$textName]); 
															}
														elseif($answersExist=="true")
															{
															//if so, write the text into the textarea
															$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
															echo stripslashes($rowCommentAnswered['text']);
															}
														else
															{
															//Only reuiqred on learntech.imsu.ox.ac.uk where db exists
															//connect to heraldID and name database
															//$dbstudent_connection = mysql_connect ($dbstudent_info['host'], $dbstudent_info['username'], $dbstudent_info['password']) or die (mysqli_error());
															//$db_select = mysql_select_db ($dbstudent_info['dbname'], $dbstudent_connection) or die (mysqli_error());
															$dbstudent_connection = mysqli_connect ($dbstudent_info['host'], $dbstudent_info['username'], $dbstudent_info['password'],$dbstudent_info['dbname']) or die (mysqli_error());
															$qStudentName = "	SELECT LASTNAME, FORENAMES
																		FROM cards
																		WHERE USERNAME = '$heraldID'";
															$qResStudentName = @mysqli_query($dbstudent_connection, $qStudentName);
															if (($qResStudentName == false))
																{
																echo "problem querying cards" . mysqli_error($db_connection);
																}
															else
																{
																if (mysqli_num_rows($qResStudentName)==1)
																	{
																	$rowStudentName = mysqli_fetch_array($qResStudentName);
																	echo "<h1>Name: ".$rowStudentName['LASTNAME'] . ", " . $rowStudentName['FORENAMES']."</h1>";
																	}
																else
																	{
																	echo "Name not found";
																	}
																}	
															mysqli_close($dbstudent_connection);
															//$db_connection = mysql_connect ($db_info['host'], $db_info['username'], $db_info['password']) or die (mysqli_error());
															//$db_select = mysql_select_db ($db_info['dbname'], $db_connection) or die (mysqli_error());
															$db_connection = mysqli_connect ($db_info['host'], $db_info['username'], $db_info['password'], $db_info['dbname']) or die (mysqli_error());
															}
													echo "</textarea>
													</td>
												</tr>";
										echo "</table>";
										break;
										}
									case 6: //date
										{
										//NOTE: No branching possible on a text question
										$dateDayName = "day_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										$dateDayID = "day_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										$dateMonthName = "month_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										$dateMonthID = "month_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										$dateYearName = "year_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										$dateYearID = "year_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										if (!$addingInstance && !$deletingInstance && $answersExist=="true")
											{
											//find out if this question already has a date from this user
											$qDateAnswered = "	SELECT AnswerDates.date
																FROM AnswerDates, Answers
																WHERE Answers.surveyInstanceID = $surveyInstanceID
																AND Answers.blockID = $blockID
																AND Answers.sectionID = $sectionID
																AND Answers.questionID = $questionID
																AND Answers.heraldID = '$heraldID'
																AND Answers.instance = $inst
																AND Answers.sinstance = $sinst
																AND AnswerDates.answerID = Answers.answerID";
											$qResDateAnswered = mysqli_query($db_connection, $qDateAnswered);
											}
											
										echo "<table class=\"normal_4\">";
										echo "	<tr>";
										echo " 		<td class=\"question\">$questionNo ".$aItems[$iBlock][$iSection][$iQuestion][0]["text"]."</td>";
										echo "	</tr>";
										echo "	<tr>
													<td colspan=\"$recordCount\">";
														if ($addingInstance || $deletingInstance)
															{
															$dayValue = $_POST[$dateDayName];
															$monthValue = $_POST[$dateMonthName];
															$yearValue = $_POST[$dateYearName];
															}
														elseif($answersExist=="true")
															{
															//if so, write the text into the textarea
															$rowDateAnswered = mysqli_fetch_array($qResDateAnswered);
															$aDate = explode("-",$rowDateAnswered['date']);
															$dayValue = intval($aDate[2]);//date("d",$surveyInstanceStartDate);
															$monthValue = intval($aDate[1]);//date("m",$surveyInstanceStartDate);
															$yearValue = intval($aDate[0]);//date("Y",$surveyInstanceStartDate);	
															}
														else
															{
															$dayValue = 0;
															$monthValue = 0;
															$yearValue = 0;
															}
														$yearMin = date("Y")-10;
														$yearMax = date("Y")+10;
														echo "
														<select id=\"$dateDayID\" name=\"$dateDayName\" size=\"1\">
															<option value=\"0\">dd</option>";
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
													echo "	<select id=\"".$dateMonthID."\" name=\"".$dateMonthName."\" size=\"1\" onChange=\"buildsel(this[this.selectedIndex].value,".$dateYearName."[".$dateYearName.".selectedIndex].value,".$dateDayName.")\">
															<option value=\"0\">mm</option>";
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
													echo "	<select id=\"".$dateYearID."\" name=\"".$dateYearName."\" size=\"1\" onChange=\"buildsel(".$dateMonthName."[".$dateMonthName.".selectedIndex].value,this[this.selectedIndex].value,".$dateDayName.")\">
															<option value=\"0\">yyyy</option>";
															for($i=$yearMin;$i<=$yearMax;$i++)
																{
																echo "		<option ";
																if($i==$yearValue)
																	{
																	echo " selected ";
																	}
																	echo "value=\"$i\">$i</option>";
																}
													echo "		</select>
													</td>
												</tr>";
										echo "</table>";
										break;
										} 
									}
								echo "</div>";
								//increment question number
								$questionNo = $questionNo + 1;
								}
							break;
							}
						case 2:
							{
							//NOTE: Only MCHOIC and MSELEC Questions can be displayed in a matrix
							echo "<div class=\"sectionMatrix\">";
							//if the section's a matrix
							//reset this to make sure top row is ouput once
							$bCreatedTopRow = false;
							echo "<table class=\"matrix\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
							//toggler for rows - so that appropriate style can be set
							$bRowOdd = true;
							//loop through questions
							for($iQuestion=1; $iQuestion<=count($aItems[$iBlock][$iSection])-1;$iQuestion++)
								{
								$questionID = $aItems[$iBlock][$iSection][$iQuestion][0]["questionID"];
							//while($rowQuestions = mysql_fetch_array($qResQuestions))
								//{
								//$questionID = $rowQuestions['questionID'];
								//get items
								//$qItems = "SELECT Items.itemID, Items.text, QuestionItems.position
										//FROM Items, QuestionItems
										//WHERE QuestionItems.questionID = $questionID
										//AND QuestionItems.visible = 1
										//AND Items.itemID = QuestionItems.itemID
										//ORDER BY QuestionItems.position";
							
								//$qResItems = mysql_query($qItems);
								//$recordCount = mysql_num_rows($qResItems);
								$recordCount = count($aItems[$iBlock][$iSection][$iQuestion])-1;
								$noOfColumns = $recordCount+1;
								
									if($bCreatedTopRow == false)
										{
										//populate top row of matrix - NOTE assumes that all questions in this section 
										//will have the same no. of items! Only needs to be executed for first question 
										//in a matrix section.
										echo "<tr class=\"matrixHeader\">
												<th></th>";
										for($iItem=1; $iItem<=count($aItems[$iBlock][$iSection][$iQuestion])-1;$iItem++)
											{
										//while($rowItems = mysql_fetch_array($qResItems))
											//{
											echo "<th align=\"center\">".$aItems[$iBlock][$iSection][$iQuestion][$iItem]["text"]."</th>";
											}
										echo "</tr>";
										$bCreatedTopRow = true;
										}
									//resets the mysql_fetch_array to start at the beginning again
									//if(mysql_num_rows($qResItems)>0)
										//{
										//mysql_data_seek($qResItems, 0);
										//}
									
									if($bRowOdd)
										{
										echo "<tr class=\"matrixRowOdd\">";
										}
									else
										{
										echo "<tr class=\"matrixRowEven\">";
										}
									$mchoicName = $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
									echo "<td class=\"question\" align=\"left\">$questionNo ".$aItems[$iBlock][$iSection][$iQuestion][0]["text"];
									if($allAnswered==false&&$aQuestions[$questionNo][5]==false)
										{
										echo "This question was not answered";
										}
									echo "</td>";
									//loop through items
									//note cannot branch from an MCHOIC or an MSELEC from within a matrix
									for($iItem=1; $iItem<=count($aItems[$iBlock][$iSection][$iQuestion])-1;$iItem++)
										{
									//while($rowItems = mysql_fetch_array($qResItems))
										//{
										//$itemID = $rowItems[itemID];
										$itemID = $aItems[$iBlock][$iSection][$iQuestion][$iItem]["itemID"];
										switch ($aItems[$iBlock][$iSection][$iQuestion][0]["questionTypeID"])
											{
											case 1: //MCHOIC					
												{
												$mchoicID = $blockID . "_" . $sectionID . "_" . $questionID . "_" . $itemID . "_i" . $inst . "_si" . $sinst;
												$mchoicValue = $blockID . "_" . $sectionID . "_" . $questionID . "_" . $itemID . "_i" . $inst . "_si" . $sinst;
												echo "<td align=\"center\">
														<input type=\"radio\" value=\"$mchoicValue\" id=\"$mchoicID\" name=\"$mchoicName\"/>
													</td>";
												break;
												}
											case 2: //MSELEC			
												{
												$mselecName = $blockID . "_" . $sectionID . "_" . $questionID. "_" . $itemID . "_i" . $inst . "_si" . $sinst;
												$mselecID = $blockID . "_" . $sectionID . "_" . $questionID . "_" . $itemID . "_i" . $inst . "_si" . $sinst;
												echo "<td align=\"center\"><input type=\"checkbox\" id=\"$mselecID\" name=\"$mselecName\"/></td>";
												break;
												}
											}
										}
									echo "</tr>";
									if($bRowOdd)
										{
										echo "<tr class=\"matrixRowOdd\">";
										}
									else
										{
										echo "<tr class=\"matrixRowEven\">";
										}
									if($aItems[$iBlock][$iSection][$iQuestion][0]["comments"]=="true")
										{
										if (!$addingInstance && !$deletingInstance && $answersExist=="true")
											{
											//find out if this question has already been answered by this user
											$qCommentAnswered = "	SELECT AnswerComments.text
																FROM AnswerComments, Answers
																WHERE Answers.surveyInstanceID = $surveyInstanceID
																AND Answers.blockID = $blockID
																AND Answers.sectionID = $sectionID
																AND Answers.questionID = $questionID
																AND Answers.heraldID = '$heraldID'
																AND Answers.instance = $inst
																AND Answers.sinstance = $sinst
																AND AnswerComments.answerID = Answers.answerID";
											$qResCommentAnswered = mysqli_query($db_connection, $qCommentAnswered);
											}
										switch ($aItems[$iBlock][$iSection][$iQuestion][0]["questionTypeID"])
											{
											case 1: //MCHOIC					
												{
												$CommentName = "comments_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
												$CommentID = "comments_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
												break;
												}
											case 2: //MSELEC			
												{
												$CommentName = "comments_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
												$CommentID = "comments_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
												break;
												}
											}
											echo "	<td colspan=\"$noOfColumns\">";
											echo "	<textarea class=\"comments\" id=\"$CommentID\" name=\"$CommentName\"";
															/*if($surveyID==39&&$blockID==263&&$sectionID==376){  //special condition for paediatrics
																echo "placeholder='If you have answered: Strongly Agree; Disagree; or Strongly Disagree, please tell us why...'";
																}
															elseif($surveyID==39&&$blockID==132&&$sectionID==188){  //special condition for paediatrics
																echo "placeholder='If you have answered: Excellent or Poor, please tell us why...'";
																}
															else{
																echo "placeholder='Please comment..'";
																}*/
														echo">";
														if ($addingInstance || $deletingInstance)
															{
															echo stripslashes($_POST[$CommentName]);
															}
														elseif($answersExist=="true")
															{
															//if so, write the text into the textarea
															$rowCommentAnswered = mysqli_fetch_array($qResCommentAnswered);
															echo stripslashes($rowCommentAnswered['text']);
															} 
														else
															{
															if($surveyID==39&&$blockID==263&&$sectionID==376){  //special condition for paediatrics
																echo "If you have answered: Strongly Agree; Disagree; or Strongly Disagree, please tell us why...";
																}
															elseif($surveyID==39&&$blockID==132&&$sectionID==188){  //special condition for paediatrics
																echo "If you have answered: Excellent or Poor, please tell us why...";
																}
															else{
																echo "Please comment..";
																}
															}
											echo "</textarea>
													</td>";
										}
									echo "</tr>";
									$bRowOdd = !$bRowOdd;
								//increment question number
								$questionNo = $questionNo + 1;
								}
							echo "</table>";
							echo "</div>";
							break;
							}
						}
					echo $aItems[$iBlock][$iSection][0]["epilogue"]."<br/>";
					//echo $rowSections[epilogue]."<br/>";
					//end the section div
					if($sectionIsInstanceable == true && $sinst == $noOfSectionInstances)
						{
						echo "<br\>";
						echo "<input type=\"hidden\" id=\"hCurrentSectionInstance" . "_" . $blockID . "_" . $sectionID . "_i" . $inst . "\" name=\"hCurrentSectionInstance" . "_" . $blockID . "_" . $sectionID . "_i" . $inst . "\" value=\"" . $sinst."\">";
						echo "<table width=\"100%\"><tr><td>Do you want to repeat this section ";
						//if($rowSections['text'] != "")
							//{
							//only output a block title if there is one
							//echo "(\"".$rowSections['text']."\")";
							//}
						if($aItems[$iBlock][$iSection][0]["text"] != "")
							{
							//only output a block title if there is one
							echo "(\"".$aItems[$iBlock][$iSection][0]["text"]."\")";
							}
						echo "? </td><td>";
						echo "<input type=\"submit\" id=\"bAddSectionInstance" . "_" . $blockID . "_" . $sectionID . "_i" . $inst ."\" name=\"bAddSectionInstance" . "_" . $blockID . "_" . $sectionID . "_i" . $inst ."\" value=\"Repeat section\" onClick=\"javascript:sethAddedToTrue()\">";
						echo "</td></tr>";
						if($sinst > 1)
							{
							//i.e if there is already more than one instance
							echo "<tr><td>Do you want to delete this section ";
							//if($rowSections['text'] != "")
								//{
								//only output a block title if there is one
								//echo "(\"".$rowSections['text'].": ".$sinst."\")";
								//}
							if($aItems[$iBlock][$iSection][0]["text"] != "")
								{
								//only output a block title if there is one
								echo "(\"".$aItems[$iBlock][$iSection][0]["text"].": ".$sinst."\")";
								}
							echo "? </td><td>";
							echo "<input type=\"submit\" id=\"bDeleteSectionInstance" . "_" . $blockID . "_" . $sectionID . "_i" . $inst ."\" name=\"bDeleteSectionInstance" . "_" . $blockID . "_" . $sectionID . "_i" . $inst ."\" value=\"Delete section\" onClick=\"javascript:sethDeletedToTrue()\">";
							echo "</td></tr>";
							}
						echo "</table>";
						}
					//end section content
					echo "</div>";
					}
				}	
			//end block content
			//echo $rowBlocks[epilogue]."<br/>";
			echo $aItems[$iBlock][0]["epilogue"]."<br/>";
			//end the block div
			//i.e only show 'Add' button at the end of a list of instanceable blocks.
			if($blockIsInstanceable == true && $inst == $noOfInstances)
				{
				echo "<br\>";
				echo "<input type=\"hidden\" id=\"hCurrentInstance" . "_" . $blockID . "\" name=\"hCurrentInstance" . "_" . $blockID . "\" value=\"" . $inst."\">";
				echo "<table width=\"100%\"><tr><td>Do you want to repeat this block ";
				//if($rowBlocks['text'] != "")
					//{
					//only output a block title if there is one
					//echo "(\"".$rowBlocks['text']."\")";
					//}
				if($aItems[$iBlock][0]["text"] != "")
					{
					//only output a block title if there is one
					echo "(\"".$aItems[$iBlock][0]["text"]."\")";
					}
				echo "? </td><td>";
				echo "<input type=\"submit\" id=\"bAddInstance" . "_" . $blockID ."\" name=\"bAddInstance" . "_" . $blockID . "\" value=\"Repeat block\" onClick=\"javascript:sethAddedToTrue()\">";
				echo "</td></tr>";
				if($inst > 1)
					{
					//i.e if there is already more than one instance
					echo "<tr><td>Do you want to delete this block ";
					//if($rowBlocks['text'] != "")
						//{
						//only output a block title if there is one
						//echo "(\"".$rowBlocks['text'].": ".$inst."\")";
						//}
					if($aItems[$iBlock][0]["text"] != "")
						{
						//only output a block title if there is one
						echo "(\"".$aItems[$iBlock][0]["text"].": ".$inst."\")";
						}
					echo "? </td><td>";
					echo "<input type=\"submit\" id=\"bDeleteInstance" . "_" . $blockID ."\" name=\"bDeleteInstance" . "_" . $blockID . "\" value=\"Delete block\" onClick=\"javascript:sethDeletedToTrue()\">";
					echo "</td></tr>";
					}
				echo "</table>";
				}
			echo "</div>";	
			}
		}
	//*******************************************************************************************************************************
	//END SHOWING SURVEY
	//*******************************************************************************************************************************
	
	//***********************************************************************
	// REPOPULATING THE FORM AFTER SUBMIT
	//
	// The next section of code deals with returning radio, checkboxes or 
	// drop-downs to the state they were in before the form was submittd.
	//
	// This has to be done at the end so that any assiciated showing and
	// hiding of sections can also be done.
	//
	// Textareas are simply filled in-situ.
	//
	//***********************************************************************
	//get blocks
	//$qBlocks = "SELECT Blocks.blockID, Blocks.title, Blocks.introduction, Blocks.epilogue, SurveyBlocks.position, Blocks.instanceable
				//FROM Blocks, SurveyBlocks 
				//WHERE SurveyBlocks.surveyID = $surveyID
				//AND SurveyBlocks.visible = 1
				//AND Blocks.blockID = SurveyBlocks.blockID
				//ORDER BY SurveyBlocks.position";
	
	//$qResBlocks = mysql_query($qBlocks);
	//counter for questions 
	//don't do any of this unless some answers exist
	if(($answersExist=="true" || $addingInstance || $deletingInstance || isset($_POST['bSaveSurvey'])) && !isset($_POST['bSubmitSurvey']))
		{
	for ($iBlock=1; $iBlock<=count($aItems);$iBlock++)
		{
		$blockID = $aItems[$iBlock][0]["blockID"];
	//while($rowBlocks = mysql_fetch_array($qResBlocks))
		//{
		$blockIsInstanceable = false;
		//$blockID = $rowBlocks['blockID'];
		if($aItems[$iBlock][0]["instanceable"]==1)
			{
			$blockIsInstanceable = true;
			if (isset($_POST[bAddInstance . "_" . $blockID]))
				{
				$noOfCurrentInstances = $_POST[hCurrentInstance . "_" . $blockID];
				$noOfInstances = $noOfCurrentInstances + 1;
				}
			elseif (isset($_POST[bDeleteInstance . "_" . $blockID]))
				{
				$noOfCurrentInstances = $_POST[hCurrentInstance . "_" . $blockID];
				$noOfInstances = $noOfCurrentInstances - 1;
				}
			else
				{
				$noOfInstances = getInstancesForBlock($blockID);
				}
			}
		else
			{
			$blockIsInstanceable = false;
			$noOfInstances = 1;
			}
		//$qSections = "SELECT Sections.sectionID, Sections.title, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, Sections.instanceable 
					//FROM Sections, BlockSections 
					//WHERE BlockSections.blockID = $blockID
					//AND BlockSections.visible = 1
					//AND Sections.sectionID = BlockSections.sectionID
					//ORDER BY BlockSections.position";
		
		//$qResSections = mysql_query($qSections);
		for($inst=1;$inst<=$noOfInstances;$inst++)
			{
			//if(mysql_num_rows($qResSections)>0)
				//{
				//mysql_data_seek($qResSections, 0);
				//}
			for($iSection=1; $iSection<=count($aItems[$iBlock])-1;$iSection++)
				{
				$sectionID = $aItems[$iBlock][$iSection][0]["sectionID"];
				$sectionIsInstanceable = false;
				if($aItems[$iBlock][$iSection][0]["instanceable"]==1)		
			//while($rowSections = mysql_fetch_array($qResSections))
				//{
				//$sectionID = $rowSections['sectionID'];
				//$sectionIsInstanceable = false;
				//if($rowSections[instanceable]==1)
					{
					$sectionIsInstanceable = true;
					if (isset($_POST[bAddSectionInstance . "_" . $blockID . "_" . $sectionID . "_i" . $inst]))
						{
						$noOfCurrentSectionInstances = $_POST[hCurrentSectionInstance . "_" . $blockID . "_" . $sectionID . "_i" . $inst];
						$noOfSectionInstances = $noOfCurrentSectionInstances + 1;
						}
					elseif (isset($_POST[bDeleteSectionInstance . "_" . $blockID . "_" . $sectionID . "_i" . $inst]))
						{
						$noOfCurrentSectionInstances = $_POST[hCurrentSectionInstance . "_" . $blockID . "_" . $sectionID . "_i" . $inst];
						$noOfSectionInstances = $noOfCurrentSectionInstances - 1;
						}
					else
						{
						$noOfSectionInstances = getInstancesForSection($blockID, $sectionID,$inst);
						}
					}
				else
					{
					$sectionIsInstanceable = false;
					$noOfSectionInstances = 1;
					}
				//get questions
				//$qQuestions = "SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position 
							//FROM Questions, SectionQuestions
							//WHERE SectionQuestions.sectionID = $sectionID
							//AND SectionQuestions.visible = 1
							//AND Questions.questionID = SectionQuestions.questionID
							//ORDER BY SectionQuestions.position";
				
				//$qResQuestions = mysql_query($qQuestions);
				for($sinst=1;$sinst<=$noOfSectionInstances;$sinst++)
					{
					//if(mysql_num_rows($qResQuestions)>0)
						//{
						//mysql_data_seek($qResQuestions, 0);
						//}
					for($iQuestion=1; $iQuestion<=count($aItems[$iBlock][$iSection])-1;$iQuestion++)
						{
						$questionID = $aItems[$iBlock][$iSection][$iQuestion][0]["questionID"];
						//echo "questionID = ". $questionID."<br/>";
					//while($rowQuestions = mysql_fetch_array($qResQuestions))
						//{
						//$questionID = $rowQuestions['questionID'];
						if (!$addingInstance && !$deletingInstance)
							{
							//only do this query if the user has an entry in the 
							//find out if this question has already been answered by this user
							$qItemAnswered = "	SELECT AnswerItems.itemID
												FROM AnswerItems, Answers
												WHERE Answers.surveyInstanceID = $surveyInstanceID
												AND Answers.blockID = $blockID
												AND Answers.sectionID = $sectionID
												AND Answers.questionID = $questionID
												AND Answers.heraldID = '$heraldID'
												AND Answers.instance = $inst
												AND Answers.sinstance = $sinst
												AND AnswerItems.AnswerID = Answers.answerID";
							$qResItemAnswered = mysqli_query($db_connection, $qItemAnswered);
							}
						//write a bit of javascript to click the appropriate button
						$questionType = $aItems[$iBlock][$iSection][$iQuestion][0]["questionTypeID"];
						switch ($questionType)
							{
							case 1:
								{
								$aItemID = "";
								if ($addingInstance || $deletingInstance)
									{
									$mchoicName = $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
									$mchoicID = $_POST[$mchoicName];
									if($mchoicID != "")
										{
										echo "	<script language=\"javascript\" type=\"text/javascript\">";
											echo "document.getElementById(\"$mchoicID\").click();";
										echo "	</script>";
										}
									//echo "mchoicID = " . $mchoicID;
									}
								else if(mysqli_num_rows($qResItemAnswered)==1)
									{
									$rowItemAnswered = mysqli_fetch_array($qResItemAnswered);
									$mchoicID = $blockID . "_" . $sectionID . "_" . $questionID . "_" . $rowItemAnswered[itemID] . "_i" . $inst . "_si" . $sinst;
									echo "	<script language=\"javascript\" type=\"text/javascript\">";
										echo "document.getElementById(\"$mchoicID\").click();";
									echo "	</script>";
									//echo "qItemAnswered = ". $qItemAnswered;
									}
								break;
								}
							case 2:
								{
								if (!$addingInstance && !$deletingInstance)
									{
									if(mysqli_num_rows($qResItemAnswered)>0)
										{
										while($rowItemAnswered = mysqli_fetch_array($qResItemAnswered))
											{
											$itemID = $rowItemAnswered['itemID'];
											$mselecID = $blockID . "_" . $sectionID . "_" . $questionID . "_" . $itemID . "_i" . $inst . "_si" . $sinst;
											echo "	<script language=\"javascript\" type=\"text/javascript\">";
											echo "	document.getElementById(\"$mselecID\").checked = true;";
											echo "	</script>";
											}
										}
									}
								for($iItem=1; $iItem<=count($aItems[$iBlock][$iSection][$iQuestion])-1;$iItem++)
									{
									$itemID = $aItems[$iBlock][$iSection][$iQuestion][$iItem]["itemID"];
									if ($addingInstance || $deletingInstance)
										{
										$mselecID = $blockID . "_" . $sectionID . "_" . $questionID . "_" . $itemID . "_i" . $inst . "_si" . $sinst;
										$mselecName = $blockID . "_" . $sectionID . "_" . $questionID . "_" . $itemID . "_i" . $inst . "_si" . $sinst;
										if ($_POST[$mselecName]=="on")
											{
											echo "	<script language=\"javascript\" type=\"text/javascript\">";
											echo "	document.getElementById(\"$mselecID\").checked = true;";
											echo "	</script>";
											}
										}
									//Branching
									$qBranchesFromItem = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
															FROM Branches, BranchDestinations
															WHERE Branches.surveyID = $surveyID
															AND Branches.blockID = $blockID
															AND Branches.sectionID = $sectionID
															AND Branches.questionID = $questionID
															AND Branches.itemID = $itemID
															AND BranchDestinations.branchID = Branches.branchID";
									$qResBranchesFromItem = mysqli_query($db_connection, $qBranchesFromItem);
									$show = "";
									if(mysqli_num_rows($qResBranchesFromItem)>0)
										{
										$i = 1;
										while($rowBranchesFromItem = mysqli_fetch_array($qResBranchesFromItem))
											{
											if($rowBranchesFromItem['blockID'] == $blockID)
												{
												if($rowBranchesFromItem['sectionID'] == $sectionID)
													{
													if($i > 1)
														{
														$show = $show . "+";
														}
													$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $inst . "_si" . $sinst : "_i" . $inst . "_si" . $sinst): "_i" . $inst . "_si" . $sinst);
													$i++;
													}
												else
													{
													$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromItem['blockID'],$rowBranchesFromItem['sectionID'],$inst);
													for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
														{
														if($i > 1)
															{
															$show = $show . "+";
															}
														$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $inst . "_si" . $bsinst : "_i" . $inst . "_si" . $bsinst): "_i" . $inst . "_si" . $bsinst);
														$i++;	
														}
													}
												}
											else
												{
												$noOfBranchInstances = getInstancesForBlock($rowBranchesFromItem['blockID']);
												$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromItem['blockID'],$rowBranchesFromItem['sectionID'],$inst);
												for($binst=1;$binst<=$noOfBranchInstances;$binst++)
													{	
													for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
														{
														if($i > 1)
															{
															$show = $show . "+";
															}
														$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $binst . "_si" . $bsinst : "_i" . $binst . "_si" . $bsinst): "_i" . $binst . "_si" . $bsinst);
														$i++;
														}
													}
												}
											}
										echo "	<script language=\"javascript\" type=\"text/javascript\">";
											echo "	showAndHideForMSELEC('".$mselecID."','".$show."');";
										echo "	</script>";
											
										}
									}
								//echo "Case 2";
								break;
								}
							case 3:
								{
								$drdownID = $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
								$showvalue = false;
								if ($addingInstance || $deletingInstance)
									{
									$drdownOptionValue = $_POST[$drdownID];
									if($drdownOptionValue!="" &&  $drdownOptionValue!="0")
										{
										$showvalue = true; //used to decide whether to write javascript to update value
										}
									}
								else
									{
									if(mysqli_num_rows($qResItemAnswered)==1)
										{
										$rowItemAnswered = mysqli_fetch_array($qResItemAnswered);
										$drdownOptionValue = $blockID . "_" . $sectionID . "_" . $questionID . "_" . $rowItemAnswered['itemID'] . "_i" . $inst . "_si" . $sinst;
										$showvalue = true;
										}
									}
								//get items
								//$qItems = "SELECT Items.itemID, Items.text, QuestionItems.position
										//FROM Items, QuestionItems
										//WHERE QuestionItems.questionID = $rowQuestions['questionID']
										//AND QuestionItems.visible = 1
										//AND Items.itemID = QuestionItems.itemID
										//ORDER BY QuestionItems.position";
							
								//$qResItems = mysql_query($qItems);
								$branchItem = "";
								$show = "";
								for($iItem=1; $iItem<=count($aItems[$iBlock][$iSection][$iQuestion])-1;$iItem++)
									{
									$itemID = $aItems[$iBlock][$iSection][$iQuestion][$iItem]["itemID"];
								//while($rowItems = mysql_fetch_array($qResItems))
									//{
									//$itemID = $rowItems[itemID];
									$j=1;
									//Branching
									$qBranchesFromItem = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
															FROM Branches, BranchDestinations
															WHERE Branches.surveyID = $surveyID
															AND Branches.blockID = $blockID
															AND Branches.sectionID = $sectionID
															AND Branches.questionID = $questionID
															AND Branches.itemID = $itemID
															AND BranchDestinations.branchID = Branches.branchID";
									$qResBranchesFromItem = mysqli_query($db_connection, $qBranchesFromItem);
									if(mysqli_num_rows($qResBranchesFromItem)>0)
										{
										if($j > 1)
											{
											$show = $show . "|";
											$branchItem = $branchItem . "|";
											}
										$branchItem = $branchItem . $blockID . "_" . $sectionID . "_" . $questionID . "_" . $itemID . "_i" . $inst . "_si" . $sinst;
										$i = 1;
										while($rowBranchesFromItem = mysqli_fetch_array($qResBranchesFromItem))
											{
											if($rowBranchesFromItem['blockID'] == $blockID)
												{
												if($rowBranchesFromItem['sectionID'] == $sectionID)
													{
													if($i > 1)
														{
														$show = $show . "+";
														}
													$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $inst . "_si" . $sinst : "_i" . $inst . "_si" . $sinst): "_i" . $inst . "_si" . $sinst);
													$i++;
													}
												else
													{
													$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromItem['blockID'],$rowBranchesFromItem['sectionID'],$inst);
													for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
														{
														if($i > 1)
															{
															$show = $show . "+";
															}
														$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $inst . "_si" . $bsinst : "_i" . $inst . "_si" . $bsinst): "_i" . $inst . "_si" . $bsinst);
														$i++;
														}
													}
												}
											else
												{
												$noOfBranchInstances = getInstancesForBlock($rowBranchesFromItem['blockID']);
												$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromItem['blockID'],$rowBranchesFromItem['sectionID'],$inst);
												for($binst=1;$binst<=$noOfBranchInstances;$binst++)
													{
													for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
														{
														if($i > 1)
															{
															$show = $show . "+";
															}
														$show = $show . "div" . $rowBranchesFromItem['blockID'] . ($rowBranchesFromItem['sectionID'] != NULL ? "_" . $rowBranchesFromItem['sectionID'] . ($rowBranchesFromItem['questionID'] != NULL ? "_" . $rowBranchesFromItem['questionID'] . "_i" . $binst . "_si" . $bsinst : "_i" . $binst . "_si" . $bsinst): "_i" . $binst . "_si" . $bsinst);
														$i++;
														}
													}
												}
											}
										$j++;
										}
									}
								if ($showvalue == true)
									{
									echo "	<script language=\"javascript\" type=\"text/javascript\">";
									echo "	for(i=0;i<document.getElementById(\"$drdownID\").length;i++)
												{
												if (document.getElementById(\"$drdownID\").options[i].value==\"".$drdownOptionValue."\")
													{
													document.getElementById(\"$drdownID\").options[i].selected = true;
													showAndHideForDRDOWN('".$drdownOptionValue."','$branchItem','$show');
													}
												}";
									echo "	</script>";
									}
								//echo "Case 3";
								break;
								}
							}
						}
					}
				//end Sections
				}
			//end instances
			}
		//end Blocks
		}
	}	

echo "	<input type=\"hidden\" id=\"surveyInstanceID\" name=\"surveyInstanceID\" value=\"$surveyInstanceID\">"; 
//only show this if it is before the finish date
//$qSurveyInstances = "SELECT surveyInstanceID, surveyID, title, startDate, finishDate 
			//FROM SurveyInstances
			//WHERE ((startDate <= CURDATE()
			//AND finishDate >= CURDATE())
			//OR (startDate IS NULL
			//AND finishDate >= CURDATE())
			//OR (startDate <= CURDATE()
			//AND finishDate IS NULL)
			//OR (startDate IS NULL
			//AND finishDate IS NULL))
			//AND surveyInstanceID = $surveyInstanceID";

//$qResSurveyInstances = @mysql_query($qSurveyInstances, $db_connection);
					
//if (($qResSurveyInstances == false))
	//{
	//echo "problem querying SurveyInstances" . mysqli_error($db_connection);
	//}
//else if (mysql_num_rows($qResSurveyInstances)==1)
	//{

if (($unixStartDate <= time() && $unixFinishDate >= time()) || ($unixStartDate == "null" && $unixFinishDate >= time()) || ($unixStartDate <= time() && $unixFinishDate == "null") || ($unixStartDate == "null" && $unixFinishDate == "null"))
	{
	//$rowSurveyInstances = mysql_fetch_array($qResSurveyInstances);
	//is the student allowed to Save the survey?
	//note that Save and Submit buttons are disbled initially anfd then enabled by Javascript
	//This will prevent people without Javascript from submitting the form
	if($surveyAllowSave == "true")
		{
		echo "<input type=\"submit\" value=\"Save for now\" id=\"bSaveSurvey\" name=\"bSaveSurvey\" onClick=\"javascript:sethSavedToTrue()\" disabled>";
		}
	echo "&nbsp;<input type=\"submit\" value=\"Final submission\" id=\"bSubmitSurvey\" name=\"bSubmitSurvey\" disabled>";
	echo "	<script language=\"javascript\" type=\"text/javascript\">";
	echo "	document.getElementById(\"bSubmitSurvey\").disabled = false;";
	if($surveyAllowSave == "true")
		{
		echo "	document.getElementById(\"bSaveSurvey\").disabled = false;";
		}
	echo "	</script>";
	}
	//}
echo "</form>";
if($hasSubmitted=="true")
	{
	echo "<p>If you would like to print out a copy of your answers, please do so now by clicking the <strong>Print submitted survey</strong> button below:</p>";
	echo "<input type=\"button\" name=\"printSurvey\" id=\"printSurvey\" value=\"Print submitted survey\" onClick=\"window.open('printsurvey.php?surveyInstanceID=$surveyInstanceID','printWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes')\">";
	echo "<br/>";
	}
//Breadcrumb
if(IsAuthor($heraldID))
	{
	require_once("includes/html/BreadCrumbsHeader.html"); 
	echo "<a href=\"index.php\">Surveys</a> &gt; <strong>$surveyInstanceTitle</strong>"; 	
	require_once("includes/html/BreadCrumbsFooter.html"); 
	}
//footer - including contact info.
require_once("includes/html/footernew.html"); 
require_once("includes/instructions/inst_showsurvey.php"); 

?>
</body>
</html>