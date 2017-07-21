<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/seedrandom/2.4.2/seedrandom.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
  <script src="http://psych.colorado.edu/~jclab/CMJSUtils/Util.js"></script>
  <script src='../Resources/ModS3JSUtil.js'></script>
  <title>IAT</title>
  <!--   <script> src = 'PATH/FILENAME.html' </script>-->
  <script type="text/javascript">
  // TODO: test dscore calculation. optional
  // TODO: Track the filename through the task.

  var ctx, can, startTime, pid, start, pic, state, response, responseNum, rt;
  var txt, timer, correct, scale, rt1;
  var dataSaved = false;
  var dataArr = [];
  var wordCol = '#1016b7';
  var wordY = -170;
  var categoryCol = '#098716';
  var categoryY = -200;

  bgColor = '#e6e6e6'
  //Can use kepress to disable/enable key presses:
  var keypress = 'disabled';

  // for now, randomly pick a pid. This will eventually come from the form.
  var N = []
  for (i= 0; i<299; i++) {
    N.push(i);
  }
  //pid = rndSelect(N, 1)[0] + 1;
  pid = <?php echo json_encode($_SESSION["pid"]); ?>

  // task and state are used to navigate between different tasks and screens
  var task = 'iat';
  var state = 'start experiment';
  // set random seed based on system time
  var tm = new Date().getTime();
  Math.seedrandom(tm);

  // Data variables
  var flPrefix = <?php
    if ($_SESSION['domain']=='politics') {
      echo '"../Resources/iatPolData/polIAT_";';
    } else if ($_SESSION['domain']=='race') {
      echo '"../Resources/iatRaceData/raceIAT_";';
    };
    ?>
  var pidStr = "00" + pid; pidStr = pidStr.substr(pidStr.length - 3);// lead 0s
  var filename = flPrefix + pidStr + '_' + String(tm) + '.csv';
  var fields = [
    'pid', 'practiceOrder', 'blockOrder', 'leftCategoryC', 'leftCategoryI',
    'rightCategoryC', 'rightCategoryI', 'leftAttC', 'leftAttI', 'rightAttC',
    'rightAttI', 'trialECategory', 'trialICategory', 'switchStimulus',
    'stimulus', 'stimulusCategory', 'startTimeSeed', 'block', 'blockType',
    'trial', 'responseNum', 'rt', 'correct', 'response'
  ];

  // stimuli
  // Set1 should be the category congruent with GOOD and set2 congruent with BAD
  <?php
  if ($_SESSION['domain'] == 'politics') {
    $set1Fls = glob('../Resources/Dems/*.png');
    $set2Fls = glob('../Resources/Reps/*.png');
    $catExampleFl = './IATPolExamples.png';
    $set1Cond = 'DEMOCRAT';
    $set2Cond = 'REPUBLICAN';
  }
  else if ($_SESSION['domain'] == 'race') {
    $set1Fls = glob('../Resources/White/*.png');
    $set2Fls = glob('../Resources/Black/*.png');
    $catExampleFl = './RaceExampleImage.png';
    $set1Cond = 'WHITE';
    $set2Cond = 'BLACK';
  }

  // echo everything out.
  echo "// domain: ".$_SESSION['domain']."\n";
  echo "var catExampleFl = '".$catExampleFl."';";
  echo "var set1FlNames = ".json_encode($set1Fls).";";
  echo "var set2FlNames = ".json_encode($set2Fls).";";
  echo "var set1Cond = '".$set1Cond."';";
  echo "var set2Cond = '".$set2Cond."';";
  ?>

  var allPicsFlNames = shuffle(set1FlNames.concat(set2FlNames));

  // These arrays will hold arrays of length 3. The first element will mark BLACK
  // for Black faces and WHITE for white faces. The second is the file name, and
  // the third is the prefix of the filename only.
  var set1Arr = [];
  for (i = 0; i < set1FlNames.length; i++) {
    set1Arr = set1Arr.concat([[
      set1Cond,
      set1FlNames[i],
      set1FlNames[i].slice(11, -4)
    ]]);
  };

  var set2Arr = [];
  for (i=0; i < set2FlNames.length; i++) {
    set2Arr = set2Arr.concat([[
      set2Cond,
      set2FlNames[i],
      set2FlNames[i].slice(11, -4)
    ]]);
  };
  allPrimeArr = set1Arr.concat(set2Arr);

  var otherPics = <?php echo json_encode(glob('./*.png')); ?>

  var pics = allPicsFlNames.concat(otherPics)

  //var practiceStims = shuffle(rndSelect(set1FlNames, 2).concat(rndSelect(set2FlNames, 2)));
  //var practice = [];
  var bad = ['HORRIBLE','ANGRY','TERRIBLE','TRAGIC','HATE',
  'DESTROY','BRUTAL','DISASTER','UGLY','EVIL'];
  var good = ['PLEASANT','ADORE','HELPFUL','JOY','HAPPY',
  'CHEERFUL','SUCCESS', 'GLORIOUS','ENJOY','SMILE'];

  // These arrays are the same as above. G for good B for bad.
  var goodWordArr = [];
  for (i=0; i < good.length; i++) {
    goodWordArr = goodWordArr.concat([['GOOD', good[i]]]);
  }
  var badWordArr = [];
  for (i=0; i < good.length; i++) {
    badWordArr = badWordArr.concat([['BAD', bad[i]]]);
  }
  allWordArr = goodWordArr.concat(badWordArr);

  var words = bad.concat(good);
  var pictures = new Array();
  var oldResponse = 'none';
  var trialNum = 0;
  var IATisi = 0;
  var correct = undefined;
  var blockNum = 1;
  var loopNum = -1;
  var respList = new Array();
  var pic = new Image();

  // Set up instruction texts. It depends on the kind of IAT we are doing.

  <?php
  if ($_SESSION["domain"]=="race") {
    $instr1FlNm = './Instructions/raceInstr1.txt';
    $practiceInstrFlNm = './Instructions/racePracticeInstr.txt';
    $practiceInstrBFlNm = './Instructions/racePracticeInstrB.txt';
    $criticalInstrFlNm = './Instructions/raceInstrCritical1.txt';
  } else if ($_SESSION["domain"]=="politics") {
    $instr1FlNm = './Instructions/polInstr1.txt';
    $practiceInstrFlNm = './Instructions/polPracticeInstr.txt';
    $practiceInstrBFlNm = './Instructions/polPracticeInstrB.txt';
    $criticalInstrFlNm = './Instructions/polInstrCritical1.txt';
  };
  ?>

  instr1Txt = <?php
  $txtFl = fopen($instr1FlNm, 'r');
  echo json_encode(fread($txtFl, filesize($instr1FlNm)));
  ?>

  catInstrTxt = <?php
  $txtFl = fopen($practiceInstrFlNm, 'r');
  echo json_encode(fread($txtFl, filesize($practiceInstrFlNm)))
  ?>

  catInstrBTxt = <?php
  $txtFl = fopen($practiceInstrBFlNm, 'r');
  echo json_encode(fread($txtFl, filesize($practiceInstrBFlNm)));
  ?>

  criticalInstr1Txt = <?php
  $txtFl = fopen($criticalInstrFlNm, 'r');
  echo json_encode(fread($txtFl, filesize($criticalInstrFlNm)));
  ?>


  // The 'block' object holds all block relevant info, including stimuli
  // and trial counts. For now, this is designed to accomodate a 5 block
  // task (2 practice blocks, a test block, a practice block, then a
  // test block).

  var block = {
    numTrials: [20, 20, 40, 20, 40],
    blockType: [],
    stimuli: [],
    keys: [],
    labels: []
  }

  var opposites = {
    BLACK: 'WHITE',
    WHITE: 'BLACK',
    DEMOCRAT: 'REPUBLICAN',
    REPUBLICAN: 'DEMOCRAT',
    GOOD: 'BAD',
    BAD: 'GOOD',
    attPractice1: 'categoryPractice1',
    categoryPractice1: 'attPractice1',
    attributes: 'category',
    category: 'attributes',
    congruent: 'incongruent',
    incongruent: 'congruent'
  };
  // This object will contain condition information for all relevant possible
  // conditions.
  var condition = {
    leftCategoryC: <?php
      if ($_SESSION["domain"] == "politics") {
        echo json_encode($_SESSION["leftPol"]);
      } else if ($_SESSION["domain"] == "race") {
        echo json_encode($_SESSION["leftRace"]);
      }
    ?>, // TODO: Here is the IAT left key choice.
    blockOrder: rndSelect(['conFirst','inconFirst'], 1)[0],
    practiceOrder: 'attFirst',//rndSelect(['attFirst','categoryFirst'], 1)[0],
    stimSwitch: 'attributes'//rndSelect(['attributes', 'category'], 1)[0]
  };
  condition.leftAttC = condition.leftCategoryC == set1Cond ? 'GOOD' : 'BAD';
  // Note that this assignment currently makes "congruent" mean that WHITE and
  // DEMOCRAT are GOOD, and BLACK and REPUBLICAN are BAD.
  condition.rightCategoryC = opposites[condition.leftCategoryC];
  condition.rightAttC = opposites[condition.leftAttC];

  //console.log(condition);

  switch (condition.stimSwitch) {
    case 'attributes':
      condition.leftAttI = opposites[condition.leftAttC];
      condition.rightAttI = opposites[condition.rightAttC];
      condition.leftCategoryI = condition.leftCategoryC;
      condition.rightCategoryI = condition.rightCategoryC;
      break;

    case 'category':
      condition.leftAttI = condition.leftAttC;
      condition.rightAttI = condition.rightAttC;
      condition.leftCategoryI = opposites[condition.leftCategoryC];
      condition.rightCategoryI = opposites[condition.rightCategoryC];
      break;

    default:
      throw "Not a real stimulus switch condition.";
  }

  // Figure out the blockTypes based on condition information.
  // Also add the key information for each block.
  var blockOrder = condition.blockOrder;
  if (condition.practiceOrder == 'attFirst') {
    block.blockType[0] = 'attPractice1';
    if (blockOrder=='conFirst') {
      block.labels[0] = {
        leftCategory: '',
        rightCategory: '',
        leftAtt: condition.leftAttC,
        rightAtt: condition.rightAttC
      };
      block.labels[1] = {
        leftCategory: condition.leftCategoryC,
        rightCategory: condition.rightCategoryC,
        leftAtt: '',
        rightAtt: ''
      };
    } else if (blockOrder = 'inconFirst') {
      block.labels[0] = {
        leftCategory: '',
        rightCategory: '',
        leftAtt: condition.leftAttI,
        rightAtt: condition.rightAttI
      };
      block.labels[1] = {
        leftCategory: condition.leftCategoryI,
        rightCategory: condition.rightCategoryI,
        leftAtt: '',
        rightAtt: ''
      };
    }
  } else {
    block.blockType[0] = 'categoryPractice1';
    if (blockOrder=='conFirst') {
      block.labels[0] = {
        leftCategory: condition.leftCategoryC,
        rightCategory: condition.rightCategoryC,
        leftAtt: '',
        rightAtt: ''
      };
      block.labels[1] = {
        leftCategory: '',
        rightCategory: '',
        leftAtt: condition.leftAttC,
        rightAtt: condition.rightAttC
      };
    } else if (blockOrder = 'inconFirst') {
      block.labels[0] = {
        leftCategory: condition.leftCategoryI,
        rightCategory: condition.rightCategoryI,
        leftAtt: '',
        rightAtt: ''
      };
      block.labels[1] = {
        leftCategory: '',
        rightCategory: '',
        leftAtt: condition.leftAttI,
        rightAtt: condition.rightAttI
      };
    }
  }
  block.blockType[1] = opposites[block.blockType[0]];

  if (blockOrder == 'conFirst') {
    block.blockType[2] = 'congruent';
    block.labels[2] = {
      leftCategory: condition.leftCategoryC,
      rightCategory: condition.rightCategoryC,
      leftAtt: condition.leftAttC,
      rightAtt: condition.rightAttC
    };
    block.labels[4] = {
      leftCategory: condition.leftCategoryI,
      rightCategory: condition.rightCategoryI,
      leftAtt: condition.leftAttI,
      rightAtt: condition.rightAttI
    };
  } else {
    block.blockType[2] = 'incongruent';
    block.labels[2] = {
      leftCategory: condition.leftCategoryI,
      rightCategory: condition.rightCategoryI,
      leftAtt: condition.leftAttI,
      rightAtt: condition.rightAttI
    };
    block.labels[4] = {
      leftCategory: condition.leftCategoryC,
      rightCategory: condition.rightCategoryC,
      leftAtt: condition.leftAttC,
      rightAtt: condition.rightAttC
    };
  }

  if (condition.stimSwitch == 'attributes') {
    block.blockType[3] = 'switchAttPractice';
    if (blockOrder == 'conFirst') {
      block.labels[3] = {
        leftCategory: '',
        rightCategory: '',
        leftAtt: condition.leftAttI,
        rightAtt: condition.rightAttI
      };
    } else if (blockOrder == 'inconFirst') {
      block.labels[3] = {
        leftCategory: '',
        rightCategory: '',
        leftAtt: condition.leftAttC,
        rightAtt: condition.rightAttC
      };
    }
  } else {
    block.blockType[3] = 'switchCategoryPractice';
    if (blockOrder == 'conFirst') {
      block.labels[3] = {
        leftCategory: condition.leftCategoryI,
        rightCategory: condition.rightCategoryI,
        leftAtt: '',
        rightAtt: ''
      };
    } else if (blockOrder == 'inconFirst') {
      block.labels[3] = {
        leftCategory: condition.leftCategoryC,
        rightCategory: condition.rightCategoryC,
        leftAtt: '',
        rightAtt: ''
      };
    }
  }

  block.blockType[4] = opposites[block.blockType[2]];

  // Figures out response keypresses. How to use:
  // If in a practice block, then just access via block.keys[blockNum]['e'/'i']
  // If in a test block, then access via block.keys[blockNum]['face'/'word']['i']
  if (condition.blockOrder == 'conFirst') {
    // Practice blocks at the beginning.
    if (block.blockType[0] == 'attPractice1') {
      block.keys[0] = {
        e: condition.leftAttC,
        i: condition.rightAttC
      }
      block.keys[1] = {
        e: condition.leftCategoryC,
        i: condition.rightCategoryC
      }
    } else if (block.blockType[0] == 'categoryPractice1') {
      block.keys[0] = {
        e: condition.leftCategoryC,
        i: condition.rightCategoryC
      }
      block.keys[1] = {
        e: condition.leftAttC,
        i: condition.rightAttC
      }
    }
    // Congruent test block
    block.keys[2] = {
      face: {
        e: condition.leftCategoryC,
        i: condition.rightCategoryC
      },
      word: {
        e: condition.leftAttC,
        i: condition.rightAttC
      }
    }
    // Incongruent practice
    if (condition.stimSwitch == 'attributes') {
      block.keys[3] = {
        e: condition.leftAttI,
        i: condition.rightAttI
      }
    } else if (condition.stimSwitch == 'category') {
      block.keys[3] = {
        e: condition.leftCategoryI,
        i: condition.rightCategoryI
      }
    }

    // Incongruent test block
    block.keys[4] = {
      face: {
        e: condition.leftCategoryI,
        i: condition.rightCategoryI
      },
      word: {
        e: condition.leftAttI,
        i: condition.rightAttI
      }
    }
  } else if (condition.blockOrder == 'inconFirst') {
    // Practice blocks at the beginning.
    if (block.blockType[0] == 'attPractice1') {
      block.keys[0] = {
        e: condition.leftAttI,
        i: condition.rightAttI
      }
      block.keys[1] = {
        e: condition.leftCategoryI,
        i: condition.rightCategoryI
      }
    } else if (block.blockType[0] == 'categoryPractice1') {
      block.keys[0] = {
        e: condition.leftCategoryI,
        i: condition.rightCategoryI
      }
      block.keys[1] = {
        e: condition.leftAttI,
        i: condition.rightAttI
      }
    }
    // Incongruent test block
    block.keys[2] = {
      face: {
        e: condition.leftCategoryI,
        i: condition.rightCategoryI
      },
      word: {
        e: condition.leftAttI,
        i: condition.rightAttI
      }
    }
    // Congruent practice
    if (condition.stimSwitch == 'attributes') {
      block.keys[3] = {
        e: condition.leftAttC,
        i: condition.rightAttC
      }
    } else if (condition.stimSwitch == 'category') {
      block.keys[3] = {
        e: condition.leftCategoryC,
        i: condition.rightCategoryC
      }
    }

    // Congruent test block
    block.keys[4] = {
      face: {
        e: condition.leftCategoryC,
        i: condition.rightCategoryC
      },
      word: {
        e: condition.leftAttC,
        i: condition.rightAttC
      }
    }
  }

  // These arrays will hold stimuli for each block. Note that these might
  // need to change if the number of trials for each block are not evenly
  // divisible by the number of stimuli available.
  for (b=0; b < block.blockType.length; b++) {
    var curBT = block.blockType[b];
    switch (true) {
      case (curBT == 'attPractice1' || curBT == 'switchAttPractice'):
        block.stimuli[b] = rndSelect(
          allWordArr,
          block.numTrials[b]
        );
        break;

      case (curBT == 'categoryPractice1' || curBT == 'switchCategoryPractice'):
      block.stimuli[b] = rndSelect(
        allPrimeArr,
        block.numTrials[b]
      );
        break;

      case (curBT == 'congruent' || curBT == 'incongruent'):
        var tempFaces = rndSelect(
          allPrimeArr,
          block.numTrials[b] / 2
        );
        var tempWords = rndSelect(
          allWordArr,
          block.numTrials[b] / 2
        );
        var tempStims = [];
        for (i=0; i < block.numTrials[b]/2; i++) {
          tempStims.push(tempFaces[i]);
          tempStims.push(tempWords[i]);
        }
        block.stimuli[b] = tempStims;
        break;

      default:
        throw 'Not a valid block type!';
    }
  }

  function init() {
    loadImgs();
    can = document.getElementById("can");
    ctx = can.getContext("2d");
    //example = document.getElementById('behEx.png');
    ctx.translate(can.width/2,can.height/2);

    // Fill it with the background color
    erase();
    document.onkeydown = keyHandler;
    window.onbeforeunload = onBrowserClose;
    ctx.font="16px Verdana";
    ctx.textAlign='left';
    ctx.textBaseline='middle';
    btnImage = document.getElementById('./blank.png');
    //IAT stuff//
    redX = document.getElementById('./redx.png');
    categoryExamples = document.getElementById(catExampleFl);
    attExamples = document.getElementById('./WordsExample.png');
    iatStim = document.getElementById('./iatStims.png');
    for(i=0; i<pics.length; i++) {
      pictures[i] = document.getElementById(pics[i]);
    }

    startTime = new Date().getTime();

    // Write the header to the data file.
    writeHeader();
    //This is supposed to help with image loading issues (I don't think it is)
    var readyStateCheckInterval = setInterval(function() {
      if (document.readyState !== "loading") {
        clearInterval(readyStateCheckInterval);
        trialEvent();
      }
    }, 10);
  }

  function loadImgs () {
    // Create all the image elements for the pictures
    for (i = 0; i < pics.length; i++) {
      var curIm = document.createElement("IMG");
      curIm.id = pics[i];
      curIm.src = pics[i];
      curIm.style.display = "none";
      document.body.appendChild(curIm);
    };
  }


  function enableKeys(){
    keypress = 'enabled';
  }

  function onBrowserClose(e) {
    $.ajax({
      type: 'POST',
      cache: false,
      url: '../Resources/destroy.php',
      error: onSaveError,
      success: onSaveSuccess,
      data: {
        filename: filename,
        filedata: dataToSend
      }
    });
  }

  function keyHandler(kEvent) {
    if (keypress == 'enabled') {
      if (state == 'done') {
        return(0);
      }
      rt = new Date().getTime() - startTime;
      if (kEvent == 69 || kEvent == 32 || kEvent == 73) {
        var unicode = kEvent;
      }
      else {var unicode=kEvent.keyCode;}

      if (state.substr(0,2)=='in' && unicode ==32){
        // When we are in an instructions block and the spacebar has been pressed
        keypress = 'disabled';
        trialEvent();
      }
      else if (state.substr(0,2) != 'in'){
        if (unicode == 69 | unicode == 73) {
          // if e or i has been pressed (no other keys get responded to).
          // 69: e (left)
          // 73: i (right)
          responseNum++;
          if (unicode == 69) {
            var k = 'e';
          } else if (unicode == 73) {
            var k = 'i';
          }

          var curType = block.blockType[blockNum - 1]
          if (curType == 'congruent' || curType == 'incongruent') {
            var curT = block.stimuli[blockNum-1][trialNum-1][0];
            if (curT == condition.leftCategoryC || curT == condition.rightCategoryC) {
              response = block.keys[blockNum-1]['face'][k];
            } else if (curT == condition.leftAttC || curT == condition.rightAttC) {
              response = block.keys[blockNum-1]['word'][k];
            }
          } else if (curType.match("attPractice1|categoryPractice1|switchAttPractice|switchCategoryPractice")) {
            response = block.keys[blockNum-1][k];
          }

            window.clearTimeout(timer);
            if (response == 'none') { throw "No real response!"; }
            trialEvent();
          }
        }
      }
    }

  function eraseRect(x, y, w, h, col) {
    col = typeof col == 'undefined' ? bgColor : col;
    ctx.fillStyle = col;
    ctx.fillRect(x, y, w, h);
  }

  function erase() {
    ctx.fillStyle = bgColor;
    ctx.fillRect(-can.width/2, -can.height/2, can.width, can.height);
  }

