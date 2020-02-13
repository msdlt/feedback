<?php
/**
 * Quote a variable to make it safe
 */
function quote_smart($value)
{
   // Stripslashes if we need to
   if (get_magic_quotes_gpc()) {
       $value = stripslashes($value);
   }

   // Quote it if it's not an integer
   if (!is_numeric($value)) {
       $value = "'" . mysqli_real_escape_string($value) . "'";
   }

   return $value;
}
?>
