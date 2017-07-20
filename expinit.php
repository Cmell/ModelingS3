<?php
// Includes
require_once('Resources/pInfo.php');
require_once('Resources/Util.php');

// Choose the condition information, and set up the order
$expOrder = array("welcome");
$expOrder = array("politicalid");

if (mt_rand(0, 1)) {
  $firstTask = "WIT";
} else {
  $firstTask = "IAT";
}

if (mt_rand(0, 1)) {
  $firstDomain = "race";

  if ($firstTask == "WIT") {
    $expOrder[] = "race_WIT";
    $expOrder[] = "pol_WIT";
    $expOrder[] = "race_IAT";
    $expOrder[] = "pol_IAT";
  } else if ($firstTask == "IAT") {
    $expOrder[] = "race_IAT";
    $expOrder[] = "pol_IAT";
    $expOrder[] = "race_WIT";
    $expOrder[] = "pol_WIT";
  }
} else {
  $firstDomain = "politics";

  if ($firstTask == "WIT") {
    $expOrder[] = "pol_WIT";
    $expOrder[] = "race_WIT";
    $expOrder[] = "pol_IAT";
    $expOrder[] = "race_IAT";
  } else if ($firstTask == "IAT") {
    $expOrder[] = "pol_IAT";
    $expOrder[] = "race_IAT";
    $expOrder[] = "pol_WIT";
    $expOrder[] = "race_WIT";
  }
}

$expOrder[] = "explicit";
$expOrder[] = "end";

// Get a pid
$pinfo = array($firstTask, $firstDomain);
$pid = getNewPID('./Resources/PID.csv', $pinfo);

// Choose key assignments.
if (mt_rand(0, 1)) {
  $leftRace = "BLACK";
  $rightRace = "WHITE";
} else {
  $leftRace = "WHITE";
  $rightRace = "BLACK";
}
if (mt_rand(0, 1)) {
  $leftPol = "REPUBLICAN";
  $rightPol = "DEMOCRAT";
} else {
  $leftPol = "DEMOCRAT";
  $rightPol = "REPUBLICAN";
}
if (mt_rand(0, 1)) {
  $leftValence = "GOOD";
  $goodKey = "e";

  $rightValence = "BAD";
  $badKey = "i";
} else {
  $leftValence = "BAD";
  $badKey = "e";

  $rightValence = "GOOD";
  $goodKey = "i";
}

// Set session variables
session_start();
$_SESSION["pid"] = $pid;
$_SESSION["taskOrder"] = $expOrder;
$_SESSION["state"] = -1;
$_SESSION["domain"] = "none";
$_SESSION["leftRace"] = $leftRace;
$_SESSION["rightRace"] = $rightRace;
$_SESSION["leftPol"] = $leftPol;
$_SESSION["rightPol"] = $rightPol;
$_SESSION["leftValence"] = $leftValence;
$_SESSION["rightValence"] = $rightValence;
$_SESSION["goodKey"] = $goodKey;
$_SESSION["badKey"] = $badKey;

// Start the control
RedirectToURL('ctrl.php');
?>
