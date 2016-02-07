<?
  require('includes/application_top.php');
  require(DIR_FS_ADMIN.'apility/apility.php');

?>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Executive Dashboard</title>
<link rel="STYLESHEET" href="js/css.css" type="text/css">
<script type="text/javascript" src="js/tips.js"></script>
<script type="text/javascript" src="js/iframe.js"></script>
<script type="text/javascript" src="js/prototype.lite.js"></script>
<script type="text/javascript" src="js/xmlfeed.js"></script>
<script language="JavaScript">
  var noDefaultBreadcrumb=1;

  var trafficSources=Array('google','yahoo','msn','aol','other');
  var trafficPieColors=Array('ff4040','ff40ff','4040ff','40ffff','40ff40');
  var trafficPieUrl='pie_chart.php?width=95&height=95&bgcolor=f0f5fb&border=808080&data=';

  var salesSections=Array('direct','ebay','affiliate');

  var ppcSources=Array('adwords','overture','other','total');

  var statsList=Array(
    { interval:300, stats:{ traffic:Array('today','thisweek','thismonth') }},
    { interval:1800, stats:{ traffic:Array('yesterday','lastweek','lastmonth') }},
//    { interval:3600, stats:{ ppc:Array('yesterday') }},
    { interval:300, stats:{ sales:Array('today','thisweek','thismonth') }},
    { interval:1800, stats:{ sales:Array('yesterday','lastweek','lastmonth') }}
  );
  
