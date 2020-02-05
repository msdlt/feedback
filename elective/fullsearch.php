<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Search electives - Full search</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" type="text/css" href="../css/SurveyStyles.css">
<link rel="stylesheet" type="text/css" href="../css/msdstyle2.css" media="screen"/>
<link href="../css/msdprint.css" rel="stylesheet" type="text/css" media="print" />
	
<?php
require_once("../includes/config.php"); 
require_once("../includes/getuser.php");
require_once("../includes/isauthor.php");
require_once("../includes/limitstring.php");
require_once("../includes/ODBCDateToTextDate.php");
error_reporting(E_ALL);
?>
<script language="javascript" type="text/javascript">
function ValidateSecondForm()
	{
	if (ValidateCriteria()==false) return false;
	return true;
	}
</script>
<script language="javascript" type="text/javascript">
//Validation of input (called by frm1 OnSubmit())
//checks that finish date is later than start date
function OnbAddCriterion()
	{
	if(ValidateCriteria()==false)
		{
		return false;
		}
	else
		{
		document.frmAnalyse.action = "<?php $_SERVER['PHP_SELF'] ?>"
		document.frmAnalyse.target = "_self";	
		document.frmAnalyse.submit();
		return true;
		}
	}
function OnbDeleteCriterion()
	{
	document.frmAnalyse.action = "<?php $_SERVER['PHP_SELF'] ?>"
	document.frmAnalyse.target = "_self";	
	document.frmAnalyse.submit();
	return true;
	}
function updateItems(iCriterion,iQuestion)
	{
	//populating qSource select box based on the question chosen
	var selectBoxName = "qSource_"+iCriterion;
	var itemText;
	var itemID;
	document.getElementById(selectBoxName).options.length = 0;
	for(i=1;i<aQuestions[iQuestion][2].length;i++)
		{
		itemText = unescape(aQuestions[iQuestion][2][i][0]);
		itemID = aQuestions[iQuestion][2][i][1];
		document.getElementById(selectBoxName).options[i-1] = new Option(itemText,itemID);
		}
	}
