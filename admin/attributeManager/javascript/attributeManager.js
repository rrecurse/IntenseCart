var placeHolderDiv;
var url = 'attributeManager/attributeManager.php';
var debug = false;

var amRequester = new Requester();

function attributeManagerInit() {
	if(amRequester.isAvailable()) 
		amRefresh(true);
}

function getElement(id) {
	return document.getElementById(id);
}

function getDropDownValue(id) {
	return getElement(id).value;
}

//------------------------------------------------------------------<< Common Stuff
function amSendRequest(requestString,functionName, refresh, target) {
	
	var arRequestString = new Array;
	
	if('' != requestString)
		arRequestString.push(requestString);
	
	if('' != productsId) 
		arRequestString.push('products_id='+productsId);
		
	if('' != pageAction)
		arRequestString.push('pageAction='+pageAction);
		
	if('' != sessionId)
		arRequestString.push(sessionId);

	if(refresh == false) 
		amRequester.setAction(amEmpty);	
	else 
		amRequester.setAction((((null == functionName) || ('' == functionName)) ? amUpdateContent : functionName));
	
	if(null == target) {
		amRequester.setTarget('attributeManager');
	}
	else {
		amRequester.setTarget(target);
		arRequestString.push('target='+target);
	}

	requestString = arRequestString.join('&');
	
	amRequester.loadURL(url, requestString);
	
	return false;
}


function amEmpty(){}

function amReportError(request) {
	alert('Sorry. There was an error.');
}

function amRefresh(bolFirstCall) {
	var rString = (!bolFirstCall) ? 'amAction=refresh' : '';
	amSendRequest(rString);
	return false;
}

function amUpdateContent(id) {
	getElement(amRequester.getTarget()).innerHTML = amRequester.getText();
	amRestoreDisplayState();
}

//------------------------------------------------------------------<< page Actions


function amSetInterfaceLanguage(languageId) {
	amSendRequest('amAction=setInterfaceLanguage&language_id='+languageId);
	return false;
}

function amUpdate(optionId, optionValueId) {
	amSendRequest('amAction=update&option_id='+optionId+'&option_value_id='+optionValueId+'&price='+getDropDownValue('price_'+optionValueId)+'&prefix='+getDropDownValue('prefix_'+optionValueId),'',false);
	getElement('price_'+optionValueId).blur();
	return false;
}

function amAddOption() {
    var trackStock = getDropDownValue('track_stock');
	amSendRequest('amAction=addOption&options='+getAllPromptTextValues()+'&track_stock='+trackStock,'',true,'newAttribute');
	removeCustomPrompt();
	return false;
}

function amAddOptionValue(){
	var optionId = getDropDownValue('optionDropDown')
	amSendRequest('amAction=addOptionValue&option_values='+getAllPromptTextValues()+'&option_id='+optionId,'',true,'newAttribute');
	removeCustomPrompt();
	return false;
}

function amAddAttributeToProduct() {
	var option = getDropDownValue('optionDropDown');
	var optionValue = getDropDownValue('optionValueDropDown');
	var pricePrefix = 	getDropDownValue('prefix_0');
	var price = getDropDownValue('newPrice');
	if(0 == option || 0 == optionValue)
		return false;
	amSendRequest('amAction=addAttributeToProduct&option_id='+option+'&option_value_id='+optionValue+'&prefix='+pricePrefix+'&price='+price);
	return false;
}

function amRemoveOptionFromProduct() {
	amSendRequest('amAction=removeOptionFromProduct&option_id='+getPromptHiddenValue('option_id'));
	return false;
}

function amRemoveOptionValueFromProduct() {
	amSendRequest('amAction=removeOptionValueFromProduct&option_id='+getPromptHiddenValue('option_id')+'&option_value_id='+getPromptHiddenValue('option_value_id'));
	return false;
}

function amAddOptionValueToProduct(optionId) {
	var optionValueId = getDropDownValue('new_option_value_'+optionId);
	if(0 == optionValueId)
		return false;
	amSendRequest('amAction=addOptionValueToProduct&option_id='+optionId+'&option_value_id='+optionValueId,'',true,'currentAttributes');
	return false;
}

