<?
class blk_text_format extends IXblock {
  function render(&$body) {
    ob_start();
    $this->renderBody($body);
    $cont=preg_replace('|<.*?>|',' ',ob_get_contents());
    ob_end_clean();
    echo $this->formatText($cont);
  }
  function formatText($cont) {
    if ($this->args['maxwords'] && preg_match('|((\S+\s+){'.floor($this->args['maxwords']).'}).*|',$cont,$contp)) {
      $this->tf=1;
      $cont=trim($contp[1]);
    }
    if ($this->args['maxchars'] && strlen($cont)>$this->args['maxchars']) {
      $this->tf=1;
      $cont=substr($cont,1,$this->args['maxchars']);
    }
    return $cont;
  }
}
?>