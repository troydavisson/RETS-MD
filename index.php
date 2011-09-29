<?php

ob_start("ob_gzhandler");


$media_url = "media/";
$path_to_me = $_SERVER['SCRIPT_NAME'];

require "phrets.php";
session_start();  // start session management

if (!isset($_REQUEST['action'])) {
	$_REQUEST['action'] = "";
}

switch ($_REQUEST['action']) {

	case "login":
	code_login();
	break;

	case "logout":
	code_logout();
	break;

	case "lookup":
	code_lookup();
	break;

	case "export":
	code_export();
	break;

	case "objects":
	code_objects();
	break;

	case "peek":
	code_peek();
	break;

	case "mddetails":
	code_mddetails();
	break;

	case "about":
	code_about();
	break;

	default:
	code_main();

}



function code_mddetails() {

	$details_for_resource = $_REQUEST['r_resource'];
	$details_for_class = $_REQUEST['r_class'];

	// check if we're supposed to pull specific metadata information about a class
	if (!empty($details_for_resource) && !empty($details_for_class)) {


		if ($_SESSION['logged_in'] != "yes") {
			not_logged_in();
		}

		// set things up
		$rets = new phRETS;

		$rets->AddHeader("Accept", "*/*");
		$rets->AddHeader("RETS-Version", "RETS/{$_SESSION['rets_version']}");
		$rets->AddHeader("User-Agent", $_SESSION['user_agent']);

		$rets->SetParam("compression_enabled", true);

		if ($_SESSION['force_basic'] == "true") {
			$rets->SetParam("force_basic_authentication", true);
		}

		// make first connection
		$connect = $rets->Connect($_SESSION['login_url'], $_SESSION['username'], $_SESSION['password'], $_SESSION['ua_pwd']);

		if (!$connect) {
			$error_details = $rets->Error();
			$error_text = strip_tags($error_details['text']);
			$error_type = strtoupper($error_details['type']);
			show_error_page("<center><span style='color:red;font-weight:bold;'>{$error_type} ({$error_details['code']}) {$error_text}</span></center>");
		}

		$resource_info = $rets->GetMetadataInfo();

		// make specific GetMetadata request for this resource class
		$rets_metadata = $rets->GetMetadataTable($details_for_resource, $details_for_class);

		if (!$rets_metadata) {
			$error_details = $rets->Error();
			$error_text = strip_tags($error_details['text']);
			$error_type = strtoupper($error_details['type']);
			echo "<center><span style='color:red;font-weight:bold;'>{$error_type} ({$error_details['code']}) {$error_text}</span></center>";
		}
		else {

			$searchable_fields = array();
			$total_fields = 0;
			$metadata_fields = "";

			$field_bg = "white-bg";

			$is_key_in_index = false;

			foreach ($rets_metadata as $field) {
				if ($field['Searchable'] == "True" || $field['Searchable'] == 1) {
					// metadata says we can search this field
					$searchable_fields[] = $field['SystemName'];
					$is_searchable = " <img src='{$GLOBALS['media_url']}search-icon-grey.gif' alt='Searchable Field'/>";
				}
				else {
					$is_searchable = "";
				}

				if ($field['InKeyIndex'] == 1) {
					$in_key_index = " <img src='{$GLOBALS['media_url']}heart.png' alt='InKeyIndex'/>";
					if (isset($resource_info[$details_for_resource]['KeyField']) && $resource_info[$details_for_resource]['KeyField'] == $field['SystemName']) {
						$is_key_in_index = true;
					}
				}
				else {
					$in_key_index = "";
				}

				if (!empty($field['LookupName'])) {
					// metadata says that lookup values exist for this.  add link to screen
					$niced_longname = $field['LongName'];
					$niced_longname = preg_replace('/\s/', '_', $niced_longname);
					$niced_longname = preg_replace('/\'/', '', $niced_longname);
					$niced_longname = addslashes( strtolower($niced_longname) );
					$show_lookup = "<a href=\"\" class='lookup-window' data-resource='{$details_for_resource}' data-lookupname='{$field['LookupName']}' title='Lookup Values for {$field['SystemName']} ({$field['LookupName']})'>Values</a>";
				}
				else {
					$show_lookup = "";
				}
				$short_longname = substr($field['LongName'], 0, 50);
				if (strlen($field['LongName']) > 50) {
					// clean up the display so it's not too long
					$short_longname .= "...";
				}

				if (isset($resource_info[$details_for_resource]['KeyField']) && $resource_info[$details_for_resource]['KeyField'] == $field['SystemName']) {
					$is_key = " <img src='{$GLOBALS['media_url']}skey.png' alt='Key Field'/>";
				}
				else {
					$is_key = "";
				}

				if (isset($field['Required']) && $field['Required'] == 1) {
					$is_required = " <img src='{$GLOBALS['media_url']}star.png' alt='Required'/>";
				}
				else {
					$is_required = "";
				}

				$metadata_fields .= "
				<tr class='{$field_bg}'>
					<td class='det_sysname'>{$field['SystemName']}{$is_required}{$is_searchable}{$in_key_index}{$is_key}</td>
					<td class='det_stdname'>{$field['StandardName']}</td>
					<td class='det_longname'>{$short_longname}</td>
					<td>{$field['DataType']}</td>
					<td>{$field['MaximumLength']}</td>
					<td>{$show_lookup}</td>
				</tr>\n";
				$total_fields++;

				$field_bg = ($field_bg == "light-bg") ? "white-bg" : "light-bg";

			}

			$searchable_fields_list = "";
			foreach ($searchable_fields as $field) {
				$searchable_fields_list .= "{$field}, ";
			}
			$searchable_fields_list = preg_replace('/\, $/', '', $searchable_fields_list);

			$show_key_in_index = "";
			if ($is_key_in_index == true) {
				$show_key_in_index = " <img src='{$GLOBALS['media_url']}heart.png' alt='InKeyIndex'/>";
			}


			echo "<table border='0' cellpadding='5' cellspacing='0' width='680'>\n";
			echo "<tr>\n";
			echo "<td width='340' valign='top'>\n";

				echo "<table border='0' cellpadding='1' cellspacing='1' width='100%'>";
				echo "<tr><td width='110' valign='top'><b>Resource:</b></td><td width='220' class='detail'>{$details_for_resource}</td></tr>\n";
				echo "<tr><td valign='top'><b>Class:</b></td><td class='detail'>{$details_for_class}</td></tr>\n";
				echo "<tr><td valign='top'><b># of Fields:</b></td><td class='detail'>{$total_fields}</td></tr>\n";
				if (isset($resource_info[$details_for_resource]['KeyField'])) {
					echo "<tr><td valign='top'><b>Key Field:</b></td><td class='detail'>{$resource_info[$details_for_resource]['KeyField']} <img src='{$GLOBALS['media_url']}skey.png' alt='Key Field' />{$show_key_in_index}</td></tr>\n";
				}

				$object_types = $rets->GetMetadataObjects($details_for_resource);
				if (count($object_types) > 0 && is_array($object_types)) {
					echo "<tr><td valign='top'><b>Object Types:</b></td><td class='detail'><a href=\"\" id='object-window' data-resource='{$details_for_resource}' title=\"View available media/object types for the '{$details_for_resource}' resource\">View Object Types</a></td></tr>\n";
				}

				echo "</table>";
			echo "</td>";
			echo "<td width='340' valign='top'>\n";
				echo "<table border='0' cellpadding='1' cellspacing='1' width='100%'>\n";

				if (detect_capable_server($_SESSION['login_url']) != 0) {
					echo "<tr><td width='1%'><img src='{$GLOBALS['media_url']}magnifier.gif' alt='View Sample Data'></td><td width='99%' class='detail'><a href=\"\" id='peek-link' data-resource='{$details_for_resource}' data-class='{$details_for_class}' data-format='COMPACT-DECODED'>Take a Peek</a> &nbsp;<span style='color: red;'>NEW!</span></td></tr>\n";
					echo "<tr><td></td><td class='detail'>View a live sample of records in the below format</td></tr>\n";
				}

				echo "<tr><td width='1%'><img src='{$GLOBALS['media_url']}export.png' alt='Export'></td><td width='99%' class='detail'><a href='{$GLOBALS['path_to_me']}?action=export&r_resource={$details_for_resource}&r_class={$details_for_class}'>Export Field Data</a></td></tr>\n";
				echo "<tr><td></td><td class='detail'>Download the metadata in CSV format</td></tr>\n";

				echo "</table>\n";

			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";

			echo "<br/>";

			echo "<table border='0' cellpadding='3' cellspacing='0' width='100%' class='metadata_details_fields' id='metadata_details_fields'>";
			echo "<tr><td width='20%' class='det_sysname'><b>SystemName</b></td><td width='20%' class='det_stdname'><b>StandardName</b></td><td width='30%'><b>Description</b></td><td width='10%'><b>Type</b></td><td width='10%'><b>Length</b></td><td width='10%'><b>Lookup</b></td></tr>\n";
			echo $metadata_fields;
			echo "</table>";

		}

		$rets->Disconnect();

	}

}



