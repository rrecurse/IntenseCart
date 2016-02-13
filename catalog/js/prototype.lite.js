/*  Prototype JavaScript framework
 *  (c) 2005 Sam Stephenson <sam@conio.net>
 *  Prototype is freely distributable under the terms of an MIT-style license.
 *  For details, see the Prototype web site: http://prototype.conio.net/
 *
 *  POST EXAMPLE: 
 *  new ajax ('sleep.php', {postBody: 'sleep=3', update: $('myelementid'), onComplete: myFunction});
 *  function myFunction(request){
 *    alert(request.responseText);
 *  }
 *  GET EXAMPLE: 
 *  new ajax ('sleep.php?sleep=3', {method: 'get', update: $('myelementid'), onComplete: myFunction});
 *  function myFunction(request){
 *    alert(request.responseText);
 *  }
/*--------------------------------------------------------------------------*/

var Class = {
	create: function() {
		return function() {
			this.initialize.apply(this, arguments);
		}
	}
}

Object.extend = function(destination, source) {
	for (property in source) destination[property] = source[property];
	return destination;
}

Function.prototype.bind = function(object) {
	var __method = this;
	return function() {
		return __method.apply(object, arguments);
	}
}

Function.prototype.bindAsEventListener = function(object) {
var __method = this;
	return function(event) {
		__method.call(object, event || window.event);
	}
}

function $() {
	if (arguments.length == 1) return get$(arguments[0]);
	var elements = [];
	$c(arguments).each(function(el){
		elements.push(get$(el));
	});
	return elements;

	function get$(el){
		if (typeof el == 'string') el = window.document.getElementById(el);
		return el;
	}
}

if (!window.Element) var Element = new Object();

Object.extend(Element, {
	remove: function(element) {
		element = $(element);
		element.parentNode.removeChild(element);
	},

	hasClassName: function(element, className) {
		element = $(element);
		if (!element) return;
		var hasClass = false;
		element.className.split(' ').each(function(cn){
			if (cn == className) hasClass = true;
		});
		return hasClass;
	},

	addClassName: function(element, className) {
		element = $(element);
		Element.removeClassName(element, className);
		element.className += ' ' + className;
	},
  
	removeClassName: function(element, className) {
		element = $(element);
		if (!element) return;
		var newClassName = '';
		element.className.split(' ').each(function(cn, i){
			if (cn != className){
				if (i > 0) newClassName += ' ';
				newClassName += cn;
			}
		});
		element.className = newClassName;
	},

	cleanWhitespace: function(element) {
		element = $(element);
		$c(element.childNodes).each(function(node){
			if (node.nodeType == 3 && !/\S/.test(node.nodeValue)) Element.remove(node);
		});
	},

	find: function(element, what) {
		element = $(element)[what];
		while (element.nodeType != 1) element = element[what];
		return element;
	}
});

var Position = {
	cumulativeOffset: function(element) {
		var valueT = 0, valueL = 0;
		do {
			valueT += element.offsetTop  || 0;
			valueL += element.offsetLeft || 0;
			element = element.offsetParent;
		} while (element);
		return [valueL, valueT];
	}
};

document.getElementsByClassName = function(className) {
	var children = document.getElementsByTagName('*') || document.all;
	var elements = [];
	$c(children).each(function(child){
		if (Element.hasClassName(child, className)) elements.push(child);
	});  
	return elements;
}

//useful array functions
Array.prototype.each = function(func){
	for(var i=0;ob=this[i];i++) func(ob, i);
}

function $c(array){
	var nArray = [];
	for (i=0;el=array[i];i++) nArray.push(el);
	return nArray;
}

ajax = Class.create();
ajax.prototype = {
	initialize: function(url, options){
		this.transport = this.getTransport();
		this.postBody = options.postBody || '';
		this.postForm = options.postForm || null;
		this.method = options.method || 'post';
		this.onComplete = options.onComplete || null;
		this.onCompleteArg = options.onCompleteArg || null;
		this.update = $(options.update) || null;
		this.evalScripts = options.evalScripts || null;
		this.request(url);
	},

	request: function(url){
		this.transport.open(this.method, url, true);
		this.transport.onreadystatechange = this.onStateChange.bind(this);
		if (this.method == 'post') {
			if (this.postForm) {
			  var e;
			  var flds=[];
			  for (var i=0;e=this.postForm.elements[i];i++) {
			    switch (e.type) {
			      case 'select-multiple':
			        for (var j=0;e.options[j];j++) if (e.options[j].selected) flds.push(escape(e.name)+'='+escape(e.options[j].value));
				break;
			      case 'checkbox': case 'radio':
			        if (!e.checked) break;
			      default:
			        flds.push(escape(e.name)+'='+escape(e.value));
			    }
			  }
			  this.postBody=flds.join('&');
			}
			this.transport.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			//if (this.transport.overrideMimeType) this.transport.setRequestHeader('Connection', 'close');
		}
		this.transport.send(this.postBody);
	},

	onStateChange: function(){
		if (this.transport.readyState == 4 && this.transport.status == 200) {
			if (this.onComplete) 
				setTimeout(function(){this.onComplete(this.transport,this.onCompleteArg);}.bind(this), 10);
			if (this.update)
				setTimeout(function() {
				  this.update.innerHTML = this.transport.responseText;
				  var sc=this.transport.responseText.match(/<eval\s+([^>]|\"[^\"]*\")+>/gi);
				  if (sc) for (var i=0;sc[i];i++) eval(sc[i].replace(/^.*?code=\"?/,'').replace(/".*/,''));
				  var sc=this.update.getElementsByTagName('script');
				  if (sc) for (var i=0;sc[i];i++) {
				    if (sc[i].src) {
				      var scn=document.createElement('script');
				      scn.type='text/javascript';
				      scn.src=sc[i].src;
				      document.getElementsByTagName('head')[0].appendChild(scn);
				    } else window.setTimeout(sc[i].innerHTML,10);
				  }
				  if (window.contentChanged) window.contentChanged();
				}.bind(this), 10);
			this.transport.onreadystatechange = function(){};
		}
	},

	getTransport: function() {
		if (window.ActiveXObject) return new ActiveXObject('Microsoft.XMLHTTP');
		else if (window.XMLHttpRequest) return new XMLHttpRequest();
		else return false;
	}
};
