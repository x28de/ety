<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body style ="font-family:'TITUS Cyberbit Basic';">

<?php 
if ($_SERVER['REQUEST_URI'] == "/") {   // temporaer weil index.php vor index.html geht
	echo "No content yet; go <a href=\"http://x28.privat.t-online.de\">here</a>";
		exit;
}

// Preparations

$eingabe = $lexem = $lang = $rule1 = $rule2 = $rem1 = $rem2 = $bedeu = $kat = "";
$search = $exactmatch = $insert_ok = FALSE;
$del ="";
$lexemnew = $langnew = $rule1new = $rule2new = $rem1new = $rem2new = $bedeunew = "";
$colstring = "";
$parentstring1 = $parentstring2 = $parentstring3 = "";
$parent_lang = $parent_lexem  = "";
$parentname = "";
$parent = 0;
$currentid = $contextroot = $rootlang = "";
$rules = $ruletitle = "";
$ambig = array();
$tree = array();
$tree_up = array();
$keep_context = FALSE;  
$hits = $i = 0;
$result3a = "";  
$dictlexem = "";
$dictlang = "";

// Environment options

$cookiename = "ety_scope";
$hide_lautlehre = FALSE;
$readonly = FALSE;

$config = 1;

switch ($config) {
	case 1: 
	$host = "localhost/dbtest";
	$ety = "ety";
	$conn = mysql_connect("localhost", "root", "(censored)");
	break;
	
	case 0:
	$host = "x28hd.de/(censored)";
	$ety = "(censored)";
	$conn = mysql_connect("localhost", $ety, "(censored));
	break;
	
	case 2:
	$host = "x28hd.de/ety";
	$ety = "(censored)";
	$conn = mysql_connect("localhost", $ety, "(censored)");
	$readonly = TRUE;
	break;
}

$debug = FALSE;
// $debug = TRUE;
if (!empty($_GET['debug'])) $debug = TRUE;

// Database connection
// (Note: the numbers N of $resultN are purely arbitrary)

if (!$conn) {
	echo "Unable to connect to DB: " . mysql_error();
	exit;
	}
if (!mysql_select_db($ety, $conn)) {
	echo "Unable to select mydbname: " . mysql_error();
	exit;
	}
mysql_set_charset ("utf8");
if ($debug) echo "Connected to database <br />"; 


// Searchbox

?>
<div align="right">
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" accept-charset="utf-8">
<table>
<tr>
	<td align="right"><em>Find a lexeme: </em><input style ="font-family:'TITUS Cyberbit Basic';" type="text" name="eingabe">
	<td><input type="submit" name="search" value="Search"></td>
</tr>
<?php if (!$readonly) {
	echo "<tr><td align=\"right\"><em>Keep current choice of parents</em></td>";
	echo "<td><input type=\"checkbox\" name=\"keep\" value=\"true\"></td>";
	echo "</tr>";
}
?>
</table>
</form>
</div>
<?php

// Collect submitted info
//
// -- a search string
// -- an ID from the URL
// -- an ID for action (pick, update or delete)
// -- a flag whether to delete
// -- a flag whether to keep previous context
// -- a cookie containing the kept context 

// Search string

if (!empty($_POST['search'])) {
	$search = TRUE; 
} else $exactmatch = TRUE;

if (!empty($_POST['eingabe'])) {
	$eingabe = $_POST['eingabe'];
	if ($eingabe != trim($eingabe, "\x22")) {
		$exactmatch = TRUE;
		$eingabe = trim($eingabe, "\x22");
		$eingabe = htmlspecialchars($eingabe);
		if ($debug) echo $eingabe . "\n";
	}
} 

// Cookie

if (!isset($_COOKIE[$cookiename])) {
	if ($debug) echo "Cookie named '" . $cookiename . "' is not found! <br />";
	$contextroot = "999";
	} else {
	if ($debug) echo "Cookie '" . $cookiename . "' is found!<br>";
	$contextroot =  $_COOKIE[$cookiename];
// 	if ($debug && $contextroot < 0) echo "Cookie < 0 ";
	if ($debug) echo "Value is: " . $contextroot . "<br />";
}

// ID for action

if (!empty($_POST['id4action'])) {
	$id4action = $_POST['id4action'];
	$currentid = $id4action;
	
	// Deleting
	
	if (!empty($_POST['del'])) {
		if ($debug) echo "<br /> Deleting record ... ";
		$sql = "delete from main where id = " . $id4action . ";";
		$result3 = mysql_db_query($ety, $sql, $conn);
		if (!$result3) {
			echo "Could not delete: " . mysql_error();
		} else {
			$sql3a = "select id, lexem from main where parent = $id4action ;";
			$result3a = mysql_db_query($ety, $sql3a, $conn);
		if ($result3a) {
			if (mysql_num_rows($result3a) > 0) {
				echo "<br />Attention: The following children are now orphans:<ul>";
				$row = 0;
				while ($row = mysql_fetch_assoc($result3a)) {
					echo "<li><a href=\"http://" . $host . "/index.php?id=" . $row['id'] . "\" target=\"_BLANK\">" . $row['lexem'] . "</a></li> ";
					$row++;
				}
				echo "</ul>";
				mysql_free_result($result3a);
			}
		}
			echo "<em>Response: Record # $id4action successfully deleted.<br /></em>";
		}
		
	// Updating
		
	} else {
		
		$lexemnew = $langnew = $rule1new = $rule2new = $rem1new = $rem2new = $parentnew = $bedeunew = "";

		if (!empty($_POST['lang'])) $langnew = " lang = \"" . $_POST['lang'] . "\",";
		if (!empty($_POST['lexem'])) $lexemnew = " lexem = \"" . $_POST['lexem'] . "\",";
		if (!empty($_POST['bedeu'])) $bedeunew = " bedeu = \"" . $_POST['bedeu'] . "\",";
		if (!empty($_POST['rule1'])) $rule1new = " rule1 = \"" . $_POST['rule1'] . "\",";
		if (!empty($_POST['rule2'])) $rule2new = " rule2 = \"" . $_POST['rule2'] . "\",";
		if (!empty($_POST['rem1'])) $rem1new = " rem1 = \"" . $_POST['rem1'] . "\",";
		if (!empty($_POST['rem2'])) $rem2new = " rem2 = \"" . $_POST['rem2'] . "\",";
		if ($_POST['parent'] != 0) $parentnew = " parent = \"" . $_POST['parent'] . "\","; else $parentnew = " parent = 0, ";
		if ($_POST['parent'] == $id4action) 
			{
			echo "<h2>Abbruch, bitte neu starten</h2>(Parent ung&uuml;ltig)";
			exit;
			}
	
		$colstring = $langnew . $lexemnew . $bedeunew . $parentnew . $rule1new . $rule2new . $rem1new . $rem2new;
		$colstring = substr($colstring, 1, strlen($colstring)-2);
	
		if (empty($_POST['pick'])) {

			$sql = "update main set " .  $colstring . " where id = " . $id4action . ";";
			$result4 = mysql_db_query($ety, $sql, $conn);

			if (!$result4) {
				echo "Could not update: " . mysql_error() . "<br />" . $sql;
			} else {
				echo "<br /><em>Response: Record # $id4action ($lexemnew) successfully updated: <br /></em>";
				$search = TRUE;
			}

		// Just picking
		
		} else $currentid = $id4action;

	}  // not delete

}  // id4action not empty


// ID in URL ?

if (!empty($_GET['id'])) {
	$currentid = $_GET['id'];
}

// No request yet? Then show 3 appetizers
if ($currentid == 0 && $eingabe == "") {
$i = 1;
$appetizer = "";
while ($i < 4) {
	$random = rand(1000,15000);
	$sql = "select lang, lexem from main where id = \"" . $random . "\";";
	$result = mysql_db_query($ety, $sql, $conn);
	if (!$result) continue;
	if (mysql_num_rows($result) < 1) continue;
	else {
		$row = mysql_fetch_assoc($result);
		$appetizer = $appetizer . $row['lang'] . ". <a href=\"http://" . $host . "/index.php?id=" . $random . "\"><em>" . $row['lexem'] . "</em></a>, ";
		$i++;
		mysql_free_result($result);
	}
}
echo "<p>Examples: " .  $appetizer . "</p>";
}

// keep previous context?

if (!empty($_POST['keep']))	
{
$keep_context = TRUE;	
if ($debug) echo "<br />KEEP</br />";
}

// Process input string 
// -- either for Search (from Search Box)
// -- or for a new record to be inserted (from lexem field in Input Mask);
// Empty search result is bad or good, respectively.
	
if (!empty($eingabe)) {
$sql = "select id, lexem, bedeu, lang, parent, rule1, rule2, rem1, rem2, kat from main where lexem regexp '^[*(]*" . $eingabe . ".*'";
if ($exactmatch)
	$sql = "select id, lexem, bedeu, lang, parent, rule1, rule2, rem1, rem2, kat from main where lexem like '" . $eingabe . "'";
$result = mysql_db_query($ety, $sql, $conn);

if (!$result) { 
	echo "Could not successfully run query ($sql) from DB: " . mysql_error();
	exit;
	}
	
$hits = mysql_num_rows($result);
	
// Insert new record 
// (ok only if at least the combination of lang and lexem is new)

if ($hits == 0) 
	if ($search) 
	{
		echo "<p>Nothing found for <em>" . $eingabe . "</em>"; 
	} else $insert_ok = TRUE;

if ($hits > 0 && !$search) 
{
	echo "Already exists";

	if (!empty($_POST['lang'])) {
		$langnew = $_POST['lang'];
		$sqlchk = "select lexem, lang from main where lexem = '" . $eingabe . "' and lang = '" . $langnew . "';";
	} else {
		$langnew = "";
		$sqlchk = "select lexem from main where lexem = '" . $eingabe . "';";
	}
	$resultchk = mysql_db_query($ety, $sqlchk, $conn);
		if (!$resultchk) {
		echo "Could not successfully run query ($sqlchk) from DB: " . mysql_error();
		exit;
	}
	
	$hitschk = mysql_num_rows($resultchk);
	if ($hitschk > 0) {
		echo "; cannot insert.";
	} else {
		echo " with different language attribute; inserting anyway.<br />";
		$insert_ok = TRUE;
	}
}
	
if ($insert_ok) {
	
	$lexemnew = $langnew = $rule1new = $rule2new = $rem1new = $rem2new = $parentnew = $bedeunew = "\"\",";
	if (!empty($_POST['lang'])) $langnew = "\"" . $_POST['lang'] . "\",";
	if (!empty($_POST['bedeu'])) $bedeunew = "\"" . $_POST['bedeu'] . "\","; 
	$lexemnew = "\"" . $eingabe . "\","; 
	if (!empty($_POST['rule1'])) $rule1new = "\"" . $_POST['rule1'] . "\","; 
	if (!empty($_POST['rule2'])) $rule2new = "\"" . $_POST['rule2'] . "\","; 
	if (!empty($_POST['rem1'])) $rem1new = "\"" . $_POST['rem1'] . "\","; 
	if (!empty($_POST['rem2'])) $rem2new = "\"" . $_POST['rem2'] . "\","; 
	
	if ($_POST['parent'] != 0) $parentnew = "\"" . $_POST['parent'] . "\",";  
	
	$colstring = $langnew . $lexemnew . $bedeunew . $parentnew . $rule1new . $rule2new . $rem1new . $rem2new;
	$colstring = substr($colstring, 0, strlen($colstring)-1);
	
	$sql = "insert into main (lang, lexem, bedeu, parent, rule1, rule2, rem1, rem2) values(" . $colstring . ");";
	$result2 = mysql_db_query($ety, $sql, $conn);
	if (!$result2) {
		echo "Could not insert: " . mysql_error();
	} else {
		echo "<br /><em>Response: $eingabe successfully inserted.<br /></em>";
	}
	
	$currentid = mysql_insert_id();
}	
}
	
// Show searched/ inserted/ updated records

if (!empty($currentid)) {
	$sql = "select id, lexem, bedeu, lang, parent, rule1, rule2, rem1, rem2, kat from main where id = '" . $currentid . "'";
	$result = mysql_db_query($ety, $sql, $conn);
	
	if (!$result) {
		echo "Could not successfully run query ($sql) from DB: " . mysql_error();
		exit;
	}
	
// 	$parentstring1 = "<select style =\"font-family:'TITUS Cyberbit Basic';\" name=\"parent\" size=\"1\"><option value=\"0\">" . $currentid . "</option>\n" . $parentstring3 . "</select>";
	$eingabe = "";
	$search = TRUE;

	$hits = mysql_num_rows($result);
}
	
if ($hits > 0) {
	
// Collect results of each row
	
$r = 0;
while ($row = mysql_fetch_assoc($result)) {
	$ambig[$r]['lexem'] = $row['lexem'];
	$ambig[$r]['id'] = $row['id'];
	$ambig[$r]['lang'] = $row['lang'];
	$ambig[$r]['parent'] = $row['parent'];
	$ambig[$r]['bedeu'] = $row['bedeu'];
	$ambig[$r]['rule1'] = $row['rule1'];
	$ambig[$r]['rule2'] = $row['rule2'];
	$ambig[$r]['rem1'] = $row['rem1'];
	$ambig[$r]['rem2'] = $row['rem2'];
	$ambig[$r]['kat'] = $row['kat'];
	
	$r++;
}
mysql_free_result($result);

// Display results (begin)

$oldcookie = $contextroot;
if ($keep_context)
{
	$contextroot = $oldcookie;
	if ($debug) echo "oldcookie " . $oldcookie;
} else {
	$contextroot = $ambig[0]['id'];
} 
$cookievalue = $contextroot;
if (!$readonly) setcookie($cookiename, $cookievalue, 0);
if ($debug) echo "\nCookie set:" . $cookievalue . "\n";


// Recursively determine the context tree  

list ($parentstring3, $output) = context($contextroot, $currentid);

$parentstring3 = $parentstring3 . "\n<option value=\"0\" >--- none ---</option>";

if (!empty($eingabe)) {
	echo "<h2>Results for <em>" . $eingabe . "</em></h2>";
	} else {
	echo "<h2>Result </h2>";
	}
	
$r=0; 
?>

<table border="1"> <tr>
<th>id</th>
<th>&nbsp;</th>
<th>lang</th>
<th>lexem</th>
<th>bedeu</th>

<?php 
if (!$readonly) echo "<th>parent</th>"; 
if (!$hide_lautlehre) {
	echo "<th>rule1</th>";
	echo "<th>rule2</th>";
} 
if (!$readonly) {
	echo "<th>rem1</th>";
	echo "<th>rem2</th>";
	echo "<th><font color=\"red\">L&ouml;sch?</font></th>";
	echo "<th>&nbsp;</th>"; 
}
echo "</tr>";

while ($r < $hits)
	
	// Lookup parent for initialising the dropdown list

	{
	$lookup = $ambig[$r]['parent'];
	$sql = "select id, lang, lexem from main where id = \"" . $lookup . "\";";
	$result5 = mysql_db_query($ety, $sql, $conn);
	if (!$result5) {
		echo "Could not find parent " . $lookup . ": " . mysql_error();
	} else {
		$row = mysql_fetch_assoc($result5);
		$parent_lang = $row['lang']; 
		$parent_lexem = $row['lexem']; 
    	$parentstring2 = "<select style =\"font-family:'TITUS Cyberbit Basic';\" name=\"parent\" size=\"1\">\n
			<option value=\"" . $lookup .  "\">" . $parent_lang . " " . $parent_lexem . "</option>\n
			" . $parentstring3 . "</select>";
    	mysql_free_result($result5);
	}
	
	// Display pickable row

	?>
<tr>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" accept-charset="utf-8">
<td><?php echo $ambig[$r]['id'];?><input type="hidden" name="id4action" value="<?php echo $ambig[$r]['id']?>"></td>
<td><input type="submit" name ="pick" value="Pick"></td>
<td><input type="text" size="5" name="lang" value="<?php echo $ambig[$r]['lang']; ?>"></td>
<td><input style ="font-family:'TITUS Cyberbit Basic';" type="text" name="lexem" value="<?php echo $ambig[$r]['lexem']; ?>"></td>
<td><input type="text" name="bedeu" value="<?php echo $ambig[$r]['bedeu']; ?>"></td>

	<?php 
	if (!$readonly) echo "<td>" . $parentstring2 . "</td>";
	if (!$hide_lautlehre) {
		echo "<td><input type=\"text\" size=\"5\" name=\"rule1\" value=\"" . $ambig[$r]['rule1'] . "\"></td>";
		echo "<td><input type=\"text\" size=\"5\" name=\"rule2\" value=\"" . $ambig[$r]['rule2'] . "\"></td>";
	}
	if (!$readonly) {
		echo "<td><input type=\"text\" name=\"rem1\" value=\"" . $ambig[$r]['rem1'] . "\"></td>";
		echo "<td><input type=\"text\" name=\"rem2\" value=\"" . $ambig[$r]['rem2'] . "\"></td>";
		echo "<td  nowrap><input type=\"checkbox\" name=\"del\" value=\"Entf\"></td>";
		echo "<td><input type=\"submit\" value=\"Change\"></td>"; 
	} ?>

</form>
</tr>
	<?php 
	$r++;
} 

?>
</table>
<br />
<?php 

// Show Tree View

?>
<h2><?php if ($keep_context) echo "Previous "?>Tree View</h2>
<?php

// echo $output . "</ul>";
$output = preg_replace("/^<li>/", "", $output);
echo $output;

echo "<br /><br /><b>Disclaimer: </b> 
		The arrow \">\" does not always mean an etymological derivation. 
		Sometimes it may be just a conjecture (even a refuted one), or an error. 
		Take the tree as a suggestion for lookup, ";
if ($dictlang == "dt") echo 
		"e.g. try <a href=\"https://dwds.de/wb/" . $dictlexem . "#et-1\">https://www.dwds.de/wb/" . $dictlexem . "#et-1</a>";
else if ($dictlang == "engl") echo
		"e.g. try <a href=\"http://etymonline.com/index.php?search=" . $dictlexem . "\">http://etymonline.com/index.php?search=" . $dictlexem . "</a>";
else if ($dictlang == "frz") echo
		".e.g. try <a href=\"https://fr.wiktionary.org/wiki/" . $dictlexem . "#.C3.89tymologie\">https://fr.wiktionary.org/wiki/" . $dictlexem . "#.C3.89tymologie</a>";
// temp

if (!empty($ambig[0]['kat'])) {
	$kat = $ambig[0]['kat'];
	echo "<p><a href=\"http://" . $host . "/simil.php?kat=" . $kat . "\">Similar words</a>";
}

}   // hits > 0

// New Input Mask form

list ($parentstring3, $output) = context($contextroot, $currentid);
$parentstring1 = "<select style =\"font-family:'TITUS Cyberbit Basic';\" name=\"parent\" size=\"1\"><option value=\"0\">Select:</option>\n" . $parentstring3 . "</select>";

if ($readonly) echo "<!-- "; 
?>
		
<br />
<h2>Input Mask</h2>
<p>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" accept-charset="utf-8">
<table border="1">
<tr> 
<th>lang</th>
<th>lexem</th>
<th>bedeu</th>
<th>parent</th>
<?php if (!$hide_lautlehre) {
	echo "<th>rule1</th>";
	echo "<th>rule2</th>";
	} ?>
<th>rem1</th>
<th>rem2</th><th>&nbsp;</th>
</tr>
<tr>
	<td><input type="text" size="5" name="lang" value="<?php echo $lang; ?>"></td>
	<td><input style ="font-family:'TITUS Cyberbit Basic';" 
	type="text" name="eingabe"></td>
	<td><input type="text" name="bedeu" value="<?php echo $bedeu; ?>"></td>
	<td><?php echo $parentstring1 ?></td> 
	<?php if (!$hide_lautlehre) {     
	echo "<td><input type=\"text\" size=\"5\" name=\"rule1\" value=\"" . $rule1 . "\"></td>";
	echo "<td><input type=\"text\" size=\"5\" name=\"rule2\" value=\"" . $rule2 . "\"></td>";
	} ?>
	<td><input type="text" name="rem1" value="<?php echo $rem1; ?>"></td>
	<td><input type="text" name="rem2" value="<?php echo $rem2; ?>"></td>
	<td><input type="submit" value="Create"></td>
</tr>
</table>
</form>

<?php if ($readonly) echo " -->"; 


// Finish

?>

<p>
&nbsp;<br /> 
<?php
if (!$hide_lautlehre)
echo "<a href=\"http://" . $host . "/help.htm\">Help</a>"; 
else echo "<a href=\"http://" . $host . "/stats.htm\">More</a>"; 
?>
</p>
</body>
</html>
<?php 
if (mysql_close($conn) && $debug) echo "<br />&nbsp;<br />Done; database connection closed";


// -----------
// Function(s)
// -----------

function context($contextroot, $currentid)

// Climb the tree upwards to the root, 
// then call the recursive function "descendant()" to descend to all leaves.
// Is used twice:
// -- for the parent dropdown list,
// -- for the Tree View

{
	global $host, $ety, $conn;
	global $parentstring3;
	global $hide_lautlehre;
	
	// Upwards to word root
	
	$depth=0;
	$lookup = $contextroot;
	
	while ($lookup != 0) {
		$tree_up[$depth] = $lookup;
		$sql = "select id, lang, lexem, parent from main where id = \"" . $lookup . "\";";
		$result7 = mysql_db_query($ety, $sql, $conn);
		if (!$result7) {
			echo "Could not find parent " . $lookup . ": " . mysql_error();
		} else {
			$row = mysql_fetch_assoc($result7);
			$lookup = $row['parent'];
			$parentname = $row['lexem'];
			$rootlang = $row['lang'];
			mysql_free_result($result7);
		}
		$depth++;
		if ($depth > 10) {
			echo "<h2>Fehler, bitte reparieren:</h2>Parent von <em>" . $currentid . "</em> ung&uuml;ltig";
			return;
		}
	}
	
	for ($n = 0; $n < $depth; $n++) {    // Reversing (rather useless?)
		$m = $depth -1 - $n;
		$tree[$n] = $tree_up[$m];
	}
	
	$parentstring3 = "<option value=\"" . $tree[0] . "\">" . $rootlang . " " . $parentname . "</option>";

	// Call the recursive function descendant() to determine the tree
	
	$output =  descendant($parentname,$tree[0],1,$contextroot);
		
return array($parentstring3, $output);
}


function descendant($nodename, $nodeid, $level, $currentid) 

// This is the recursive function to find the children of $nodeid .
// The word of $currentid (which was searched for, or picked) is highlighted,
// $nodename may be useless except for further debug statements 
// $level was to limit loops (in case grandchild is father of grandfather);
//   (did not work, $depth worked; so $level also useless except for debug).
// 

// How it works: 

// On each level, the strings for the Tree View ($retstring) and the Parent Dropdown list
// ($parentstring3) are appended by the new subtree.
// Initially, the hierarchy is indicated by <li> indentation, but if the total output length 
// on a given level underruns some threshold, the indentation is replaced by semicolons.  
 
{
	global $host, $ety, $conn;
	global $parentstring3;
	global $hide_lautlehre;
	global $dictlexem, $dictlang;
	
	// Fetch info for Tree View
	
	$sql = "select lexem, lang, bedeu, rule1, rule2, rem1, rem2 from main where id = $nodeid";
	$result5 = mysql_db_query($ety, $sql, $conn);
	if (!$result5) {
		echo "Should not happen:" . mysql_error();
		echo "<br />" . $sql;
	} else {
		$row0 = mysql_fetch_assoc($result5);
		
		// Phonological (Lautlehre) information from the rule1/2 columns is presented 
		// as hoverable and clickable anchors, unless suprressed by $hide_lautlehre
		$rules = "";
		if (! $hide_lautlehre) {
			$rule1 = $row0['rule1'];
			if (!empty($rule1)) {
				$sql2 = "select num, title from rules where num = \"" . $rule1 . "\"";
				$result8 = mysql_db_query($ety, $sql2, $conn);
				if (!$result8) {
					echo "Should not happen:" . mysql_error();
					echo "<br />" . $sql2;
					} else {
					$row2 = mysql_fetch_assoc($result8);
					$ruletitle = $row2['title'];
					mysql_free_result($result8);
				}
				$rule1url = str_replace(utf8_encode("§"), "%C2%A7", $rule1);
				$rules = " (<a href=\"http://" . $host . "/rules.php?rule=" . $rule1url . "\" title=\"" . $ruletitle . "\">" . $rule1 . "</a>) ";
			}
			$rule2 = $row0['rule2'];
			if (!empty($rule2)) $rules = substr($rules, 0,  strlen($rules)-2) . ", " . $rule2 . ") ";
		} // end of lautlehre
		
		
		// Remarks rem1/2 are presented in parentheses
		$rem1 = $row0['rem1'];
		if (!empty($rem1)) $rem1 = " (" . $rem1 . ") ";
		$rem2 = $row0['rem2'];
		if (!empty($rem2)) $rem2 = " (" . $rem2 . ") ";
		
		// the lexem 
		$lexem = $row0['lexem'];
		if ($nodeid == $currentid) {
			$dictlexem = $lexem . "";
			$dictlang = $row0['lang'];
			$lexem = "<font color =\"red\">" . $lexem . "</font>";
		}
		$lexem = "<em>" . $lexem . "</em>";
		
		// the meaning (Bedeutung) in quotes
		$bedeu = $row0['bedeu'];
		if (!empty($bedeu)) $bedeu = " &ldquor;" . $bedeu . "&rdquor;";
		
		// the whole Tree View info of one record  
		$retstring = $row0['lang'] . ". " . $lexem . $bedeu . $rules . $rem1 . $rem2 ;	 

		mysql_free_result($result5);
	}
	
	// Process hierarchy info
	
	if ($level > 11) exit;

	$sql2 = "select id, lang, lexem from main where parent = $nodeid";    // ########### Find children ##########

	$result6 = mysql_db_query($ety, $sql2, $conn);
	if (!$result6) {
		echo "Could not find children " . $nodeid . ": " . mysql_error();
		echo "<br />" . $sql2;
	} else {

		// no children
		if (mysql_num_rows($result6) == 0) {
			$retstring = "<li>" . $retstring . ", </li>";
			
		// loop through children	
		} else {
			$retstring = "<li>" . $retstring . " ></li><ul>";
			while ($row = mysql_fetch_assoc($result6)) {
				$parentstring3 = $parentstring3 . "<option value=\"" . $row['id'] . "\"> " . $row['lang'] . " " . $row['lexem'] . "</option>\n";
				
				$delta = descendant($row['lexem'], $row['id'], $level + 1, $currentid);   // ######## Call recursive function ########
				
				$retstring = $retstring . $delta;
			}
			$retstring = $retstring . "</ul>";
		}
		mysql_free_result($result6);
	}
	
	// Simplify small subtrees
	
	if (strlen($retstring) < 200) {
		$retstring = str_replace("<li>","",$retstring);
		$retstring = str_replace("</li>","",$retstring);
		$retstring = str_replace("<ul>","",$retstring);
		$retstring = str_replace(", </ul>"," ; ",$retstring);
		$retstring = str_replace("; </ul>"," ; ; ",$retstring);
		$retstring = "<li>" . $retstring . "</li>";
	} 	
	return $retstring;
}
?>
