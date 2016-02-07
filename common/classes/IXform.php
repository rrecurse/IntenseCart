<?
class IXform {
  function renderTag($items) {
    return '<'.join(' ',$items).'>';
  }

  function field($defs,$val,$extra) {
    switch ($defs['type']) {
      case 'select':
      case 'textarea':
      case 'radio':
        $sp=$defs['separator'];
	if (!isset($sp)) $sp=' ';
        foreach ($this->listValues($defs) AS $optn) $rs[]=$this->tag(Array('input','type="radio"',($optn['id']==$val?'checked':NULL),),$extra).htmlspacialchars($val['text']);
	return join($sp,$rs);
      case 'checkbox':
      default:
    }
    
  }

}
?>