function dataToCsvString () {
  var csvStr = '';
  var curLine = '';
  var t, f;

  // Make the header row
  for (f in fields) {
    csvStr += fields[f];
    if (fields[f] != fields[fields.length-1]) {
      csvStr += ',';
    }
  }
  csvStr += '\n';

  // loop through each trial and create a CSV line out of it
  // Append each line with a newline character in between
  for (t in dataArr) {
    curLine = '';
    for (f in fields) {
      curLine += String(dataArr[t][fields[f]]);
      if (fields[f] != fields[fields.length-1]) {
        curLine += ',';
      }
    }
    csvStr += curLine + '\n';
  };
  return(csvStr);
};

function storeData() {
  var tECat, tICat, sCat, stimLiteral;

  sCat = block.stimuli[blockNum - 1][trialNum - 1][0];
  if (block.keys[blockNum - 1].hasOwnProperty('face')) {
    // congruent or incongruent blocks
    if (sCat == 'BLACK' || sCat == 'WHITE' ) {
        // image trials
          tECat = block.keys[blockNum - 1]['face']['e'];
          tICat = block.keys[blockNum - 1]['face']['i'];
          stimLiteral = block.stimuli[blockNum - 1][trialNum - 1][1].slice(11, 18);
        } else {
        // word trials
          tECat = block.keys[blockNum - 1]['word']['e'];
          tICat = block.keys[blockNum - 1]['word']['i'];
          stimLiteral = block.stimuli[blockNum - 1][trialNum - 1][1];
        }

  } else {
    // practice blocks
    curBT = block.blockType[blockNum - 1];
    if (curBT.match('attPractice1|switchAttPractice')) {
      stimLiteral = block.stimuli[blockNum - 1][trialNum - 1][1];
    } else {
      stimLiteral = block.stimuli[blockNum - 1][trialNum - 1][1].slice(11, 18);
    }
    tECat = block.keys[blockNum - 1]['e'];
    tICat = block.keys[blockNum - 1]['i'];
  }

  trialObj = {
    pid: pid,
    practiceOrder: condition.practiceOrder,
    blockOrder: condition.blockOrder,
    leftCategoryC: condition.leftCategoryC,
    leftCategoryI: condition.leftCategoryI,
    rightCategoryC: condition.rightCategoryC,
    rightCategoryI: condition.rightCategoryI,
    leftAttC: condition.leftAttC,
    leftAttI: condition.leftAttI,
    rightAttC: condition.rightAttC,
    rightAttI: condition.rightAttI,
    trialECategory: tECat,
    trialICategory: tICat,
    switchStimulus: condition.stimSwitch,
    stimulus: stimLiteral,
    stimulusCategory: sCat,
    startTimeSeed: tm,
    block: blockNum,
    blockType: block.blockType[blockNum-1],
    trial: trialNum,
    responseNum: responseNum,
    rt: rt,
    correct: correct,
    response: response
  };
  dataArr.push(trialObj);
  // save each trial individually
  sendData(generateTrialStr(trialObj));
  //debugger;
}