function code_peek() {

	if ($_SESSION['logged_in'] != "yes") {
		echo "Not logged in.";
		exit;
	}

	if ($_REQUEST['r_format'] == "COMPACT") {
		$current_format = "COMPACT";
		$format_link = "<a href='' id='format-switch' data-resource='{$_REQUEST['r_resource']}' data-class='{$_REQUEST['r_class']}' data-format='COMPACT-DECODED'>Switch to COMPACT-DECODED</a>";
	}
	else {
		$current_format = "COMPACT-DECODED";
		$format_link = "<a href='' id='format-switch' data-resource='{$_REQUEST['r_resource']}' data-class='{$_REQUEST['r_class']}' data-format='COMPACT'>Switch to COMPACT</a>";
	}

	// set things up
	$rets = new phRETS;

	$rets->AddHeader("Accept", "*/*");
	$rets->AddHeader("RETS-Version", "RETS/{$_SESSION['rets_version']}");
	$rets->AddHeader("User-Agent", $_SESSION['user_agent']);

	$rets->SetParam("compression_enabled", true);

	if ($_SESSION['force_basic'] == "true") {
		$rets->SetParam("force_basic_authentication", true);
	}

	// make first connection
	$connect = $rets->Connect($_SESSION['login_url'], $_SESSION['username'], $_SESSION['password'], $_SESSION['ua_pwd']);

	if (!$connect) {
		$error_details = $rets->Error();
		$error_text = strip_tags($error_details['text']);
		$error_type = strtoupper($error_details['type']);
		show_error_page("<center><span style='color:red;font-weight:bold;'>{$error_type} ({$error_details['code']}) {$error_text}</span></center>");
	}

	$records = array();
	$search = null;

	if (detect_capable_server($_SESSION['login_url']) === 1) {
		$search = $rets->SearchQuery($_REQUEST['r_resource'], $_REQUEST['r_class'], '*', array('Format' => $current_format, 'Limit' => 5, 'RestrictedIndicator' => 'RETSMDRESTR') );
		while ($rec = $rets->FetchRow($search)) {
			$records[] = $rec;
		}
	}
	elseif (detect_capable_server($_SESSION['login_url']) === 2) {
		$search = $rets->SearchQuery($_REQUEST['r_resource'], $_REQUEST['r_class'], "", array('Format' => $current_format, 'QueryType' => 'DMQL2', 'Limit' => 5, 'RestrictedIndicator' => 'RETSMDRESTR') );
		while ($rec = $rets->FetchRow($search)) {
			$records[] = $rec;
		}
	}
	else {

	}

	if (!$search) {
		$error_details = $rets->Error();
		$error_text = strip_tags($error_details['text']);
		$error_type = strtoupper($error_details['type']);
		show_error_page("<center><span style='color:red;font-weight:bold;'>{$error_type} ({$error_details['code']}) {$error_text}</span></center>");
	}

?>

<div class='box'>
	<a name='md-details'></a>
	<div class='box_heading dark-bg'>
<?php echo $_REQUEST['r_resource']; ?>:<?php echo $_REQUEST['r_class']; ?> - View Sample Data<br/>
<small><?php echo $current_format; ?> format (<?php echo $format_link; ?>)</small>
	</div>
	<div class='box_content light-bg'>

		<div style="width: 100%; height: 250px; overflow: auto;">
<?php

	$field_list = $rets->SearchGetFields($search);

	$system_to_long = array();
	$table_metadata = $rets->GetMetadataTable($_REQUEST['r_resource'], $_REQUEST['r_class']);
	foreach ($table_metadata as $fi) {
		$system_to_long["{$fi['SystemName']}"] = $fi['LongName'];
	}

	echo "<table border='1' cellpadding='2' cellspacing='0' width='100%' class='metadata_details_fields'>\n";

	echo "<tr>\n";
	foreach ($field_list as $fi) {
		echo "<td valign='top'><b>{$fi}</b><br/>{$system_to_long["{$fi}"]}</td>";
	}
	echo "</tr>\n";

	$field_bg = "white-bg";

	foreach ($records as $rec) {
		echo "<tr class='{$field_bg}'>";
			foreach ($field_list as $fi) {
				if ($rec[$fi] == "RETSMDRESTR") {
					$rec[$fi] = "<span style='color:red;'>RESTRICTED</span>";
				}
				echo "<td valign='top'>{$rec[$fi]}</td>\n";
			}
		echo "</tr>\n";
		$field_bg = ($field_bg == "light-bg") ? "white-bg" : "light-bg";
	}

	echo "<tr>\n";
	foreach ($field_list as $fi) {
		echo "<td valign='top'><b>{$fi}</b><br/>{$system_to_long["{$fi}"]}</td>";
	}
	echo "</tr>\n";

	echo "</table>\n";
	
	$rets->Disconnect();

echo "
		</div>
	</div>
</div>

";


}


