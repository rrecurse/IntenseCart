<?
/*
$id author Puddled Internet - http://www.puddled.co.uk
  email support@puddled.co.uk
   
  

  

  
*/



 function tep_create_rma_value($length, $type = 'digits') {
    if ( ($type != 'mixed') && ($type != 'chars') && ($type != 'digits')) return false;

    while (1) {

    $rand_value = '';
    while (strlen($rand_value)<$length) {
      if ($type == 'digits') {
        $char = tep_rand(0,9);
      } else {
        $char = chr(tep_rand(0,255));
      }
      if ($type == 'mixed') {
        if (preg_match('/^[a-z0-9]$/i', $char)) $rand_value .= $char;
      } elseif ($type == 'chars') {
        if (preg_match('/^[a-z]$/i', $char)) $rand_value .= $char;
      } else if ($type == 'digits') {
        if (preg_match('/^[0-9]$/', $char)) $rand_value .= $char;
      }
    }
    
    if (!IXdb::read("SELECT rma_value FROM returned_products WHERE rma_value='$rand_value'")) break;
    }

    return $rand_value;
  }
?>
