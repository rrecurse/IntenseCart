replace into IXcore.configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('API Certificate', 'MODULE_PAYMENT_PAYPAL_DP_CERT', 'paypal_wpp.crt', 'Paste the content of your WPP API Certificate here, leave blank to keep old', '6', '2', 'tep_cfg_save_file(\'/--BEGIN CERTIFICATE--.*--END CERTIFICATE--/s\', ', now());