function code_about() {

page_header("What is RETSMD.com?", false);

echo "
<div class='box'>
	<div class='box_heading dark-bg'>What is RETSMD.com?</div>
	<div class='box_content light-bg'>
<p><a href='http://rets.org'>RETS</a> servers handle 3 main purposes:</p>

<ol>
  <li>To provide detailed descriptions of the types of data and fields you can receive</li>
  <li>To deliver data matching what #1 describes</li>
  <li>To deliver objects (mainly photos) for records provided as part of #2</li>
</ol>

<p>RETS M.D. tries to handle, simplify and summarize #1 while also providing small glimpses into #2 and #3.  Since the
information learned from #1 is often a one-time need, using a pre-built metadata viewing tool is often a much better
option compared to writing a custom application which will show you much of the same information.</p>

<p>Using the information filled into the login page on RETSMD.com, the site will begin communicating with the given
RETS server so it can learn about what it has to offer.  Several things are discovered during this time, including:</p>

<ul>
  <li>What capabilities the server has</li>
  <li>What categories (and sub-categories) of data are made available</li>
  <li>Whether or not those categories support photos belonging to records</li>
  <li>What kinds of fields can be retrieved from each sub-category</li>
</ul>

<p>This service is created to provide you with as much information as is typically needed while trying to hide as many
of the often-unused details in the background.</p>

<p>All of the information and features this site contains are made possible by using
<a href='http://troda.com/projects/phrets'>PHRETS</a> as the underlying RETS library allowing it to communicate with
compliant RETS servers.</p>

	</div>
</div>

<p style='text-align: center'><a href='{$GLOBALS['path_to_me']}'>Back to Main Page</a></p>

";

page_footer(false);

}