</script>
<script language="javascript" type="text/javascript">
function setFocus(focusme) {
  var layer = document.getElementById(focusme);
  var focusIt = layer.getElementsByTagName('a')[0];//This is an array, get the first link.
  focusIt.focus();
}
</script>


	<style type="text/css">
	/* CSS NEEDED ONLY IN THE DEMO 
	html{
		width:100%;
		overflow-x:hidden;
	}
	body{
		font-family: Trebuchet MS, Lucida Sans Unicode, Arial, sans-serif;
		width:100%;
		margin:0px;
		padding:0px;
		text-align:center;
		background-color:#E2EBED;	
		font-size:0.7em;	
		overflow-x:hidden;
	}*/
		
	#mainContainer{
		width:571px;
		margin:0;
		text-align:left;
		/*background-color:#FFF;*/
	}
	/*h4{
		margin:0px;
	}
	p{
		margin-top:5px;
	}*/
	
	
	
	/* This is the box that is parent to the dragable items */
	#dragableElementsParentBox{
		padding:0px;	/* Air */
		width:571px;
	}
	
	.smallArticle,.bigArticle{
		float:left;
		/*border:1px solid #000;
		background-color:#DDD;
		margin-right:10px;
		margin-bottom:5px;*/
		padding:5px;
		
	}
	/*.smallArticle img,.bigArticle img{
		float:left;
		padding:0px;
	}
	.smallArticle .rightImage,.bigArticle .rightImage{
		float:right;
	}*/
	.smallArticle{
		width:283px;
		padding:5px;
		overflow-x:hidden		
	}
	.bigArticle{
		width:570px;
		padding:0px;
		overflow-x:hidden
	}
	.clear{
		clear:both;
	}
	
	/* REQUIRED CSS */
	
	#rectangle{
		float:left;
		border:1px dotted #F00;	/* Red border */
		background-color:#FFF;
	}
	#insertionMarker{	/* Don't change the rules for the insertionMarker */
		width:6px;
		position:absolute;
		display:none;
	}
	#insertionMarker img{	/* Don't change the rules for the insertionMarker */
		float:left;
	}		
	#dragDropMoveLayer{	/* Dragable layer - Not need if you're using 'rectangle' mode */
		position:absolute;
		display:none;
		border:1px solid #000;
		filter:alpha(opacity=60);	/* 50% opacity , i.e. transparency */
		opacity:0.6;	/* 50% opacity , i.e. transparency */

	}
	
	/* END REQUIRED CSS */
	</style>
	
	<script type="text/javascript">
		
	var rectangleBorderWidth = 2;	// Used to set correct size of the rectangle with red dashed border
	var useRectangle = false;	
	var autoScrollSpeed = -1;	// Autoscroll speed	- Higher = faster
	
	/* The saveData function creates a string containing the ids of your dragable elements. 
	
	The format of this string is as follow
	
	id of item 1;id of item 2;id of item 3
	
	i.e. a semi colon separated list. The id is something you put in as "id" attribute of your dragable elements.
	
	*/
	
	function saveData()
	{
		var saveString = "";
		for(var no=0;no<dragableObjectArray.length;no++){
			if(saveString.length>0)saveString = saveString + ';';
			ref = dragableObjectArray[no];
			saveString = saveString + ref['obj'].id;
		}	
		
		alert(saveString);	// For demo only
		
		/* 	Put this item into a hidden form field and then submit the form 
		
		example:
		
		document.forms[0].itemOrder.value = saveString;
		document.forms[0].submit;
		
		On the server explode the values by use of server side script. Then update your database with the new item order
		
		*/
	}

	
	/* Don't change anything below here */
	
	
	var dragableElementsParentBox;
	var opera = navigator.appVersion.indexOf('Opera')>=0?true:false;
		
	var rectangleDiv = false;
	var insertionMarkerDiv = false;
	var mouse_x;
	var mouse_y;
	
	var el_x;
	var el_y;
		
	var dragDropTimer = -1;	// -1 = no drag, 0-9 = initialization in progress, 10 = dragging
	var dragObject = false;
	var dragObjectNextObj = false;
	var dragableObjectArray = new Array();
	var destinationObj = false;	
	var currentDest = false;
	var allowRectangleMove = true;
	var insertionMarkerLine;
	var dragDropMoveLayer;
	var autoScrollActive = false;
	var documentHeight = false;
	var documentScrollHeight = false;
	var dragableAreaWidth = false;
	
	function getTopPos(inputObj)
	{		
	  var returnValue = inputObj.offsetTop;
	  while((inputObj = inputObj.offsetParent) != null){
	  	if(inputObj.tagName!='HTML')returnValue += inputObj.offsetTop;
	  }
	  return returnValue;
	}
	
	function getLeftPos(inputObj)
	{
	  var returnValue = inputObj.offsetLeft;
	  while((inputObj = inputObj.offsetParent) != null){
	  	if(inputObj.tagName!='HTML')returnValue += inputObj.offsetLeft;
	  }
	  return returnValue;
	}
		
	function cancelSelectionEvent()
	{
		if(dragDropTimer>=0)return false;
		return true;
	}
	
	function getObjectFromPosition(x,y)
	{
		var height = dragObject.offsetHeight;
		var width = dragObject.offsetWidth;
		var indexCurrentDragObject=-5;
		for(var no=0;no<dragableObjectArray.length;no++){			
			ref = dragableObjectArray[no];			
			if(ref['obj']==dragObject)indexCurrentDragObject=no;
			if(no<dragableObjectArray.length-1 && dragableObjectArray[no+1]['obj']==dragObject)indexCurrentDragObject=no+1;
			if(ref['obj']==dragObject && useRectangle)continue;	
			if(x > ref['left'] && y>ref['top'] && x<(ref['left'] + (ref['width']/2)) && y<(ref['top'] + ref['height'])){
				if(!useRectangle && dragableObjectArray[no]['obj']==dragObject)return 'self';
				if(indexCurrentDragObject==(no-1))return 'self';
				return Array(dragableObjectArray[no],no);
			}
			
			if(x > (ref['left'] + (ref['width']/2)) && y>ref['top'] && x<(ref['left'] + ref['width']) && y<(ref['top'] + ref['height'])){
				if(no<dragableObjectArray.length-1){
					if(no==indexCurrentDragObject || (no==indexCurrentDragObject-1)){
						return 'self';
					}
					if(dragableObjectArray[no]['obj']!=dragObject){
						return Array(dragableObjectArray[no+1],no+1);
					}else{
						if(!useRectangle)return 'self';
						if(no<dragableObjectArray.length-2)return Array(dragableObjectArray[no+2],no+2);
					}
				}else{
					if(dragableObjectArray[dragableObjectArray.length-1]['obj']!=dragObject)return 'append';	
					
				}
			}
			if(no<dragableObjectArray.length-1){
				if(x > (ref['left'] + ref['width']) && y>ref['top'] && y<(ref['top'] + ref['height']) && y<dragableObjectArray[no+1]['top']){
					return Array(dragableObjectArray[no+1],no+1);
				}
			}
		}	
		if(x>ref['left'] && y>(ref['top'] + ref['height']))return 'append';				
		return false;	
	}
		
	function initDrag(e)
	{
		if(document.all)e = event;
		mouse_x = e.clientX;
		mouse_y = e.clientY;
		if(!documentScrollHeight)documentScrollHeight = document.documentElement.scrollHeight + 100;
		el_x = getLeftPos(this)/1;
		el_y = getTopPos(this)/1;
		dragObject = this;
		if(useRectangle){
			rectangleDiv.style.width = this.clientWidth - (rectangleBorderWidth*2) +'px';
			rectangleDiv.style.height = this.clientHeight - (rectangleBorderWidth*2) +'px';
			rectangleDiv.className = this.className;
		}else{
			insertionMarkerLine.style.width = '6px';
		}
		dragDropTimer = 0;
		dragObjectNextObj = false;
		if(this.nextSibling){
			dragObjectNextObj = this.nextSibling;
			if(!dragObjectNextObj.tagName)dragObjectNextObj = dragObjectNextObj.nextSibling;
		}
		initDragTimer();
		return false;
	}
	
	function initDragTimer()
	{
		if(dragDropTimer>=0 && dragDropTimer<10){
			dragDropTimer++;
			setTimeout('initDragTimer()',5);
			return;
		}
		if(dragDropTimer==10){
			
			if(useRectangle){
				dragObject.style.opacity = 0.5;
				dragObject.style.filter = 'alpha(opacity=50)';
				dragObject.style.cursor = 'default';
			}else{
				var newObject = dragObject.cloneNode(true);
				dragDropMoveLayer.appendChild(newObject);
			}
		}
	}
	
	
	function autoScroll(direction,yPos)
	{
		if(document.documentElement.scrollHeight>documentScrollHeight && direction>0)return;
		
		window.scrollBy(0,direction);
		
		if(direction<0){
			if(document.documentElement.scrollTop>0){
				mouse_y = mouse_y - direction;
				if(useRectangle){
					dragObject.style.top = (el_y - mouse_y + yPos) + 'px';
				}else{
					dragDropMoveLayer.style.top = (el_y - mouse_y + yPos) + 'px';
				}			
			}else{
				autoScrollActive = false;
			}
		}else{
			if(yPos>(documentHeight-50)){		

				mouse_y = mouse_y - direction;
				if(useRectangle){
					dragObject.style.top = (el_y - mouse_y + yPos) + 'px';
				}else{
					dragDropMoveLayer.style.top = (el_y - mouse_y + yPos) + 'px';
				}				
			}else{
				autoScrollActive = false;
			}
		}
		if(autoScrollActive)setTimeout('autoScroll('+direction+',' + yPos + ')',5);
	}
	
	function moveDragableElement(e)
	{
		if(document.all)e = event;

		if(dragDropTimer<10)return;
		if(!allowRectangleMove)return false;
		
		
		if(e.clientY<50 || e.clientY>(documentHeight-50)){
			if(e.clientY<50 && !autoScrollActive){
				autoScrollActive = true;
				autoScroll((autoScrollSpeed*-1),e.clientY);
			}
			
			if(e.clientY>(documentHeight-50) && document.documentElement.scrollHeight<=documentScrollHeight && !autoScrollActive){
				autoScrollActive = true;
				autoScroll(autoScrollSpeed,e.clientY);
			}
		}else{
			autoScrollActive = false;
		}
		if(useRectangle){			
			if(dragObject.style.position!='absolute'){
				dragObject.style.position = 'absolute';
				setTimeout('repositionDragObjectArray()',50);
			}
		}		
	
		if(useRectangle){
			rectangleDiv.style.display='block';
		}else{
			insertionMarkerDiv.style.display='block';	
			dragDropMoveLayer.style.display='block';	
		}
		
		if(useRectangle){
			dragObject.style.left = (el_x - mouse_x + e.clientX + Math.max(document.body.scrollLeft,document.documentElement.scrollLeft)) + 'px';
			dragObject.style.top = (el_y - mouse_y + e.clientY) + 'px';
		}else{
			dragDropMoveLayer.style.left = (el_x - mouse_x + e.clientX + Math.max(document.body.scrollLeft,document.documentElement.scrollLeft)) + 'px';
			dragDropMoveLayer.style.top = (el_y - mouse_y + e.clientY) + 'px';
		}
		dest = getObjectFromPosition(e.clientX+Math.max(document.body.scrollLeft,document.documentElement.scrollLeft),e.clientY+Math.max(document.body.scrollTop,document.documentElement.scrollTop));
		
		if(dest!==false && dest!='append' && dest!='self'){
			destinationObj = dest[0]; 
			
			if(currentDest!==destinationObj){
				currentDest = destinationObj;
				if(useRectangle){
					destinationObj['obj'].parentNode.insertBefore(rectangleDiv,destinationObj['obj']);
					repositionDragObjectArray();
				}else{
					if(dest[1]>0 && (dragableObjectArray[dest[1]-1]['obj'].offsetLeft + dragableObjectArray[dest[1]-1]['width'] + dragObject.offsetWidth) < dragableAreaWidth){
						insertionMarkerDiv.style.left = (getLeftPos(dragableObjectArray[dest[1]-1]['obj']) + dragableObjectArray[dest[1]-1]['width'] + 2) + 'px';
						insertionMarkerDiv.style.top = (getTopPos(dragableObjectArray[dest[1]-1]['obj']) - 2) + 'px';
						insertionMarkerLine.style.height = dragableObjectArray[dest[1]-1]['height'] + 'px';
					}else{					
						insertionMarkerDiv.style.left = (getLeftPos(destinationObj['obj']) - 8) + 'px';
						insertionMarkerDiv.style.top = (getTopPos(destinationObj['obj']) - 2) + 'px';
						insertionMarkerLine.style.height = destinationObj['height'] + 'px';
					}
					
					
				}
			}
		}
		
		if(dest=='self' || !dest){
			insertionMarkerDiv.style.display='none';
			destinationObj = dest;	
		}
		
		if(dest=='append'){
			if(useRectangle){
				dragableElementsParentBox.appendChild(rectangleDiv);
				dragableElementsParentBox.appendChild(document.getElementById('clear'));
			}else{
				var tmpRef = dragableObjectArray[dragableObjectArray.length-1];
				insertionMarkerDiv.style.left = (getLeftPos(tmpRef['obj']) + 2) + tmpRef['width'] + 'px';
				insertionMarkerDiv.style.top = (getTopPos(tmpRef['obj']) - 2) + 'px';
				insertionMarkerLine.style.height = tmpRef['height'] + 'px';	
			}
			destinationObj = dest;
			repositionDragObjectArray();
		}	
		
		if(useRectangle && !dest){
			destinationObj = currentDest;
		}
		
		allowRectangleMove = false;
		setTimeout('allowRectangleMove=true',50);
	}
	
	function stop_dragDropElement()
	{
		dragDropTimer = -1;
		
		if(destinationObj && destinationObj!='append' && destinationObj!='self'){
			destinationObj['obj'].parentNode.insertBefore(dragObject,destinationObj['obj']);
		}
		if(destinationObj=='append'){
			dragableElementsParentBox.appendChild(dragObject);
			dragableElementsParentBox.appendChild(document.getElementById('clear'));
		}
		
		if(dragObject && useRectangle){
			dragObject.style.opacity = 1;
			dragObject.style.filter = 'alpha(opacity=100)';
			dragObject.style.cursor = 'move';
			dragObject.style.position='static';
		}
		rectangleDiv.style.display='none';
		insertionMarkerDiv.style.display='none';
		dragObject = false;
		currentDest = false;
		resetObjectArray();
		destinationObj = false;
		if(dragDropMoveLayer){
			dragDropMoveLayer.style.display='none';
			dragDropMoveLayer.innerHTML='';
		}
		autoScrollActive = false;
		documentScrollHeight = document.documentElement.scrollHeight + 100;
	}
	
	function cancelEvent()
	{
		return false;
	}
	
	function repositionDragObjectArray()
	{
		for(var no=0;no<dragableObjectArray.length;no++){
			ref = dragableObjectArray[no];
			ref['left'] = getLeftPos(ref['obj']);
			ref['top'] = getTopPos(ref['obj']);			
		}	
		documentScrollHeight = document.documentElement.scrollHeight + 100;
		documentHeight = document.documentElement.clientHeight;
	}
	
	function resetObjectArray()
	{
		dragableObjectArray.length=0;
		var subDivs = dragableElementsParentBox.getElementsByTagName('*');
		var countEl = 0;

		for(var no=0;no<subDivs.length;no++){
			var attr = subDivs[no].getAttribute('dragableBox');
			if(opera)attr = subDivs[no].dragableBox;
			if(attr=='true'){
				var index = dragableObjectArray.length;
				dragableObjectArray[index] = new Array();
				ref = dragableObjectArray[index];
				ref['obj'] = subDivs[no];
				ref['width'] = subDivs[no].offsetWidth;
				ref['height'] = subDivs[no].offsetHeight;
				ref['left'] = getLeftPos(subDivs[no]);
				ref['top'] = getTopPos(subDivs[no]);
				ref['index'] = countEl;
				countEl++;
			}
		}	
	}
	

	
	function initdragableElements()
	{
		dragableElementsParentBox = document.getElementById('dragableElementsParentBox');
		insertionMarkerDiv = document.getElementById('insertionMarker');
		insertionMarkerLine = document.getElementById('insertionMarkerLine');
		dragableAreaWidth = dragableElementsParentBox.offsetWidth;
		
		if(!useRectangle){
			dragDropMoveLayer = document.createElement('DIV');
			dragDropMoveLayer.id = 'dragDropMoveLayer';		
			document.body.appendChild(dragDropMoveLayer);	
		}
		
		var subDivs = dragableElementsParentBox.getElementsByTagName('*');
		var countEl = 0;
		for(var no=0;no<subDivs.length;no++){
			var attr = subDivs[no].getAttribute('dragableBox');
			if(opera)attr = subDivs[no].dragableBox;
			if(attr=='true'){
				subDivs[no].style.cursor='move';	
				subDivs[no].onmousedown = initDrag;
				
				var index = dragableObjectArray.length;
				dragableObjectArray[index] = new Array();
				ref = dragableObjectArray[index];
				ref['obj'] = subDivs[no];
				ref['width'] = subDivs[no].offsetWidth;
				ref['height'] = subDivs[no].offsetHeight;
				ref['left'] = getLeftPos(subDivs[no]);
				ref['top'] = getTopPos(subDivs[no]);
				ref['index'] = countEl;
				countEl++;
			}
		}
		
		/* Creating rectangel indicating where item will be dropped */
		rectangleDiv = document.createElement('DIV');
		rectangleDiv.id='rectangle';
		rectangleDiv.style.display='none';
		dragableElementsParentBox.appendChild(rectangleDiv);
		
		
		document.body.onmousemove = moveDragableElement;
		document.body.onmouseup = stop_dragDropElement;
		document.body.onselectstart = cancelSelectionEvent;
		document.body.ondragstart = cancelEvent;
		window.onresize = repositionDragObjectArray; 
		
		documentHeight = document.documentElement.clientHeight;
	}
	
	window.onload = initdragableElements;
	
	</script>


