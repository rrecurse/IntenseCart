<?
  // Google Notification API

  $no_sts=1;
  require('includes/application_top.php');

  require_once(DIR_WS_CLASSES.'order.php');
  $order=new order;

  $mod=tep_module('payment_google','payment');

  //$file=$mod->handlerLogFile;
  if ($GdbgFile) {
    echo nl2br(htmlspecialchars(file_get_contents($GdbgFile)));
    unlink($GdbgFile);
    exit;
  }

  if (!$mod->initiateResponseHandler()) {
    // Failed authentication
    exit;
  }

  $root=$mod->responseRoot;
  if ($root=="merchant-calculation-callback") {
    echo "This handler does not processes merchant calculations!";
    exit;
  }
  $data=$mod->responseData;
  $Grequest=&$mod->request;
  $Gresponse=&$mod->response;
  $GorderID=$data[$root]["google-order-number"]["VALUE"];
  $pay=$mod->findPayment($GorderID,'payment_google');

  /* Commands to send the various order processing APIs
   * Send charge order : $Grequest->SendChargeOrder($data[$root]
   *    ['google-order-number']['VALUE'], <amount>);
   * Send process order : $Grequest->SendProcessOrder($data[$root]
   *    ['google-order-number']['VALUE']);
   * Send deliver order: $Grequest->SendDeliverOrder($data[$root]
   *    ['google-order-number']['VALUE'], <carrier>, <tracking-number>,
   *    <send_mail>);
   * Send archive order: $Grequest->SendArchiveOrder($data[$root]
   *    ['google-order-number']['VALUE']);
   *
   */

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
require_once(DIR_WS_FUNCTIONS.'debug.php');
IXdebug("Notification API [$title]\n".ob_get_clean());
// END debug

  switch ($root) {
    case "request-received": {
      break;
    }
    case "error": {
      break;
    }
    case "diagnosis": {
      break;
    }
    case "checkout-redirect": {
      break;
    }
    case "new-order-notification": {

      $shipdata=$data[$root]["buyer-shipping-address"];
      $billdata=$data[$root]["buyer-billing-address"];

      // Init order
      $order->create(
        NULL,
        array(
          'name'=>$shipdata["contact-name"]["VALUE"],
          'company'=>$shipdata["company-name"]["VALUE"],
          'street_address'=>$shipdata["address1"]["VALUE"],
          'suburb'=>$shipdata["address2"]["VALUE"],
          'city'=>$shipdata["city"]["VALUE"],
          'postcode'=>$shipdata["postal-code"]["VALUE"],
          'state'=>$shipdata["region"]["VALUE"],
          'country'=>$shipdata["country-code"]["VALUE"],
          'telephone'=>$shipdata["phone"]["VALUE"],
          'fax'=>$shipdata["fax"]["VALUE"],
          'email_address'=>$shipdata["email"]["VALUE"]
        ),
        array(
          'name'=>$billdata["contact-name"]["VALUE"],
          'company'=>$billdata["company-name"]["VALUE"],
          'street_address'=>$billdata["address1"]["VALUE"],
          'suburb'=>$billdata["address2"]["VALUE"],
          'city'=>$billdata["city"]["VALUE"],
          'postcode'=>$billdata["postal-code"]["VALUE"],
          'state'=>$billdata["region"]["VALUE"],
          'country'=>$billdata["country-code"]["VALUE"],
          'telephone'=>$billdata["phone"]["VALUE"],
          'fax'=>$billdata["fax"]["VALUE"],
          'email_address'=>$billdata["email"]["VALUE"]
        )
      );
      //@@ Should I do this explicitly?
      //$order->setStatus(1);

      // Init payment
      $mod->initPayment($data[$root]["order-total"]["VALUE"],$order);
      $mod->setPaymentStatus("incomplete",$GorderID);

      // Add products
      $cartdata=get_arr_result($data[$root]["shopping-cart"]["items"]["item"]);
      foreach($cartdata as $item) {
        $itemID=$item["merchant-item-id"]["VALUE"];
        $itemQty=$item["quantity"]["VALUE"];
        $itemPrice=$item["unit-price"]["VALUE"];
        $order->addProduct(
          $itemID, // item ID
          $itemQty, // quantity
          NULL, // attributes
          $itemPrice // unit price
        );
      }
      
      // Save order
      $order->saveOrder();
      // Finalize payment record (fills in order ID)
      $mod->finishPayment($order);
      // Send ACK back to Google.
      $Gresponse->SendAck();
      break;
    }
    case "order-state-change-notification": {
      $new_financial_state = $data[$root]['new-financial-order-state']['VALUE'];
      $old_financial_state = $data[$root]['previous-financial-order-state']['VALUE'];
      $new_fulfillment_order = $data[$root]['new-fulfillment-order-state']['VALUE'];
      $old_fulfillment_order = $data[$root]['previous-fulfillment-order-state']['VALUE'];
      if ($new_financial_state != $old_financial_state) {
        switch($new_financial_state) {
          case 'REVIEWING': {
            // This is the initial state, shouldn't need to do anything special here
            break;
          }
          case 'CHARGEABLE': {
            //$Grequest->SendProcessOrder($data[$root]['google-order-number']['VALUE']);
            //$Grequest->SendChargeOrder($data[$root]['google-order-number']['VALUE'],'');
            if ($pay) {
              // This is Google's automatic autorization; for explicit authorization
              // requests, see below, where $root="authorization-amount-notification"
              $tid=$pay->startTransaction('authorization',$pay->amount);
              $pay->finishTransaction($tid,'complete');
              $pay->setPaymentStatus("pending");
            }
            break;
          }
          case 'CHARGING': {
            // We only react on charge-amount-notification
            break;
          }
          case 'CHARGED': {
            // We only react on charge-amount-notification
            break;
          }
          case 'PAYMENT_DECLINED': {
            break;
          }
          case 'CANCELLED': {
            if ($pay) {
              $transactions=$pay->findTransactions('cancel','incomplete');
              if ($transactions) {
                foreach($transactions as $transaction_ID) {
                  $pay->finishTransaction($transaction_ID,'complete');
                }
              }
              $pay->amount=0;
              $pay->setPaymentStatus('cancelled');
              $order=$pay->getOrderObject();
              $_IX_payment_google_inhibit_API_calls=true;
              $order->setStatus(0);
              $_IX_payment_google_inhibit_API_calls=false;
            }
            break;
          }
          case 'CANCELLED_BY_GOOGLE': {
            //$Grequest->SendBuyerMessage($data[$root]['google-order-number']['VALUE'],
            //    "Sorry, your order is cancelled by Google", true);
            if ($pay) {
              $tid=$pay->startTransaction('cancelByGoogle',$pay->amount);
              $pay->finishTransaction($tid,'complete');
              $pay->amount=0;
              $pay->setPaymentStatus('cancelled');
              $order=$pay->getOrderObject();
              $order->setStatus(0);
            }
            break;
          }
          default:
            break;
        }
      }
      if ($new_fulfillment_order!=$old_fulfillment_order) {
        switch($new_fulfillment_order) {
          case 'NEW': {
            // This is the initial state, shouldn't need to do anything special here
            break;
          }
          case 'PROCESSING': {
            break;
          }
          case 'DELIVERED': {
            break;
          }
          case 'WILL_NOT_DELIVER': {
            break;
          }
          default:
            break;
        }
      }
      $Gresponse->SendAck();
      break;
    }
    case "charge-amount-notification": {
      //$Grequest->SendDeliverOrder($data[$root]['google-order-number']['VALUE'],
      //    <carrier>, <tracking-number>, <send-email>);
      //$Grequest->SendArchiveOrder($data[$root]['google-order-number']['VALUE'] );
      $GlatestChargeAmount=$data[$root]["latest-charge-amount"]['VALUE'];
      if ($pay) {
        $transactions=$pay->findTransactions('charge','pending',$GlatestChargeAmount);
        if ($transactions) {
          $pay->finishTransaction($transactions[0],'complete');
        } else {
          $tid=$pay->startTransaction('charge',$GlatestChargeAmount);
          $pay->finishTransaction($tid,'complete');
        }
        $pay->updateAmount();
        $pay->setPaymentStatus("complete");
        // Should change order status if payment is truly complete
        $order=$pay->getOrderObject();
        $order->setStatus();
      }
      $Gresponse->SendAck();
      break;
    }
    case "chargeback-amount-notification": {
      $Gresponse->SendAck();
      break;
    }
    case "refund-amount-notification": {
      $GlatestRefundAmount=$data[$root]["latest-refund-amount"]['VALUE'];
      if ($pay) {
        $transactions=$pay->findTransactions('refund','pending',$GlatestRefundAmount);
        if ($transactions) {
          $pay->finishTransaction($transactions[0],'complete');
        } else {
          $tid=$pay->startTransaction('refund',$GlatestRefundAmount);
          $pay->finishTransaction($tid,'complete');
        }
        $pay->updateAmount();
        IXdebug("Setting payment status!");
        $pay->setPaymentStatus("complete");

        // Might need to change order status
        $order=$pay->getOrderObject();
        $order->setStatus();

      }
      $Gresponse->SendAck();
      break;
    }
    case "risk-information-notification": {
      $Gresponse->SendAck();
      break;
    }
    case "authorization-amount-notification": {
      if ($pay) {
        $transactions=$pay->findTransactions('authorization','incomplete');
        if ($transactions) {
          $pay->finishTransaction($transactions[0],'complete');
        } else {
          $tid=$pay->startTransaction('authorization',$pay->amount);
        }
        $pay->finishTransaction($tid,'complete');
        $pay->setPaymentStatus("pending");
      }

      $Gresponse->SendAck();
      break;
    }
    default:
      $Gresponse->SendBadRequestStatus("Invalid or not supported Message");
      break;
  }

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