function code_export() {

	$fields_to_export = array('SystemName', 'StandardName', 'LongName', 'DBName',
		'ShortName', 'MaximumLength', 'DataType', 'Precision',
		'Searchable', 'Interpretation', 'Alignment',
		'UseSeparator', 'EditMaskID', 'LookupName',
		'MaxSelect', 'Units', 'Index', 'Minimum',
		'Maximum', 'Default', 'Required', 'SearchHelpID',
		'Unique', 'MetadataEntryID', 'ModTimeStamp',
		'ForeignKeyName', 'ForeignField', 'InKeyIndex'
	);


	if ($_SESSION['logged_in'] != "yes") {
		echo "Not logged in.";
		exit;
	}

	// set things up
	$rets = new phRETS;

	$rets->AddHeader("Accept", "*/*");
	$rets->AddHeader("RETS-Version", "RETS/{$_SESSION['rets_version']}");
	$rets->AddHeader("User-Agent", $_SESSION['user_agent']);

	$rets->SetParam("compression_enabled", true);

	if ($_SESSION['force_basic'] == "true") {
		$rets->SetParam("force_basic_authentication", true);
	}

	// make first connection
	$connect = $rets->Connect($_SESSION['login_url'], $_SESSION['username'], $_SESSION['password'], $_SESSION['ua_pwd']);

	if (!$connect) {
		$error_details = $rets->Error();
		$error_text = strip_tags($error_details['text']);
		$error_type = strtoupper($error_details['type']);
		show_error_page("<center><span style='color:red;font-weight:bold;'>{$error_type} ({$error_details['code']}) {$error_text}</span></center>");
	}


	$table = $rets->GetMetadataTable($_REQUEST['r_resource'], $_REQUEST['r_class']);

	$csv = fopen('php://temp/maxmemory:'. (5*1024*1024), 'r+');

	$output_list = array();
	foreach ($fields_to_export as $fi) {
		$output_list[] = $fi;
	}

	fputcsv($csv, $output_list);

	foreach ($table as $field) {
		$output_field = array();

		foreach ($fields_to_export as $fi) {
			if (isset($field[$fi])) {
				$output_field[$fi] = $field[$fi];
			}
		}

		fputcsv($csv, $output_field);

	}

	rewind($csv);
	$output = stream_get_contents($csv);

	$filename = strtolower("rets-metadata-{$_REQUEST['r_resource']}-{$_REQUEST['r_class']}.csv");

	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"{$filename}\"");

	echo $output;

}


function code_objects() {

	if ($_SESSION['logged_in'] != "yes") {
		not_logged_in();
	}

	// set things up
	$rets = new phRETS;

	$rets->AddHeader("Accept", "*/*");
	$rets->AddHeader("RETS-Version", "RETS/{$_SESSION['rets_version']}");
	$rets->AddHeader("User-Agent", $_SESSION['user_agent']);

	$rets->SetParam("compression_enabled", true);

	if ($_SESSION['force_basic'] == "true") {
		$rets->SetParam("force_basic_authentication", true);
	}

	// make first connection
	$connect = $rets->Connect($_SESSION['login_url'], $_SESSION['username'], $_SESSION['password'], $_SESSION['ua_pwd']);

	if (!$connect) {
		$error_details = $rets->Error();
		$error_text = strip_tags($error_details['text']);
		$error_type = strtoupper($error_details['type']);
		show_error_page("<center><span style='color:red;font-weight:bold;'>{$error_type} ({$error_details['code']}) {$error_text}</span></center>");
	}

	$field_bg = "white-bg";

	$object_table = "";

	$object_types = $rets->GetMetadataObjects($_REQUEST['r_resource']);

	if (count($object_types) > 0 && is_array($object_types)) {
		foreach ($object_types as $type) {
			$object_table .= "<tr class='{$field_bg}'><td>{$type['ObjectType']}</td><td>{$type['Description']}</td><td>{$type['MimeType']}</td></tr>\n";
			$field_bg = ($field_bg == "light-bg") ? "white-bg" : "light-bg";
		}
	}
	elseif (is_array($object_types)) {
              $object_table .= "<tr class='white-bg'><td align='center' colspan='3'>No available objects for this resource</td></tr>\n";
	}
	else {
		$object_table .= "<tr><td colspan='3'>" . print_r($rets->Error(), true) . "</td></tr>\n";
	}

	// disconnect from the RETS server
	$rets->Disconnect();

?>

<div class='box'>
	<a name='md-details'></a>
	<div class='box_heading dark-bg'><?php echo $_REQUEST['r_resource']; ?> - Available Media/Objects</div>
	<div class='box_content light-bg'>

<table border='0' cellpadding='2' cellspacing='0' width='100%' class='metadata_details_fields'>
<tr><td width='25%'><b>Type</b></td><td width='50%'><b>Description</b></td><td width='25%'><b>MIME Type</b></td></tr>
<?php echo $object_table; ?>
</table>

	</div>
</div>


<?php


}