</head>
<body style="background-color:transparent;" onLoad="setFocus('focusme');">
<? include(DIR_WS_INCLUDES.'header.php') ?>
<div style="overflow-x:hidden; width:571px;" id="focusme"><a id="focuschild" href="javascript:void(null)" style="cursor: default"></a></div>

<div id="mainContainer">
	<div id="dragableElementsParentBox">
		<div class="bigArticle" dragableBox="true" id="seotips">
			<table width="571" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                     <tr>
                       <td style="height:1px; background-color:#8CA9C4"></td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#FFFFFF"></td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#8CA9C4;"></td>
                     </tr>
                     <tr>
                       <td style="height:16px; background-color:#6295FD; font:bold 11px arial; color:#FFFFFF;">&nbsp; Today's
                         Marketing Tips:</td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#19487E;"></td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#FFFFFF;"></td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#8CA9C4;"></td>
	      </tr>
                     <tr>
                       <td valign="top" style="padding-top:3px;"><table width="541" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="541" valign="top" style="padding-top:3px; padding-left:10px; padding-right:12px;"><?php include 'feed2html.php';?></td>
  </tr>
  
</table></td>
  </tr>
</table></td>
    </tr>
					 <tr>
					 <td style="height:1px; background:url(images/dot-line.gif) repeat-x;"></td>
					 </tr>
					  <tr>
					    <td style="height:20px; padding-top:4px; padding-bottom:5px; padding-left:5px;"><table border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td align="center"><img src="images/archive-icon.jpg" width="12" height="10" alt=""></td>
                            <td style="padding-left:4px;"><a href="#">More Tips</a></td>
                            <td style="padding-left:20px;">&nbsp;</td>
                            <td align="center"><img src="images/archive-icon.jpg" width="12" height="10" alt=""></td>
                            <td style="padding-left:4px;"><a href="#">Tips Archive</a></td>
                          </tr>
                        </table></td>
					  </tr>
      </table>
		</div>
		<div class="smallArticle" dragableBox="true" id="ppctable_summary">
			
			<div style="width:283px; overflow-x:hidden"><table width="283" border="0" cellspacing="0" cellpadding="0">
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
                     </tr>
                     <tr>
                       <td width="261" style="height:20px; background-color:#6295FD; font:bold 12px arial;color:#FFFFFF;">&nbsp; Pay-Per-Click Search Summary & Costs</td>
                       <td width="22" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br>')" onMouseout="hideddrivetip()"> </div></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
                     </tr>
                    <tr>
                       <td colspan="2" style="padding-top:1px; background-color:#DEEAF8; height:20px;"><table width="100%" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td><table width="283" border="0" cellpadding="0" cellspacing="0">
                             <tr>
                               <td width="71" align="center" style="font:bold 12px Arial;"><b>Yesterday:</b></td>
                               <td width="53" align="center">Google</td>
                               <td width="53" align="center">Yahoo</td>
                               <td width="53" align="center">Other</td>
                               <td width="53" align="center">Totals</td>
                             </tr>
                           </table></td>
                         </tr>
