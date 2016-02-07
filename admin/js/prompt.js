function returnPromptBox(box,val) {
  while (box) {
    if (box.fnReturnPromptBox) break;
    box=box.parentNode;
  }
  if (!box) return;
  box.fnReturnPromptBox(val);
}

function createPromptBox(blk,box,flds,func) {
	if ($('popupContents')) return false;
	var blkpos=findPos(blk);
	var blkwd=blk.scrollWidth;
	var blkht=blk.scrollHeight;
 	// cover the attribute manager with a semi tranparent div
 	newBit = blk.appendChild(document.createElement("div"));
 	newBit.id = "blackout";
 	newBit.style.height = blkht;
 	newBit.style.width = blkwd;
 	newBit.style.left = blkpos.x;
 	newBit.style.top = blkpos.y;
	newBit.style.position='absolute';
 	
 	// hide select boxes (for IE)
	showHideSelectBoxes(blk,'hidden'); 
	
	// create a popup shaddow
	popupShadow = blk.appendChild(document.createElement("div"));
	popupShadow.id = "popupShadow";
	
	// create the contents div
	popupContents = blk.appendChild(box.cloneNode(true));
	popupContents.id = "popupContents";
	
	for (var fld in flds) setField(popupContents,fld,flds[fld]);
	popupContents.style.position = "absolute";
	popupContents.style.visibility = "hidden";
	popupContents.style.display = "block";
	
	// work out the center postion for the box
	var leftPos = (((blkwd - popupContents.scrollWidth) / 2) + blkpos.x);
	var topPos = (((blkht - popupContents.scrollHeight) / 2) + blkpos.y);
	
	// position the box
	popupContents.style.left = leftPos;
	popupContents.style.top = topPos;
	popupContents.style.visibility = "visible";
	
	// size the shadow
	popupShadow.style.width = popupContents.scrollWidth;
	popupShadow.style.height =popupContents.scrollHeight;
	
	// position the shadow
	popupShadow.style.left = leftPos+6;
	popupShadow.style.top = topPos+6;

	// if the form has any inputs focus on the first one
	inputs = popupContents.getElementsByTagName("input");
	if (inputs[0]) inputs[0].focus();

	blk.fnReturnPromptBox=function(val) { removePromptBox(this); if (func) func(val); }
	
	return false;
}


function removePromptBox(blk) {
	blk.removeChild($("popupContents"));
	blk.removeChild($("popupShadow"));
	blk.removeChild($("blackout"));
	showHideSelectBoxes(blk,'visible');	
}

function findPos(obj) {
	var pos={x:0,y:0};
	if (obj.offsetParent){
		while (obj.offsetParent) {
			pos.x += obj.offsetLeft;
			pos.y += obj.offsetTop;
			obj = obj.offsetParent;
		}
	}
	else {
		if (obj.x) pos.x += obj.x;
		if (obj.y) pos.y += obj.y;
	}
	return pos;
}

function showHideSelectBoxes(blk,vis) {
	var selects = blk.getElementsByTagName("select");
	for(var i = 0; i < selects.length; i++) 
		selects[i].style.visibility = vis;
	return false;
}