function not_logged_in() {
	echo "Not logged in.";
	exit;
}



function code_lookup() {

	if ($_SESSION['logged_in'] != "yes") {
		not_logged_in();
	}

	// set things up
	$rets = new phRETS;

	$rets->AddHeader("Accept", "*/*");
	$rets->AddHeader("RETS-Version", "RETS/{$_SESSION['rets_version']}");
	$rets->AddHeader("User-Agent", $_SESSION['user_agent']);

	$rets->SetParam("compression_enabled", true);

	if ($_SESSION['force_basic'] == "true") {
		$rets->SetParam("force_basic_authentication", true);
	}

	// make first connection
	$connect = $rets->Connect($_SESSION['login_url'], $_SESSION['username'], $_SESSION['password'], $_SESSION['ua_pwd']);

	if (!$connect) {
		$error_details = $rets->Error();
		$error_text = strip_tags($error_details['text']);
		$error_type = strtoupper($error_details['type']);
		show_error_page("<center><span style='color:red;font-weight:bold;'>{$error_type} ({$error_details['code']}) {$error_text}</span></center>");
	}

	// make lookup request
	$rets_lookup_values = $rets->GetLookupValues($_REQUEST['r_resource'], $_REQUEST['r_lookupname']);

	$field_bg = "white-bg";

	$lookup_table = "";

	if (is_array($rets_lookup_values) && count($rets_lookup_values) > 0) {
		// loop through loopup values returned
		foreach ($rets_lookup_values as $lookup) {
			$lookup_table .= "<tr class='{$field_bg}'><td>{$lookup['Value']}</td><td>{$lookup['ShortValue']}</td><td>{$lookup['LongValue']}</td></tr>\n";
			$field_bg = ($field_bg == "light-bg") ? "white-bg" : "light-bg";
		}
	}
	elseif (is_array($rets_lookup_values) && count($rets_lookup_values) == 0) {
		$lookup_table .= "<tr class='white-bg'><td align='center' colspan='3'>No available lookups for this field</td></tr>\n";
	}
	else {
		$lookup_table .= "<tr><td colspan='3'>" . print_r($rets->Error(), true) . "</td></tr>\n";
	}

	// disconnect from the RETS server
	$rets->Disconnect();

?>

<div class='box'>
	<a name='md-details'></a>
	<div class='box_heading dark-bg'><?php echo $_REQUEST['r_resource'].':'.$_REQUEST['r_lookupname']; ?> Lookup Values</div>
	<div class='box_content light-bg'>

<table border='0' cellpadding='2' cellspacing='0' width='100%' class='metadata_details_fields'>
<tr><td width='15%'><b>Value</b></td><td width='35%'><b>Short Value</b></td><td width='50%'><b>Long Value</b></td></tr>
<?php echo $lookup_table; ?>
</table>

	</div>
</div>


<?php


}




function code_logout() {
	// delete local session data
	session_destroy();
	if (isset($_COOKIE[session_name()])) {
		// clear cookie
		setcookie(session_name(), '', time()-42000, '/');
	}

	// send back to the beginning
	header("Location: {$GLOBALS['path_to_me']}");
	exit;
}



function code_login() {

	if (empty($_REQUEST['login_url']) || empty($_REQUEST['username']) || empty($_REQUEST['password']) || empty($_REQUEST['rets_version']) || empty($_REQUEST['user_agent'])) {
		show_error_page("All fields are required.");
	}

	// set things up
	$rets = new phRETS;

	$rets->AddHeader("Accept", "*/*");
	$rets->AddHeader("RETS-Version", "RETS/{$_REQUEST['rets_version']}");
	$rets->AddHeader("User-Agent", $_REQUEST['user_agent']);

	$rets->SetParam("cookie_file", $GLOBALS['cookie_file_name']);

	if ($_REQUEST['force_basic'] == "true") {
		 $rets->SetParam("force_basic_authentication", true);
	}

	// test connection
	$connect = $rets->Connect(trim($_REQUEST['login_url']), trim($_REQUEST['username']), trim($_REQUEST['password']), trim($_REQUEST['ua_pwd']));

	if (!$connect) {
		$error_details = $rets->Error();
		$error_text = strip_tags($error_details['text']);
		$error_type = strtoupper($error_details['type']);
		show_error_page("<center><span style='color:red;font-weight:bold;'>{$error_type} ({$error_details['code']}) {$error_text}</span></center>");
	}

	// disconnect
	$rets->Disconnect();

	// if we made it this far, the connection worked and everything was fine.

	// save login values to the user's session
	$_SESSION['logged_in'] = "yes";

	if (isset($_REQUEST['login_url'])) {
		$_SESSION['login_url'] = trim($_REQUEST['login_url']);
	}
	if (isset($_REQUEST['username'])) {
		$_SESSION['username'] = trim($_REQUEST['username']);
	}
	if (isset($_REQUEST['password'])) {
		$_SESSION['password'] = trim($_REQUEST['password']);
	}
	if (isset($_REQUEST['rets_version'])) {
		$_SESSION['rets_version'] = $_REQUEST['rets_version'];
	}
	if (isset($_REQUEST['user_agent'])) {
		$_SESSION['user_agent'] = $_REQUEST['user_agent'];
	}
	if (isset($_REQUEST['ua_pwd'])) {
		$_SESSION['ua_pwd'] = $_REQUEST['ua_pwd'];
	}
	if (isset($_REQUEST['force_basic'])) {
		$_SESSION['force_basic'] = $_REQUEST['force_basic'];
	}

	// send back to the beginning
	header("Location: {$GLOBALS['path_to_me']}");

}