function errorRate () {
  var countCorrect = function (total, trial) {
    var acc = 0;
    if (typeof(total) == 'object') {
      total = total['correct'];
    }
    if (trial['correct']) {
      acc = 1;
    }
    return total + acc;
  };
  var numCorrect = dataArr.reduce(countCorrect);
  var accRate = numCorrect / dataArr.length;
  return 1 - accRate;
}

function dScore () {
  var conTrials = dataArr.filter(function (t) {
    return(
      t['blockType'] == 'congruent' && t['correct']
    );
  });
  var inconTrials = dataArr.filter(function (t) {
    return(
      t['blockType'] == 'incongruent' && t['correct']
    );
  });

  if (conTrials.length == 0 || inconTrials.length == 0) {
    // This is for testing
    return((Math.random() * 2 - 1));
  }

  var cRTs = conTrials.map(
    function (t) {return(t['rt']);}
  );
  var iRTs = inconTrials.map(
    function (t) {return(t['rt']);}
  );

  var cAvg = mean(cRTs); var iAvg = mean(iRTs);
  var pooledVar = ((cRTs.length - 1) * variance(cRTs) + (iRTs.length - 1) * variance(iRTs))/(cRTs.length + iRTs.length - 2);
  var pooledSd = Math.sqrt(pooledVar);

  var d = (iAvg - cAvg)/pooledSd;

  return(d);
};

