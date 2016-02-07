<?php
/*
  Copyright (c) 2003 IntenseCart eCommerce
*/


function ChangeSwitch($line, $arg) {
  if (isset($arg))
   $line = str_replace("0", "1", $line);
  else  
   $line = str_replace("1", "0", $line);
   
  return $line; 
}

//returns true if line is a comment
function IsComment($line) {   
  return ((strpos($line, "//") === 0) ? true : false);
}

function IsTitleSwitch($line) {   
  if (strpos($line, "define('HTTA") === 0 && strpos($line, "define('HTTA_CAT") === FALSE)
    return true;
  else
    return false; 
}

function IsDescriptionSwitch($line) {   
  return ((strpos($line, "define('HTDA") === 0) ? true : false);
}

function IsKeywordSwitch($line) {   
  return ((strpos($line, "define('HTKA") === 0) ? true : false); 
}

function IsCatSwitch($line) {   
  return ((strpos($line, "define('HTTA_CAT") === 0) ? true : false); 
}

function IsTitleTag($line) {   
  return ((strpos($line, "define('HEAD_TITLE_TAG") === 0) ? true : false);  
}

function IsDescriptionTag($line) {   
  return ((strpos($line, "define('HEAD_DESC_TAG") === 0) ? true : false);
}

function IsKeywordTag($line) {   
  return ((strpos($line, "define('HEAD_KEY_TAG") === 0) ? true : false);
}

function FileNotUsingHeaderTags($file) {
  $file = '..' . DIR_WS_CATALOG.$file;
  $fp = file($file);
  for ($i = 0; $i < count($fp); ++$i) {
      if (strpos($fp[$i], "Header Tags Controller") !== FALSE)
        return false;
  }
  return true;
}

function GetArgument(&$line, $arg_new, $formActive) {
  $arg = explode("'", $line);
  
  if ($formActive)
  {
    $line = ReplaceArg($line, $arg_new);
  }
  else
  {             
    for ($i = 4; $i < count($arg); ++$i)
    {
       if (strpos($arg[$i], ");") === FALSE)
         $arg[3] .= $arg[$i];
    }             
  
    $arg[3] = str_replace("\\", "'", $arg[3]);
  }
 
  return $arg[3];
}

function GetMainArgument(&$line, $arg, $arg2, $formActive)
{
/* what the bullshit?
  $def = explode("'", $line);
  for ($i = 3; $i < count($def); ++$i)
  {
    if (strpos($def[$i], ");") === FALSE)
    {
      $arg .= $def[$i];
    }  
  }

  $arg = str_replace("\\", "'",$arg);
*/

  preg_match('|define\s*\(\s*\'(.*?)\'\s*,\s*\'(.*)\'\s*\)|',$line,$lp);
  if ($lp) $arg.=$lp[2];

  if ($formActive)
  {
/*
     if (! tep_not_null($arg))                      //the default tag is empty
     {
        $arg_tmp = "'xyz123'";                      //fill it with temp pattern
        $arg = "xyz123";                            //match the original but no '' since they will be deleted
        $line = str_replace("''", $arg_tmp, $line); //fill in the string so it can be replaced below 
     }
     else
       $arg = addslashes($arg);
  
     $line = str_replace($arg, $arg2, $line);
*/

     $line=preg_replace('|define\s*\(\s*\'(.*?)\'.*|','define(\'\1\',\''.$arg2."');",$line);
     $arg = $arg2;
  }  
  
  return $arg;  
}

function GetSectionName($line)
{
  $name = explode(" ", $line);
  $name[1] = trim($name[1]);
  $pos = strpos($name[1], '.');
  return (substr($name[1], 0, $pos)); 
}

function GetSwitchSetting($line)
{
  return ((strpos($line, "'0'") === FALSE) ? 1 : 0);     
}

function NotDuplicatePage($fp, $pagename)  //return false if the name entered is already present
{
  for ($idx = 0; $idx < count($fp); ++$idx)   
  {
     $section = GetSectionName($fp[$idx]);
     if (! empty($section))
     {
        if (strcasecmp($section, $pagename) === 0)
          return false;
     }     
  }
  return true;
}

function ReplaceArg($line, $arg)
{
  $parts = explode("'", $line);         //break apart the line   
  $parts[3] = $arg;                     //replace the argument  
  
  if (strpos($parts[3], "\\") === FALSE)
    $parts[3] = addslashes($parts[3]);  
   
  $parts = $parts[0] . "'" . $parts[1] . "'" . $parts[2] . "'" . $parts[3] . '\');' . "\n";
  return $parts; 
  return implode("'", $parts);          //put line back together
}

function TotalPages($filename)
{
  $ctr = 0;
  $findTitles = false;
  $fp = file($filename);  
      
  for ($idx = 0; $idx < count($fp); ++$idx)
  { 
    $line=$fp[$idx];

    if (strpos($line, "define('HEAD_TITLE_TAG_ALL','") !== FALSE)
      continue;
    else if (strpos($line, "define('HEAD_DESC_TAG_ALL") !== FALSE)
      continue;
    else if (strpos($line, "define('HEAD_KEY_TAG_ALL") !== FALSE)
    {
      $findTitles = true;  //enable next section
      continue;
    } 
    else if ($findTitles)
    {
      if (($pos = strpos($fp[$idx], '.php')) !== FALSE)
        $ctr++; 
    }
  }  
  return $ctr;
}

function WriteHeaderTagsFile($filename, $fp)
{
  if (!is_writable($filename)) 
  {
//echo 'File, directory or somthing else isnt writable. check your permissions and ownership';
exec ('chmod $filename 0777');
exec ('chown $filename apache');
  exit;
/*
     if (!chmod($filename, 0666)) {
        echo "Cannot change the mode of file ($filename)";
        exit;
     }
*/
  }
  $fpOut = fopen($filename, "w");
 
  if (!fpOut)
  {
     echo 'Failed to open file '.$filename;
     exit;
  }
       
  for ($idx = 0; $idx < count($fp); ++$idx)
    if (fwrite($fpOut, $fp[$idx]) === FALSE)
    {
       echo "Cannot write to file ($filename)";
       exit;
    } 
  fclose($fpOut);   
}
?>
