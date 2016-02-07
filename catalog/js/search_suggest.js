  var autoSuggestList=[];
  var autoSuggestLoaded=null;
  var autoSuggestOptns={};
  function setAutoSuggest(line,blkid,optns) {
    var blk=$(blkid);
    blk.autoSuggestPhrase=line;
    if (optns) autoSuggestOptns=optns;
    if (blk.autoSuggestTimeout) clearTimeout(blk.autoSuggestTimeout);
    blk.autoSuggestTimeout=setTimeout('showAutoSuggest(\''+blkid+'\')',200);
  }
  function showAutoSuggest(blkid) {
    var blk=$(blkid);
    var rx=[];
    var rxn=[];
    var sl=blk.autoSuggestPhrase.split(/\W+/);
    for (var i=0;i<sl.length;i++) {
      if (sl[i]!='') {
        rx.push(new RegExp('\\b'+sl[i],'i'));
        rxn.push(new RegExp('\\b0*'+sl[i].match(/^0*(\w+)/)[1],'i'));
      }
    }
    var html='';
    if (rx.length) {
      if (autoSuggestLoaded==null) {
        autoSuggestLoaded=false;
	loadAutoSuggest(blkid);
	return false;
      }
      if (!autoSuggestLoaded) return null;
      var sg;
      var rows=Number(autoSuggestOptns.max);
      if (!rows) rows=15;
      for (var i=0;sg=autoSuggestList[i];i++) {
        var ok=false;
        if (sg.name && rx.length) {
	  ok=true;
	  for (var j=0;rx[j];j++) if (!sg.name.match(rx[j])) { ok=false; break }
	}
	if (!ok && sg.code && rxn.length) {
	  ok=true;
	  for (var j=0;rxn[j];j++) if (!sg.code.match(rxn[j])) { ok=false; break }
	}
	if (ok) {

html+='<tr class="autoSuggestOut" onMouseover="this.className=\'autoSuggestOver\'" onMouseout="this.className=\'autoSuggestOut\'" onClick="document.location=\''+sg.url+'\'"><td class="autosuggestImage">'+(sg.img?'<img src="'+sg.img+'" width="'+autoSuggestOptns.img_width+'" height="'+autoSuggestOptns.img_height+'">':'&nbsp;')+'</td><td class="autosuggestText">&nbsp; <a href="'+sg.url+'">'+sg.name+'</a></td></tr>';


	 // html+='<tr onMouseover="this.style.background=\'#FFFFC4\'" onMouseout="this.style.background=\'#FFFFFF\'" onClick="document.location=\''+sg.url+'\'"><td class="autosuggestImage">'+(sg.img?'<img src="'+sg.img+'" width="'+autoSuggestOptns.img_width+'" height="'+autoSuggestOptns.img_height+'">':'&nbsp;')+'</td><td class="autosuggestText">&nbsp;<a href="'+sg.url+'"> '+sg.name+'</a></td></tr>';
	  if (--rows<=0) break;
	}
      }
    }
    if (html) {
      blk.innerHTML='<div style="autosuggestDiv"><table class="autosuggestTable">'+html+'</table></div>';
      blk.style.display='block';
    } else blk.style.display='none';
  }

  function loadAutoSuggest(blkid) {
    new ajax('/search_suggest.xml.php',{onComplete:function(req) {
      var xml=req.responseXML;
      if (!xml) { alert('error'); return; }
      xml=xml.getElementsByTagName('suggest')[0];
      if (!xml) { alert(req.responseXML); return; }
      var items=xml.getElementsByTagName('item');
      for (var i=0;items[i];i++) {
        var sg={};
	for (var e=items[i].firstChild;e;e=e.nextSibling) if (e.tagName) sg[e.tagName]=e.firstChild?e.firstChild.nodeValue:null;
        autoSuggestList.push(sg);
      }
      autoSuggestLoaded=true;
      if (blkid) showAutoSuggest(blkid);
    }
    });
  }

	// # prevent the search form submission if null or default value set

	jQuery(document).ready(function($) {
		jQuery.noConflict();

		jQuery('form[name="quick_find"]').submit(function(e) {

			var kw = jQuery("#autocompleteOFF").val();
		
			if(kw == 'Product Search:' || !kw.trim() || kw == ' ' || kw.length === 0) {
				e.preventDefault();
			} 
		});

		jQuery('form[name="advanced_search"]').submit(function(e) {

			var kww = jQuery('#keywords').val();

			if(!kww || kww == '' || kww == 'Product Search:' || kww.length < 2){
				e.preventDefault();
			}
		});

	});