function amAddNewOptionValueToProduct() {
	var optionId = getPromptHiddenValue('option_id');
	var optionValues = getAllPromptTextValues();
	amSendRequest('amAction=addNewOptionValueToProduct&option_values='+optionValues+'&option_id='+optionId,'',true,'currentAttributes');
	removeCustomPrompt();
	return false;
}

function amUpdateNewOptionValue(optionId) {
	amSendRequest('amAction=updateNewOptionValue&option_id='+optionId,'',true,'newAttribute');
	return false;
}


function loadTemplate() {
	var templateId = getDropDownValue('template_drop');
	amSendRequest('amAction=loadTemplate&template_id='+templateId);
	removeCustomPrompt();
	resetOpenClosedState();
}

function saveTemplate(){
	var newName = getAllPromptTextValues();
	var templateId = getElement("existing_template").value;
		
	amSendRequest('amAction=saveTemplate&new_template_id='+templateId+'&template_name='+newName,'',true,'topBar');
	removeCustomPrompt();
	return false;	
}

function renameTemplate() {
	var newName = getAllPromptTextValues();
	var templateId = getPromptHiddenValue('template_id');
	amSendRequest('amAction=renameTemplate&template_name='+newName+"&template_id="+templateId,'',true,'topBar');
	removeCustomPrompt();
	return false;	
}

function deleteTemplate() {
	var templateId = getDropDownValue('template_drop');
	amSendRequest('amAction=deleteTemplate&template_id='+templateId,'',true,'topBar');
	removeCustomPrompt();
}


//------------------------------------------------------------------<< custom prompts

function getAllPromptTextValues() {
	var allValues = getElement("popupContents").getElementsByTagName("input");
	var returnArray = new Array;
	for (var i = 0; i < allValues.length; i++) 
		if('text' == allValues[i].type) 
			returnArray.push(allValues[i].id+':'+getElement(allValues[i].id).value);
	return returnArray.join('|');
}

function getPromptHiddenValue(id) {
	if(getElement(id))
		return getElement(id).value;
	else 
		return false;
}

function customPrompt(section,getVars) {
	var requestString = 'amAction=prompt&section='+section
	if(null != getVars)
		requestString += '&gets='+getVars;
	amSendRequest(requestString, createCustomPrompt, true, 'prompt');
	return false;
}

function customTemplatePrompt(section) {
	var templateDrop = getElement('template_drop');
	var templateId = templateDrop.value;
	var templateName = templateDrop.options[templateDrop.selectedIndex].text;
	var requestString = 'amAction=prompt&section='+section+'&gets=template_name:'+templateName+'|'+'template_id:'+templateId;
	
	if(0 != templateId)
		amSendRequest(requestString, createCustomPrompt, true, 'prompt');
	else
		templateDrop.focus();
	
	return false;
}

function createCustomPrompt() {
 	var attributeManager = getElement("attributeManager");
 	var attributeManagerX = findPosX(attributeManager);
 	var attributeManagerY = findPosY(attributeManager)
 	var attributeManagerW = attributeManager.scrollWidth;
 	var attributeManagerH = attributeManager.scrollHeight;
 	
 	// cover the attribute manager with a semi tranparent div
 	newBit = attributeManager.appendChild(document.createElement("div"));
 	newBit.id = "blackout";
 	newBit.style.height = attributeManagerH;
 	newBit.style.width = attributeManagerW;
 	newBit.style.left = attributeManagerX;
 	newBit.style.top = attributeManagerY;
 	
 	// hide select boxes (for IE)
	showHideSelectBoxes('hidden'); 
	
	// create a popup shaddow
	popupShaddow = attributeManager.appendChild(document.createElement("div"));
	popupShaddow.id = "popupShaddow";
	
	// create the contents div
	popupContents = attributeManager.appendChild(document.createElement("div"));
	popupContents.id = "popupContents";
	
	// put the ajax reqest text in the box
	popupContents.innerHTML = amRequester.getText();
	
	// work out the center postion for the box
	leftPos = (((attributeManagerW - popupContents.scrollWidth) / 2) + attributeManagerX);
	topPos = (((attributeManagerH - popupContents.scrollHeight) / 2) + attributeManagerY);
	
	// position the box
	popupContents.style.left = leftPos;
	popupContents.style.top = topPos;
	
	// size the shadow
	popupShaddow.style.width = popupContents.scrollWidth;
	popupShaddow.style.height =popupContents.scrollHeight;
	
	// position the shadow
	popupShaddow.style.left = leftPos+6;
	popupShaddow.style.top = topPos+6;

	// if the form has any inputs focus on the first one
	if(inputs = popupContents.getElementsByTagName("input"))
		inputs[0].focus();
	
	return false;
}



