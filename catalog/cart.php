<?php

  require('includes/application_top.php');

  $hp=parse_url($_SERVER['HTTP_REFERER']);
  $helper_path = $hp['scheme'] .'://'. $hp['host'];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>" />
<link rel="stylesheet" type="text/css" href="/layout/css/cart_anywhere.css" />
<script type="text/javascript" src="/layout/jquery-1.6.1.min.js"></script>
<script type="text/javascript" src="/layout/cart_anywhere.js"></script>
<script language="javascript">
    $(document).ready(function() {
	$("#categories_tree").treeview({
		animated: "fast",
		url: "cart_anywhere_ajax.php"
	});
    });

    $('a.remote').click(function(e) { 
      return false;
    });

  function load_page(url)
  {
   //alert(parent.document.getElementById("_content").innerHTML);
  }

  var oldheight=0;

  function updateHeight ()
  {
   window.setInterval(iframeResizePipe, 300);
  }


  function iframeResizePipe()
  {
     // What's the page height?
     if ($.browser.webkit) 
      var height = document.documentElement.scrollHeight;
     else
      var height = document.body.scrollHeight+10;



     if (height!=oldheight)
     {


     // Going to 'pipe' the data to the parent through the helpframe..
     var pipe = document.getElementById('helpframe');

     <?php if (strlen($_GET['helper_path'])>0) { ?>
     var helper_path="<?php echo $_GET['helper_path']; ?>";
     <?php } else { ?>
     var helper_path="<?php echo $helper_path; ?>";
     <?php } ?>
     // Cachebuster a precaution here to stop browser caching interfering
     pipe.src = helper_path+'/helper.html?height='+height+'&cacheb='+Math.random();
     oldheight=height;     
     }

  }

</script>
</head>
<body onload="updateHeight()">
<iframe id="helpframe" src='' height='0' width='0' frameborder='0'></iframe>
<ul id="categories_tree"></ul>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>