<?php
require_once("includes/config.php"); 
require_once("includes/getuser.php");
require_once("includes/isauthor.php");
require_once("includes/quote_smart.php");
require_once("includes/ODBCDateToTextDate.php");
//require_once("includes/gettextoutput.php");
require_once("includes/getinstancesforblockandsection.php");
require_once("includes/renderstringforjavascript.php");

if (IsAuthor($heraldID))
	{
	//ie. authors don't have to come from a certain referrer
	//check that we have a surveyID - won't have a s
	if ((isset($_POST['surveyID']) && $_POST['surveyID']!="")||(isset($_GET['surveyID']) && $_GET['surveyID']!=""))
		{
		//pass surveyInstanceID on
		$surveyID = $_GET['surveyID'];
		//find out which survey this is an instance of
		$qSurvey = "	SELECT title  
						FROM Surveys
						WHERE surveyID = $surveyID";
		$qResSurvey = mysqli_query($qSurvey);
		if (($qResSurvey == false))
			{
			echo "problem querying Surveys" . mysqli_error();
			}
		else
			{
			$rowSurvey = mysqli_fetch_array($qResSurvey);
			$surveyTitle = $rowSurvey[title];
						
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
			$qResBlocks = mysqli_query($qBlocks);
			$iBlock = 1;
			while($rowBlocks = mysqli_fetch_array($qResBlocks))
				{
				$aItems[$iBlock][0]["blockID"] = $rowBlocks[blockID];
				$aItems[$iBlock][0]["text"] = $rowBlocks[text];
				$aItems[$iBlock][0]["introduction"] = $rowBlocks[introduction];
				$aItems[$iBlock][0]["epilogue"] = $rowBlocks[epilogue];
				$aItems[$iBlock][0]["position"] = $rowBlocks[position];
				$aItems[$iBlock][0]["instanceable"] = $rowBlocks[instanceable];
				$qSections = "	SELECT Sections.sectionID, Sections.text, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, Sections.instanceable 
								FROM Sections, BlockSections 
								WHERE BlockSections.blockID = $rowBlocks[blockID]
								AND BlockSections.visible = 1
								AND Sections.sectionID = BlockSections.sectionID
								ORDER BY BlockSections.position";
				$qResSections = mysqli_query($qSections);
				$iSection = 1;
				while($rowSections = mysqli_fetch_array($qResSections))
					{
					$aItems[$iBlock][$iSection][0]["sectionID"] = $rowSections[sectionID];
					$aItems[$iBlock][$iSection][0]["text"] = $rowSections[text];
					$aItems[$iBlock][$iSection][0]["introduction"] = $rowSections[introduction];
					$aItems[$iBlock][$iSection][0]["epilogue"] = $rowSections[epilogue];
					$aItems[$iBlock][$iSection][0]["position"] = $rowSections[position];
					$aItems[$iBlock][$iSection][0]["sectionTypeID"] = $rowSections[sectionTypeID];
					$aItems[$iBlock][$iSection][0]["instanceable"] = $rowSections[instanceable];
					$qQuestions = "	SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position 
									FROM Questions, SectionQuestions
									WHERE SectionQuestions.sectionID = $rowSections[sectionID]
									AND SectionQuestions.visible = 1
									AND Questions.questionID = SectionQuestions.questionID
									ORDER BY SectionQuestions.position";
					$qResQuestions = mysqli_query($qQuestions);
					$iQuestion = 1;
					while($rowQuestions = mysqli_fetch_array($qResQuestions))
						{
						$aItems[$iBlock][$iSection][$iQuestion][0]["questionID"] = $rowQuestions[questionID];
						$aItems[$iBlock][$iSection][$iQuestion][0]["comments"] = $rowQuestions[comments];
						$aItems[$iBlock][$iSection][$iQuestion][0]["questionTypeID"] = $rowQuestions[questionTypeID];
						$aItems[$iBlock][$iSection][$iQuestion][0]["text"] = $rowQuestions[text];
						$aItems[$iBlock][$iSection][$iQuestion][0]["position"] = $rowQuestions[position];
						$qItems = "	SELECT Items.itemID, Items.text, QuestionItems.position
									FROM Items, QuestionItems
									WHERE QuestionItems.questionID = $rowQuestions[questionID]
									AND QuestionItems.visible = 1
									AND Items.itemID = QuestionItems.itemID
									ORDER BY QuestionItems.position";
						$qResItems = mysqli_query($qItems);
						$iItem = 1;
						while($rowItems = mysqli_fetch_array($qResItems))
							{
							$aItems[$iBlock][$iSection][$iQuestion][$iItem]["itemID"] = $rowItems[itemID];
							$aItems[$iBlock][$iSection][$iQuestion][$iItem]["text"] = $rowItems[text];
							$aItems[$iBlock][$iSection][$iQuestion][$iItem]["position"] = $rowItems[position];
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
<style type="text/css" media="all">
	@import "script/widgEditor/css/widgEditor.css";
</style>
<script language="javascript" src="script/widgEditor/scripts/widgEditor.js"></script>
<script language="javascript" type="text/javascript">
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
	$qSurveys = "SELECT title, introduction, epilogue, allowSave, allowViewByStudent
				FROM Surveys
				WHERE surveyID = $surveyID";
	$qResSurvey = mysqli_query($qSurveys);
	$rowSurvey = mysqli_fetch_array($qResSurvey);
	$surveyTitle = $rowSurvey['title'];
	$surveyIntroduction = $rowSurvey['introduction'];
	$surveyEpilogue = $rowSurvey['epilogue'];
	$surveyAllowSave = $rowSurvey['allowSave'];
	$allowViewByStudent = $rowSurvey['allowViewByStudent'];
	echo "<title>$surveyTitle</title>";
?>	


	<link rel="stylesheet" type="text/css" href="css/SurveyStyles.css"></link>
<?php
echo"<link rel=\"stylesheet\" type=\"text/css\" href=\"css/msdstyle2.css\" media=\"screen\"/>";
?>
	<link  rel="stylesheet" type="text/css" href="css/msdprint.css" media="print" />
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

</head>

<body>

<?php
//*******************************************************************************************************************************
//BEGIN SHOWING SURVEY
//*******************************************************************************************************************************
	//alwsys show previw with student header not author header
	if($allowViewByStudent=="true")
		{
		require_once("includes/html/chooserheader.html");
		}	
	else
		{
		require_once("includes/html/header.html"); 
		}
	echo "<a name=\"maintext\" id=\"maintext\"></a>";
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
	<h1>Preview of $surveyTitle</h1>";
	//warning to students if survey is not anonymous
	if($allowViewByStudent=="true")
		{
		echo "<h2><span class=\"errorMessage\">Warning: Your entries will not be anonymous</span></h2>";
		}		
	echo "$surveyIntroduction
	</div>";
	echo "<form id=\"frmSurvey\" name=\"frmSurvey\" action=\"$_SERVER['PHP_SELF']\" method=\"post\" onSubmit=\"return ValidateForm()\">";

	//counter for questions 
	$questionNo = 1;
	//need to work out globally whether the form is being refreshed after adding data or is being opened anew
	//create a hidden form field to hold boolean
	echo "<input type=\"hidden\" name=\"hAdded\" id=\"hAdded\" value=\"false\">";
	echo "<input type=\"hidden\" name=\"hDeleted\" id=\"hDeleted\" value=\"false\">";
	echo "<input type=\"hidden\" name=\"hSaved\" id=\"hSaved\" value=\"false\">";
	$addingInstance = false;
	$deletingInstance = false;
	if ((isset($_POST[hAdded]) && $_POST[hAdded] == "true") || $allAnswered==false)
		{
		$addingInstance = true;
		}
	if ((isset($_POST[hDeleted]) && $_POST[hDeleted] == "true"))
		{
		$deletingInstance = true;
		}
	for ($iBlock=1; $iBlock<=count($aItems);$iBlock++)
		{
		$blockID = $aItems[$iBlock][0]["blockID"];
		$blockIsInstanceable = false;
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
				$noOfInstances = 1;
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
		$qResThisObjectIsBranchedTo = mysqli_query($qThisObjectIsBranchedTo);
		$ThisObjectIsBranchedTo = false;
		while($rowThisObjectIsBranchedTo = mysqli_fetch_array($qResThisObjectIsBranchedTo))
			{
			if($rowThisObjectIsBranchedTo[blockID] == $blockID && 
			$rowThisObjectIsBranchedTo[sectionID] == NULL && 
			$rowThisObjectIsBranchedTo[questionID] == NULL)
				{
				$ThisObjectIsBranchedTo = true;
				}
			}
		//get sections
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
			for($iSection=1; $iSection<=count($aItems[$iBlock])-1;$iSection++)
				{
				$sectionID = $aItems[$iBlock][$iSection][0]["sectionID"];
				$sectionIsInstanceable = false;
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
				//is this block branched to?
				$qThisObjectIsBranchedTo = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
												FROM BranchDestinations";										
				$qResThisObjectIsBranchedTo = mysqli_query($qThisObjectIsBranchedTo);
				$ThisObjectIsBranchedTo = false;
				while($rowThisObjectIsBranchedTo = mysqli_fetch_array($qResThisObjectIsBranchedTo))
					{
					if($rowThisObjectIsBranchedTo[blockID] == $blockID && 
					$rowThisObjectIsBranchedTo[sectionID] == $sectionID && 
					$rowThisObjectIsBranchedTo[questionID] == NULL)
						{
						$ThisObjectIsBranchedTo = true;
						}
					}
				
				//get questions
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
							for($iQuestion=1; $iQuestion<=count($aItems[$iBlock][$iSection])-1;$iQuestion++)
								{
								$questionID = $aItems[$iBlock][$iSection][$iQuestion][0]["questionID"];
								//is this question branched to?
								$qThisObjectIsBranchedTo = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
																FROM BranchDestinations";										
								$qResThisObjectIsBranchedTo = mysqli_query($qThisObjectIsBranchedTo);
								$ThisObjectIsBranchedTo = false;
								while($rowThisObjectIsBranchedTo = mysqli_fetch_array($qResThisObjectIsBranchedTo))
									{
									if($rowThisObjectIsBranchedTo[blockID] == $blockID && 
									$rowThisObjectIsBranchedTo[sectionID] == $sectionID && 
									$rowThisObjectIsBranchedTo[questionID] == $questionID)
										{
										$ThisObjectIsBranchedTo = true;
										}
									}
								
								echo "<div class=\"questionNormal\" id=\"div".$blockID."_".$sectionID."_".$questionID."_i".$inst."_si".$sinst."\"".($ThisObjectIsBranchedTo?"style=\"display:none\"":"").">";
								//get items
								switch ($aItems[$iBlock][$iSection][$iQuestion][0]["questionTypeID"])
									{
									case 1: //MCHOIC
										{
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
											$qResBranchesFromItem = mysqli_query($qBranchesFromItem);
											$show = "";
											if(mysqli_num_rows($qResBranchesFromItem)>0)
												{
												$itemIsInvolvedInBranching = true;
												$i = 1;
												//if a branch goes to sections or questions within this block then we need to show them only within this instance of the block
												//if a branch goes to sections or questions within another block then show them within all instances of the block
												while($rowBranchesFromItem = mysqli_fetch_array($qResBranchesFromItem))
													{
													if($rowBranchesFromItem[blockID] == $blockID)
														{ 
														//if a branch goes to sections or questions within this block then we need to show them only within this instance of the block
														if($rowBranchesFromItem[sectionID] == $sectionID)
															{
															//if a branch goes to sections or questions within this section then we need to show them only within this instance of the section
															if($i > 1)
																{
																$show = $show . "+";
																}
															$show = $show . "div" . $rowBranchesFromItem[blockID] . ($rowBranchesFromItem[sectionID] != NULL ? "_" . $rowBranchesFromItem[sectionID] . ($rowBranchesFromItem[questionID] != NULL ? "_" . $rowBranchesFromItem[questionID] . "_i" . $inst . "_si" . $sinst: "_i" . $inst . "_si" . $sinst): "_i" . $inst . "_si" . $sinst);
															$i++;
															}
														else
															{
															//find out how many branches are there of the section being branched to
															$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromItem[blockID],$rowBranchesFromItem[sectionID],$inst);
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$show = $show . "+";
																	}
																$show = $show . "div" . $rowBranchesFromItem[blockID] . ($rowBranchesFromItem[sectionID] != NULL ? "_" . $rowBranchesFromItem[sectionID] . ($rowBranchesFromItem[questionID] != NULL ? "_" . $rowBranchesFromItem[questionID] . "_i" . $inst . "_si" . $bsinst : "_i" . $inst . "_si" . $bsinst): "_i" . $inst . "_si" . $bsinst);
																$i++;
																}
															}
														}
													else //i.e. not branching within this block
														{ 
														//find out how many branches are there of the block being branched to
														$noOfBranchInstances = getInstancesForBlock($rowBranchesFromItem[blockID]);
														//find out how many branches are there of the section being branched to
														$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromItem[blockID],$rowBranchesFromItem[sectionID],$inst);
														for($binst=1;$binst<=$noOfBranchInstances;$binst++)
															{
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$show = $show . "+";
																	}
																$show = $show . "div" . $rowBranchesFromItem[blockID] . ($rowBranchesFromItem[sectionID] != NULL ? "_" . $rowBranchesFromItem[sectionID] . ($rowBranchesFromItem[questionID] != NULL ? "_" . $rowBranchesFromItem[questionID] . "_i" . $binst . "_si" . $bsinst : "_i" . $binst . "_si" . $bsinst): "_i" . $binst . "_si" . $bsinst);
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
											$qResBranchesFromQuestion = mysqli_query($qBranchesFromQuestion);
											$hide = "";
											if(mysqli_num_rows($qResBranchesFromQuestion)>0)
												{
												$itemIsInvolvedInBranching = true;
												$i = 1;
												while($rowBranchesFromQuestion = mysqli_fetch_array($qResBranchesFromQuestion))
													{
													if($rowBranchesFromQuestion[blockID] == $blockID)
														{
														if($rowBranchesFromQuestion[sectionID] == $sectionID)
															{
															if($i > 1)
																{
																$hide = $hide . "+";
																}
															$hide = $hide . "div" . $rowBranchesFromQuestion[blockID] . ($rowBranchesFromQuestion[sectionID] != NULL ? "_" . $rowBranchesFromQuestion[sectionID] . ($rowBranchesFromQuestion[questionID] != NULL ? "_" . $rowBranchesFromQuestion[questionID] . "_i" . $inst . "_si" . $sinst : "_i" . $inst . "_si" . $sinst): "_i" . $inst . "_si" . $sinst);
															$i++;
															}
														else
															{
															//find out how many branches are there of the section being branched to
															$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromQuestion[blockID],$rowBranchesFromQuestion[sectionID],$inst);
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$hide = $hide . "+";
																	}
																$hide = $hide . "div" . $rowBranchesFromQuestion[blockID] . ($rowBranchesFromQuestion[sectionID] != NULL ? "_" . $rowBranchesFromQuestion[sectionID] . ($rowBranchesFromQuestion[questionID] != NULL ? "_" . $rowBranchesFromQuestion[questionID] . "_i" . $inst . "_si" . $bsinst : "_i" . $inst . "_si" . $bsinst): "_i" . $inst . "_si" . $bsinst);
																$i++;
																}
															}
														}
													else
														{
														$noOfBranchInstances = getInstancesForBlock($rowBranchesFromQuestion[blockID]);
														$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromQuestion[blockID],$rowBranchesFromQuestion[sectionID],$inst);
														for($binst=1;$binst<=$noOfBranchInstances;$binst++)
															{
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$hide = $hide . "+";
																	}
																$hide = $hide . "div" . $rowBranchesFromQuestion[blockID] . ($rowBranchesFromQuestion[sectionID] != NULL ? "_" . $rowBranchesFromQuestion[sectionID] . ($rowBranchesFromQuestion[questionID] != NULL ? "_" . $rowBranchesFromQuestion[questionID] . "_i" . $binst . "_si" . $bsinst : "_i" . $binst . "_si" . $bsinst): "_i" . $binst . "_si" . $bsinst);
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
															echo "Please comment..";
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
											$qResBranchesFromItem = mysqli_query($qBranchesFromItem);
											$show = "";
											if(mysqli_num_rows($qResBranchesFromItem)>0)
												{
												$itemIsInvolvedInBranching = true;
												$i = 1;
												while($rowBranchesFromItem = mysqli_fetch_array($qResBranchesFromItem))
													{
													if($rowBranchesFromItem[blockID] == $blockID)
														{
														if($rowBranchesFromItem[sectionID] == $sectionID)
															{
															if($i > 1)
																{
																$show = $show . "+";
																}
															$show = $show . "div" . $rowBranchesFromItem[blockID] . ($rowBranchesFromItem[sectionID] != NULL ? "_" . $rowBranchesFromItem[sectionID] . ($rowBranchesFromItem[questionID] != NULL ? "_" . $rowBranchesFromItem[questionID] . "_i" . $inst . "_si" . $sinst : "_i" . $inst . "_si" . $sinst): "_i" . $inst . "_si" . $sinst);
															$i++;
															}
														else
															{
															$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromItem[blockID],$rowBranchesFromItem[sectionID],$inst);
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$show = $show . "+";
																	}
																$show = $show . "div" . $rowBranchesFromItem[blockID] . ($rowBranchesFromItem[sectionID] != NULL ? "_" . $rowBranchesFromItem[sectionID] . ($rowBranchesFromItem[questionID] != NULL ? "_" . $rowBranchesFromItem[questionID] . "_i" . $inst . "_si" . $bsinst : "_i" . $inst . "_si" . $bsinst): "_i" . $inst . "_si" . $bsinst);
																$i++;
																}
															}
														}
													else
														{
														$noOfBranchInstances = getInstancesForBlock($rowBranchesFromItem[blockID]);
														$noOfBranchSectionInstances = getInstancesForSection($rowBranchesFromItem[blockID],$rowBranchesFromItem[sectionID],$inst);
														for($binst=1;$binst<=$noOfBranchInstances;$binst++)
															{
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$show = $show . "+";
																	}
																$show = $show . "div" . $rowBranchesFromItem[blockID] . ($rowBranchesFromItem[sectionID] != NULL ? "_" . $rowBranchesFromItem[sectionID] . ($rowBranchesFromItem[questionID] != NULL ? "_" . $rowBranchesFromItem[questionID] . "_i" . $binst . "_si" . $bsinst : "_i" . $binst . "_si" . $bsinst): "_i" . $binst . "_si" . $bsinst);
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
															echo "Please comment..";
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
										echo "<td colspan=\"$recordCount\" class=\"question\">$questionNo ".$aItems[$iBlock][$iSection][$iQuestion][0]["text"];
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
											//Branching
											$qBranchesFromItem = "	SELECT BranchDestinations.blockID,BranchDestinations.sectionID,BranchDestinations.questionID
																	FROM Branches, BranchDestinations
																	WHERE Branches.surveyID = $surveyID
																	AND Branches.blockID = $blockID
																	AND Branches.sectionID = $sectionID
																	AND Branches.questionID = $questionID
																	AND Branches.itemID = $itemID
																	AND BranchDestinations.branchID = Branches.branchID";
											$qResBranchesFromItem = mysqli_query($qBranchesFromItem);
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
													if($rowBranchesFromItem[blockID] == $blockID)
														{
														if($rowBranchesFromItem[sectionID] == $sectionID)
															{
															if($i > 1)
																{
																$show = $show . "+";
																}
															$show = $show . "div" . $rowBranchesFromItem[blockID] . ($rowBranchesFromItem[sectionID] != NULL ? "_" . $rowBranchesFromItem[sectionID] . ($rowBranchesFromItem[questionID] != NULL ? "_" . $rowBranchesFromItem[questionID] . "_i" . $inst . "_si" . $sinst : "_i" . $inst . "_si" . $sinst): "_i" . $inst . "_si" . $sinst);
															$i++;
															}
														else
															{
															$noOfBranchSectionInstances = getInstancesForBlock($rowBranchesFromItem[blockID],$rowBranchesFromItem[sectionID]);
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$show = $show . "+";
																	}
																$show = $show . "div" . $rowBranchesFromItem[blockID] . ($rowBranchesFromItem[sectionID] != NULL ? "_" . $rowBranchesFromItem[sectionID] . ($rowBranchesFromItem[questionID] != NULL ? "_" . $rowBranchesFromItem[questionID] . "_i" . $inst . "_si" . $bsinst : "_i" . $inst . "_si" . $bsinst): "_i" . $inst . "_si" . $bsinst);
																$i++;
																}
															}
														}
													else
														{
														$noOfBranchInstances = getInstancesForBlock($rowBranchesFromItem[blockID]);
														$noOfBranchSectionInstances = getInstancesForBlock($rowBranchesFromItem[blockID],$rowBranchesFromItem[sectionID]);
														for($binst=1;$binst<=$noOfBranchInstances;$binst++)
															{
															for($bsinst=1;$bsinst<=$noOfBranchSectionInstances;$bsinst++)
																{
																if($i > 1)
																	{
																	$show = $show . "+";
																	}
																$show = $show . "div" . $rowBranchesFromItem[blockID] . ($rowBranchesFromItem[sectionID] != NULL ? "_" . $rowBranchesFromItem[sectionID] . ($rowBranchesFromItem[questionID] != NULL ? "_" . $rowBranchesFromItem[questionID] . "_i" . $binst . "_si" . $bsinst : "_i" . $binst . "_si" . $bsinst): "_i" . $binst . "_si" . $bsinst);
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
										for($iItem=1; $iItem<=count($aItems[$iBlock][$iSection][$iQuestion])-1;$iItem++)
											{
											$itemID = $aItems[$iBlock][$iSection][$iQuestion][$iItem]["itemID"];
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
															echo "Please comment..";
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
														<textarea class=\"widgEditor\" id=\"$textID\" name=\"$textName\" rows=\"30\">";
												echo "</textarea>
													</td>
												</tr>";
										echo "</table>";
										break;
										}
									case 6: //date
										{
										//NOTE: No branching possible on a date question
										$dateDayName = "day_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										$dateDayID = "day_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										$dateMonthName = "month_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										$dateMonthID = "month_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										$dateYearName = "year_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
										$dateYearID = "year_" . $blockID . "_" . $sectionID . "_" . $questionID . "_i" . $inst . "_si" . $sinst;
																					
										echo "<table class=\"normal_4\">";
										echo "	<tr>";
										echo " 		<td class=\"question\">$questionNo ".$aItems[$iBlock][$iSection][$iQuestion][0]["text"]."</td>";
										echo "	</tr>";
										echo "	<tr>
													<td colspan=\"$recordCount\">";
														$dayValue = 0;
														$monthValue = 0;
														$yearValue = 0;
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
									echo "</td>";
									//loop through items
									//note cannot branch from an MCHOIC or an MSELEC from within a matrix
									for($iItem=1; $iItem<=count($aItems[$iBlock][$iSection][$iQuestion])-1;$iItem++)
										{
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
											echo "	<td colspan=\"$noOfColumns\">
														<textarea class=\"comments\" id=\"$CommentID\" name=\"$CommentName\">";
														echo "Please comment..";
											echo "		</textarea>
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

echo "	<input type=\"hidden\" id=\"surveyID\" name=\"surveyID\" value=\"$surveyID\">"; 
echo "</form>";
echo "<INPUT type=\"button\" value=\"Close Window\" onClick=\"window.close()\">";
//footer - including contact info.
require_once("includes/html/footernew.html"); 
require_once("includes/instructions/inst_showsurvey.php"); 
?>
<script type="text/javascript">
 run();
</script>
</body>
</html>