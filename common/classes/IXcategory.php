<?
// Base Class for Inventory Category
class IXcategory {

  function load($cid) {
  }
  function getID() {
  }
  function getName($lang=NULL) {
  }
  function getInfo($field,$lang=NULL) {
  }
  function getHRef($lang=NULL,$optns=NULL) {
  }
  function getProductHRef(&$prod,$lang=NULL,$optns=NULL) {
  }
  function getProducts() {
  }
  function getParent() {
  }
  function getProductCount() {
  }
  function getMedia() {
  }
  function getImage() {
    $m=$this->getMedia();
    return $m['image']['default'][0];
  }
  function getTemplate() {
  }
  function listTemplates() {
  }
  function getDefaultProductTemplate() {
  }
  function setCache(&$cache) {
  }
  function getViewStats($start,$end=NULL) {
  }
  
  function create() {
  }
  function clone() {
  }
  function addProduct(&$prod) {
  }
  function removeProduct($prod) {
  }
  function setParent(&$parent) {
  }
  function setName($name) {
  }
  function setInfo($field,$val) {
  }
  function setMedia($media) {
  }
  // ?
  function setHRef($href) {
  }
  function setTemplate($tmpl) {
  }
  function setDefaultProductTemplate($tmpl) {
  }
  function remove() {
  }
}
?>