<?
  $no_sts=1;
  require('includes/application_top.php');
  require_once(DIR_WS_CLASSES.'order.php');

  $order=new order($_SESSION['cart']);

  $mod=tep_module('payment_google','payment');

  $googleCart=&$mod->getCart();
  $shipping=$order->getShippingMethods();
  foreach($shipping as $option) {
    $ship = new GoogleMerchantCalculatedShipping(
      $option['title'],
      $option['price']
    );

//    $address_filter = new GoogleShippingFilters();
//    $address_filter->SetAllowedWorldArea(true);
//    $ship->AddAddressFilters($address_filter);

    $googleCart->AddShipping($ship);
  }

  //@@ Must be generated dynamically
  // Set default tax options
  $tax_rule = new GoogleDefaultTaxRule(0.15);
  $tax_rule->SetWorldArea(true);
  $googleCart->AddDefaultTaxRules($tax_rule);

  /*
  //@@ how do I get the cart ID? Is there one to begin with? Is it being stored?
  $googleCart->SetMerchantPrivateData(
     new MerchantPrivateData(array("cart-id" => $_SESSION['cart'])));
  */
     
  echo "Products:";
  $products=$_SESSION['cart']->get_products();
  if (!$products) {
    header("Location: /shopping_cart.php");
    exit;
  }
  foreach($products as $product) {
/*
    echo "Product: ".$product['name'];
    echo "<pre>";
    var_dump($product);
    echo "</pre>";
*/
    $googleItem=new GoogleItem(
      $product['name'],
      $product['description'],
      $product['quantity'],
      $product['price']
    );
    $googleItem->SetMerchantItemId($product['products_id']);
    $googleItem->SetMerchantPrivateItemData(serialize($product['attributes']));
    $googleCart->AddItem($googleItem);
  }
  //exit;
  //@@ how do I determine the URL?
  //@@ configuring whether to use tax, coupons and gift certs
  /*
    function SetMerchantCalculations($url, $tax_option = "false",
        $coupons = "false", $gift_cert = "false")
  */
  $mod->checkout();
  echo "Bad";
?>