</table></td>
                    </tr>
                    <tr>
                      <td colspan="2" valign="top" style="padding-top:3px; background-color:#F0F5FB;"><?

  $stats=Array();
  foreach(Array('adwords','overture','other') AS $scope) $stats[$scope]=Array(avail=>0,clicks=>0,conv=>0,cost=>0.0);
  $date_today=date('Y-m-d',time());
  $date_yesterday=date('Y-m-d',time()-86400);
  $ppc_cache_query=tep_db_query("SELECT * FROM ppc_stats WHERE start_date='$date_yesterday' AND finish_date='$date_today'");
  while ($ppc_cache=tep_db_fetch_array($ppc_cache_query)) {
    $stats[$ppc_cache['ppc_source']]['clicks']=$ppc_cache['ppc_clicks'];
    $stats[$ppc_cache['ppc_source']]['cost']=$ppc_cache['ppc_cost'];
    $stats[$ppc_cache['ppc_source']]['conv']=$ppc_cache['ppc_conversions'];
    $stats[$ppc_cache['ppc_source']]['avail']=$stats[$ppc_cache['ppc_source']]['cache']=1;
  }

  if (!$stats['adwords']['avail']) {
    $campaign_objs=APIlity_getAllCampaigns();
    if (is_array($campaign_objs)) {
      $stats['adwords']['avail']=1;
      foreach ($campaign_objs AS $c) {
        $camp_stats=$c->getCampaignStats(date('Y-m-d',time()-86400),date('Y-m-d',time()));
        $stats['adwords']['clicks']+=$camp_stats['clicks'];
        $stats['adwords']['cost']+=$camp_stats['cost'];
        $stats['adwords']['conv']+=$camp_stats['conversions'];
      }
    }
  }
  foreach ($stats AS $scope=>$stats_row) {
    if (!isset($stats_row['cache']) && $stats_row['avail']) {
      tep_db_query("INSERT IGNORE INTO ppc_stats (start_date,finish_date,ppc_source,ppc_clicks,ppc_cost,ppc_conversions) VALUES ('$date_yesterday','$date_today','$scope','".$stats_row['clicks']."','".$stats_row['cost']."','".$stats_row['conv']."')");
    }
  }
  $stats_total=Array('avail'=>0);
  foreach ($stats AS $stats_row) {
    if ($stats_row['avail']) {
      $stats_total['avail']=1;
      foreach ($stats_row AS $stats_key=>$stats_val) $stats_total[$stats_key]+=$stats_val;
    }
  }
  $stats['total']=&$stats_total;
  
?><table border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td width="71" align="center" class="tableinfo_right-btm">Click Cost:</td>
						  <? foreach ($stats AS $scope=>$st) { ?>
                          <td width="53" align="center" class="tableinfo_right-btm" id="ppc_yesterday_<?=$scope?>_clickcost"><?=$st['avail']?($st['clicks']?sprintf("$%.2f",$st['cost']/$st['clicks']):'-'):'n/a'?></td>
						  <? } ?>
                        </tr>
                        <tr>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm"># of Clicks</td>
						  <? foreach ($stats AS $scope=>$st) { ?>
						  <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="ppc_yesterday_<?=$scope?>_clicks"><?=$st['avail']?(sprintf("%d",$st['clicks'])):'n/a'?></td>
						  <? } ?>
                        </tr>
                        <tr>
                          <td align="center" class="tableinfo_right-btm">Conv. Rate</td>
						  <? foreach ($stats AS $scope=>$st) { ?>
                          <td align="center" class="tableinfo_right-btm" id="ppc_yesterday_<?=$scope?>_convrate"><?=$st['avail']?($st['clicks']?sprintf("%.2f%%",$st['conv']/$st['clicks']*100):'-'):'n/a'?></td>
						  <? } ?>
                        </tr>
                        <tr>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">Cost / Conv.</td>
						  <? foreach ($stats AS $scope=>$st) { ?>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="ppc_yesterday_<?=$scope?>_convcost"><?=$st['avail']?($st['conv']?sprintf("%.2f",$st['cost']/$st['conv']):'-'):'n/a'?></td>
						  <? } ?>
                        </tr>
                      </table> </td>
                    </tr>
                    <tr>
                      <td colspan="2" style="padding:5px; background-color:#F0F5FB"><table border="0" align="center" cellpadding="0" cellspacing="0">
                        <tr>
                          <td><img src="images/graph-icon.jpg" width="16" height="15"></td>
                          <td style="padding-left:6px;"><a href="#">view
                              full report</a></td>
                        </tr>
                    </table></td>
                    </tr>
                   </table></div>
		</div>
		<div class="smallArticle" dragableBox="true" id="ppctable_perform">
<div style="width:283px; overflow-x:hidden"><table width="283" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
	  <tr>
        <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
      </tr>
      <tr>
        <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
      <tr>
        <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
      </tr>
      <tr>
        <td width="261" style="height:20px; background-color:#6295FD; font:bold 12px arial;color:#FFFFFF;">&nbsp; Pay-Per-Click Performance:</td>
        <td width="22" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br>')" onMouseout="hideddrivetip()"> </div></td>
      </tr>
      <tr>
        <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
      </tr>
      <tr>
        <td colspan="2" style="padding-top:1px; background-color:#DEEAF8; height:20px;"></td>
      </tr>
      <tr>
        <td colspan="2" align="center" valign="top" style="padding-top:3px; background-color:#F0F5FB;"><table width="283" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td style="padding-left:9px"></td>
                        </tr>
                        <tr>
                          <td height="90" align="center" style="padding:5px;">&nbsp;</td>
                        </tr>
                        <tr>
                          <td height="25" align="center" style="padding:5px;"><table border="0" align="center" cellpadding="0" cellspacing="0">
                            <tr>
                              <td><img src="images/graph-icon.jpg" width="16" height="15"></td>
                              <td style="padding-left:6px;"><a href="#">view
                                  full report</a></td>
                            </tr>
                          </table></td>
                        </tr>
                  </table></td>
      </tr>
    </table></div>
		</div>
		<div class="smallArticle" dragableBox="true" id="ebaytable_summary">
			<div style="width:283px; overflow-x:hidden"><table width="283" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td colspan="3" style="height:1px; background-color:#8CA9C4"></td>
      </tr>
      <tr>
        <td colspan="3" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
      <tr>
        <td colspan="3" style="height:1px; background-color:#8CA9C4;"></td>
      </tr>
      <tr>
        <td width="35" style="height:20px; padding-left:6px; background-color:#6295FD;"><img src="images/ebaylogo-sm.jpg" width="31" height="13" alt=""></td>
        <td width="226" style="background-color:#6295FD;"><span style="font:bold 12px arial;color:#FFFFFF;"> &nbsp;Ebay&#8482;            Auctions</span></td>
        <td width="22" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br>')" onMouseout="hideddrivetip()"> </div></td>
      </tr>
      <tr>
        <td colspan="3" style="height:2px; background-color:#FFFFFF;"></td>
      </tr>
      <tr>
        <td colspan="3" style="padding-top:1px; background-color:#DEEAF8; height:20px;"><table width="283" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="67" align="center"><span style="font:bold 12px arial; color:#0B2D86;"><?=date('M Y')?></span></td>
            <td width="46" align="center">Live</td>
            <td width="92" align="center"><a href="#"># of Bids:</a></td>
            <td width="80" align="center">Net Totals</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="3" style="padding-top:3px; background-color:#F0F5FB; height:120px;"><table width="283" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="67" align="center" class="tableinfo_right-btm"><a href="#">eBay:</a></td>
            <td width="46" align="center" class="tableinfo_right-btm">0</td>
            <td width="92" align="center" class="tableinfo_right-btm">0</td>
            <td width="80" align="center" class="tableinfo_right-end"><a href="#">$0</a></td>
          </tr>
          <tr>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm"><a href="#">YouBid:</a></td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">0</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">0</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-end"><a href="#">$0</a></td>
          </tr>
          <tr>
            <td align="center" class="tableinfo_right-btm"><a href="#">Buy Now: </a></td>
            <td align="center" class="tableinfo_right-btm">0</td>
            <td align="center" class="tableinfo_right-btm">0</td>
            <td align="center" class="tableinfo_right-end"><a href="#">$0</a></td>
          </tr>
          <tr>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm"><a href="#">Inquiries:</a></td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">0</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">0</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-end"><a href="#">$0</a></td>
          </tr>
          <tr>
            <td colspan="4" align="center" style="padding:5px;"><table border="0" align="center" cellpadding="0" cellspacing="0">
                <tr>
                  <td><img src="images/gavel-icon.gif" width="22" height="13"></td>
                  <td style="padding-left:6px;"><a href="#">open auction manager</a></td>
                </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></div>
		</div>
		<div class="smallArticle" dragableBox="true" id="article6">
