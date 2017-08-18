<?php
// Includes
require_once('./Resources/Util.php');
session_start();

// Send the correct place based on the state
$_SESSION["state"] += 1;
$curTask = $_SESSION["taskOrder"][$_SESSION["state"]];

switch ($curTask) {
  case "welcome":
    $redirectUrl = './welcome.php';
    break;

  case "politicalid":
    $redirectUrl = './PIT/pit.php';
    break;

  case "race_WIT":
    $_SESSION["domain"] = "race";
    $redirectUrl = './WIT/seqprime.php';
    break;

  case "pol_WIT":
    $_SESSION["domain"] = "politics";
    $redirectUrl = './WIT/seqprime.php';
    break;

  case "race_IAT":
    $_SESSION["domain"] = "race";
    $redirectUrl = './IAT/iat.php';
    break;

  case "pol_IAT":
    $_SESSION["domain"] = "politics";
    $redirectUrl = './IAT/iat.php';
    break;

  case "explicit":
    $redirectUrl =
    'https://cuboulder.qualtrics.com/jfe/form/SV_79BMieMTCGHAZX7?pid='.$_SESSION["pid"].'&mturkCode='.$_SESSION["mturkCode"];
    break;

  case "end":
    break;
}

  // Send all the things.
  RedirectToURL($redirectUrl);
  //echo $redirectUrl;
?>