function code_main() {
	// check if a session (w/ login) exists
	if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] != "yes") {
		code_login_page();
	}

	// check if we're trying to get metadata details.  if so, chop up the request
	if (isset($_REQUEST['details_for']) && !empty($_REQUEST['details_for'])) {
		list($details_for_resource,$details_for_class) = explode("|", $_REQUEST['details_for']);
	}
	else {
		$details_for_resource = "";
		$details_for_class = "";
	}

	// set things up
	$rets = new phRETS;

	$rets->AddHeader("Accept", "*/*");
	$rets->AddHeader("RETS-Version", "RETS/{$_SESSION['rets_version']}");
	$rets->AddHeader("User-Agent", $_SESSION['user_agent']);

	$rets->SetParam("cookie_file", $GLOBALS['cookie_file_name']);
	$rets->SetParam("compression_enabled", true);

	if ($_SESSION['force_basic'] == "true") {
	        $rets->SetParam("force_basic_authentication", true);
	}

	// make first connection
	$connect = $rets->Connect($_SESSION['login_url'], $_SESSION['username'], $_SESSION['password'], $_SESSION['ua_pwd']);

	if (!$connect) {
		$error_details = $rets->Error();
		$error_text = strip_tags($error_details['text']);
		$error_type = strtoupper($error_details['type']);
		show_error_page("<center><span style='color:red;font-weight:bold;'>{$error_type} ({$error_details['code']}) {$error_text}</span></center>");
	}

	$resource_info = $rets->GetMetadataInfo();

	page_header("Server Information");

	echo "<div class='box'>
	<div class='box_heading dark-bg'>Server Information</div>
	<div class='box_content light-bg'>
		<table border='0' cellpadding='1' cellspacing='1' width='100%'>
		";

	// read back information from connection request
	$rets_server_information = $rets->GetServerInformation();
	echo "<tr><td width='25%' valign='top'><b>RETS Server:</b></td><td width='75%' class='detail'>{$rets_server_information['SystemDescription']}</td></tr>\n";
	echo "<tr><td valign='top'><b>RETS System ID:</b></td><td class='detail'>{$rets_server_information['SystemID']}</td></tr>\n";
	if (array_key_exists('TimeZoneOffset', $rets_server_information) && !empty($rets_server_information['TimeZoneOffset'])) {
		echo "<tr><td valign='top'><b>Server Timezone:</b></td><td class='detail'>{$rets_server_information['TimeZoneOffset']}</td></tr>\n";
	}

	// read back information from connection request
	$full_login_url = $rets->GetLoginURL();
	echo "<tr><td valign='top'><b>Login URL:</b></td><td class='detail'>{$full_login_url}</td></tr>\n";

	// read back information from connection request
	$server_version = preg_replace('/RETS\//', '', $rets->GetServerVersion());
	echo "<tr><td valign='top'><b>RETS Version:</b></td><td class='detail'>{$server_version}</td></tr>\n";

	// read back information from connection request
	$server_software = $rets->GetServerSoftware();
	if (empty($server_software)) {
		$server_software = "(unknown)";
	}
	echo "<tr><td valign='top'><b>Server Software:</b></td><td class='detail'>{$server_software}</td></tr>\n";

	// read back information from connection request
	$auth_support = "";
	if ($rets->CheckAuthSupport("digest") == true) {
		$auth_support .= "Digest, ";
	}
	if ($rets->CheckAuthSupport("basic") == true) {
		$auth_support .= "Basic, ";
	}
	$auth_support = preg_replace('/\, $/', '', $auth_support);
	if (empty($auth_support)) {
		$auth_support = "(unknown - assuming Basic)";
		// cURL blindly sends the login information if Basic so it doesn't give us a chance to auto-detect
	}
	echo "<tr><td valign='top'><b>Authen. Supported:</b></td><td class='detail'>{$auth_support}</td></tr>\n";

	// read back information from login response
	$transactions = $rets->GetAllTransactions();
	$transactions_list = "";
	foreach ($transactions as $transaction) {
		$transactions_list .= "<acronym title='{$rets->capability_url[$transaction]}'>{$transaction}</acronym>, ";
	}
	$transactions_list = preg_replace('/\, $/', '', $transactions_list);
	echo "<tr><td valign='top'><b>Transactions Supported:</b></td><td class='detail'>{$transactions_list}</td></tr>\n";

	// make first general GetMetadata request to see what's there
	$rets_metadata_types = $rets->GetMetadataTypes();
	$resources_avail = "";
	foreach ($rets_metadata_types as $resource) {
		$resources_avail .= "<a href='#md-{$resource['Resource']}'>{$resource['Resource']}</a>, ";
	}
	$resources_avail = preg_replace('/\, $/', '', $resources_avail);
	echo "<tr><td valign='top'><b>Metadata Resources:</b></td><td class='detail'>{$resources_avail}</td></tr>\n";

	echo "		</table>
	</div>
	</div>
	";