<div style="width:283px; overflow-x:hidden"><table width="283" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td colspan="3" style="height:1px; background-color:#8CA9C4"></td>
      </tr>
      <tr>
        <td colspan="3" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
      <tr>
        <td colspan="3" style="height:1px; background-color:#8CA9C4;"></td>
      </tr>
      <tr>
        <td width="79" style="height:20px; padding-left:4px; background-color:#6295FD;"><img src="images/overstock-icon-sm.gif" width="77" height="15" alt=""></td>
        <td width="182" style="background-color:#6295FD;"><span style="font:bold 12px arial;color:#FFFFFF;">&nbsp;Overstock.com&#8482;</span></td>
        <td width="22" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br>')" onMouseout="hideddrivetip()"> </div></td>
      </tr>
      <tr>
        <td colspan="3" style="height:2px; background-color:#FFFFFF;"></td>
      </tr>
      <tr>
        <td colspan="3" style="padding-top:1px; background-color:#DEEAF8; height:20px;"><table width="283" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="67" align="center"><span style="font:bold 12px arial; color:#0B2D86;"><?=date('M Y')?>
            </span></td>
            <td width="46" align="center">Live</td>
            <td width="90" align="center"><a href="#"># of Bids:</a></td>
            <td width="80" align="center">Net Totals</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="3" style="padding-top:3px; background-color:#F0F5FB; height:120px;"><table width="283" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="67" align="center" class="tableinfo_right-btm"><a href="#">eBay:</a></td>
            <td width="46" align="center" class="tableinfo_right-btm">0</td>
            <td width="90" align="center" class="tableinfo_right-btm">0</td>
            <td width="80" align="center" class="tableinfo_right-end"><a href="#">$0</a></td>
          </tr>
          <tr>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm"><a href="#">YouBid:</a></td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">0</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">0</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-end"><a href="#">$0</a></td>
          </tr>
          <tr>
            <td align="center" class="tableinfo_right-btm"><a href="#">Buy Now: </a></td>
            <td align="center" class="tableinfo_right-btm">0</td>
            <td align="center" class="tableinfo_right-btm">0</td>
            <td align="center" class="tableinfo_right-end"><a href="#">$0</a></td>
          </tr>
          <tr>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm"><a href="#">Inquiries:</a></td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">0</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">0</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-end"><a href="#">$0</a></td>
          </tr>
          <tr>
            <td colspan="4" align="center" style="padding:5px;"><table border="0" align="center" cellpadding="0" cellspacing="0">
                <tr>
                  <td><img src="images/gavel-icon.gif" width="22" height="13"></td>
                  <td style="padding-left:6px;"><a href="#">open auction manager</a></td>
                </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></div>
		</div>
		<div class="smallArticle" dragableBox="true" id="article7">
			<div style="width:283px; overflow-x:hidden"><table width="283" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
	  <tr>
        <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
      </tr>
      <tr>
        <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
      <tr>
        <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
      </tr>
      <tr>
        <td width="261" style="height:20px; background-color:#6295FD; font:bold 12px arial;color:#FFFFFF;">&nbsp; Natural
             Search Traffic Summary:</td>
        <td width="22" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br>')" onMouseout="hideddrivetip()"> </div></td>
      </tr>
      <tr>
        <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
      </tr>
      <tr>
        <td colspan="2" style="padding-top:1px; background-color:#DEEAF8; height:20px;"><table width="283" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="61" align="center"><span style="font:bold 12px arial; color:#0B2D86;"><?=date('M Y')?></span></td>
            <td width="45" align="center">Google</td>
            <td width="45" align="center">Yahoo</td>
            <td width="44" align="center">MSN</td>
            <td width="44" align="center">AOL</td>
            <td width="44" align="center">Totals</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="2" align="center" style="padding-top:3px; background-color:#F0F5FB;"><table width="283" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td width="61" align="center" class="tableinfo_right-btm"><a href="#">Yesterday:</a></td>
              <td width="45" align="center" class="tableinfo_right-btm" id="traffic_yesterday_google_count">&nbsp;</td>
              <td width="45" align="center" class="tableinfo_right-btm" id="traffic_yesterday_yahoo_count">&nbsp;</td>
              <td width="44" align="center" class="tableinfo_right-btm" id="traffic_yesterday_msn_count">&nbsp;</td>
              <td width="44" align="center" class="tableinfo_right-btm" id="traffic_yesterday_aol_count">&nbsp;</td>
              <td width="44" align="center" id="traffic_yesterday_total_count" class="tableinfo_right-end"><a href="#"><b>&nbsp;</b></a></td>
            </tr>
            <tr>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm"><a href="#">Last
                  Week:</a></td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_google_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_yahoo_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_msn_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_aol_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-end" id="traffic_lastweek_total_count"><b><a href="#">&nbsp;</a></b></td>
            </tr>
            <tr>
              <td align="center" class="tableinfo_right-btm"><a href="#">This
                  Month:</a></td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_google_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_yahoo_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_msn_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_aol_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-end" id="traffic_thismonth_total_count"><b><a href="#">&nbsp;</a></b></td>
            </tr>
            <tr>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm"><a href="#">Last
                  Month:</a></td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_google_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_yahoo_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_msn_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_aol_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-end" id="traffic_lastmonth_total_count"><b><a href="#">&nbsp;</a></b></td>
            </tr>
            <tr>
              <td colspan="6" align="center" style="padding:5px; border-bottom:1px solid #FFFFFF; color:#FF0000">*
                Dashboard data is refreshed  every 5 minutes. </td>
              </tr>
        </table>
          <div align="center" style="color:#FF0000; padding:5px">
           <table border="0" align="center" cellpadding="0" cellspacing="0">
                        <tr>
                          <td><img src="images/graph-icon.jpg" width="16" height="15"></td>
                          <td style="padding-left:6px;"><a href="supertracker.php">view
                              full report</a></td>
                        </tr>
                    </table> </div></td>
      </tr>
    </table></div>
		</div>	
		<div class="smallArticle" dragableBox="true" id="article8">

			<div style="width:283px; overflow-x:hidden"><table width="283" border="0" cellspacing="0" cellpadding="0">
                     <tr>
        <td colspan="3" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
	  <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
                     </tr>
                     <tr>
                       <td width="261" style="height:20px; background-color:#6295FD; font:bold 12px arial; color:#FFFFFF;">&nbsp; Natural Traffic Averages:</td>
                       <td width="22" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br>')" onMouseout="hideddrivetip()"> </div></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
                     </tr>
                    <tr>
                       <td colspan="2" style="padding-top:1px; background-color:#DEEAF8; height:20px;"><table width="283%" border="0" cellspacing="0" cellpadding="0">
                         <tr>
                           <td><span style="font:bold 12px arial; color:#0B2D86;">&nbsp; <?=date('F Y')?></span></td>
                         </tr>
