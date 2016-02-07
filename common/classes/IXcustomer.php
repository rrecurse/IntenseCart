<?
// Base Class for Store Customer / Affiliate / Reseller / Supplier
class IXcustomer {

  function load($cid) {
  }
  function getID() {
  }
  function getName($lang=NULL) {
  }
  // returns IXaddress
  function getAddress() {
  }
  function getCustomerGroup() {
  }
  function getAffiliateGroup() {
  }
  function getOrderIDs() {
  }
  function getOrders() {
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