echo "
<div class='box'>
	<div class='box_heading dark-bg'>Metadata Information</div>
	<div class='box_content light-bg'>

";

$metadata_keyfield = array();
$metadata_details_pulldown = "";

	foreach ($rets_metadata_types as $resource) {
		echo "<a name='md-{$resource['Resource']}'></a>";
		$metadata_details_pulldown .= "<optgroup label='{$resource['Resource']}'>";
		echo "<div class='box-inner white-bg'>";
		echo "<b>{$resource['Resource']}</b><br/><br/>\n";
		echo "<span style='font-size: 8pt;'>";
		foreach ($resource['Data'] as $class) {
			$this_selected = ($details_for_resource == $resource['Resource'] && $details_for_class == $class['ClassName']) ? " selected='selected'" : "";
			$metadata_details_pulldown .= "<option value='{$resource['Resource']}|{$class['ClassName']}'{$this_selected}>{$class['ClassName']} - {$class['VisibleName']} - {$class['Description']}</option>";
			echo " &nbsp; &nbsp; &nbsp; &nbsp;&middot; <b><a href='' class='resource-class-link' data-resource='{$resource['Resource']}' data-class='{$class['ClassName']}' title='SystemName: {$class['ClassName']}   StandardName: {$class['StandardName']}'>{$class['ClassName']}</a></b> - {$class['VisibleName']} - {$class['Description']}<br/>";
			echo " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; StandardName: {$class['StandardName']} &nbsp; &nbsp; &nbsp; &nbsp; Last Updated: {$class['TableDate']} &nbsp; &nbsp; &nbsp;  &nbsp; Version: {$class['TableVersion']}<br/>";
			echo "<br/>\n";
		}
		$metadata_details_pulldown .= "</optgroup>";
		echo "</span>\n";
		echo "</div>\n\n";
	}

echo "

	</div>
</div>
";




echo "
<div class='box'>
	<a name='md-details' id='md-details'></a>
	<div class='box_heading dark-bg'>Metadata Details</div>
	<div class='box_content light-bg'>

";

echo "<center><select name='details_for' id='resource-class-selector' ><option value=''></option>{$metadata_details_pulldown}</select></center><br/>";


echo "

		<div id='md-details-content'>

		</div>

	</div>
</div>
";



echo "
<div style='text-align: center;'>
<p><img src='{$GLOBALS['media_url']}search-icon-grey.gif' alt='Searchable Field'/> Searchable Field &nbsp; &nbsp; &nbsp; <img src='{$GLOBALS['media_url']}skey.png' alt='Key Field'/> Key Field &nbsp; &nbsp; &nbsp; <img src='{$GLOBALS['media_url']}star.png' alt='Required'/> Required &nbsp; &nbsp; &nbsp; <img src='{$GLOBALS['media_url']}heart.png' alt='InKeyIndex'/> InKeyIndex</p>
</div>
";

	// disconnect from RETS server
	$rets->Disconnect();
	page_footer();

}



function show_error_page($error_message = "") {

page_header("Error");
echo "
<div class='box'>
	<div class='box_heading dark-bg'>Error</div>
	<div class='box_content light-bg'>
		<p><b>There was an issue communicating with the RETS server:</b></p>
		<p>{$error_message}</p>
	</div>

</div>
";
page_footer();

exit;

}




