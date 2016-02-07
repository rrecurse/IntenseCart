<?php

require('includes/application_top.php');
require(DIR_WS_LANGUAGES.$language.'/qbi_general.php');

// Perform update if form submitted
if (isset($stage) AND $stage=='configupdate') {
	$tax_rate=str_replace('%','',$tax_rate);
	$config_fields=array("qbi_qb_ver","qbi_dl_iif","qbi_prod_rows","qbi_log","orders_status_import","qbi_status_update","qbi_cc_status_select","qbi_mo_status_select","qbi_email_send","qbi_cc_clear","orders_docnum","orders_ponum","cust_nameb","cust_namer","cust_limit","cust_type","cust_state","cust_country","cust_compcon","cust_phone","invoice_acct","invoice_salesacct","invoice_toprint","invoice_pmt","invoice_termscc","invoice_terms","invoice_rep","invoice_fob","invoice_comments","invoice_message","invoice_memo","item_acct","item_asset_acct","item_class","item_cog_acct","item_osc_lang","item_match_inv","item_match_noninv","item_match_serv","item_default","item_default_name","item_import_type","item_active","ship_name","ship_desc","ship_acct","ship_class","ship_tax","tax_on","tax_name","tax_agency","tax_rate","tax_lookup","pmts_memo","prods_sort","prods_width","qbi_config_active");
	foreach ($config_fields as $dbfield) {
		if (!isset($$dbfield)) $$dbfield=0;
			$config_fields1[]=$dbfield."='".$$dbfield."'";
		}
    $config_fields2 = implode(', ', $config_fields1);
	$success=tep_db_query("UPDATE ".TABLE_QBI_CONFIG." SET qbi_config_active=1, $config_fields2");
	$message=CONFIG_SUCCESS."<br /><br />";
	}

// Now read new configuration
require(DIR_WS_INCLUDES . 'qbi_version.php');
require(DIR_WS_INCLUDES . 'qbi_definitions.php');
require(DIR_WS_INCLUDES . 'qbi_page_top.php');
require(DIR_WS_INCLUDES . 'qbi_menu_tabs.php');

if ($msg==1) $message=CONFIG_SET_OPT."<br />".CONFIG_SET_OPT2."<br /><br />";
echo $message;
echo '<div class="createhead">'.CONFIG_QBI_VER." ".QBI_VER.'</div>';

$config = new form_fields;

?>
<table>
<form action="<?php echo $_SERVER[PHP_SELF] ?>" method="post" name="qbi_config" id="qbi_config">
<input name="stage" id="stage" type="hidden" value="configupdate" />
<input name="qbi_config_active" id="qbi_config_active" type="hidden" value="1" />

<tr><td class="configsec"><?php echo CONFIG_SEC_QBI ?>:</td><td></td><td></td></tr>
<tr><td><label for "qbi_qb_ver"><?php echo QBI_QB_VER_L ?>:</label></td>
<td><select name="qbi_qb_ver" id="qbi_qb_ver">
	<option value="1999" <?php if (QBI_QB_VER==1999) {echo 'selected="selected" ';} ?>><?php echo QBI_QB_VER_1999 ?></option>
	<option value="2001" <?php if (QBI_QB_VER==2001) {echo 'selected="selected" ';} ?>><?php echo QBI_QB_VER_2001 ?></option>
	<option value="2003" <?php if (QBI_QB_VER==2003) {echo 'selected="selected" ';} ?>><?php echo QBI_QB_VER_2003 ?></option>
	</select></td><td><?php echo QBI_QB_VER_C ?></td></tr>
<?php
$config->checkbox("qbi_dl_iif");
$config->textbox("qbi_prod_rows");
?>
<tr><td><label for "prods_sort"><?php echo PRODS_SORT_L ?>:</label></td>
<td><select name="prods_sort" id="prods_sort">
	<option value="0" <?php if (PRODS_SORT==0) {echo 'selected="selected" ';} ?>><?php echo PRODS_SORT_NAME ?></option>
	<option value="1" <?php if (PRODS_SORT==1) {echo 'selected="selected" ';} ?>><?php echo PRODS_SORT_DESC ?></option>
	</select></td><td><?php echo PRODS_SORT_C ?></td></tr>
<?php
$config->textbox("prods_width");
$config->checkbox("qbi_log");
?>
<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>

<tr><td class="configsec"><?php echo CONFIG_SEC_ORDERS ?>:</td><td></td><td></td></tr>
<tr><td><label for "orders_status_import"><?php echo ORDERS_STATUS_IMPORT_L ?>:</label></td>
<td><select name="orders_status_import" id="orders_status_import">
<?php $statbox=status_dropdown(ORDERS_STATUS_IMPORT,0); ?>
<?php echo $statbox; ?>
</select></td>
<td><?php echo ORDERS_STATUS_IMPORT_C ?></td></tr>
<tr><td><label for "qbi_status_update"><?php echo QBI_STATUS_UPDATE_L ?>:</label></td><td><input type="checkbox" name="qbi_status_update" id="qbi_status_update" value="1" 
<?php if (QBI_STATUS_UPDATE==1) {echo 'checked="checked" ';} ?>/></td><td><?php echo QBI_STATUS_UPDATE_C ?></td></tr>
<tr><td><label for "qbi_cc_status_select"><?php echo '&nbsp;&nbsp;&nbsp;'.QBI_CC_STATUS_SELECT_L ?>:</label></td>
<td><select name="qbi_cc_status_select" id="qbi_cc_status_select">
<?php $statbox=status_dropdown(QBI_CC_STATUS_SELECT,1); ?>
<?php echo $statbox; ?>
</select></td>
<td><?php echo QBI_CC_STATUS_SELECT_C ?></td></tr>
<tr><td><label for "qbi_mo_status_select"><?php echo '&nbsp;&nbsp;&nbsp;'.QBI_MO_STATUS_SELECT_L ?>:</label></td>
<td><select name="qbi_mo_status_select" id="qbi_mo_status_select">
<?php $statbox=status_dropdown(QBI_MO_STATUS_SELECT,1); ?>
<?php echo $statbox; ?>
</select></td>
<td><?php echo QBI_MO_STATUS_SELECT_C ?></td></tr>
<?php
$config->checkbox("qbi_email_send");
$config->checkbox("qbi_cc_clear");
?>
<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>

