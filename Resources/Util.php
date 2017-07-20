<?php

function RedirectToURL($url)
{
    header("Location: $url");
    exit;
}

function SetWebsiteName($sitename)
{
    $this->sitename = $sitename;
}

function getWords($flName) {
  // Get the list of words and return it as an array.
  if (($fp = fopen($flName, "r")) === FALSE) {
    throw new Exception("Couldn't open file!");
  }
  $words = array();
  while (($data = fgetcsv($fp, 1000, ",")) !== FALSE) {
    $words[] = $data[0];
  }
  return($words);
}
?>