function code_login_page() {

if (isset($_REQUEST['load']) && $_REQUEST['load'] == "demo") {
	$login_url = "http://demo.crt.realtors.org:6103/rets/login";
	$username = "Joe";
	$password = 'Schmoe';
	$user_agent = "RETSMD/1.0";
	$rets_version = "1.5";
}
else {
	$user_agent = "RETSMD/1.0";
	$rets_version = "1.5";
	$username = $_REQUEST['username'];
	$password = $_REQUEST['password'];
	$login_url = $_REQUEST['login_url'];
}

$possible_versions = array("1.0","1.5","1.7","1.7.2");
$version_options = "";
foreach ($possible_versions as $version) {
	if ($version == $rets_version) {
		$this_selected = " selected='selected'";
	}
	else {
		$this_selected = "";
	}
	$version_options .= "<option value='{$version}'{$this_selected}>{$version}</option>";
}

page_header();

echo "
<div class='box'>
	<div class='box_heading dark-bg'>Login</div>
	<div class='box_content light-bg'>
		<form method='post' action='{$GLOBALS['path_to_me']}'>
		<input type='hidden' name='action' value='login'/>
		<table border='0' cellpadding='1' cellspacing='1' width='100%'>
			<tr><td width='25%'><b><label for='l-login_url'>Login URL:</label></b></td><td width='75%'><input type='text' id='l-login_url' name='login_url' size='65' value='{$login_url}'/></td></tr>
			<tr><td><b><label for='l-username'>Username:</label></b></td><td><input type='text' id='l-username' name='username' size='15' value='{$username}'/></td></tr>
			<tr><td><b><label for='l-password'>Password:</label></b></td><td><input type='password' id='l-password' name='password' size='15' value='{$password}'/></td></tr>
			<tr class='extra-link-row'><td></td><td><a href='' id='extra-link'>Show More Options</a></td></tr>
			<tr class='extra'><td><b><label for='l-user_agent'>User-Agent:</label></b></td><td><input type='text' id='l-user_agent' name='user_agent' size='20' value='{$user_agent}'/></td></tr>
			<tr class='extra'><td><b><label for='l-ua_pwd'>User-Agent Password:</label></b></td><td><input type='password' id='l-ua_pwd' name='ua_pwd' size='15' value='{$ua_pwd}'/> <small>(Leave blank if you don't have one)</small></td></tr>
			<tr class='extra'><td><b><label for='l-rets_version'>RETS Version:</label></b></td><td><select id='l-rets_version' name='rets_version'>{$version_options}</select></td></tr>
			<tr class='extra'><td><b>Force Basic Auth.:</b></td><td><label for='force_basic_y'><input type='radio' name='force_basic' value='true' id='force_basic_y'/> Yes &nbsp;</label> &nbsp; <label for='force_basic_n'><input type='radio' name='force_basic' value='false' checked='checked' id='force_basic_n'/> No</label></td></tr>
			<tr><td></td><td><input type='submit' value='    Login    ' id='login-button' /></td></tr>
		</table>
		</form>
	</div>

</div>

<p style='text-align: center'><a href='{$GLOBALS['path_to_me']}?load=demo'>Try the demo</a></p>\n

<!-- <a href='http://github.com/troydavisson/RETS-MD'><img style='position: absolute; top: 0; right: 0; border: 0;' src='https://a248.e.akamai.net/assets.github.com/img/e6bef7a091f5f3138b8cd40bc3e114258dd68ddf/687474703a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f7265645f6161303030302e706e67' alt='Fork me on GitHub'></a> -->

";

page_footer();

exit;


}




function page_header($page_title = "", $show_logout_link = true) {

$page_title = (empty($page_title)) ? "Main" : $page_title;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>RETS M.D. - <?php echo $page_title; ?></title>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<link href="<?php echo $GLOBALS['media_url']; ?>styles.css?3" rel="stylesheet" type="text/css" />
<script language="javascript" type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.0/jquery.min.js"></script>
<script type="text/javascript">
jQuery.noConflict();
</script>
<script language="javascript" type="text/javascript" src="<?php echo $GLOBALS['media_url']; ?>jquery.simplemodal.1.4.1.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $GLOBALS['media_url']; ?>jquery.scrollTo-min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $GLOBALS['media_url']; ?>js.js?11"></script>
<script language="javascript" type="text/javascript">
	var this_page = "<?php echo $GLOBALS['path_to_me']; ?>";
	var this_media = "<?php echo $GLOBALS['media_url']; ?>";
</script>
</head>
<body>
<div id="big-container">
<div id="container">
<div id="top">RETS M.D.</div>
<?php

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == "yes" && $show_logout_link) {
	echo "<p style='text-align: center'><a href='{$GLOBALS['path_to_me']}?action=logout'>Logout</a></p>\n";
}

}

function page_footer($show_logout_link = true) {

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == "yes" && $show_logout_link) {
	echo "<p style='text-align: center'><a href='{$GLOBALS['path_to_me']}?action=logout'>Logout</a></p>\n";
}

?>
</div>
</div>

<div id="footer">
	<div style='height: 5px; border-bottom: 2px solid #5573FF;' class='dark-bg'>&nbsp;</div>
	<div class='body_content light-bg' style='text-align: center;'>
<br />
version 1.5 &nbsp; - &nbsp; powered by <a href='http://troda.com/projects/phrets' title='PHRETS'>PHRETS</a> &nbsp; - &nbsp; <a href="<?php echo $GLOBALS['path_to_me']; ?>?action=about">what is this?</a> &nbsp; - &nbsp; <a href="mailto:troy.davisson@gmail.com">feedback</a><br />
<br />
	</div>
</div>

</body>
</html>

<?php

}


// used to detect if a server supports the ability to pull records using an "open" query style
function detect_capable_server($login_url) {
	if (strpos($login_url, 'retsgw.flexmls.com') !== false || strpos($login_url, 'rets2.nefmls.com') !== false) {
		// optional Query and QueryType
		return 1;
	}
	elseif (strpos($login_url, 'trend.trendrets.com') !== false || strpos($login_url, 'wirex.wirexrets.com') !== false) {
		// optional Query but QueryType still required
		return 2;
	}
	else {
		return 0;
	}
}
