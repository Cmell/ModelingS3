<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Welcome!</title>
</head>
<style>
.button {
    background-color: #5e95ed;
    border: none;
    border-radius: 6px;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
    margin: 4px 2px;
    cursor: pointer;
}
.button:hover {
    background-color: #617ba5;
}
  #welcomebody {
    width: 800px;
    margin: 0 auto;
  }
  #welcomehead {
    background-color: #B6B6B6;
    padding-left: 25px;
    border: solid;
    border-radius: 6px;
    text-align: center;
  }
</style>
<body id="welcomebody">

<h2 id="welcomehead">Welcome to the experiment!</h2>
<p>
  This experiment is an experiment designed to assess your attitudes toward
  several groups of people. <b>It will take time and require attention!</b> If
  you wish to continue, please make sure you have a <b>full 35 minutes</b> to
  work continuously. Also, <b>please turn off music, TV, or other
  distractions </b> before you begin. This will ensure that the data you provide
  is of high quality. Thank you for your cooperation!
</p>
<p>
  This experiment will consist of several different parts. Each part will
include its own instructions.
</p>

<p>
  <strong>Please read the instructions for each task carefully!</strong>
</p>

<p>
  To participate in this study, you must agree to the
  <a href="./Resources/15-0511 Consent Form (10Aug16).pdf">informed consent</a>.
  Please click the link and read that document now. It will open in a new window.
</p>

<p>
  If you agree with the consent document, press "I agree" below to begin the task.
</p>

<div style="text-align:center; padding:15px">
  <input type="button" id="continuebutton" class="button" value="I Agree to Participate">
</div>

<script>
var continueClick = function () {
  window.location = "ctrl.php";
};

var cstBtn = document.getElementById("continuebutton");
if (cstBtn.addEventListener)
  cstBtn.addEventListener("click", continueClick, false);
else if (cstBtn.attachEvent)
  cstBtn.attachEvent('onclick', continueClick);
</script>

</body>
</html>