<tr><td class="configsec"><?php echo CONFIG_SEC_CUST ?>:</td><td></td><td></td></tr>
<?php
$config->textbox("cust_nameb");
$config->textbox("cust_namer");
$config->textbox("cust_limit");
$config->textbox("cust_type");
$config->checkbox("cust_state");
$config->checkbox("cust_country");
$config->checkbox("cust_compcon");
$config->checkbox("cust_phone");
?>
<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>

<tr><td class="configsec"><?php echo CONFIG_SEC_INV ?>:</td><td></td><td></td></tr>
<?php
$config->textbox("invoice_acct");
$config->textbox("invoice_salesacct");
$config->textbox("orders_docnum");
$config->textbox("orders_ponum");
$config->checkbox("invoice_toprint");
$config->textbox("invoice_termscc");
$config->textbox("invoice_terms");
$config->textbox("invoice_rep","41");
$config->textbox("invoice_fob","13");
$config->checkbox("invoice_comments");
$config->textbox("invoice_message");
$config->textbox("invoice_memo");
?>
<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>

<tr><td class="configsec"><?php echo CONFIG_SEC_ITEM ?>:</td><td></td><td></td></tr>
<?php
$config->textbox("item_acct");
$config->textbox("item_asset_acct");
$config->textbox("item_class");
$config->textbox("item_cog_acct");
?>
<tr><td><label for "item_osc_lang"><?php echo ITEM_OSC_LANG_L ?>:</label></td>
<td><select name="item_osc_lang" id="item_osc_lang">
	<option value="0" <?php if (ITEM_OSC_LANG==0) {echo 'selected="selected" ';} ?>><?php echo ITEM_LANG_DEF ?></option>
	<option value="1" <?php if (ITEM_OSC_LANG==1) {echo 'selected="selected" ';} ?>><?php echo ITEM_LANG_CUST ?></option>
	</select></td><td><?php echo ITEM_OSC_LANG_C ?></td></tr>
<tr><td><label for "item_match"><?php echo ITEM_MATCH_L ?>:</label></td><td></td><td><?php echo ITEM_MATCH_C ?></td></tr>
<?php
$config->checkbox("item_match_inv");
$config->checkbox("item_match_noninv");
$config->checkbox("item_match_serv");
$config->checkbox("item_default");
$config->textbox("item_default_name");
?>
<tr><td><label for "item_import_type"><?php echo ITEM_IMPORT_TYPE_L ?>:</label></td>
<td><select name="item_import_type" id="item_import_type">
	<option value="0" <?php if (ITEM_IMPORT_TYPE==0) {echo 'selected="selected" ';} ?>><?php echo ITEM_IMPORT_INV ?></option>
	<option value="1" <?php if (ITEM_IMPORT_TYPE==1) {echo 'selected="selected" ';} ?>><?php echo ITEM_IMPORT_NONINV ?></option>
	<option value="2" <?php if (ITEM_IMPORT_TYPE==2) {echo 'selected="selected" ';} ?>><?php echo ITEM_IMPORT_SERV ?></option>
	</select></td><td><?php echo ITEM_IMPORT_TYPE_C ?></td></tr>
<?php
$config->checkbox("item_active");
?>
<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
<tr><td class="configsec"><?php echo CONFIG_SEC_SHIP ?>:</td><td></td><td></td></tr>
<?php
$config->textbox("ship_name");
$config->textbox("ship_desc");
$config->textbox("ship_acct");
$config->textbox("ship_class");
$config->checkbox("ship_tax");
?>
<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>

<tr><td class="configsec"><?php echo CONFIG_SEC_TAX ?>:</td><td></td><td></td></tr>
<?php
$config->checkbox("tax_on");
$config->textbox("tax_name");
$config->textbox("tax_agency");
$config->textbox("tax_rate");
?>
<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>

<tr><td class="configsec"><?php echo CONFIG_SEC_PMTS ?>:</td><td></td><td></td></tr>
<tr><td><label for "invoice_pmt"><?php echo INVOICE_PMT_L ?>:</label></td>
<td><select name="invoice_pmt" id="invoice_pmt">
	<option value="0" <?php if (INVOICE_PMT==0) {echo 'selected="selected" ';} ?>><?php echo INVOICE_PMT_NONE ?></option>
	<option value="1" <?php if (INVOICE_PMT==1) {echo 'selected="selected" ';} ?>><?php echo INVOICE_PMT_PMT ?></option>
	<option value="2" <?php if (INVOICE_PMT==2) {echo 'selected="selected" ';} ?>><?php echo INVOICE_PMT_SR ?></option>
	</select></td><td><?php echo INVOICE_PMT_C ?></td></tr>
<?php
$config->textbox("pmts_memo");
?>
<tr><td><input name="submit" type="submit" id="submit" value="<?php echo CONFIG_SUBMIT ?>" /></td><td></td><td></td></tr>
</form></table><?php
require(DIR_WS_INCLUDES . 'qbi_page_bot.php');
?>