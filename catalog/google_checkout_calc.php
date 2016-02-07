<?
  // Google Merchant Calculations
  
  $no_sts=1;
  require('includes/application_top.php');
  require(DIR_WS_CLASSES.'order.php');

  $mod=tep_module('payment_google','payment');

/*
  echo nl2br(htmlspecialchars(file_get_contents($mod->handlerLogFile)));
  unlink($mod->handlerLogFile);
  exit;
*/

  if (!$mod->initiateResponseHandler()) {
    // Failed authentication
    exit;
  }
  
  $root=$mod->responseRoot;
  if ($root!="merchant-calculation-callback") {
    echo "This handler only processes merchant calculations!";
    exit;
  }
  $data=$mod->responseData;
  
// START debug
ob_start();
echo "Root:\n$root\n\n$data:\n";
var_dump($data);
$title=$root;
if ($root=='order-state-change-notification') {
  $title="(state) ".
    $data[$root]['previous-fulfillment-order-state']['VALUE'].'/'.$data[$root]['previous-financial-order-state']['VALUE'].
    ' -> '.
    $data[$root]['new-fulfillment-order-state']['VALUE'].'/'.$data[$root]['new-financial-order-state']['VALUE'];
}
//mail('jim@intensecart.com','calc',ob_get_clean());
ob_end_clean();
// END debug

  // Create the results and send it
  $merchant_calc = new GoogleMerchantCalculations($currency);

  /*
    Notes:

    * Empirical evidence suggests there's never more than one anonymous-address
      tag in the XML we receive (even if the buyer has multiple addresses
      registered with Google Checkout, a new callback is performed each
      time they switch between addresses). However, we do provide support for
      such an option, because Google's specification doesn't FORBID multiple
      such tags, and things can change in the future.

    * There are situations however when no anonymous-address fields are present
      (e.g. when an unauthenticated Google Checkout user first hits Google's
      interface).
  
  */
  // Loop through the list of address ids from the callback
  $addresses = get_arr_result($data[$root]['calculate']['addresses']['anonymous-address']);
  foreach($addresses as $curr_address) {
    $curr_id = $curr_address['id'];
    $country = $curr_address['country-code']['VALUE'];
    $city = $curr_address['city']['VALUE'];
    $region = $curr_address['region']['VALUE'];
    $postal_code = $curr_address['postal-code']['VALUE'];
    
    $order=new order();
    $order->create(NULL,Array(
      'city'=>$curr_address['city']['VALUE'],
      'zip'=>$curr_address['postal-code']['VALUE'],
      'state'=>$curr_address['region']['VALUE'],
      'country'=>$curr_address['country-code']['VALUE'],
    ));
    foreach(get_arr_result($data[$root]["shopping-cart"]["items"]["item"]) AS $item) {
      $order->addProduct(
        $item["merchant-item-id"]["VALUE"],
        $item["quantity"]["VALUE"],
        unserialize($item["merchant-private-item-data"]["VALUE"]),
        $item["unit-price"]["VALUE"]
      );
    }

    $shipping = get_arr_result($data[$root]['calculate']['shipping']['method']);
    foreach($shipping as $curr_ship) {
      // SHIPPING
      $name = $curr_ship['name'];
      $price = 12.35; // Modify this to get the actual price
      $shippable = "true"; // Modify this as required
      $merchant_result = new GoogleResult($curr_id);
      $merchant_result->SetShippingDetails($name, $price, $shippable);

      if ($shippable=='false') {
        continue;
      }
      
      // TAX
      if($data[$root]['calculate']['tax']['VALUE'] == "true") {
        //Compute tax for this address id and shipping type
        $amount = $order->getOrderTotalValue('ot_tax');
        $merchant_result->SetTaxDetails($amount+10);
//  mail('jim@intensecart.com','calc',$amount);
      }

      // COUPONS
      if(isset($data[$root]['calculate']['merchant-code-strings']['merchant-code-string'])) {
        $codes = get_arr_result($data[$root]['calculate']['merchant-code-strings']['merchant-code-string']);
        foreach($codes as $curr_code) {
          //Update this data as required to set whether the coupon is valid, the code and the amount
          $coupons = new GoogleCoupons("true", $curr_code['code'], 5, "test2");
          $merchant_result->AddCoupons($coupons);
        }
      }
      $merchant_calc->AddResult($merchant_result);
    }
  }
  
  /*
  // Can't add new shipping methods on the fly -- the chunk below doesn't work
  $mc=new GoogleResult($curr_id);
  $mc->SetShippingDetails('Test Delivery','1.23','true');
  $mc->SetTaxDetails('4.56');
  $merchant_calc->AddResult($mc);
  */

  ob_start();
  
  $mod->response->ProcessMerchantCalculations($merchant_calc);
//  mail('jim@intensecart.com','calc',ob_get_clean());
  ob_end_clean();

  /*
    In case the XML API contains multiple open tags
    with the same value, then invoke this function and
    perform a foreach on the resultant array.
    This takes care of cases when there is only one unique tag
    or multiple tags.

    Examples of this are "anonymous-address", "merchant-code-string"
    from the merchant-calculations-callback API
  */
  function get_arr_result($child_node) {
    $result = array();
    if(isset($child_node)) {
      if(is_associative_array($child_node)) {
        $result[] = $child_node;
      }
      else {
        foreach($child_node as $curr_node){
          $result[] = $curr_node;
        }
      }
    }
    return $result;
  }
  
  /*
    Returns true if a given variable represents an associative array
  */
  function is_associative_array($var) {
    return is_array($var) && !is_numeric(implode('', array_keys($var)));
  }


?>