function removeCustomPrompt() {
	getElement("attributeManager").removeChild(getElement("popupContents"));
	getElement("attributeManager").removeChild(getElement("popupShaddow"));
	getElement("attributeManager").removeChild(getElement("blackout"));
	showHideSelectBoxes('visible');	
}

function findPosX(obj) {
	var curleft = 0;
	if (obj.offsetParent){
		while (obj.offsetParent) {
			curleft += obj.offsetLeft
			obj = obj.offsetParent;
		}
	}
	else if (obj.x)
		curleft += obj.x;
	return curleft;
}

function findPosY(obj) {
	var curtop = 0;
	if (obj.offsetParent) {
		while (obj.offsetParent) {
			curtop += obj.offsetTop
			obj = obj.offsetParent;
		}
	}
	else if (obj.y)
		curtop += obj.y;
	return curtop;
}

function showHideSelectBoxes(vis) {
	var selects = getElement('attributeManager').getElementsByTagName("select");
	for(var i = 0; i < selects.length; i++) 
		selects[i].style.visibility = vis;
	return false;
}
//------------------------------------------------------------------<< Display Controls

var openClosedState;
var attributeManagerClosedState = true;
var attributeTemplatesClosedState = true;

function resetOpenClosedState() {
	 openClosedState = new Object()
}
resetOpenClosedState();

function amRestoreDisplayState() {

	// Im sure this is a really bad way to do this but i couldn't figure out another 
	var allTrs = getElement('attributeManager').getElementsByTagName("tr");
	for (var i = 0; i < allTrs.length; i++) {
		
		for(var a in openClosedState) {
			var reg = new RegExp("trOptionsValues_"+a+"$");
			if (reg.test(allTrs[i].id)) {
				if(true == openClosedState[a]) {
					allTrs[i].style.display =  "";
					getElement("show_hide_"+a).src = "attributeManager/images/icon_minus.gif";
				}
				else {
					allTrs[i].style.display =  "none";
					getElement("show_hide_"+a).src = "attributeManager/images/icon_plus.gif";
				}
			}
		}
	}
}

function amShowHideAttributeManager() {
	getElement('attributeManagerAll').style.display = (true == attributeManagerClosedState) ? "none" : "";
	attributeManagerClosedState = (true == attributeManagerClosedState) ? false : true;
	getElement('showHideAll').src = "attributeManager/images/icon_"+ ((true == attributeManagerClosedState) ? "minus.gif" : "plus.gif");
	return false;
}



function amShowHideAllOptionValues(options, show) {
	for(var i =0; i < options.length; i++) {
		openClosedState[options[i]] = !show;
		amShowHideOptionsValues(options[i]);
	}
	return false;
}

function amShowHideOptionsValues(id) {
	var allTrs = getElement('attributeManager').getElementsByTagName("tr");
	for (var i = 0; i < allTrs.length; i++) {
		
		var reg = new RegExp("trOptionsValues_"+id+"$");
		if (reg.test(allTrs[i].id)) 
			allTrs[i].style.display = (true == openClosedState[id]) ? "none" : "";
	}
	if(true == openClosedState[id]){
		getElement("show_hide_"+id).src = "attributeManager/images/icon_plus.gif";
		openClosedState[id] = false;
	}
	else{
		getElement("show_hide_"+id).src = "attributeManager/images/icon_minus.gif";
		openClosedState[id] = true;
	}
	return false;
}



