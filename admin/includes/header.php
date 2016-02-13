<script type="text/javascript">

jQuery(window).resize(function() {
	//top.resizeIframe('myframe');
});

  if (window==top) {
    document.cookie="iframe_src_myframe="+document.location;
    document.location="/admin/index.php";
  } else {

    if (0 && document.getElementById) {
      if (!document.all) {
        document.addEventListener("mouseup",rmenuInFrame,false);
      } else {
	document.attachEvent("oncontextmenu",rmenuInFrame); 
	document.attachEvent("onclick",rmenuInFrame);
      }
      document.oncontextmenu=new Function("return false") ;
    }
  }
  
  function contentChanged() {
    return top.resizeIframe('myframe');
  }
  
  function rmenuInFrame(event) {
    return top.showMenu(event,'myframe');
  }

  var isSetBreadcrumb;
  function setBreadcrumb(cont) {
    if (isSetBreadcrumb) return true;
    isSetBreadcrumb=1;
    return top.setBreadcrumb(cont);
  }
  
  if (!window.noDefaultBreadcrumb) {
    var titleElement=document.getElementsByTagName('head')[0].getElementsByTagName('title')[0];
    var breadcrumbs=Array({},{});
    if (titleElement) breadcrumbs.push({title:titleElement.innerHTML,link:document.location});
    setBreadcrumb(breadcrumbs);
  }
  
</script>

<?php 
	// # main system messages! DONT REMOVE!
	$self_array = array('/admin/core/','.php');
	$self = str_replace($self_array,'',$_SERVER['PHP_SELF']);

	if ($messageStack->size > 0) {
    	echo $messageStack->output($self);
	}
?> 