</script>
<?php
	$surveyID = 18; 
	if(isset($_POST['bAddCriterion']))
		{
		//must have just added a criterion
		$noOfCriteria = $_POST['hNoOfCriteria'] + 1;
		}
	elseif(isset($_POST['bDeleteCriterion']))
		{
		//must have just deleted a criterion
		$noOfCriteria = $_POST['hNoOfCriteria'] - 1;
		}
	else
		{
		$noOfCriteria = 1;
		}
	if(!isset($_POST['rStartDate'])||$_POST['rStartDate']==0)
		{
		$startDate="NULL";
		}
	else
		{
		$startDate = date("Y-m-d", mktime(0, 0, 0, $_POST['startMonth'], $_POST['startDay'], $_POST['startYear'])); 
		}
	if(!isset($_POST['rFinishDate'])||$_POST['rFinishDate']==0)
		{
		$finishDate="NULL";
		}
	else
		{
		$finishDate = date("Y-m-d", mktime(0, 0, 0, $_POST['finishMonth'], $_POST['finishDay'], $_POST['finishYear']));
		}
	echo "	<script language=\"JavaScript\" src=\"../script/OptionTransfer.js\"></script>";
	//write script to handle option transfers
	echo "<script language=\"JavaScript\">";
	for($i=1;$i<=$noOfCriteria;$i++)
		{
		echo "	var opt_$i = new OptionTransfer(\"qSource_$i\",\"qDestination_$i\"); 
				opt_$i.setAutoSort(true); 
				opt_$i.saveNewRightOptions(\"hidQuestions_$i\");";
		}
	echo "function ValidateCriteria()
			{";
	for($i=1;$i<=$noOfCriteria;$i++)
		{
		echo "if (document.getElementById(\"qAnalyseBy_$i\").selectedIndex != 0)
				{
				if (document.getElementById(\"qDestination_$i\").length == 0)
					{
					alert ('Please choose one or more answers to search for in search criterion $i.');
					return false;
					}
				}
			else
				{
				if (document.getElementById(\"qAnalyseBy_$i\").selectedIndex == 0)
					{
					alert ('Please choose one or more questions to search by in search criterion $i.');
					return false;
					}
				}
			";
		}
		echo "
			}
		";
	echo "</script>";
	echo"</head>";
	$bodyTag = "<body onLoad=\"";
	for($i=1;$i<=$noOfCriteria;$i++)
		{
		$bodyTag = $bodyTag . "opt_$i.init(document.frmAnalyse);";
		}
	for($i=1;$i<=$noOfCriteria;$i++)
		{
		$bodyTag = $bodyTag . "opt_$i.transferRight();";
		}
	$bodyTag = $bodyTag . "\">"; 
	echo $bodyTag;
	require_once("includes/html/electiveheader.html");
	require_once("../includes/html/BreadCrumbsHeader.html"); 
	echo "<a href=\"index.php\">Search electives</a> &gt; <strong>Full search</strong>"; 	
	require_once("../includes/html/BreadCrumbsFooter.html");
?>		
<?php
echo "<a name=\"maintext\" id=\"maintext\"></a>";
echo "<h1>Full search</h1>";
//if(isset($_POST['bFullSearch'])||isset($_POST['bAddCriterion'])||isset($_POST['bDeleteCriterion']))
//	{
	//get blocks - returning SurveyBlocks.visible will allow us to choose whether to include 'deleted' blocks for which there are results
	$qBlocks = "SELECT Blocks.blockID, Blocks.title, Blocks.introduction, Blocks.epilogue, SurveyBlocks.position, Blocks.instanceable, SurveyBlocks.visible
				FROM Blocks, SurveyBlocks 
				WHERE SurveyBlocks.surveyID = $surveyID
				AND Blocks.blockID = SurveyBlocks.blockID
				ORDER BY SurveyBlocks.position";
	$qResBlocks = mysql_query($qBlocks);
	$questionNo = 1;
	$aQuestions = array();
	$aAllowAnalyseByThisQuestion = array(); //are these qestions allowed as ones to be analysed by
	$aTextandID = array();
	$aItems = array();
	while($rowBlocks = mysql_fetch_array($qResBlocks))
		{
		$blockID = $rowBlocks['blockID'];
		$blockVisible = $rowBlocks['visible'];
		//$qResSections = mysql_query($qSections);
		//get sections
		$qSections = "SELECT Sections.sectionID, Sections.title, Sections.introduction, Sections.epilogue, BlockSections.position, Sections.sectionTypeID, BlockSections.visible 
					FROM Sections, BlockSections 
					WHERE BlockSections.blockID = $blockID
					AND Sections.sectionID = BlockSections.sectionID
					ORDER BY BlockSections.position";
		
		$qResSections = mysql_query($qSections);
		//counter for questions 
		while($rowSections = mysql_fetch_array($qResSections))
			{
			$sectionID = $rowSections['sectionID'];
			$sectionVisible = $rowSections['visible'];
			//get questions 
			$qQuestions = "SELECT Questions.questionID, Questions.comments, Questions.questionTypeID, Questions.text, SectionQuestions.position, SectionQuestions.visible
						FROM Questions, SectionQuestions
						WHERE SectionQuestions.sectionID = $sectionID
						AND Questions.questionID = SectionQuestions.questionID
						ORDER BY SectionQuestions.position";
			
			$qResQuestions = mysql_query($qQuestions);
			
			while($rowQuestions = mysql_fetch_array($qResQuestions))
				{
				$questionID = $rowQuestions['questionID'];
				$questionVisible = $rowQuestions['visible'];
				unset($aTextandID);
				$drdownOptionValue = $blockID . "_" . $sectionID . "_" . $questionID;
				$questionText = limitString($rowQuestions['text'],30);
				$aTextandID[0] = $questionText;
				$aTextandID[1] = $drdownOptionValue;
				$aQuestions[$questionNo] = $aTextandID;
				//only populate the drop-down with questions on which a choice could be based i.e not text/questionTypeID = 4
				if ($rowQuestions['questionTypeID'] == 1 || $rowQuestions['questionTypeID'] == 2 || $rowQuestions['questionTypeID'] == 3)
					{
					$aAllowAnalyseByThisQuestion[$questionNo] = true;
					}
				else
					{
					$aAllowAnalyseByThisQuestion[$questionNo] = false;
					}
				//get items for this question
				//get questions 
				$qItems = "SELECT Items.itemID, Items.text, QuestionItems.position, QuestionItems.visible
						FROM Items, QuestionItems
						WHERE QuestionItems.questionID = $questionID
						AND Items.itemID = QuestionItems.itemID
						ORDER BY QuestionItems.position";
				$qResItems = mysql_query($qItems);
				unset($aItems);
				$aItems = array();
				$itemNo = 1;
				while($rowItems = mysql_fetch_array($qResItems))
					{
					$itemID = $rowItems['itemID'];
					unset($aTextandID);
					$drdownOptionValue = $blockID . "_" . $sectionID . "_" . $questionID . "_" . $itemID;
					$itemText = limitString($rowItems['text'],30);
					//urlencode prevents problems with ' and " when text is stored in javascript variables
					$aTextandID[0] = rawurlencode(trim($itemText));
					$aTextandID[1] = $drdownOptionValue;
					$aItems[$itemNo] = $aTextandID;
					$itemNo = $itemNo + 1;
					}
				$aQuestions[$questionNo][2] = $aItems;//increment question number
				$questionNo = $questionNo + 1;
				}
			}
		}
	echo "
	<script language=\"javascript\" type=\"text/javascript\">
		var aQuestions = new Array();";
		for ($i= 1; $i<=count($aQuestions);$i++)
			{
			echo "aQuestions[".$i."]=new Array(3);\n";
			echo "aQuestions[".$i."][0]='".$aQuestions[$i][0]."';\n";//question text
			echo "aQuestions[".$i."][1]='".$aQuestions[$i][1]."';\n";//question ID
			echo "aQuestions[".$i."][2]=new Array;\n";
			for($j=1;$j<=count($aQuestions[$i][2]);$j++)
				{
				echo "aQuestions[".$i."][2][$j] = new Array;\n";
				echo "aQuestions[".$i."][2][$j][0]='".$aQuestions[$i][2][$j][0]."';\n";//item text
				echo "aQuestions[".$i."][2][$j][1]='".$aQuestions[$i][2][$j][1]."';\n";//item ID
				}
			}
echo "</script>
	<form id=\"frmAnalyse\" name=\"frmAnalyse\" method=\"post\" action=\"electiveresults.php\" onsubmit=\"return ValidateSecondForm();\">
		<div class=\"block\">
			<h2>Step 1: Limit results</h2>";
		echo"
			<div class=\"section\">
			<table class=\"normal_3\">
				<tr>
					<td class=\"question\">Limit to electives from :</td>
					<td>";
						if(isset($_POST['sLastYears']))
							{
							$lastYearValue = $_POST['sLastYears'];
							}
						else
							{
							$lastYearValue = 0;
							}
						echo "	<select id=\"sLastYears\" name=\"sLastYears\" size=\"1\">
								<option ";
									if($lastYearValue==0)
										{
										echo " selected ";
										}
										echo "value=\"0\">All</option>";
								for($i=1;$i<=20;$i++)
									{
						echo "		<option ";
									if($i==$lastYearValue)
										{
										echo " selected ";
										}
										echo "value=\"$i\">last $i</option>";
									}
						echo "	</select> year(s).
						<input type=\"hidden\" id=\"surveyID\" name=\"surveyID\" value=\"$surveyID\">
						<input type=\"hidden\" id=\"hNoOfCriteria\" name=\"hNoOfCriteria\" value=\"$noOfCriteria\">";
			echo"	</td>
				</tr>
			</table>
			</div>
		</div>
		<div class=\"block\">
			<h2>Step 2: Create search criteria</h2>";//set up list boxes to choose questions
		for($i=1;$i<=$noOfCriteria;$i++)
				{
			echo"<div class=\"section\">
					<h3>Search criterion $i</h3>
					<table class=\"normal_3\">
						<tr>
							<td class=\"question\">
								Choose question to search by: 
							</td>
							<td colspan=\"2\" class=\"question\">
								<select id=\"qAnalyseBy_$i\" name=\"qAnalyseBy_$i\" size=\"1\" onchange=\"updateItems($i,this.selectedIndex)\">";
				echo "				<option value=\"0\">Choose a question</option>";
				$questionNo = 1;
				for($j = 1;$j<=count($aQuestions);$j++)
					{
					if ($questionNo < 10)
						{
						$questionNoForOutput = "0" . $questionNo;
						}
					else
						{
						$questionNoForOutput = $questionNo;
						}
					//now find out whether this question has been used in a previous criterion
					if ($i>1) //only bother if there are one or more criteria
						{
						for ($l=1;$l<$i;$l++)
							{
							$analyseByName = "qAnalyseBy_" . $l;
							if($_POST[$analyseByName] == $aQuestions[$j][1])
								{
								$aAllowAnalyseByThisQuestion[$j] = false;
								}
							}
						}
					if ($aAllowAnalyseByThisQuestion[$j] == true)
						{
						$analyseByName = "qAnalyseBy_" . $i;
						if($_POST[$analyseByName] == $aQuestions[$j][1])
							{
							echo "<option value=\"".$aQuestions[$j][1]."\" selected>" . $questionNoForOutput . " " . $aQuestions[$j][0] . "	</option>";
							$QuestionChosen = $questionNo;
							}
						else
							{
							echo "<option value=\"".$aQuestions[$j][1]."\">" . $questionNoForOutput . " " . $aQuestions[$j][0] . "	</option>";
							}
						}
					else
						{
						echo "<option value=\"".$aQuestions[$j][1]."\" disabled>". $questionNoForOutput . " Not searchable</option>";
						}
					$questionNo++;
					}
				
				echo "			</select>
							</td>";
				echo "	</tr>";
				//now need to repopulate qSource and qDestination for previously seslected criteria
				echo "	<tr>
							<td class=\"question\">
								Choose answer(s) to search for:<br/>
								<select id=\"qSource_$i\" name=\"qSource_$i\" multiple size=\"10\" onDblClick=\"opt_$i.transferRight()\">";
								if($noOfCriteria>1&&$i<$noOfCriteria) //but only if there is a previous criterion and this is not the last one
									{
									$aThisItemPreviouslySelected = array(); //Array to hold those questions which were selected for analysis before
									//populate this array with falses
									for($j=1;$j<=count($aQuestions[$QuestionChosen][2]);$j++)
										{
										$aThisItemPreviouslySelected[$j]=false;
										}
									//First get list of questions selected in this criterion before
									$nameOfHidQuestions = "hidQuestions" . "_$i";
									$aItemsToAnalyse = explode(",",$_POST[$nameOfHidQuestions]);
									//now run through items, checking which was previously selected
									for($j=1;$j<=count($aQuestions[$QuestionChosen][2]);$j++)
										{
										//now check whether this option is listed in the list
										for($k=0;$k<count($aItemsToAnalyse);$k++)
											{
											if($aQuestions[$QuestionChosen][2][$j][1] == $aItemsToAnalyse[$k])
												{
												$aThisItemPreviouslySelected[$j]=true;
												}
											}
										}
									for($j=1;$j<count($aQuestions[$QuestionChosen][2])+1;$j++)
										{
										echo "			<option value=\"".$aQuestions[$QuestionChosen][2][$j][1]."\"".($aThisItemPreviouslySelected[$j]==true?" selected":"").">" . rawurldecode($aQuestions[$QuestionChosen][2][$j][0]) . "	</option>";
										}
									}
				echo "			</select>
							</td>
							<td valign=\"middle\" align=\"center\">";
								//if this is not the last criterion, disable the buttons which allow its contents to be changed
								//only the laast criterion can be changed at any one time - this prevents
								//problems with the list of questions which can be analysed in subsequent criteria having to change
								//in response to chnaged in questions being analysed in this criterion
								echo"<input type=\"button\" name=\"right\" value=\"&gt;&gt;\" onclick=\"opt_$i.transferRight()\"".($i<$noOfCriteria ? " disabled":"")."><br/><br/>
								<input type=\"button\" name=\"right\" value=\"All &gt;&gt;\" onclick=\"opt_$i.transferAllRight()\"".($i<$noOfCriteria ? " disabled":"")."><br/><br/>
								<input type=\"button\" name=\"left\" value=\"&lt;&lt;\" onclick=\"opt_$i.transferLeft()\"".($i<$noOfCriteria ? " disabled":"")."><br/><br/>
								<input type=\"button\" name=\"left\" value=\"All &lt;&lt;\" onclick=\"opt_$i.transferAllLeft()\"".($i<$noOfCriteria ? " disabled":"").">
							</td>
							<td class=\"question\">
								Answer(s) to search for:<br/>
								<select id=\"qDestination_$i\" name=\"qDestination_$i\" multiple size=\"10\" onDblClick=\"opt_$i.transferLeft()\">
								</select>
								<input type=\"hidden\" id=\"hidQuestions_$i\" name=\"hidQuestions_$i\">
							</td>
						</tr>";
				if($i==$noOfCriteria)
							{
							echo"<tr>
								<td colspan=\"3\">
									<input type=\"submit\" value=\"Add search criterion\" id=\"bAddCriterion\" name=\"bAddCriterion\" onClick=\"return OnbAddCriterion();\">
									<input type=\"submit\" value=\"Delete criterion\" id=\"bDeleteCriterion\" name=\"bDeleteCriterion\" onClick=\"return OnbDeleteCriterion();\">
								</td>
							</tr>";
							}
				echo"</table>
				</div>";
				}
	echo "	<input type=\"hidden\" id=\"surveyID\" name=\"surveyID\" value=\"$surveyID\">
			<h2>Step 3: Generate Results</h2>
			<input type=\"submit\" value=\"Search\" id=\"bFullSearch\" name=\"bFullSearch\">
		</form>
	</div>
	<br/><br/>";
?>
<?php
	//Breadcrumb
	require_once("../includes/html/BreadCrumbsHeader.html"); 
	echo "<a href=\"index.php\">Search electives</a> &gt; <strong>Full search</strong>"; 		
	require_once("../includes/html/BreadCrumbsFooter.html"); 
	//footer - including contact info.
	require_once("../includes/html/footernew.html");
	require_once("../includes/instructions/inst_fullsearch.php"); 

?>
</body>
</html>