function saveAllData () {
  // This is now deprecated, and only retained for historical purposes.

  //var curLine = '';
  var t, f;

  // Make the header row
  var csvStr = generateHeaderStr('');

  // loop through each trial and create a CSV line out of it
  // Append each line with a newline character in between
  for (t=0; t < dataArr.length; t++) {
    csvStr += generateTrialStr(t);
  };

  sendData(csvStr);
};

function sendData(dataToSend) {
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

function onSaveSuccess(data, textStatus, jqXHR) {
  saveSuccessCode = 0;
  numSaveAttempts++;
};

function onSaveError(data, textStatus, jqXHR) {
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

// Functions for saving the data one trial at a time.
function generateHeaderStr (csvStr) {
  // Make the header row
  var f;
  for (f=0; f < fields.length; f++) {
    csvStr += fields[f];
    if (fields[f] != fields[fields.length-1]) {
      csvStr += ',';
    }
  }
  csvStr += '\n';

  return(csvStr);
}

function generateTrialStr (trial) {
  var f;
  var curLine = '';
  for (f=0; f < fields.length; f++) {
    curLine += String(trial[fields[f]]);
    if (fields[f] != fields[fields.length-1]) {
      curLine += ',';
    }
  }

  curLine += '\n';
  return(curLine);
}

function writeHeader () {
  var header = generateHeaderStr('');
  sendData(header);
}

// These are the functions that actually draw trials.
function drawLabels (bLabs) {
  // Draw response labels
  ctx.font='28px Veranda';
  ctx.fillStyle=categoryCol;
  ctx.fillText(bLabs.leftCategory, -250, categoryY);
  ctx.fillText(bLabs.rightCategory, 250, categoryY);
  ctx.fillStyle=wordCol;
  ctx.fillText(bLabs.leftAtt,-250, wordY) ;
  ctx.fillText(bLabs.rightAtt,250, wordY) ;
}

/*
function attPractice (stimulus) {
  // practice for good and bad words

  ctx.font = '36px Veranda';
  ctx.fillStyle = 'black';
  ctx.fillText(stimulus[1], 0,0);
  ctx.font='28px Veranda';
}

function categoryPractice (stimulus) {
  // category practice block
  pic.src = stimulus[1];
  ctx.drawImage(pic,0-(pic.width/2),0-(pic.height/2));
}
*/

function drawStimulus (stimulus) {
  // Critical block

  // draw the stimulus
  if (stimulus[0] == 'BAD' || stimulus[0] == 'GOOD'){
    ctx.font = '36px Veranda';
    ctx.fillStyle = 'black';
    ctx.fillText(stimulus[1], 0,0);
    ctx.font='28px Veranda';
  }
  else{
    pic.src = stimulus[1];
    ctx.drawImage(pic,0-(pic.width/2),0-(pic.height/2));
  }
}

function showText(startHeight, instrTxt, leftPt) {
  var maxCharacters = 65;
  var lineWidth = 20;
  if (leftPt === undefined) {
    leftPt = -290;
  }

  var instrArr = instrTxt.split(' ');
  instrArr.reverse();
  var linesArr = [];
  var tempLine = '';
  var curWord = '';
  var newLineWord = '*n';
  while (instrArr.length > 0) {
    curWord = instrArr.pop();
    if (curWord == newLineWord) {
      linesArr.push('');
      continue;
    }
    tempLine = curWord;
    if (instrArr.length > 0) {
      while (tempLine.length + instrArr[instrArr.length-1].length <= maxCharacters) {
        curWord = instrArr.pop();
        if (curWord == newLineWord) {
          break;
        }
        tempLine = tempLine + ' ' + curWord;
        if (instrArr.length <= 0) { break; }
      }
    }
    linesArr.push(tempLine);
  }

  ctx.textAlign='left';
  ctx.font='16px Verdana';
  ctx.fillStyle='black';
  for (i=0; i < linesArr.length; i++) {
    ctx.fillText(linesArr[i], leftPt, startHeight + i * lineWidth)
  }
}

function trialEvent() {

  //console.log('Trial event, state: ' + state);

  if (state == 'start experiment'){
    if (condition.practiceOrder == 'categoryFirst') {
      state = 'instructionsCategoryPractice';
    } else {
      state = 'instructionsAttPractice';
    }
    eraseRect(-400,-250,800,500);
    ctx.textAlign = 'left';
    ctx.font = '16px Verdana';
    ctx.fillText('SORTING TASK', -300, -200)

    showText(-120, instr1Txt);
    ctx.textAlign='center';
    ctx.fillText('Press the SPACEBAR to continue.', 0, 160);
    window.setTimeout(enableKeys, 1000);
  }
  else if (state == 'instructionsCategoryPractice'){
    state = 'instructions3Category';
    eraseRect(-400,-250,800,500);
    ctx.drawImage(categoryExamples, 0 - (categoryExamples.width/2),25- (categoryExamples.height/2));
    ctx.textAlign='left';
    ctx.fillText('SORTING TASK', -300,-200);

    showText(-230, catInstrTxt);
    ctx.textAlign='center';
    ctx.fillText('Press the SPACEBAR to continue.', 0, 220);
    window.setTimeout(enableKeys, 1000);
  }
  else if (state == 'instructionsAttPractice'){
    state = 'instructions3Att';
    eraseRect(-400,-250,800,500);

    txt = <?php
    $txtFl = fopen('./Instructions/attPracticeInstr.txt', 'r');
    echo json_encode(fread($txtFl, filesize('./Instructions/attPracticeInstr.txt')));
    fclose($txtFl);
    ?>

    showText(-120, txt);
    // Draw the good and bad words
    var fac = .8;

    ctx.textAlign='center';
    ctx.fillText('Press the SPACEBAR to continue.', 0, 220);
    window.setTimeout(enableKeys, 1000);
  }
  else if (state == 'instructions3Category'){
    state = 'instructions';
    if (condition.blockOrder == 'conFirst') {
      var leftLab = condition.leftCategoryC;
      var rightLab = condition.rightCategoryC;
    } else {
      var leftLab = condition.leftCategoryI;
      var rightLab = condition.rightCategoryI;
    }
    erase();
    ctx.font='28px Veranda';
    ctx.fillStyle=categoryCol;
    ctx.fillText(leftLab, -250, categoryY);
    ctx.fillText(rightLab, 250, categoryY);
    ctx.textAlign='left';
    ctx.font='16px Verdana';
    ctx.fillStyle='black';

    showText(-80, catInstrBTxt);

    ctx.textAlign='center';
    ctx.fillText('Press the SPACEBAR to continue.', 0, 160);
    window.setTimeout(enableKeys, 1000);
  }
  else if (state == 'instructions3Att'){
    state = 'instructions';
    if (condition.blockOrder == 'conFirst') {
      var leftLab = condition.leftAttC;
      var rightLab = condition.rightAttC;
    } else {
      var leftLab = condition.leftAttI;
      var rightLab = condition.rightAttI;
    }
    erase();
    ctx.font='28px Veranda';
    ctx.fillStyle=wordCol;
    ctx.fillText(leftLab, -250, wordY);
    ctx.fillText(rightLab, 250,wordY);
    ctx.textAlign='left';
    ctx.font='16px Verdana';
    ctx.fillStyle='black';

    txt = <?php
    $txtFl = fopen('./Instructions/attPracticeInstrB.txt', 'r');
    echo json_encode(fread($txtFl, filesize('./Instructions/attPracticeInstrB.txt')));
    fclose($txtFl);
    ?>

    showText(-80, txt);
    ctx.textAlign='center';
    ctx.fillText('Press the SPACEBAR to continue.', 0, 160);
    window.setTimeout(enableKeys, 1000);
  }
  else if (state == 'instrSwitchAtt') {
    state = 'instructions';
    if (condition.blockOrder == 'inconFirst') {
      var leftLab = condition.leftAttC;
      var rightLab = condition.rightAttC;
    } else {
      var leftLab = condition.leftAttI;
      var rightLab = condition.rightAttI;
    }
    erase();
    ctx.font='28px Veranda';
    ctx.fillStyle=wordCol;
    ctx.fillText(leftLab, -250, wordY);
    ctx.fillText(rightLab, 250, wordY);
    ctx.textAlign='left';
    ctx.font='16px Verdana';
    ctx.fillStyle='black';

    txt = <?php
    $txtFl = fopen('./Instructions/instrSwitchAtt.txt', 'r');
    echo json_encode(fread($txtFl, filesize('./Instructions/instrSwitchAtt.txt')));
    fclose($txtFl);
    ?>

    showText(-80, txt);
    ctx.textAlign='center';
    ctx.fillText('Press the SPACEBAR to continue.', 0, 160);
    window.setTimeout(enableKeys, 1000);
  }
  else if (state == 'instrSwitchCategory') {
    state = 'instructions';
    if (condition.blockOrder == 'inconFirst') {
      var leftLab = condition.leftCategoryC;
      var rightLab = condition.rightCategoryC;
    } else {
      var leftLab = condition.leftCategoryI;
      var rightLab = condition.rightCategoryI;
    }
    erase();
    ctx.font='28px Veranda';
    ctx.fillStyle=categoryCol;
    ctx.fillText(leftLab, -250, categoryY);
    ctx.fillText(rightLab, 250, categoryY);
    ctx.textAlign='left';
    ctx.font='16px Verdana';
    ctx.fillStyle='black';

    txt = <?php
    $txtFl = fopen('./Instructions/instrSwitchCategory.txt', 'r');
    echo json_encode(fread($txtFl, filesize('./Instructions/instrSwitchCategory.txt')));
    fclose($txtFl);
    ?>

    showText(-80, txt);
    ctx.textAlign='center';
    ctx.fillText('Press the SPACEBAR to continue.', 0, 160);
    window.setTimeout(enableKeys, 1000);
  }
  else if (state == 'instrCritical') {
    state = 'instructions';
    if (block.blockType[blockNum - 1] == 'congruent') {
      var leftCategoryLab = condition.leftCategoryC;
      var rightCategoryLab = condition.rightCategoryC;
      var leftAttLab = condition.leftAttC;
      var rightAttLab = condition.rightAttC;
    } else {
      var leftCategoryLab = condition.leftCategoryI;
      var rightCategoryLab = condition.rightCategoryI;
      var leftAttLab = condition.leftAttI;
      var rightAttLab = condition.rightAttI;
    }
    erase();
    ctx.font='28px Veranda';
    ctx.fillStyle=categoryCol;
    ctx.fillText(leftCategoryLab, -250, categoryY);
    ctx.fillText(rightCategoryLab, 250, categoryY);
    ctx.fillStyle=wordCol;
    ctx.fillText(leftAttLab, -250, wordY);
    ctx.fillText(rightAttLab, 250, wordY);
    ctx.textAlign='left';
    ctx.font='16px Verdana';
    ctx.fillStyle='black';

    showText(-100, criticalInstr1Txt);
    ctx.textAlign='center';
    ctx.fillText('Press the SPACEBAR to continue.', 0, 160);
    window.setTimeout(enableKeys, 1000);
  }
  else if (state == 'newBlock'){
    trialNum = 0;
    loopNum = -1;
    blockNum ++;
    erase();

    curBT = block.blockType[blockNum - 1]
    switch (true) {
      case (curBT == 'attPractice1'):
        state = 'instructionsAttPractice';
        break;

      case (curBT == 'categoryPractice1'):
        state = 'instructionsCategoryPractice';
        break;

      case (curBT == 'switchAttPractice'):
        state = 'instrSwitchAtt';
        break;

      case (curBT == 'switchCategoryPractice'):
        state = 'instrSwitchCategory';
        break;

      case (curBT == 'congruent' || curBT == 'incongruent'):
        state = 'instrCritical';
        break;

      default:
        state = 'done';
    }
    trialEvent();
  }

  else if (state == 'nexttrial' || state == 'instructions') {
    state = 'isi';
    trialNum++;
    response = 'none';
    responseNum = 0;
    correct = undefined;
    window.setTimeout(trialEvent, IATisi);
  }

  else if (state == 'isi') {
    state = 'stimulus';
    startTime = new Date().getTime();
    rt1 = 'NA';

    if (trialNum == 1) {
      eraseRect(-400, -250, 800, 500);
      drawLabels(block.labels[blockNum - 1]);
    } else {
      eraseRect(-150, -250, 300, 500);
    }
    curBT = block.blockType[blockNum - 1];
    drawStimulus(block.stimuli[blockNum - 1][trialNum - 1]);

    keypress = 'enabled';
  }

  else if (state == 'stimulus') {
    if (response != 'none') {
      state = 'feedback';
      //rt = new Date().getTime() - startTime;
      trialEvent();
    } else {
      keypress = 'enabled';
    }
  }

  else if (state == 'feedback') {
    if (response == block.stimuli[blockNum-1][trialNum-1][0]) {
      correct = true;
      // Check to see if we are at a new block.
      if (block.stimuli[blockNum-1].length <= trialNum) {
        state = 'newBlock';
      } else {
        state = 'nexttrial';
      }
    } else {
      correct = false;
      state = 'wrong';
    }
    storeData();
    trialEvent();
  }
    //eraseRect(-400,-250,800,500);
    //window.setTimeout(trialEvent, 500);


  else if (state == 'wrong') {
    if (trialNum > block.stimuli[blockNum-1].length){
      correct = true;
      state = 'done';
    }
    state = 'stimulus'
    ctx.drawImage(redX, -133,-148);
    correct = false;
    rt1 = rt;
    keypress = 'enabled';
  }
  else if (state == 'done') {
    //saveData();
    window.location = "../ctrl.php";
  }
}

</script>
</head>
<body onload="init()">
<canvas id="can" width="800" height="500"
style="background-color: white;
margin: auto;
position: absolute;
top: 0px;
bottom: 0px;
left: 0px;
right: 0px;
border:3px solid #595959;"
>
Your browser does not support HTML5 canvas and will not work with this experiment. Please update your browser.
</canvas>

<br />
</body>
</html>
