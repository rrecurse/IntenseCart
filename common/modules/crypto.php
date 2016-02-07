<?
class crypto {
  function crypto() {
    $this->dkey=defined('SITE_CRYPT_KEY')?SITE_CRYPT_KEY:NULL;
  }
  function encrypt($s,$key=NULL) {
    if (!$key) $key=$this->dkey;
    if (!$key) return NULL;
    $rs=NULL;
    @openssl_public_encrypt(serialize($s),$rs,'file://'.DIR_FS_SITE.'keys/'.SITE_CRYPT_KEY.'.pub');
    if (isset($rs)) return SITE_CRYPT_KEY.' '.base64_encode($rs);
    return NULL;
  }
  function decrypt($s) {
    if (!preg_match('/([\w\-]+)\s+(.*)/',$s,$p)) return NULL;
    $rs=NULL;
    @openssl_private_decrypt(base64_decode($p[2]),$rs,'file://'.DIR_FS_SITE.'keys/'.$p[1].'.key');
    return $rs?unserialize($rs):$rs;
  }
}
?>
