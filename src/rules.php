<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Lautlehre</title>
</head>
<body style ="font-family:'TITUS Cyberbit Basic';">
<?php
$len = 0;
$rule = $rulenum = "";
$breadcrumb = "";

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
if (!$conn) {
	echo "Unable to connect to DB: " . mysql_error();
	exit;
}

if (!mysql_select_db($ety, $conn)) {
	echo "Unable to select mydbname: " . mysql_error();
	exit;
}

mysql_set_charset ("utf8");
?>
<!-- Connected to database <br />&nbsp;<br />  -->

<?php
if (empty($_GET['rule'])) exit;

$breadcrumb = "";
$rule = $_GET['rule'];
$rulenum = substr($rule,3);
$len = strlen($rule);
$pagefound = FALSE;
while ($len > 2) {
	$sql2 = "select num, title, page from rules where num = \"" . $rule . "\"";
	$result8 = mysql_db_query($ety, $sql2, $conn);
	if (!$result8) {
		echo "Should not happen:" . mysql_error();
	} else {
		$row2 = mysql_fetch_assoc($result8);
		if (!$pagefound) {
			$page = $row2['page'];
			$pagefound = TRUE;
		}
		if (!empty($breadcrumb)) $breadcrumb = " > " . $breadcrumb;
		$rule1url = str_replace(utf8_encode("§"), "%C2%A7", $rule);
		if ($len > 3) $breadcrumb = "<a href=\"http://" . $host . "/rules.php?rule=" . $rule1url . "\">" . $rule . " " . $row2['title'] . "</a>" . $breadcrumb;
// echo $breadcrumb . "<br />&nbsp;<br 7>";
		mysql_free_result($result8);
	}
	$rule = substr($rule,0,strlen($rule)-1);
	$len = strlen($rule);
}

echo "Lautlehre" .  $breadcrumb . "<br />&nbsp;<br 7>";

echo "<h2>Lautlehre " . $_GET["rule"] . "</h2>";

?>
Siehe Willms, <em>Klassische Philologie und Sprachwissenschaft</em>, Seite <?php echo $page; ?>  
<hr>
<iframe width="90%" src="rules/<?php echo $rulenum;?>.xhtml"></iframe>
<hr>

<?php echo "<h3>Alle bisher erfassten Beispiele f&uuml;r diese Regel: </h3>";

$sql ="select id, lang, lexem from main where rule1 = \"" . $_GET['rule'] . "\" order by lang, lexem asc;";
$result = mysql_db_query($ety, $sql, $conn);

if (!$result) {
	echo "Sollte nicht vorkommen: " . mysql_error();
} else {
while ($row = mysql_fetch_assoc($result)) {
	echo $row['lang'] .". <a href=\"http://" . $host . "/index.php?id=" . $row['id'] . "\"><em>" . $row['lexem'] . "</em></a>, <br />";
}
}
?>

</body>
</html>

<?php 
if (mysql_close($conn)) {
// 	echo "<br />&nbsp;<br />Done; database connection closed";
}
?>