</table></td>
                    </tr>
                    <tr>
                      <td colspan="2" valign="top" style="padding-top:3px; background-color:#F0F5FB;"><table width="283" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td width="112" height="23" class="tableinfo_right-btm" style="padding-left:9px"><table width="93" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="12" align="center"><div style="height:11px; width:12px; border:1px solid #333333; background-color:#FF0000;"></div></td>
                              <td width="81" nowrap style="padding-left:8px;"><a href="#">Google Search:</a></td>
                            </tr>
                          </table></td>
                          <td width="50" align="center" class="tableinfo_right-btm"><span id="traffic_thismonth_google_percent">0</span>%</td>
                          <td width="121" colspan="2" rowspan="6" align="center" valign="top" style="padding-top:15px;"><img src="images/pixel_trans.gif" id="traffic_pie" width="95" height="95"></td>
                        </tr>
                        <tr>
                          <td height="23" bgcolor="#EBF1F5" class="tableinfo_right-btm" style="padding-left:9px"><table width="90" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="12" align="center"><div style="height:11px; width:12px; border:1px solid #333333; background-color:#FF90FF;"></div></td>
                              <td width="78" nowrap style="padding-left:8px;"><a href="#">Yahoo 
                                  Search:</a></td>
                            </tr>
                          </table></td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm"><span id="traffic_thismonth_yahoo_percent">0</span>%</td>
                        </tr>
                        <tr>
                          <td height="23" class="tableinfo_right-btm" style="padding-left:9px"><table width="81" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="12" align="center"><div style="height:11px; width:12px; border:1px solid #333333; background-color:#5B5BFF;"></div></td>
                              <td width="69" nowrap style="padding-left:8px;"><a href="#">MSN 
                                  Search:</a></td>
                            </tr>
                          </table></td>
                          <td align="center" class="tableinfo_right-btm"><span id="traffic_thismonth_msn_percent">0</span>%</td>
                        </tr>
                        <tr>
                          <td height="23" bgcolor="#EBF1F5" class="tableinfo_right-btm" style="padding-left:9px"><table width="80" border="0" cellpadding="0" cellspacing="0">
                              <tr>
                                <td width="12" align="center"><div style="height:11px; width:12px; border:1px solid #333333; background-color:#90C6FF;"></div></td>
                                <td width="68" nowrap style="padding-left:8px;"><a href="#">AOL 
                                    Search:</a></td>
                              </tr>
                          </table></td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm"><span id="traffic_thismonth_aol_percent">0</span>%</td>
                        </tr>
                        <tr>
                          <td height="23" class="tableinfo_right-btm" style="padding-left:9px"><table width="52" border="0" cellpadding="0" cellspacing="0">
                              <tr>
                                <td width="12" align="center"><div style="height:11px; width:12px; border:1px solid #333333; background-color:#5BFF5B;"></div></td>
                                <td width="40" nowrap style="padding-left:8px;"><a href="#">Other:</a></td>
                              </tr>
                          </table></td>
                          <td align="center" class="tableinfo_right-btm"><span id="traffic_thismonth_other_percent">0</span>%</td>
                        </tr>

                        <tr>
                          <td height="25" colspan="4" align="center" style="padding:5px;"><table border="0" align="center" cellpadding="0" cellspacing="0">
                            <tr>
                              <td><img src="images/graph-icon.jpg" width="16" height="15"></td>
                              <td style="padding-left:6px;"><a href="supertracker.php">view
                                  full report</a></td>
                            </tr>
                          </table></td>
                        </tr>
                      </table>                      </td>
                    </tr>
                   
                   </table>
    </div>
		</div>	
		<div class="smallArticle" dragableBox="true" id="article9">
			<div style="width:283px; overflow-x:hidden"><table width="283" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
	  <tr>
        <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
      </tr>
      <tr>
        <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
      <tr>
        <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
      </tr>
      <tr>
        <td width="261" style="height:20px; background-color:#6295FD; font:bold 12px arial;color:#FFFFFF;">&nbsp; Natural Search Engine Page Ranking:</td>
        <td width="22" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br>')" onMouseout="hideddrivetip()"> </div></td>
      </tr>
      <tr>
        <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
      </tr>
      <tr>
        <td colspan="2" style="padding-top:1px; background-color:#DEEAF8; height:20px;"><table width="283" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="61" align="center"><span style="font:bold 12px arial; color:#0B2D86;"><?=date('Y')?></span></td>
            <td width="45" align="center">Google</td>
            <td width="45" align="center">Yahoo</td>
            <td width="44" align="center">MSN</td>
            <td width="44" align="center">AOL</td>
            <td width="44" align="center">Totals</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="2" align="center" style="padding-top:3px; background-color:#F0F5FB;"><table width="283" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td width="61" align="center" class="tableinfo_right-btm">&nbsp;</td>
              <td width="45" align="center" class="tableinfo_right-btm" >&nbsp;</td>
              <td width="45" align="center" class="tableinfo_right-btm">&nbsp;</td>
              <td width="44" align="center" class="tableinfo_right-btm" >&nbsp;</td>
              <td width="44" align="center" class="tableinfo_right-btm">&nbsp;</td>
              <td width="44" align="center" class="tableinfo_right-end"><a href="#"><b>&nbsp;</b></a></td>
            </tr>
            <tr>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_google_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_yahoo_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_msn_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_aol_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-end" id="traffic_lastweek_total_count"><b><a href="#">&nbsp;</a></b></td>
            </tr>
            <tr>
              <td align="center" class="tableinfo_right-btm">&nbsp;</td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_google_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_yahoo_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_msn_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_aol_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-end" id="traffic_thismonth_total_count"><b><a href="#">&nbsp;</a></b></td>
            </tr>
            <tr>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_google_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_yahoo_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_msn_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_aol_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-end" id="traffic_lastmonth_total_count"><b><a href="#">&nbsp;</a></b></td>
            </tr>
            <tr>
              <td colspan="6" align="center" style="padding:5px; border-bottom:1px solid #FFFFFF; color:#FF0000">*
                Dashboard data is refreshed  every 5 minutes. </td>
              </tr>
        </table>
          <div align="center" style="color:#FF0000; padding:5px">
           <table border="0" align="center" cellpadding="0" cellspacing="0">
                        <tr>
                          <td><img src="images/graph-icon.jpg" width="16" height="15"></td>
                          <td style="padding-left:6px;"><a href="supertracker.php">view
                              full report</a></td>
                        </tr>
                    </table> </div></td>
      </tr></table></div>
		</div>		
		<div class="smallArticle" dragableBox="true" id="article10">
			<div style="width:283px; overflow-x:hidden"><table width="283" border="0" cellspacing="0" cellpadding="0">
                     <tr>
        <td colspan="3" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
	  <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
              </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
                     </tr>
                     <tr>
                       <td width="261" style="height:20px; background-color:#6295FD; font:bold 12px arial; color:#FFFFFF;">&nbsp; SERP Saturation:</td>
                       <td width="22" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br>')" onMouseout="hideddrivetip()"> </div></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
                     </tr>
                    <tr>
                       <td colspan="2" style="padding-top:1px; background-color:#DEEAF8; height:20px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                         <tr>
                           <td><span style="font:bold 12px arial; color:#0B2D86;">&nbsp; <?=date('F Y')?></span></td>
                         </tr>
