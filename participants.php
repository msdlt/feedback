<?php
$thisPage = basename($_SERVER['PHP_SELF']);
require_once("sessionChecker.php"); 
session_set_cookie_params (60 * 60);
session_start();
session_checker(); 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Participants</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" type="text/css" href="css/SurveyStyles.css"/>
<script language="javascript" type="text/javascript">
	function goTo(URL)
		{
		window.location.href = URL;
		} 
</script>

</head>

<body>
<?php
//Include config.php which contains db details and connects to the db)
require_once("includes/config.php"); 

if (isset($_POST['delete']))
	{
	//Find out ids of all the checkboxes which were checked
	$aParticipants = $_POST['check_Participants'];
	for ($i=0;$i<count($aParticipants);$i++)
		{
		$delParticipants = "DELETE FROM Participants
						WHERE participantID = $aParticipants[$i]";
		$result_query = @mysqli_query($db_connection,$delParticipants);
		if (($result_query == false) && mysqli_affected_rows($db_connection) == 0)
			{
			echo "problem deleting from Participants" . mysqli_error();
			}
		}
	}
?>
<?php
require_once("includes/html/header.html"); 
echo "<span class=\"breadcrumb\">Participants</span>";

if (isset($_GET['orderBy']))
	{
	$orderBy = $_GET['orderBy'];
	$qParticipants = "SELECT *
				FROM Participants
				ORDER BY $orderBy";
	}
else	
	{
	$qParticipants = "SELECT *
				FROM Participants
				ORDER BY lastName";
	}
		
		
$qResParticipants = @mysqli_query($db_connection, $qParticipants);
echo "	<h1>Participants</h1>";
echo "	<a href=\"join.php\">Add a single participant.</a><br/>
		<a href=\"upload.php\">Upload multiple participants from a text file.</a>";	
echo "	<div class=\"questionNormal\">";
echo "		<table class=\"normal_3\" width=\"100%\" summary=\"\">";
echo "			<tr>";
echo "				<td class=\"question\">Choose a participant:</td>";
echo "			</tr>";
echo "			<tr>
					<td>";
echo "			<form id=\"frmDelete\" name=\"frmDelete\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
echo "				<table width=\"100%\" class=\"matrix\" summary=\"\">
						<tr class=\"matrixHeader\">
							<td>
							</td>
							<td align=\"center\">
								<a class=\"participants\" href=\"".$_SERVER['PHP_SELF']."?orderBy=lastName\">Name</a>
							</td>
							<td align=\"center\">
								<a class=\"participants\" href=\"".$_SERVER['PHP_SELF']."?orderBy=userName\">Username</a>
							</td>
							<td align=\"center\">
								<a class=\"participants\" href=\"".$_SERVER['PHP_SELF']."?orderBy=decryptPassword\">Password</a>
							</td>
							<td align=\"center\">
								<a class=\"participants\" href=\"".$_SERVER['PHP_SELF']."?orderBy=email\">e-mail address</a>
							</td>
							<td>
							</td>
						</tr>
						";		
if (($qResParticipants == false))
	{
	echo "problem querying Surveys" . mysqli_error();
	}
else
	{
	$bRowOdd = true;
	while($rowParticipants = mysqli_fetch_array($qResParticipants))
		{
		if($bRowOdd)
			{
			$rowClass = "matrixRowOdd";
			}
		else
			{
			$rowClass = "matrixRowEven";
			}
		echo "			<tr class=\"$rowClass\">
							<td align=\"center\">
								<input type=\"checkbox\" id=\"check_$rowParticipants[participantID]\" name=\"check_Participants[]\" value=\"$rowParticipants[participantID]\"/>
							</td>
							<td>";
		echo 					$rowParticipants[lastName] . ", " . $rowParticipants[firstName] . " " . $rowParticipants[initial] . ".";
		echo "				</td>
							<td>
								$rowParticipants[userName]
							</td>
							<td>
								$rowParticipants[decryptPassword]
							</td>
							<td>
								$rowParticipants[email]
							</td>
							<td align=\"center\">
								<input type=\"button\" id=\"edit_$rowParticipants[participantID]\" name=\"edit_$rowParticipants[participantID]\" value=\"Edit\" onClick=\"goTo('editParticipant.php?participantID=$rowParticipants[participantID]')\" />
							</td>
						</tr>";
		$bRowOdd = !$bRowOdd;
		}
	}	
echo " 				</table>";
echo "				<input type=\"submit\" id=\"delete\" name=\"delete\" value=\"Delete\" onclick=\"return checkCheckBoxes()\"/>";	
echo "			</form>
					</td>";
echo "			</tr>";
echo " 		</table>";	
echo "	</div>"	;

//resets the mysql_fetch_array to start at the beginning again
echo "	<script language=\"JavaScript\" type=\"text/javascript\" >
			function checkCheckBoxes()
				{
				iNoOfParticipants = 0;
				aParticipants = new Array();";				
mysqli_data_seek($qResParticipants, 0);
while($rowParticipants = mysqli_fetch_array($qResParticipants))
	{
echo "			if (document.getElementById(\"check_$rowParticipants[participantID]\").checked == true)
					{
					iNoOfParticipants=iNoOfParticipants+1;
					aParticipants[iNoOfParticipants] = \"$rowParticipants[lastName], $rowParticipants[firstName] $rowParticipants[initial]\";
					}";
	}
echo "			if (iNoOfParticipants == 0)
					{
					alert(\"Please select a participant to delete.\");
					return false;
					}
				else
					{
					if (iNoOfParticipants == 1)
						{
						var confirmText = \"Are you sure you want to delete \" + aParticipants[iNoOfParticipants] + \".\";
						}
					else
						{
						var confirmText = \"Are you sure you want to delete these \" + iNoOfParticipants + \" particpants?\";
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
	echo "<span class=\"breadcrumb\">Participants</span>";
	require_once("includes/html/footer.html"); 
?>
</body>

</html>
