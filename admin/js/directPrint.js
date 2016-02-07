/*

*/

;
var directPrint = function () {
	 var chr = function(i) {
		 return String.fromCharCode(i);
     }
	 
	var allowComponents = {
		formFeed 		: chr(12),
		lineFeed 		: chr(10),
		reverseFeed 	: chr(27) + chr(74),
		newLine 		: '\n',
		CR 				: '\r',
		init 			: chr(27) + chr(64),
		bold 			: chr(27) + chr(69),
		regular 		: chr(27) + chr(70),
		condensed 		: chr(15),
		noCondensed 	: chr(18),
		bigFont 		: chr(27) + 'w1' + chr(27) + 'W1',
		noBigFont 		: chr(27) + 'w0' + chr(27) + 'W0',
		vSpacing6LPI 	: chr(27) + chr(50),
		vSpacing8LPI 	: chr(27) + chr(48),
		/* Box drawing component */
		drawLine 		: chr(196),
		drawDLine 		: chr(205),
		topLeft 		: chr(218),
		topRight 		: chr(191),
	 	vLine 			: chr(33),
		vIntersectR 	: chr(180),
		vIntersectL 	: chr(195),
		vIntersectTC 	: chr(194),
		intersect	 	: chr(197),
		vIntersectBC 	: chr(193),
		bottomLeft 		: chr(192),
		bottomRight 	: chr(217),
	}
	var whiteSpaceChar = ' ';
	var applet;
	var printerName = "LX300";
	var result = '';
	var isError = false;
	var errorMessage = '';
	var self = this;
	
	var _detectApplet = function (name) {
		var _applet = $(name);
		var applet1;
		if ((_applet.tagName == 'APPLET') && (_applet.length == 1)) {applet1 = _applet;}
		else if($('applet[name="jZebra"]').length == 1) {applet1 = $('applet[name="jZebra"]');}
		else if($('applet').length == 1) {applet1 = $('applet');}
		if ((applet1 != null) && applet1[0] != null) applet = applet1[0];
		if (!applet || applet == undefined) return false;
		else return true;
	}
	
	var _detectPrinter = function () {
		if(applet == null) _detectApplet();
		if (applet != null) {
			applet.findPrinter(printerName);
			while (!applet.isDoneFinding()) {
				// Wait
			}
			var ps = applet.getPrintService();
			if (ps == null) var info="Printer is not ready";
			else var info="Printer \"" + ps.getName() + "\" is ready";
		}
		else
			var info="Java Runtime is not ready!";
		window.setTimeout(_detectPrinter,5000);
		return info;
	}

	var _findPrinters = function(name) {
		if(applet == null) _detectApplet();
		if (applet != null) {
			applet.findPrinter(",");
		}
	}
	
	var _getPrinterList = function() {
		if(applet == null) _detectApplet();
		if ( (applet != null) && (applet.isDoneFinding()) ) {
			return applet.getPrinters();
		}
	}
	
	var _printFile = function (file) {
		if (applet == null) _detectApplet();
		if (applet != null) {

			if (file.indexOf("://") == -1) {
				var loc = window.location;
				var pathName = loc.pathname.substring(0, loc.pathname.lastIndexOf('/') + 1);
				url = loc.href.substring(0, loc.href.length - ((loc.pathname + loc.search + loc.hash).length - pathName.length));
				file = url + file;
			}
			_detectPrinter();
			done = false;
			while (!done) {
				done = _isPrintFinished();
			}
			applet.appendFile(file);
			applet.print();
		}
	}
	
	var _doPrint = function (txt) {
		if (applet == null) _detectApplet();
		if (applet != null) {
			if (!txt) txt = result;
			_detectPrinter();
			done = false;
			while (!done) {
				done = _isPrintFinished();
			}
			applet.append(txt);            
			applet.print();
		}
	
	}
	
	var _isPrintFinished = function () {
		if(applet == null) _detectApplet();
		if (applet != null) {
			return applet.isDonePrinting();
		} else return false;
	}
	
	var _isFindFinished = function () {
		if(applet == null) _detectApplet();
		if (applet != null) {
			return applet.isDoneFinding();
		} else return false;
	}
	
	var _getPrinter = function () {return printerName;}
	var _setPrinter = function (name) {printerName = name;}
	
	var _getEncoding = function () {
		if (applet == null) _detectApplet();
		return applet.getEncoding();
	}
	
	var _setEncoding = function (name) {
		if (applet == null) _detectApplet();
		applet.setEncoding(name);
	}
	
	var _init = function (selector, extComponents, charset) {
		result = '';
		if (!charset) charset = 'windows-1252';
		_setEncoding(charset);
		$.extend(allowComponents, extComponents);
		
		$(selector).each(function () {
			$class = $(this).attr('class').split(' ');
			$componentResult = '';
			$str = $(this).text();
			for (i in $class) {
				_cl = $class[i].split(/\[|,|\]/);
				if (_cl[0] in allowComponents) {
					$componentResult += _applyComponent(_cl);
				} else if (_cl[0] == 'format') { //special class
					$str = _applyFormatting($(this).text(), _cl);
				}
			}
			
			result += $componentResult + $str;
		});
	}
	
	var _addComponents = function (newC) {
		$.extend(allowComponents, newC);
	}
	
	//for formatting text
	var _applyFormatting = function (txt, cl) {
		if (!cl[1]) {
			isError = true;
			errorMessage = 'Text format is not specified!';
			return false;
		}
		if (!cl[2]) {
			isError = true;
			errorMessage = 'Text length is not specified!';
			return false;
		}
		txt = txt.trim();
		var txtlen = txt.length;
		var len = parseInt(cl[2]);
		var dLength = len - txtlen;
		var lSpace = 0;
		var rSpace = 0;
		if (txtlen >= len) return (txt.substr(0, len)); //truncated if text is longer than available space
		switch (cl[1]) {
			case 'left' :
				rSpace = dLength + 1;
				break; 
			case 'right' :
				lSpace = dLength + 1;
				break; 
			case 'center' :
				lSpace = Math.floor(dLength / 2) + 1;
				rSpace = dLength - lSpace + 2;
				break; 
		}
		return ( Array(lSpace).join(whiteSpaceChar) + txt + Array(rSpace).join(whiteSpaceChar));
	}
	
	var _applyComponent = function (cl) {
		_component = allowComponents[cl[0]];
		var ret = _component;
		var repeat = false;
		var max = false;
		if (cl.length > 1)
			for (var i = 1; i < cl.length; i++) {
				switch (cl[i].trim()) {
					case 'repeat' :
						if (!cl[i + 1]) {
							isError = true;
							errorMessage = 'Component length is not specified!';
							return false;
						}
						repeat = parseInt(cl[i + 1]);
						break;
					case 'max' :
						if (!cl[i + 1]) {
							isError = true;
							errorMessage = 'Component length is not specified!';
							return false;
						}
						max = parseInt(cl[i + 1]);
						break;
				}
			}
		if (repeat) ret += Array(repeat).join(_component);
		if (max) ret = ret.substr(0, max);
		return ret;
	}
	
	var _getResult = function () {
		if (!isError) {
			return result;
		} else {
			alert(errorMessage);
			return false;
		}
	}
	
	return {
		init: _init,
		getResult: _getResult,
		detectPrinter: _detectPrinter,
		findPrinters: _findPrinters,
		getPrinterList: _getPrinterList,
		getPrinterName: _getPrinter,
		setPrinterName: _setPrinter,
		detectApplet: _detectApplet,
		printFile: _printFile,
		print: _doPrint,
		isPrintFinished: _isPrintFinished,
		isFindFinished: _isFindFinished,
		getEncoding: _getEncoding,
		setEncoding: _setEncoding,
		chr: chr,
		addComponents: _addComponents,
	}
} ();