</table></td>
                    </tr>
                    <tr>
                      <td height="140" colspan="2" valign="top" style="padding-top:3px; background-color:#F0F5FB;"><table width="283" border="0" cellpadding="0" cellspacing="0">
                        

                        <tr>
                          <td height="115" align="center" style="padding:5px;">&nbsp;</td>
                        </tr>
                        <tr>
                          <td height="25" align="center" style="padding:5px;"><table border="0" align="center" cellpadding="0" cellspacing="0">
                            <tr>
                              <td><img src="images/graph-icon.jpg" width="16" height="15"></td>
                              <td style="padding-left:6px;"><a href="supertracker.php">view
                                  full report</a></td>
                            </tr>
                          </table></td>
                        </tr>
                      </table>                      </td>
                    </tr>                 
                   </table>
    </div>
		</div>
		<div class="smallArticle" dragableBox="true" id="article11">
			<div style="width:283px; overflow-x:hidden"><table width="283" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
	  <tr>
        <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
      </tr>
      <tr>
        <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
      <tr>
        <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
      </tr>
      <tr>
        <td width="261" style="height:20px; background-color:#6295FD; font:bold 12px arial;color:#FFFFFF;">&nbsp; Affiliate
          Sales Summary:</td>
        <td width="22" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br>')" onMouseout="hideddrivetip()"> </div></td>
      </tr>
      <tr>
        <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
      </tr>
      <tr>
        <td colspan="2" style="padding-top:1px; background-color:#DEEAF8; height:20px;"><table width="283" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="61" align="center"><span style="font:bold 12px arial; color:#0B2D86;"><?=date('Y')?></span></td>
            <td width="45" align="center">Google</td>
            <td width="45" align="center">Yahoo</td>
            <td width="44" align="center">MSN</td>
            <td width="44" align="center">AOL</td>
            <td width="44" align="center">Totals</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="2" align="center" style="padding-top:3px; background-color:#F0F5FB;"><table width="283" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td width="61" align="center" class="tableinfo_right-btm">&nbsp;</td>
              <td width="45" align="center" class="tableinfo_right-btm" >&nbsp;</td>
              <td width="45" align="center" class="tableinfo_right-btm">&nbsp;</td>
              <td width="44" align="center" class="tableinfo_right-btm" >&nbsp;</td>
              <td width="44" align="center" class="tableinfo_right-btm">&nbsp;</td>
              <td width="44" align="center" class="tableinfo_right-end"><a href="#"><b>&nbsp;</b></a></td>
            </tr>
            <tr>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_google_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_yahoo_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_msn_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_aol_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-end" id="traffic_lastweek_total_count"><b><a href="#">&nbsp;</a></b></td>
            </tr>
            <tr>
              <td align="center" class="tableinfo_right-btm">&nbsp;</td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_google_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_yahoo_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_msn_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_aol_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-end" id="traffic_thismonth_total_count"><b><a href="#">&nbsp;</a></b></td>
            </tr>
            <tr>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_google_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_yahoo_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_msn_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_aol_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-end" id="traffic_lastmonth_total_count"><b><a href="#">&nbsp;</a></b></td>
            </tr>
            <tr>
              <td colspan="6" align="center" style="padding:5px; border-bottom:1px solid #FFFFFF; color:#FF0000">*
                Dashboard data is refreshed  every 5 minutes. </td>
              </tr>
        </table>
          <div align="center" style="color:#FF0000; padding:5px">
           <table border="0" align="center" cellpadding="0" cellspacing="0">
                        <tr>
                          <td><img src="images/graph-icon.jpg" width="16" height="15"></td>
                          <td style="padding-left:6px;"><a href="supertracker.php">view
                              full report</a></td>
                        </tr>
                    </table> </div></td>
      </tr></table></div>
		</div>
				<div class="smallArticle" dragableBox="true" id="article12">
			<div style="width:283px; overflow-x:hidden"><table width="283" border="0" cellspacing="0" cellpadding="0">
                     <tr>
        <td colspan="3" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
	  <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
                </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
                     </tr>
                     <tr>
                       <td width="261" style="height:20px; background-color:#6295FD; font:bold 12px arial; color:#FFFFFF;">&nbsp; Banner
                         &amp; Referral Summary:</td>
                       <td width="22" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br>')" onMouseout="hideddrivetip()"> </div></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
                     </tr>
                    <tr>
                       <td colspan="2" style="padding-top:1px; background-color:#DEEAF8; height:20px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                         <tr>
                           <td><span style="font:bold 12px arial; color:#0B2D86;">&nbsp; <?=date('F Y')?></span></td>
                         </tr>
</table></td>
                    </tr>
                    <tr>
                      <td colspan="2" valign="top" style="padding-top:3px; background-color:#F0F5FB;"><table width="283" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td class="tableinfo_right-btm" style="padding-left:9px">&nbsp;<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br></td>
                        </tr>
                        
                        <!--tr>
                          <td height="23" bgcolor="#EBF1F5" class="tableinfo_right-btm" style="padding-left:9px; font:bold; color:#FF0000">Totals:</td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_avg_total">0</td>
                        </tr-->
                        <tr>
                          <td height="25" align="center" style="padding:5px;"><table border="0" align="center" cellpadding="0" cellspacing="0">
                            <tr>
                              <td><img src="images/graph-icon.jpg" width="16" height="15"></td>
                              <td style="padding-left:6px;"><a href="#">view
                                  full traffic report</a></td>
                            </tr>
                          </table></td>
                        </tr>
                      </table>                      </td>
                    </tr>
                   </table>
    </div>
		</div>	
		
			<div class="smallArticle" dragableBox="true" id="article13">
			<div style="width:283px; overflow-x:hidden"><table width="283" border="0" cellspacing="0" cellpadding="0">
                     <tr>
        <td colspan="3" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
	  <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
                </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
                     </tr>
                     <tr>
                       <td width="261" style="height:20px; background-color:#6295FD; font:bold 12px arial; color:#FFFFFF;">&nbsp; Email Marketing Summary:</td>
                       <td width="22" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br>')" onMouseout="hideddrivetip()"> </div></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
                     </tr>
                    <tr>
                       <td colspan="2" style="padding-top:1px; background-color:#DEEAF8; height:20px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                         <tr>
                           <td><span style="font:bold 12px arial; color:#0B2D86;">&nbsp; <?=date('F Y')?></span></td>
                         </tr>
