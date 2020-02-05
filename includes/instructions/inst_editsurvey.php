<?php
	//Instructions box
	echo "	<div id=\"homeRight\">
				<div class=\"loginBox\">
					<h5>Instructions</h5>
					";
			echo "<p>Only the <strong>Name</strong> is required but this must be unique.</p>";
			echo "<p>The schedule title rather than the survey <strong>Name</strong> is shown on the final survey.</p>";
			echo "<p>You may enter HTML tags into the <strong>Introduction</strong> and <strong>Epilogue</strong> boxes.</p>
					<p>
						Authors are identified by their heraldID - you will need to get this before adding an author.
					</p>
					<p>
						<strong>Allow save before submit</strong> displays a <strong>Save</strong> button with which students can save partially completed surveys.
					</p>
					<p>
						<strong>Allow administrators to view an individual student's choices</strong> is used for surveys in which students make choices between options - it should not be used when collecting feedback. A warning message is displayed to students making it clear that the survey is not anonymous and the title of the page is changed to 'Option Chooser'.
					</p>";
		echo "</div>
			</div>";
?>