<?php
require_once('../Resources/Util.php');
session_start();
?>

<!doctype html>
<html>
<head>
  <title>Experiment</title>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/seedrandom/2.4.2/seedrandom.min.js"></script>
	<script src="../Resources/jspsych-5.0.3/jspsych.js"></script>
  <script src="../Resources/jspsych-5.0.3/plugins/jspsych-sequential-priming.js"></script>
  <script src="../Resources/jspsych-5.0.3/plugins/jspsych-text.js"></script>
  <script src="../Resources/jspsych-5.0.3/plugins/jspsych-call-function.js"></script>
  <script src="https://rawgit.com/Cmell/JavascriptUtils/master/Util.js"></script>
  <script src='../Resources/ModS3JSUtil.js'></script>
	<link href="../Resources/jspsych-5.0.3/css/jspsych.css" rel="stylesheet" type="text/css"></link>
</head>
<body>

</body>
<script>
  // Define vars
  var seed, pid, taskTimeline;
  var leftKeyCode, rightKeyCode, correct_answer, goodKeyCode, badKeyCode;
  var mask, redX, check, expPrompt;
  var instr1, instructStim, countdown, countdownNumbers;
  var timeline = [];
  var numTrials = 80;
  var timing_parameters = [200, 200, 200, 500];

  // The timing_parameters should correspond to the planned set of stimuli.
  // In this case, I'm leading with a mask (following Ito et al.), and then
  // the prime, and then the stimulus, and then the mask until the end of the
  // trial.

  // get the pid, read keys:
  <?php
  // Get the pid:
  $pid = $_SESSION["pid"];
  // put the variables out
  echo "goodKey = '".$_SESSION["goodKey"]."';";
  echo "badKey = '".$_SESSION["badKey"]."';";
  echo "pid = ".$pid.";";
  echo "var taskDomain = '".$_SESSION["domain"]."';";
  ?>

  d = new Date();
  seed = d.getTime();
  Math.seedrandom(seed);

  // Some utility variables
  var pidStr = "00" + pid; pidStr = pidStr.substr(pidStr.length - 3);// lead 0s

  var flPrefix = <?php
    if ($_SESSION['domain']=='politics') {
      echo '"../Resources/witPolData/polWIT_";';
    } else if ($_SESSION['domain']=='race') {
      echo '"../Resources/witRaceData/raceWIT_";';
    };
    ?>

  var filename = flPrefix + pidStr + "_" + seed + ".csv";

  var fields = [
    "pid",
    "bad_key",
    "good_key",
    "internal_node_id",
    "key_press",
    "left_valence",
    "right_valence",
    "seed",
    "trial_index",
    "trial_type",
    "trial_num",
    "word",
    "word_type",
    "prime_cat",
    "prime_id",
    "rt",
    "time_elapsed",
    "rt_from_start",
    "correct"
  ]

  // Choose keys:
  leftKey = "e";
  rightKey = "i";
  leftKeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(leftKey);
  rightKeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(rightKey);
  goodKeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(goodKey);
  badKeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(badKey);
  leftValence = goodKeyCode == leftKeyCode ? "GOOD" : "BAD";
  rightValence = goodKeyCode == rightKeyCode ? "GOOD" : "BAD";

  if ((leftValence != "GOOD" && leftValence != "BAD") || (rightValence != "GOOD" && rightValence != "BAD")) {
    throw "keys are bad!";
  }

  // Append pid and condition information to all trials, including my
  // trialNum tracking variable (dynamically updated).
  jsPsych.data.addProperties({
    pid: pid,
    seed: seed,
    good_key: goodKey,
    bad_key: badKey,
    left_valence: leftValence,
    right_valence: rightValence
  });

  // Save data function
  var saveAllData = function () {
    var filedata = jsPsych.data.dataAsCSV;
    // send it!
  	sendData(filedata, filename);
  };

  var endTrial = function (trialObj) {
    // Extract trial information from the trial object adding data to the trial
    var trialCSV = trialObjToCSV(trialObj);
    sendData(trialCSV, filename);
  };

  var generateHeader = function () {
    var line = '';
    var f;
    var fL = fields.length;
    for (i=0; i < fL; i++) {
      f = fields[i];
      if (i < fL - 1) {
        line += f + ',';
      } else {
        // don't include the comma on the last one.
        line += f;
      }
    }

    // Add an eol character or two
    line += '\r\n';
    return(line);
  };

  var sendHeader = function () {
    sendData(generateHeader(), filename);
  }

  var trialObjToCSV = function (t, extras) {
    // t is the trial object
    var f;
    var line = '';
    var fL = fields.length;
    var thing;

    for (i=0; i < fL; i++) {
      f = fields[i];
      thing = typeof t[f] === 'undefined' ? 'NA' : t[f];
      if (i < fL - 1) {
        line += thing + ',';
      } else {
        // Don't include the comma on the last one.
        line += thing;
      }
    }
    // Add an eol character or two
    line += '\r\n';
    return(line);
  };

  var sendData = function (dataToSend) {
    // AJAX stuff to actually send data. The script saves in append mode now.
    $.ajax({
  		type: 'POST',
  		cache: false,
  		url: '../Resources/SaveData.php',
      error: onSaveError,
      success: onSaveSuccess,
  		data: {
  			filename: filename,
  			filedata: dataToSend
  		}
  	});
  };

  var onSaveSuccess = function (data, textStatus, jqXHR) {
    saveSuccessCode = 0;
    numSaveAttempts++;
  };

  var onSaveError = function (data, textStatus, jqXHR) {
    console.log(textStatus);
    if (numSaveAttempts < maxSaveAttempts) {
      sendData();
      numSaveAttempts++;
      console.log(textStatus);
    } else {
      saveSuccessCode = 1;
      console.log(textStatus);
      console.log('Maximum number of save attempts exceeded.')
    }
  };

  // Initialize the data file
  sendHeader();

  // Load instruction strings
  if (goodKeyCode == 69) {
    instr1 = <?php
    $myfile = fopen("./Texts/InstructionsScreen1e-good.txt", "r") or die("Unable to open file!");
    echo json_encode(fread($myfile,filesize("./Texts/InstructionsScreen1e-good.txt")));
    fclose($myfile);
    ?>

  } else {
    instr1 = <?php
    $myfile = fopen("./Texts/InstructionsScreen1i-good.txt", "r") or die("Unable to open file!");
    echo json_encode(fread($myfile,filesize("./Texts/InstructionsScreen1i-good.txt")));
    fclose($myfile);
    ?>

  }

  // Make the expPrompt
  expPrompt = '<table style="width:100%">'
  + '<tr> <th>"' +
  leftKey + '" key: ' + leftValence + '</th> <th>' +
  '"' + rightKey + '" key: ' + rightValence + '</th> </tr>' + '</table>';

  // Make the instruction stimulus.
  instructStim = {
    type: "text",
    text: instr1,
    cont_key: [32]
  };

  // Make a countdown sequence to begin the task
  countdownNumbers = [
    '<div id="jspsych-countdown-numbers">3</div>',
    '<div id="jspsych-countdown-numbers">2</div>',
    '<div id="jspsych-countdown-numbers">1</div>'
  ]
  countdown = {
    type: "sequential-priming",
    stimuli: countdownNumbers,
    is_html: [true, true, true],
    choices: [],
    prompt: expPrompt,
    timing: [1000, 1000, 1000],
    response_ends_trial: false,
    feedback: false,
    timing_post_trial: 0,
    iti: 0
  };

  // Load stimulus lists

  // primes:
  if (taskDomain == "race") {
    set1Fls = <?php echo json_encode(glob("../Resources/Black/*.png")); ?>;
    set2Fls = <?php echo json_encode(glob("../Resources/White/*.png")); ?>;
  } else if (taskDomain == "politics") {
    set1Fls = <?php echo json_encode(glob("../Resources/Dems/*.png")); ?>;
    set2Fls = <?php echo json_encode(glob("../Resources/Reps/*.png")); ?>;
  }

  // words:
  goodWords = <?php echo json_encode(getWords('../good.csv')); ?>;
  badWords = <?php echo json_encode(getWords('../bad.csv')); ?>;

  // Put the stimuli in lists with the relevant information.
  var set1Lst = [];
  var set2Lst = [];
  var goodLst = [];
  var badLst = [];

  var makeStimObjs = function (fls, condVar, condValue) {
    var tempLst = [];
    var tempObj;
    for (i=0; i<fls.length; i++) {
      fl = fls[i];
      flVec = fl.split("/");
      tempObj = {
        file: fl,
        stId: flVec[flVec.length-1]
      };
      tempObj[condVar] = condValue;
      tempLst.push(tempObj);
    }
    return(tempLst);
  };

  var makeWordObjs = function (words, condValue) {
    var tempLst = [];
    var tempObj;
    for (i=0; i<words.length; i++) {
      var w = words[i];
      var htmlStr = '<h2 style="text-align:center;font-size:90px;margin:0;">' + w + '</h2>';
      tempObj = {
        valence: condValue,
        word: w,
        html: htmlStr
      };
      tempLst.push(tempObj);
    }
    return(tempLst);
  };

  if (taskDomain == "race") {
    var set1Lst = makeStimObjs(set1Fls, "primeCat", "black");
    var set2Lst = makeStimObjs(set2Fls, "primeCat", "white");
  } else if (taskDomain == "politics") {
    var set1Lst = makeStimObjs(set1Fls, "primeCat", "democrat");
    var set2Lst = makeStimObjs(set2Fls, "primeCat", "republican");
  }

  var goodLst = makeWordObjs(goodWords, "good");
  var badLst = makeWordObjs(badWords, "bad");

  mask = "MaskReal.png";
  redX = "XwithSpacebarMsg.png";
  check = "CheckReal.png";
  tooSlow = "TooSlow.png";
  blank = "Blank.png"

  // utility sum function
  var sum = function (a, b) {
    return a + b;
  };

  // Randomize the order of trials, but recycle the list first, and randomly
  // choose what's needed in the remaining.
  var words = randomRecycle(goodLst, numTrials/2).concat(randomRecycle(badLst, numTrials/2));

  words = rndSelect(words, words.length);

  var primes = randomRecycle(set1Lst, numTrials/2).concat(randomRecycle(set2Lst, numTrials/2));
  primes = rndSelect(primes, primes.length);

  // Make all the trials and timelines.
  taskTrials = {
    type: "sequential-priming",
    choices: [leftKeyCode, rightKeyCode],
    prompt: expPrompt,
    timing_stim: timing_parameters,
    is_html: [false, false, true, false],
    response_ends_trial: true,
    timeline: [],
    timing_response: timing_parameters[2] + timing_parameters[3],
    response_window: [timing_parameters[0] + timing_parameters[1], Infinity],
    feedback: true,
    //feedback_duration: 1000,
    key_to_advance: 32,
    correct_feedback: check,
    incorrect_feedback: redX,
    timeout_feedback: tooSlow,
    timing_post_trial: 0,
    iti: 800,
    on_finish: endTrial
  };

  for (i=0; i<numTrials; i++){
    correct_answer = words[i].valence == 'good' ? goodKeyCode : badKeyCode;
    tempTrial = {
      stimuli: [mask, primes[i].file, words[i].html, mask],
      data: {
        prime_cat: primes[i].primeCat,
        word_type: words[i].valence,
        prime_id: primes[i].stId,
        word: words[i].word,
        trial_num: i + 1
      },
      correct_choice: correct_answer
    };
    taskTrials.timeline.push(tempTrial);
  }

  /*
  // save the data before the thank you screen.
  // Deprecated.
  saveCall = {
    type: "call-function",
    func: saveData
  };
  */

  // Push everything to the big timeline in order
  timeline.push(instructStim);
  timeline.push(countdown);
  timeline.push(taskTrials);
  //timeline.push(saveCall);
  //timeline.push(thankyouTrial);

  // try to set the background-color
  document.body.style.backgroundColor = '#d9d9d9';

  jsPsych.init({
  	timeline: timeline,
    fullscreen: false,
  	on_finish: function() {
      window.location = "../ctrl.php";
  		;
  	}
  });

</script>
</html>
