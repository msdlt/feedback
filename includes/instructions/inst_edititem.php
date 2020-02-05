<?php
		//Instructions box
	echo "	<div id=\"homeRight\">
				<div class=\"loginBox\">
					<h5>Instructions</h5>
					";
	if($itemID=="upload")
		{
		echo "<p>Text must be entered as either comma or tab separated values with item title followed by item text. Each item must be on a seaparate row e.g.</p>
		<p>iTitle1, iText1<br/>
		iTitle2, iText2<br/>
		iTitle3, etc.<br/></p>";
		}
	else
		{
		echo "<p>Both the <strong>Name</strong> and <strong>Text</strong> are required but only the <strong>Name</strong> must be unique.</p>
		<p> The <strong>Text</strong> is what is actually
			shown on the survey.</p>";
		}
			echo "</div>
			</div>";
?>