<?php  
//from php.net datavortex at gmail dot com
//18-Mar-2005 11:19
//This is a litttle function I cobbled together from my own code, and snippets from this site to calculate the difference between two datetimes without having to confine it to simply one interval.  This will tell you how many weeks, days, hours, minutes, and seconds there are between the given datetimes, and also makes a little English string you can use.

//This could easily be expanded to include months, and years, but I didn't want to have to deal with any of the leap year and variable month length stuff.

//$stringPrecision:
//1=week
//2=day
//3=hour
//4=minute
//5=second

function dateDiff($dateTimeBegin,$dateTimeEnd,$stringPrecision = 5) 
	{
  	$dateTimeBegin =strtotime($dateTimeBegin);
  	$dateTimeEnd  =strtotime($dateTimeEnd);
  	if($dateTimeEnd === -1 || $dateTimeBegin === -1) 
		{
   		# error condition
   		return false;
  		}

  	$diff = $dateTimeEnd - $dateTimeBegin;

	if ($diff < 0) 
		{
	   	# error condition
	   	return false;
	  	}

  	$weeks = $days = $hours = $minutes = $seconds = 0; # initialize vars

  	if($diff % 604800 > 0) 
		{
   		$rest1  = $diff % 604800;
   		$weeks  = ($diff - $rest1) / 604800; # seconds a week
   		if($rest1 % 86400 > 0) 
			{
     		$rest2 = ($rest1 % 86400);
     		$days  = ($rest1 - $rest2) / 86400; # seconds a day
     		if( $rest2 % 3600 > 0 ) 
				{
       			$rest3 = ($rest2 % 3600);
       			$hours = ($rest2 - $rest3) / 3600; # seconds an hour
       			if( $rest3 % 60 > 0 ) 
					{
         			$seconds = ($rest3 % 60);
         			$minutes = ($rest3 - $seconds) / 60;  # seconds a minute
       				} 
				else 
					{
         			$minutes = $rest3 / 60;
       				}
     			} 
			else 
				{
       			$hours = $rest2 / 3600;
     			}
   			} 
		else 
			{
     		$days = $rest1/ 86400;
   			}
  		}
	else 
		{
   		$weeks = $diff / 604800;
  		}

  	$string = array();
  	if($stringPrecision>0)
		{
		if($weeks > 1) 
			{
			$string[]  = "$weeks weeks";
			} 
		elseif ($weeks == 1) 
			{
			$string[]  = "a week";
			}
		if($stringPrecision>1)
			{
			if($days > 1) 
				{
				$string[] = "$days days";
				} 
			elseif($days == 1) 
				{
				$string[] = "a day";
				}
			if($stringPrecision>2)
				{
				if($hours > 1) 
					{
					$string[] = "$hours hours";
					} 
				elseif ($hours == 1) 
					{
					$string[] = "an hour";
					}
				if($stringPrecision>3)
					{
					if($minutes > 1) 
						{
						$string[] = "$minutes minutes";
						} 
					elseif ($minutes == 1) 
						{
						$string[] = "a minute";
						}
					if($stringPrecision>4)
						{
						if($seconds > 1) 
							{
							$string[] = "$seconds seconds";
							} 
						elseif($seconds == 1) 
							{
							$string[] = "a second";
							}
						}	
					}
				}
			}
		}

  	# join together all the strings in the array above except the last element
  	$text  = join(', ', array_slice($string,0,sizeof($string)-1)) . ", and ";
  	$text .= array_pop($string);  # put the last one on after the and
 
  	return array($text, $weeks, $days, $hours, $minutes, $seconds);
	}
?>
