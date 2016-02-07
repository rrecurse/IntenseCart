<?

// Define $template variable as global
global $template;

// Init template variables to blanks
$template['productname'] = '';
$template['productdesctrunc'] = '';

// Use this to truncate a string at the first HTML tag or at $maxlength number of characters
function sts_truncate($truncstring, $maxlength, $eol_marker) {
   // Determine how much of the products_description to print out
   $tag_start = strpos($truncstring, '<');
   if ($tag_start < $maxlength) {
        $truncate_chars = $tag_start ;
   } else {
        $truncate_chars = $maxlength;
   }

   if ($truncate_chars != $max_desc_chars) {
        $truncate_marker = $eol_marker;
   } else {
        $truncate_marker = "";
   }

   return(substr($truncstring, 0, $truncate_chars) . $truncate_marker);
}

function sts_boxcontent($boxcontent, $boxclass) {
        // $boxcontent is an array of arrays.  The inner array contains "alignment" and "content".

        $tmpstr="<table class='$boxclass' width=100%>\n";
        foreach ($boxcontent as $key=>$value) {
                $alignment = $value["align"];
                $textstr = $value["text"];
                $tmpstr .= "<tr><td align=\"$alignment\">$textstr</td></tr>\n";
                $formstr = $value["form"];
		if ($formstr != "") {
			$tmpstr = "$formstr$tmpstr</form>";
		}
        }
        $tmpstr .= "</table>\n";
        return $tmpstr;
}

function GetBacktrace()
{
   $s = '';
   $MAXSTRLEN = 64;
  
   $s = '<pre align=left>';
   $traceArr = debug_backtrace();
   array_shift($traceArr);
   $tabs = sizeof($traceArr)-1;
   foreach($traceArr as $arr)
   {
       for ($i=0; $i < $tabs; $i++) $s .= ' &nbsp; ';
       $tabs -= 1;
       $s .= '<font face="Courier New,Courier">';
       if (isset($arr['class'])) $s .= $arr['class'].'.';
       $args = array();
       if(!empty($arr['args'])) foreach($arr['args'] as $v)
       {
           if (is_null($v)) $args[] = 'null';
           else if (is_array($v)) $args[] = 'Array['.sizeof($v).']';
           else if (is_object($v)) $args[] = 'Object:'.get_class($v);
           else if (is_bool($v)) $args[] = $v ? 'true' : 'false';
           else
           {
               $v = (string) @$v;
               $str = htmlspecialchars(substr($v,0,$MAXSTRLEN));
               if (strlen($v) > $MAXSTRLEN) $str .= '...';
               $args[] = "\"".$str."\"";
           }
       }
       $s .= $arr['function'].'('.implode(', ',$args).')</font>';
       $Line = (isset($arr['line'])? $arr['line'] : "unknown");
       $File = (isset($arr['file'])? $arr['file'] : "unknown");
       $s .= sprintf("<font color=#808080 size=-1> # line %4d, file: <a href=\"file:/%s\">%s</a></font>",
           $Line, $File, $File);
       $s .= "\n";
   }   
   $s .= '</pre>';
   return $s;
}

?>
