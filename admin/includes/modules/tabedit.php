<?
function renderTabEdit($varname,$fld,$cont) {
  $tabs=Array();
  if (preg_match_all('|<newtab>(.*?)</newtab>|s',$cont,$ts)) {
    foreach ($ts[1] AS $t) {
      preg_match('|<tabname>(.*?)</tabname>|s',$t,$tn);
      preg_match('|<tabtext>(.*?)</tabtext>|s',$t,$tc);
      $tabs[]=Array('tab'=>$tn[1],'content'=>$tc[1]);
    }
  } else $tabs[]=Array('tab'=>NULL,'content'=>$cont);
  ?> <div style="padding:5px"><a href="" onClick="<?=$varname?>.addTab(); return false;"><img src="/admin/includes/languages/english/images/buttons/button_newtab.gif" border="0" alt=""></a></div>
<div id="<?=$varname?>" class="tabEdit">
<table border="0" cellspacing="5" cellpadding="0"><tr style="<?=isset($tabs[0]['tab'])?'':'display:none;'?>">
<? foreach ($tabs AS $idx=>$tab) { ?>
<td class="<?=$idx?'tabClosed':'tabOpen'?>" onClick="<?=$varname?>.switchTab(this);"><?=$tab['tab']?></td>
<? } ?>
</tr></table>

<? foreach ($tabs AS $idx=>$tab) { ?>
<div style="<?=$idx?'display:none;':''?>"><div style="<?=isset($tabs[0]['tab'])?'':'display:none;'?>">Tab Name: <?=tep_draw_input_field($fld.'[tab][]',$tab['tab'],'onChange="'.$varname.'.setTitle(this,this.value)"')?> &nbsp; 
<a href="" onClick="if (window.confirm('Delete this tab?')) <?=$varname?>.deleteTab(this); return false;"><img src="/admin/images/icon_delete.png" alt="Delete this Tab?" title="Delete this tab?" border="0"> </a>
</div>
<?=tep_draw_textarea_field($fld.'[content][]','soft',70,15,$tab['content'])?></div>
<? } ?>
</div>
<script type="text/javascript">
function tabObject(tabs) {
  return {
    tabs:tabs,
    getTabTitles: function() {
      var tr=this.tabs.getElementsByTagName('tr')[0];
      var tds=[];
      for (var td=tr.firstChild;td;td=td.nextSibling) if (td.tagName=='TD') tds.push(td);
      return tds;
    },
    getTabContents:function() {
      var divs=[];
      for (var div=this.tabs.firstChild;div;div=div.nextSibling) if (div.tagName=='DIV') divs.push(div);
      return divs;
    },
    switchTab:function(idx) {
      idx=this.tabIndex(idx);
      var tds=this.getTabTitles();
      var divs=this.getTabContents();
      if (idx>=tds.length) idx=tds.length-1;
      for (var i=0;tds[i];i++) {
        tds[i].className=i==idx?'tabOpen':'tabClosed';
        tds[i].parentNode.style.display='';
        divs[i].style.display=i==idx?'':'none';
      }
    },
    tabIndex:function(obj) {
      if (!isNaN(obj)) return obj;
      var tds=this.getTabTitles();
      var divs=this.getTabContents();
      var idx;
      while (obj && obj!=this.tabs) {
        for (var i=0;tds[i];i++) if (tds[i]==obj) return i;
        for (var i=0;divs[i];i++) if (divs[i]==obj) return i;
	obj=obj.parentNode;
      }
    },
    setTitle:function(idx,title) {
      idx=this.tabIndex(idx);
      if (title==null || title.match(/^\s*$/)) title='Tab '+(idx+1);
      this.getTabTitles()[idx].innerHTML=title;
      this.getTabContents()[idx].getElementsByTagName('input')[0].value=title;
    },
    addTab:function() {
      var tds=this.getTabTitles();
      var divs=this.getTabContents();
      var idx=tds.length;
      if (tds[0].parentNode.style.display=='none') {
        tds[0].parentNode.style.display='';
        divs[0].getElementsByTagName('div')[0].style.display='';
	idx--;
      } else {
        var newdiv;
        tds[0].parentNode.insertBefore(tds[0].cloneNode(true),null);
        divs[0].parentNode.insertBefore((newdiv=divs[0].cloneNode(true)),null);
	newdiv.getElementsByTagName('textarea')[0].innerHTML='';
      }
      this.setTitle(idx);
      this.switchTab(idx);
    },
    deleteTab:function(idx) {
      idx=this.tabIndex(idx);
      var tds=this.getTabTitles();
      var divs=this.getTabContents();
      if (!tds[idx]) return false;
      if (tds.length<=1) {
        tds[0].parentNode.style.display='none';
	var cdiv=divs[0].getElementsByTagName('div')[0];
	cdiv.style.display='none';
	cdiv.getElementsByTagName('input')[0].value='';
	return true;
      }
      tds[idx].parentNode.removeChild(tds[idx]);
      divs[idx].parentNode.removeChild(divs[idx]);
      this.switchTab(idx);
      return true;
    }
  };
}
</script>

<script type="text/javascript">
  var <?=$varname?>=new tabObject($('<?=$varname?>'));
</script>
  <?
}

function collectTabEdit($post) {
  if (!is_array($post)) return $post;
  if (!isset($post['tab'][0]) || $post['tab'][0]=='') return $post['content'][0];
  $rs='';
  for ($i=0;isset($post['content'][$i]);$i++) $rs.='<newtab><tabname>'.$post['tab'][$i].'</tabname><tabtext>'.$post['content'][$i].'</tabtext></newtab>';
  return $rs;
}

?>