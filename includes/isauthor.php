<?php

function IsAuthor($heraldID)
	{
	global $db_connection;
	$qAuthors = "SELECT authorID
				FROM Authors
				WHERE heraldID = '$heraldID'";
	$qResAuthors = @mysqli_query($db_connection, $qAuthors);
	if($qResAuthors == false)
		{
		echo "Problem querying Authors table";
		}
	else
		{
		if (mysqli_num_rows($qResAuthors)>0)
			{
			return(true);
			}
		else
			{
			return(false);
			}
		}
	}
?>