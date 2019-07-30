<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Similar Words</title>
</head>
<body style ="font-family:'TITUS Cyberbit Basic';">
<?php
$len = 0;
$kat = "";
$bedeu = "";
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
if (empty($_GET['kat'])) exit;

$breadcrumb = "";
$kat = $_GET['kat'];
$len = strlen($kat);
while ($len > 2) {
	$sql2 = "select num, title from rules where num = \"" . $kat . "\"";
	$result8 = mysql_db_query($ety, $sql2, $conn);
	if (!$result8) {
		echo "Should not happen:" . mysql_error();
	} else {
		$row2 = mysql_fetch_assoc($result8);
		if (!empty($breadcrumb)) $breadcrumb = " >> " . $breadcrumb;
		if ($len > 3) $breadcrumb = $kat . " " . $row2['title'] . $breadcrumb;
// echo $breadcrumb . "<br />&nbsp;<br 7>";
		mysql_free_result($result8);
	}
	$kat = substr($kat,0,strlen($kat)-1);
	$len = strlen($kat);
}

// echo "Similar Words" . $breadcrumb . "<br />&nbsp;<br 7>";

// echo "<h2>Similar words " . $_GET["kat"] . "</h2>";
?>
<p>

<?php
echo "<h3>Alle bisher erfassten Beispiele f&uuml;r diese Sachkategorie: </h3>";

$sql ="select id, lexem, lang, bedeu from main where kat = \"" . $_GET['kat'] . "\" order by lexem;";
$result = mysql_db_query($ety, $sql, $conn);

if (!$result) {
	echo "Sollte nicht vorkommen: " . mysql_error();
} else {
while ($row = mysql_fetch_assoc($result)) {
	if (!empty($row['bedeu'])) {
		$bedeu = $row['bedeu'];
		$bedeu = " &ldquor;" . $bedeu . "&rdquor;";
	} else {
		$bedeu ="";
	}
	echo $row['lang'] . ". <a href=\"http://" . $host . "/index.php?id=" . $row['id'] . "\"><em>" . $row['lexem'] . "</em></a>" . $bedeu . ", ";
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