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
  <script src="../Resources/FileToNames.js"></script>
  <script src="../Resources/jspsych-5.0.3/plugins/jspsych-categorize.js"></script>
  <script src="../Resources/jspsych-5.0.3/plugins/jspsych-html.js"></script>
  <script src="https://rawgit.com/Cmell/JavascriptUtils/master/Util.js"></script>
  <script src='../Resources/ModS3JSUtil.js'></script>
	<link href="../Resources/jspsych-5.0.3/css/jspsych.css" rel="stylesheet" type="text/css"></link>
</head>
<script>
  // Task parameters.
  var d = new Date();
  var seed = d.getTime();
  Math.seedrandom(seed);
  var pid =<?php echo json_encode($_SESSION["pid"]);?>;
  var taskTimeline = [];
  var numTrialsPerStimulus = 2;
  var leftParty =<?php echo json_encode($_SESSION["leftPol"]);?>;
  var rightParty =<?php echo json_encode($_SESSION["rightPol"]);?>;
  if (leftParty == "DEMOCRAT") {
    var keysByParty = {
      DEMOCRAT: "e",
      REPUBLICAN: "i"
    };
  } else if (leftParty == "REPUBLICAN"){
    var keysByParty = {
      REPUBLICAN: "e",
      DEMOCRAT: "i"
    };
  };

  // Generate filename
  var pidStr = "00" + pid; pidStr = pidStr.substr(pidStr.length - 3);// lead 0s
  var flPrefix = "../Resources/pitData/pit_";
  var filename = flPrefix + pidStr + "_" + seed + ".csv";

  // Fields for saving data
  var fields = [
    "pid",
    "seed",
    "trial",
    "base_file",
    "party",
    "stimulus",
    "key_press",
    "rt",
    "correct"
  ];
// TODO: Randomize manually, and add a trial number counter.
  // Get stimulus filenames
  var demFls = <?php echo json_encode(glob("../Resources/Dems/*.png")); ?>;
  var repFls = <?php echo json_encode(glob("../Resources/Reps/*.png")); ?>;
  var demContactSheet = "../Resources/DemocratContactSheet.png";
  var repContactSheet = "../Resources/RepublicanContactSheet.png";

  // Functions

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

  var makeCatTrial = function (fl, party) {
    var baseFile = fl.split("/")[fl.split("/").length - 1];
    baseFile = baseFile.split(".")[0]; // we only want the prefix, not the ext
    var person = fileIndex[baseFile];
    obj = {
      stimulus: fl,
      text_answer: party,
      correct_text: "<p class='prompt' style='text-align:center'>\
      Correct! " + person + " is a %ANS%.</p>",
      incorrect_text: "<p class='prompt' style='text-align:center'>\
      Wrong! " + person + " is a %ANS%.</p>",
      key_answer: jsPsych.pluginAPI.convertKeyCharacterToKeyCode(keysByParty[party]),
      data: {
        base_file: baseFile,
        party: party,
        person: person
      }
    };
    return(obj);
  };

  // Make instruction trials.
  var instrTrials = {
    type: 'html',
    timeline: [],
    cont_key: 32,
    timing_post_trial: 0
  };
  instrTrials.timeline.push({url: './instructions1.html'})
  instrTrials.timeline.push({url: './demContactSheet.html'})
  instrTrials.timeline.push({url: './repContactSheet.html'})
  instrTrials.timeline.push({url: './instructions2.html'})

  // Initialize the file to save in.
  sendHeader();

  // Build the categorization trials.
  catTrials = {
    type: 'categorize',
    choices: ["e", "i"],
    timing_feedback_duration: 1500,
    timeline: [],
    randomize_order: false,
    on_finish: endTrial,
    timing_post_trial: 250,
    prompt: '<p class="prompt" style="text-align:center">\
    Press "e" for ' +
    leftParty +
    ' or "i" for ' + rightParty + '.</p>'
  };

  for (i=0; i < demFls.length; i++) {
    for (t=0; t < numTrialsPerStimulus; t++) {
      catTrials.timeline.push(
        makeCatTrial(demFls[i], 'DEMOCRAT')
      );
    };
  };
  for (i=0; i < repFls.length; i++) {
    for (t=0; t < numTrialsPerStimulus; t++) {
      catTrials.timeline.push(
        makeCatTrial(repFls[i], 'REPUBLICAN')
      );
    };
  };

  // Shuffle the timeline for the categorization trials.
  catTrials.timeline = jsPsych.randomization.shuffleNoRepeats(
    catTrials.timeline,
    function (t1, t2) {return(t1.data.person == t2.data.person);}
  );

  // Add trialnumbers.
  for (tNum=1; tNum <= catTrials.timeline.length; tNum++) {
    catTrials.timeline[tNum-1].data.trial = tNum;
  };

  // Push all trials onto the timeline
  taskTimeline.push(instrTrials);
  taskTimeline.push(catTrials);

  // Add permanent data to all trials.
  jsPsych.data.addProperties({
    pid: pid,
    seed: seed
  });

  jsPsych.init({
    timeline: taskTimeline,
    on_finish: function() {
      window.location = "../ctrl.php";
  		}
  });

</script>
</html>
