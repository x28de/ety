<?php
header("Content-type: text/plain; charset=utf-8");
$dumprow = "";

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

// Dump DB content

$sql = "select * from main;";
$result = mysql_db_query($ety, $sql, $conn);
if (!$result) {
	echo "Should not happen: Select failed;" . mysql_error();
} else {
	$row = 0;
	echo "\"id\";\"lang\";\"lexem\";\"bedeu\";\"parent\";\"rule1\";\"rule2\";\"rem1\";\"rem2\";\"kat\";\n";
	while ($row = mysql_fetch_assoc($result)) {
		$dumprow = "";
		$dumprow = $dumprow . "\"" . $row['id'] . "\";";
		$dumprow = $dumprow . "\"" . $row['lang'] . "\";";
		$dumprow = $dumprow . "\"" . $row['lexem'] . "\";";
		$dumprow = $dumprow . "\"" . $row['bedeu'] . "\";";
		if ($row['parent'] == 0) {
			$dumprow = $dumprow . "\"\";";
		} else {
			$dumprow = $dumprow . "\"" . $row['parent'] . "\";";
		}
		$dumprow = $dumprow . "\"" . $row['rule1'] . "\";";
		$dumprow = $dumprow . "\"" . $row['rule2'] . "\";";
		$dumprow = $dumprow . "\"" . $row['rem1'] . "\";";
		$dumprow = $dumprow . "\"" . $row['rem2'] . "\";";
		$dumprow = $dumprow . "\"" . $row['kat'] . "\";";
		echo $dumprow . "\n";
		$row++;
	}
	mysql_free_result($result);
	exit;
}

