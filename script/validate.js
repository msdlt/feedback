// JavaScript handles form validation
//checks that formField contains an entry
function validRequired(formField,fieldLabel)
	{
	var result = true;
	if (formField.value == "")
		{
		alert('Please answer: ' + fieldLabel);
		formField.focus();
		result = false;
		}
	return result;
	}
//checks whether the entry is valid text
function validText(formField,fieldLabel,required)
	{
	var result = true;
	if (required && !validRequired(formField,fieldLabel)) result = false;
 	if (result)
 		{
		//check that it only contains normal text and numbers
		var reg = /[a-zA-Z0-9 -']/;
		if (reg.exec(formField.value)==null)
			{
			alert('Please enter a valid ' + fieldLabel + '.');
			formField.focus();		
			result=false;
			}
		}
	return result;
	}
//checks whether the entry is valid text
function validPassword(formField,fieldLabel,required)
	{
	var result = true;
	if (required && !validRequired(formField,fieldLabel)) result = false;
 	if (result)
 		{
		//check that it only contains normal text and numbers
		var reg = /[a-zA-Z0-9]/;
		if (reg.exec(formField.value)==null)
			{
			alert('Please enter a valid ' + fieldLabel + '.');
			formField.focus();		
			result=false;
			}
		}
	return result;
	}
//checks whether the entry is valid text
function validTextNoNumbers(formField,fieldLabel,required)
	{
	var result = true;
	if (required && !validRequired(formField,fieldLabel)) result = false;
 	if (result && formField.value != "")
 		{
		//check that it only contains normal text and numbers
		var reg = /[a-zA-Z -']/;
		if (reg.exec(formField.value)==null)
			{
			alert('Please enter a valid ' + fieldLabel + '.');
			formField.focus();		
			result=false;
			}
		}
	return result;
	}
//checks whether the number is an integer in the range valueMin to valueMax
function validIntinRange(formField,fieldLabel,required,valueMin,valueMax)
	{
	var result = true;
	if (!validInt(formField,fieldLabel,required)) result = false;	
	if (result)
 		{
 		if (formField.value < valueMin || formField.value > valueMax)
			{
 			alert('Please enter a whole number for: ' + fieldLabel +' which is greater than or equal to' + valueMin + ' and less than or equal to ' + valueMax + '.');
			formField.focus();		
			result = false;
			}
		} 
	return result;
	}
//checks whether the number is an integer only
function validInt(formField,fieldLabel,required)
	{
	var result = true;
	if (required && !validRequired(formField,fieldLabel)) result = false;
 	if (result)
 		{
 		var num = parseInt(formField.value);
 		if (isNaN(num))
 			{
 			alert('Please enter a whole number for: ' + fieldLabel + '.');
			formField.focus();		
			result = false;
			}
		} 
	return result;
	}
//checks whether the number is an float in the range valueMin to valueMax
function validFloatinRange(formField,fieldLabel,required,valueMin,valueMax)
	{
	var result = true;
	if (!validFloat(formField,fieldLabel,required)) result = false;	
	if (result)
 		{
 		if(formField.value < valueMin || formField.value > valueMax)
			{
 			alert('Please enter a number for: ' + fieldLabel +' which is greater than or equal to ' + valueMin + ' and less than or equal to ' + valueMax + '.');
			formField.focus();		
			result = false;
			}
		} 
	return result;
	}
//checks whether the number is a float only
function validFloat(formField,fieldLabel,required)
	{
	var result = true;
	if (required && !validRequired(formField,fieldLabel)) result = false;
 	if (result)
 		{
 		var num = parseFloat(formField.value);
 		if (isNaN(num))
 			{
 			alert('Please enter a number for: ' + fieldLabel  + '.');
			formField.focus();		
			result = false;
			}
		} 
	return result;
	}
//checks that email is valid using regular expression 
function validEmail(formField,fieldLabel,required)
{
	var result = true;
	if (required && !validRequired(formField,fieldLabel)) result = false;
	if (result)
 	{
 		var reg = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
		if (reg.exec(formField.value)==null)
			{
			alert('Please enter a valid ' + fieldLabel + '.');
			formField.focus();		
			result=false;
			}
	} 
	return result;
}