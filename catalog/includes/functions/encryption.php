<?php
  function tep_cc_encrypt($string) {
   $result = '';
   for($i=0; $i<strlen($string); $i++) {
     $char = substr($string, $i, 1);
     $keychar = substr(CC_ENCRYPTION_KEY, ($i % strlen(CC_ENCRYPTION_KEY))-1, 1);
     $char = chr(ord($char)+ord($keychar));
     $result.=$char;
   }

   return base64_encode($result);
  }

  function tep_cc_decrypt($string) {
   $result = '';
   $string = base64_decode($string);

   for($i=0; $i<strlen($string); $i++) {
     $char = substr($string, $i, 1);
     $keychar = substr(CC_ENCRYPTION_KEY, ($i % strlen(CC_ENCRYPTION_KEY))-1, 1);
     $char = chr(ord($char)-ord($keychar));
     $result.=$char;
   }

   return $result;
  }

?>