</table></td>
                    </tr>
                    <tr>
                      <td colspan="2" valign="top" style="padding-top:3px; background-color:#F0F5FB;"><table width="283" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td class="tableinfo_right-btm" style="padding-left:9px">&nbsp;<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br></td>
                        </tr>
                        
                        <!--tr>
                          <td height="23" bgcolor="#EBF1F5" class="tableinfo_right-btm" style="padding-left:9px; font:bold; color:#FF0000">Totals:</td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_avg_total">0</td>
                        </tr-->
                        <tr>
                          <td height="25" align="center" style="padding:5px;"><table border="0" align="center" cellpadding="0" cellspacing="0">
                            <tr>
                              <td><img src="images/graph-icon.jpg" width="16" height="15"></td>
                              <td style="padding-left:6px;"><a href="#">view
                                  full traffic report</a></td>
                            </tr>
                          </table></td>
                        </tr>
                      </table>                      </td>
                    </tr>
                   </table>
    </div>
		</div>
		
			<div class="smallArticle" dragableBox="true" id="article14">
			<div style="width:283px; overflow-x:hidden"><table width="283" border="0" cellspacing="0" cellpadding="0">
                     <tr>
        <td colspan="3" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
	  <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
                </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
                     </tr>
                     <tr>
                       <td width="261" style="height:20px; background-color:#6295FD; font:bold 12px arial; color:#FFFFFF;">&nbsp; Product Feeds Summary:</td>
                       <td width="22" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br>')" onMouseout="hideddrivetip()"> </div></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
                     </tr>
                    <tr>
                       <td colspan="2" style="padding-top:1px; background-color:#DEEAF8; height:20px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                         <tr>
                           <td><span style="font:bold 12px arial; color:#0B2D86;">&nbsp; <?=date('F Y')?></span></td>
                         </tr>
</table></td>
                    </tr>
                    <tr>
                      <td colspan="2" valign="top" style="padding-top:3px; background-color:#F0F5FB;"><table width="283" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td class="tableinfo_right-btm" style="padding-left:9px">&nbsp;<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br></td>
                        </tr>
                        <tr>
                          <td height="25" align="center" style="padding:5px;"><table border="0" align="center" cellpadding="0" cellspacing="0">
                            <tr>
                              <td><img src="images/graph-icon.jpg" width="16" height="15"></td>
                              <td style="padding-left:6px;"><a href="#">view
                                  full traffic report</a></td>
                            </tr>
                          </table></td>
                        </tr>
                      </table>                      </td>
                    </tr>
                   </table>
    </div>
		</div>
		
		<div class="clear" id="clear"></div>		
	</div>
	
	<input type="button" value="Save" onclick="saveData()">
</div>
<!-- REQUIRED DIVS -->
<div id="insertionMarker">
	<img src="images/marker_top.gif">
	<img src="images/marker_middle.gif" id="insertionMarkerLine">
	<img src="images/marker_bottom.gif">
</div>
<!-- END REQUIRED DIVS -->

<script language="JavaScript">

setBreadcrumb(Array({title:'Executive Dashboard',link:document.location}));

var trafficPieCurrentUrl;

function setBox(id,val) {
  var box=$(id);
  if (!box) return false;
  box.innerHTML=val;
  return true;
}

function statsFeed(req) {
  var data=parseXMLFeed(req);
  if (!data) return;
  if (data.traffic) trafficFeed(data.traffic);
  if (data.sales) salesFeed(data.sales);
  if (data.ppc) ppcFeed(data.ppc);
}

function statsRequest(lst) {
  var feeds=new Array();
  for (var f in lst) feeds[feeds.length]=f+'='+(lst[f].join?lst[f].join(','):lst[f]);
  if (!feeds.length) return;
  new ajax('stats_feed.xml.php?'+feeds.join('&'), { onComplete: statsFeed });
}

function populateFields(prefix,sections,subs,data,addall) {
  for (var range in data) if (data[range]) {
    var sec;
    var total={};
    for (var sub in subs) total[sub]=0;
    for(var si=0;sec=sections[si];si++) {
      var displ=addall;
      for (var sub in subs) {
        var val=(data[range][sec] && data[range][sec][sub])?data[range][sec][sub]:subs[sub];
        if (setBox(prefix+range+'_'+sec+'_'+sub,val)) displ=true;
      }
      if (displ && data[range][sec] && !data[range].total) {
        for (var sub in subs) {
          var val=Number(data[range][sec][sub]);
          if (!isNaN(val)) total[sub]+=val;
	}
      }
    }
    if (!data[range].total) for (var sub in subs) setBox(prefix+range+'_total_'+sub,total[sub]);
  }
}

function trafficFeed(data) {
  populateFields('traffic_',trafficSources,{count:0},data);
  if (data.thismonth) {
    var source;
    var total=0;
    for(var source in data.thismonth) if (!isNaN(data.thismonth[source].count)) total+=Number(data.thismonth[source].count);
    var pie=new Array();
    for(var si=0;source=trafficSources[si];si++) {
      var box=$('traffic_thismonth_'+source+'_percent');
      var val=data.thismonth[source]?Number(data.thismonth[source].count)*100/total:0;
      if (isNaN(val)) val=0;
      if (box) box.innerHTML=val.toFixed(1);
      if (val) pie[pie.length]=trafficPieColors[si]+':'+Math.floor(val*10);
    }
    var img=$('traffic_pie');
    if (img) {
      pieurl=trafficPieUrl+pie.join(',');
      if (trafficPieCurrentUrl!=pieurl) trafficPieCurrentUrl=img.src=pieurl;
    }
  }
}

function salesFeed(data) {
  populateFields('sales_',salesSections,{count:0,amount:0},data);
}

function ppcFeed(data) {
  for (var range in data) {
    var sec;
    var total;
    for (sec in data[range]) {
      if (!total) total={clicks:0,conv:0,cost:0.0};
      for (var t in total) total[t]+=Number(data[range][sec][t]);
    }
    data[range].total=total;
    for (sec in data[range]) {
      var s=data[range][sec];
      s.clickcost=Number(s.clicks)?Number(s.cost/s.clicks).toFixed(2):'-';
      s.convcost=Number(s.conv)?Number(s.cost/s.conv).toFixed(2):'-';
      s.convrate=Number(s.clicks)?Number(s.conv/s.clicks*100).toFixed(1):'-';
    }
  }
  populateFields('ppc_',ppcSources,{clicks:'n/a',conv:'n/a',cost:'n/a',convrate:'n/a',convcost:'n/a',clickcost:'n/a'},data);
}


var statsCheckInterval=10;
var statsRefreshed=new Array();

function refreshData() {
  var refr={};
  var st;
  for(var i=0;st=statsList[i];i++) {
    var rf=false;
    if (statsRefreshed[i]==undefined) {
      rf=true;
      statsRefreshed[i]=st.interval;
    } else if (statsRefreshed[i]<=0) {
      rf=st.interval>0;
      statsRefreshed[i]+=st.interval;
    }
    statsRefreshed[i]-=statsCheckInterval;
    if (rf) {
      for (f in st.stats) {
        if (!refr[f]) refr[f]=new Array();
	for (var j=0;st.stats[f][j];j++) refr[f][refr[f].length]=st.stats[f][j];
      }
    }
  }
  statsRequest(refr);
  setTimeout("refreshData()",statsCheckInterval*1000);
}

refreshData();
</script>
</body>
</html>
