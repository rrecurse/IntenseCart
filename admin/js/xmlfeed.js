// By MegaJim

function parseXMLFeed(req) {
  var xml=req.responseXML;
  if (!xml) return undefined;
  return parseXMLData(xml.documentElement);
}

function parseXMLData(e) {
  var parse,txt;
  for(var ep=e.firstChild;ep;ep=ep.nextSibling) {
    if (ep.data!=undefined) {
      txt=ep.data;
    } else {
      var name=ep.nodeName;
      if (!parse) parse={};
      parse[name]=parseXMLData(ep);
    }
  }
  if (txt!=undefined) {
    if (!parse) return txt;
//    parse['_']=txt;
  }
  return parse?parse:{};
}
