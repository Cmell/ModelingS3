<?php
require_once("./Resources/Util.php");
session_start();
$_SESSION["pid"] = 12;
$_SESSION["domain"] = "politics";
$_SESSION["leftRace"] = "BLACK";
$_SESSION["rightRace"] = "WHITE";
$_SESSION["leftPol"] = "DEMOCRAT";
$_SESSION["rightPol"] = "REPUBLICAN";
$_SESSION["leftValence"] = "GOOD";
$_SESSION["rightValence"] = "BAD";
$_SESSION["goodKey"] = "e";
$_SESSION["badKey"] = "i";

RedirectToURL("./PIT/pit.php");
 ?>
