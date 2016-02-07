<?php

/**
 * Manages CSV profiles for orders, and imports orders from CSV files.
 * Public methods:
 * - {@link editProfile}()
 * - {@link saveProfile}()
 * - {@link loadProfile}()
 * - {@link getProfiles}()
 * - {@link importOrders}()
 * @author Bogdan Stancescu <bogdan@moongate.ro>
 */
class orderfeed_csv extends IXmodule {
	/**
	 * Private. Current file pointer.
	 * @var string
	 */
	var $fp = NULL;

	/**
	 * Must be equal or greater than the length of the longest line in the file to be processed
	 * @var integer
	 */
	var $maxLineLength = 1024;

	/**
	 * Private. Human-readable file type (e.g. "TAB-delimited", "comma-delimited", etc)
	 * @var string
	 */
	var $ftype;

	/**
	 * CSV delimiter (see {@link http://www.php.net/manual/en/function.fgetcsv.php})
	 * @var string
	 */
	var $fdelimiter;

	/**
	 * CSV enclosure (see {@link http://www.php.net/manual/en/function.fgetcsv.php})
	 * @var string
	 */
	var $enclosure = '"';

	/**
	 * Protected. The current profile name.
	 * @var string
	 * @see loadProfile()
	 * @see saveProfile()
	 * @see editProfile()
	 */
	var $profileName = '';

	/**
	 * Private. Associative array describing the passed values.
	 * @var array
	 * @see addPassValue()
	 */
	var $passValues = array ();

	/**
	 * Private. This object's errors.
	 * Set errors with {@link addError}(); retrieve the list of errors with {@link getErrors}()
	 * @var string
	 */
	var $error = '';

	/**
	 * Private. The current column mapping.
	 * @var array
	 * @see parseMapping()
	 */
	var $columnMapping = array ();

	/**
	 * Private. Human-readable configuration string describing
	 * an association between internal keys, conventional strings
	 * shown by {@link doColumnMapping}() in the interface, and
	 * the legitimate types of CSV columns these can be mapped on.
	 * These internal keys match the internal keys in $rawOrderFieldsMapping.
	 * This string is parsed into a computer-friendly array by {@link getOrderFieldInfo}().
	 * @var string
	 * @see getOrderFieldInfo()
	 * @see $rawOrderFieldsMapping
	 */
	var $rawOrderFields = "
      ordernum,    Order Number,        none/unique
      custemail,   Customer Email,      none/unique
      phone,       Phone,               none/unique
      comments,    Comments,            none/unique
      date_purch,  Purchase date,		none/unique
      d_fname,     Delivery First Name, none/unique
      d_lname,     Delivery Last Name,  none/unique
      d_company,   Delivery Company Name, none/unique
      d_addr1,     Delivery Address,    none/unique
      d_addr2,     Delivery Address2,   none/unique
      d_city,      Delivery City,       none/unique
      d_state,     Delivery State,      none/unique
      d_pcode,     Delivery Postcode,   none/unique
      d_country,   Delivery Country,    none/unique
      b_fname,     Billing First Name,  none/unique
      b_lname,     Billing Last Name,   none/unique
      b_addr1,     Billing Address,     none/unique
      b_addr2,     Billing Address2,    none/unique
      b_company,   Billing Company Name, none/unique
      b_city,      Billing City,        none/unique
      b_state,     Billing State,       none/unique
      b_pcode,     Billing Postcode,    none/unique
      b_country,   Billing Country,     none/unique
      b_pay_method,Payment Method,      none/unique
      s_method,    Shipping Method,     none/unique
      s_cost,      Shipping Cost,       none/unique
      s_cost2,     Shipping Extra Cost, none/unique
      p_sku,       Product SKU,         none/unique/numbered
      p_upc,       Product UPC,         none/unique/numbered
      p_ord_id,    Product Order ID,    none/unique/numbered
      p_name,      Product Name,        none/unique/numbered
      p_price,     Product Price,       none/unique/numbered
      p_qty,       Product Quantity,    none/unique/numbered
      p_tax,       Product Tax,         none/unique/numbered
      p_ship,      Product Shipping Cost, none/unique/numbered
      p_ship2,     Product Shipping Extra Cost, none/unique/numbered
      dsc_reason,  Discount Reason,     none/unique/numbered
      dsc_amt,     Discount Amount,     none/unique/numbered
      pkg_num,     Package Number,      none/unique/numbered
      pkg_weight,  Package Weight,      none/unique/numbered
      pkg_tracking, Package Tracking Code, none/unique/numbered
      total_amt,   Order Total Amount,  none/unique
    ";

	/**
	 * Private. Human-readable configuration string describing
	 * an association between internal keys and actual order/product
	 * fields.
	 * This string is parsed into a computer-friendly array by {@link getOrderFieldMapping}().
	 * That array is used by {@link saveOrder}() to re-arrange the array
	 * describing the order built by {@link importOrders}() into values
	 * used for saving the order.
	 * These internal keys match the internal keys in $rawOrderFields.
	 * @var string
	 * @see getOrderFieldMapping()
	 * @see $rawOrderFields
	 */
	var $rawOrderFieldsMapping = "
      ordernum,    ordernum
      custemail,   shipdata.email_address + billdata.email_address
      phone,       shipdata.telephone     + billdata.telephone
      comments,    comments
      date_purch,  date_purch
      d_company,   shipdata.company
      d_fname,     shipdata.first_name
      d_lname,     shipdata.last_name
      d_addr1,     shipdata.street_address
      d_addr2,     shipdata.suburb
      d_city,      shipdata.city
      d_state,     shipdata.state
      d_pcode,     shipdata.postcode
      d_country,   shipdata.country
      b_company,   billdata.company
      b_fname,     billdata.first_name
      b_lname,     billdata.last_name
      b_addr1,     billdata.street_address
      b_addr2,     billdata.suburb
      b_city,      billdata.city
      b_state,     billdata.state
      b_pcode,     billdata.postcode
      b_country,   billdata.country
      b_pay_method,payment_method
      s_method,    shipxtra.method
      s_cost,      shipxtra.cost
      s_cost2,     shipxtra.cost2
      p_sku,       itemdata.sku
      p_upc,       itemdata.upc
      p_ord_id,    itemdata.order_item_id
      p_name,      itemdata.name
      p_price,     itemdata.price
      p_qty,       itemdata.qty
      p_tax,       itemdata.tax
      p_ship,      itemdata.ship
      p_ship2,     itemdata.ship2
      dsc_reason,  discount.reason
      dsc_amt,     discount.amount
      pkg_num,     package.num
      pkg_weight,  package.weight
      pkg_tracking, package.tracking
      total_amt,   total_amount
      numbered_discount, numbered_discount
      numbered_item,     numbered_item
      numbered_package,     numbered_package
    ";

	/**
	 * Private. Stores the currently processed order for {@link getOrderData()} and
	 * {@link getOrderItems()}.
	 *
	 * @var object|NULL
	 */
	var $corder = NULL;

	/**
	 * Public. Export mode -- numbered or multiline.
	 *
	 * This class supports exports in CSV format in one of two ways:
	 * - numbered: one record per order, repeat item- and discount-specific fields
	 * as many times as needed to record all associated items and discounts;
	 * - multiline: as many records per order as needed to include all items
	 * and discounts; the criteria for parsing is the order id (lines
	 * featuring the same order ID should only be parsed for items/discounts,
	 * except for the first line where the legitimate order information must
	 * be included).
	 *
	 * @var string
	 */
	var $exportMode = 'numbered';

	/**
	 * Private. Used in {@link exportHeader()} and {@link exportOrders()}
	 * @var array
	 */
	var $_headerMapping = array ();

	/**
	 * Constructor.
	 * No logic in here, just includes the order class.
	 */
	function orderfeed_csv () {
		require_once (dirname (dirname (dirname (dirname (__FILE__)))) . "/catalog/includes/classes/order.php");
		$this->readPassedValues ();
	}

	function getName () {
		return 'Order CSV Import/Export';
	}

	/**
	 * Adds an error to the current object.
	 * Read existing errors with {@link getErrors}()
	 * @param string $message the error message
	 * @return boolean true unconditionally
	 * @see $error
	 * @see getErrors()
	 */
	function addError ($message) {
		$this->error .= "$message\n";
		return true;
	}

	/**
	 * Retrieve this object's list of errors.
	 * Add errors with {@link addError}()
	 * @return string HTML-formatted string
	 * @see $error
	 * @see addError()
	 */
	function getErrors () {
		return nl2br ($this->error);
	}

	/**
	 * Private. Processes values passed from one page to the next while
	 * editing a profile.
	 * This method calls {@link readPOSTvalues}() and
	 * {@link parsePassedValues}().
	 * @return boolean true on success or false on failure
	 */
	function readPassedValues () {
		if (!$this->readPOSTvalues ()) {
			return false;
		}
		return $this->parsePassedValues ();
	}

	/**
	 * Private. Reads POST values passed from one page to the next while
	 * editing a profile, and adds them to the list of passed variables.
	 * @return boolean true on success or false if no relevant POST data is available
	 */
	function readPOSTvalues () {
		if (!isset ($_POST['importorders_csv']) || !is_array ($_POST['importorders_csv'])) {
			return NULL;
		}
		$return = true;
		foreach ($_POST['importorders_csv'] as $key => $value) {
			if (get_magic_quotes_gpc ()) {
				$value = stripslashes ($value);
			}
			$this->addPassValue ($key, $value);
		}
		return true;
	}

	/**
	 * Private. Populates object properties from passed values.
	 * Errors can be retrieved with {@link getErrors}().
	 * @return boolean true on success or false if any errors have been encountered
	 */
	function parsePassedValues () {
		$return = true;
		foreach ($this->passValues as $key => $value) {
			$xkey = explode ('_', $key);
			switch ($xkey[0]) {
				case 'delimiter' :
					$this->fdelimiter = chr ($value);
					break;
				case 'ftype' :
					$this->ftype = $value;
					break;
				case 'profile' :
					$this->profileName = $value;
					break;
				case 'mapping' :
					$db_key = substr ($key, 8);
					$xcsv = explode ('_', $value);
					$csv_key = implode ('_', array_slice ($xcsv, 1));
					$this->columnMapping[$db_key] = $csv_key;
					$this->columnType[$db_key] = $xcsv[0];
					break;
				case 'options' :
					$db_key = substr ($key, 8);
					$this->columnOptions[$db_key] = unserialize ($value);
					break;
				case 'confirm' :
					switch ($xkey[1]) {
						case 'edit' :
							$this->editConfirmed = $value;
							break;
						case 'mapping' :
							$this->mappingConfirmed = $value;
							break;
						case 'delimiter' :
							$this->delimiterConfirmed = $value;
							break;
						default :
							$this->addError ("Unknown confirmation: $key");
							$return = false;
					}
					break;
				default :
					$this->addError ("Unknown field: $key");
					$return = false;
			}
		}
		return $return;
	}

	/**
	 * Private. Adds a new value to pass from one page to the next while editing profiles
	 * @param string $var the name of the variable to pass
	 * @param string $val the value of the variable
	 * @return string the value of the variable, exactly as it was passed
	 */
	function addPassValue ($var, $val) {
		$this->passValues[$var] = $val;
		return $val;
	}

	/**
	 * Private. Reads one CSV line from the current CSV file, according to the current rules,
	 * and returns the result.
	 * The current CSV file is opened by {@link initFile}().
	 * The current rules are stored in variables {@link $maxLineLength}, {@link $fdelimiter},
	 * and {@link $enclosure}. They are typically populated from a profile data
	 * via {@link loadProfile}().
	 * @return array|false an array representing the current CSV line, or false on EOF
	 */
	function readCSVLine () {
		return fgetcsv ($this->fp, $this->maxLineLength, $this->fdelimiter, $this->enclosure);
	}

	/**
	 * Private. Reads one line from the current CSV file.
	 * The current CSV file is opened by {@link initFile}().
	 * This method is typically used only by {@link parseHeading}().
	 * @return string|false a string representing the current line in the CSV file, or false on EOF
	 */
	function readRawLine () {
		return fgets ($this->fp, $this->maxLineLength);
	}

	/**
	 * Private. Parses the heading of the current CSV file and tries to determine its format.
	 * Used internally while editing profiles, typically called by {@link findDelimiter}().
	 * On success, it populates the format information in all the right places in this object
	 * (both in the object properties and in passed values).
	 * It also processes POST values resulted from a previous call to {@link renderDelimiterSelector}(),
	 * if that's the case.
	 * @return true if it succeeds in determining the format, false otherwise
	 */
	function parseHeading () {
		$line = $this->readRawLine ();
		rewind ($this->fp);
		if (strpos ($line, "\t")) {
			$this->ftype = "TAB-delimited";
			$this->fdelimiter = "\t";
		} elseif (strpos ($line, ",")) {
			$this->ftype = "Comma-delimited";
			$this->fdelimiter = ",";
		} elseif (strpos ($line, ";")) {
			$this->ftype = "Semi-colon delimited";
			$this->fdelimiter = ";";
		} elseif ($_POST['delimiter']) {
			$this->addPassValue ('confirm_delimiter', 1);
			$this->ftype = "Custom delimiter";
			$d = $_POST['delimiter'];
			if (get_magic_quotes_gpc ()) {
				$d = stripslashes ($d);
			}
			if (preg_match ("/^\\(([0-9]+)\\)$/", $d, $m)) {
				$this->fdelimiter = chr ($m[1]);
			} elseif (preg_match ("/^\\\\x([0-9A-Fa-f]{1,2})$/", $d, $m)) {
				$this->fdelimiter = chr (hexdec ($m[1]));
			} else {
				$this->fdelimiter = substr ($d, 0, 1);
			}
		} else {
			return false;
		}
		$this->addPassValue ('delimiter', ord ($this->fdelimiter));
		$this->addPassValue ('ftype', $this->ftype);
		return true;
	}

	/**
	 * Private. Parses the CSV header in order to determine which fields are static and which are numbered.
	 * Used while editing a profile
	 * @param array $data an array of values as the one returned by {@link readCSVline}().
	 * @return array an indexed array with the same keys as $data which describes the fields
	 */
	function parseHeaderFields ($data) {
		$fields = array ();
		foreach ($data as $atom) {
			$field = array ();
			if (preg_match ("/^(.+?)([0-9]+)$/", $atom, $matches)) {
				$field['type'] = 'numbered';
				$field['prefix'] = $matches[1];
				$field['no'] = $matches[2];
				$field['name'] = $atom;
				$fields[] = $field;
			} else
				$fields[] = Array ('type' => 'unique', 'name' => $atom);
		}
		return $fields;
	}

	/**
	 * Private. Initializes the current file.
	 * Opens a file and populates {@link $fp} with a pointer to it.
	 * If errors are encountered, they are available via {@link getErrors}().
	 * @param string $file the path to the file to open
	 * @param string $mode the type of access (see {@link http://www.php.net/manual/en/function.fopen.php PHP's fopen()})
	 * @return boolean true on success or false on failure.
	 */
	function initFile ($file, $mode = 'r') {
		if ($mode == 'r' && !is_file ($file)) {
			$this->addError ("File $file doesn't exist!");
			return false;
		}
		if ($mode == 'r' && !is_readable ($file)) {
			$this->addError ("Not enough permissions to open file $file!");
			return false;
		}
		if (($mode == 'a' || $mode == 'w') && (!is_writable ($file) && !is_writable (dirname ($file)))) {
			$this->addError ("Can't write to file $file!");
			return false;
		}
		$this->fp = fopen ($file, $mode);
		if (!$this->fp) {
			$this->addError ("Failed opening file $file for an unknown reason!");
			return false;
		}
		return true;
	}

	/**
	 * Private. Triggers the logic for identifying the basic parameters
	 * of a CSV file during profile editing.
	 * Typically called by {@link editProfile}().
	 * Tries to automatically determine the delimiter using {@link parseHeading}(),
	 * calls {@link renderDelimiterSelector}() on failure and sets a confirmation
	 * flag as a passed value on success, so future calls to {@link editProfile}()
	 * will retrieve data from passed values directly.
	 * @param string $file the path to the CSV file to parse
	 * @return boolean|string true on success, false on failure or the HTML controls to be rendered
	 * if it was unable to automatically determine the delimiter.
	 */
	function findDelimiter ($file) {
		if (!$this->initFile ($file)) {
			// No need to complain here, initFile already has.
			return false;
		}
		$headingData = $this->parseHeading ();
		if (!$headingData) {
			return $this->renderPage ($this->renderDelimiterSelector ());
		}
		$this->addPassValue ('confirm_delimiter', 1);
		return true;
	}

	/**
	 * Private. Processes {@link $rawOrderFieldsMapping} and returns an array
	 * describing it.
	 * @return array the order fields mapping
	 */
	function getOrderFieldMapping () {
		if ($this->cachedOrderMappingInfo) {
			return $this->cachedOrderMappingInfo;
		}
		$mapping_info = array ();
		$raw_fields = explode ("\n", $this->rawOrderFieldsMapping);
		foreach ($raw_fields as $raw_field) {
			$raw_field = trim ($raw_field);
			if (!$raw_field) {
				continue;
			}
			$raw_data = explode (",", $raw_field);
			$raw_mappings = explode ("+", trim ($raw_data[1]));
			$mappings = array ();
			foreach ($raw_mappings as $mapping) {
				$mappings[] = explode (".", trim ($mapping));
			}
			$mapping_info[trim ($raw_data[0])] = $mappings;
		}
		$this->cachedOrderMappingInfo = $mapping_info;
		return $mapping_info;
	}

	/**
	 * Private. Processes {@link $rawOrderFields} and returns an array
	 * describing it.
	 * @return array the order fields
	 */
	function getOrderFieldInfo () {
		if ($this->cachedOrderFieldInfo) {
			return $this->cachedOrderFieldInfo;
		}
		$field_info = array ();
		$raw_fields = explode ("\n", $this->rawOrderFields);
		foreach ($raw_fields as $raw_field) {
			$raw_field = trim ($raw_field);
			if (!$raw_field) {
				continue;
			}
			$raw_data = explode (",", $raw_field);
			$raw_options = explode ("/", trim ($raw_data[2]));
			$options = array ();
			foreach ($raw_options as $raw_option) {
				$options[$raw_option] = true;
			}
			$field_info[] = array ('name' => trim ($raw_data[0]), 'display' => trim ($raw_data[1]), 'options' => $options);
		}
		$this->cachedOrderFieldInfo = $field_info;
		return $field_info;
	}

	/**
	 * Private. Pre-processes the fields returned by {@link parseHeaderFields}()
	 * for use in {@link doColumnMapping}().
	 * It converts the discrete list of fields in the CSV header
	 * into a more compact form, better suited for rendering in a <SELECT>.
	 * @param array $fields an array of fields as the one returned by {@link parseHeaderFields}()
	 * @return array a more compact array describing the same structure
	 */
	function makeCanonicalFields ($fields) {
		$result = array ();
		$processed = array ();
		$xtra = array ();
		foreach ($fields as $field) {
			if ($field['type'] != 'numbered') {
				$result[] = $field;
				continue;
			}
			$result[] = Array ('name' => $field['name'], 'type' => 'unique');
			$idx = array_search ($field['prefix'], $processed);
			if ($idx !== false) {
				$xtra[$idx] .= ", " . $field['no'];
				continue;
			}
			$field['xtra'] = count ($xtra);
			$xtra[] = $field['no'];
			$processed[] = $field['prefix'];
			unset ($field['name']);
			unset ($field['no']);
			$result[] = $field;
		}
		foreach ($result as $key => $val) {
			if (isset ($val['xtra'])) {
				$val['name'] = $val['prefix'] . " (" . $xtra[$val['xtra']] . ")";
				$val['value'] = $val['prefix'];
				unset ($val['xtra']);
			} else {
				$val['value'] = $val['name'];
			}
			$result[$key] = $val;
		}
		return $result;
	}

	/**
	 * GUI -- renders an HTML SELECT tag based on the input data.
	 *
	 * The input array is an indexed array of associative arrays of
	 * the following form:
	 * <code>
	 * array(
	 * #(*) => array(
	 * 'value'=>$value,
	 * 'display'=>$display
	 * )
	 * )
	 * </code>
	 * where each resulting OPTION element is of the form
	 * <code>
	 * <OPTION value=$value>$display</OPTION>
	 * </code>
	 *
	 * @param string $name the name of the element
	 * @param array $data the data to populate the SELECT
	 * @param string $selected the value of the selected entry
	 * @return string the HTML representation of the SELECT
	 */
	function renderSelect ($name, $data, $selected = NULL, $extra = NULL) {
		$result = "";
		$result .= "<select name='$name'" . ($extra ? " $extra" : "") . ">\n";
		foreach ($data as $option) {
			$result .= "<option value='" . addslashes ($option['value']) . "'";
			if ($option['value'] == $selected) {
				$result .= " selected";
			}
			$result .= ">" . htmlspecialchars ($option['display']) . "</option>\n";
		}
		$result .= "</select>\n";
		return $result;
	}

	/**
	 * Private. Parses the POST variables incoming from a previous call to {@link confirmEdit}()
	 * into {@link @columnMapping}.
	 * @return boolean true on success or false on failure
	 */
	function parseMapping () {
		$mapping = array ();
		$ofi = $this->getOrderFieldInfo ();
		$legit = array ();
		foreach ($ofi as $field) {
			$legit[] = $field['name'];
		}
		$ok = true;
		foreach ($_POST['mapping'] as $db_field => $csv_field) {
			if (!$csv_field) {
				continue;
			}
			$this->addPassValue ("mapping_$db_field", $csv_field);
			if ($vmap = $_POST['value_mapping'][$db_field]) {
				$optns = array_map ('stripslashes', $_POST['option'][$db_field][$vmap]);
				$this->addPassValue ("options_$db_field", serialize (Array ($vmap => $optns)));
			}
			$csv_field = substr ($csv_field, strpos ($csv_field, "_") + 1);
			if (!in_array ($db_field, $legit)) {
				$ok = false;
				$this->addError ("Unknown field \"$db_field\"");
				continue;
			}
			$mapping[$db_field] = $csv_field;
		}
		$this->columnMapping = $mapping;
		return $ok;
	}

	/**
	 * Private. Triggers the column mapping logic used during profile editing.
	 * Typically called by {@link editProfile}().
	 * Parses the header fields using {@link parseHeaderFields}(),
	 * pre-processes them with {@link makeCanonicalFields}(),
	 * corroborates them with the order field info retrieved via
	 * {@link getOrderFieldInfo}(), renders the appropriate controls,
	 * calls {@link parseMapping}() if incoming data is already available
	 * and if that call is successful it adds a flag to avoid future calls
	 * from {@link editProfile}() on this object.
	 * @return boolean|string boolean true if mapping is complete, or HTML string representing the controls otherwise.
	 */
	function doColumnMapping () {
		if ($_POST['mapping'] && is_array ($_POST['mapping'])) {
			if ($this->parseMapping ()) {
				$this->addPassValue ('confirm_mapping', 1);
				return true;
			}
		}
		$file_fields = $this->parseHeaderFields ($this->readCSVLine ());
		$canonical_file_fields = $this->makeCanonicalFields ($file_fields);
		//      print_r($canonical_file_fields);
		$ofi = $this->getOrderFieldInfo ();
		$table = array ();
		foreach ($ofi as $field) {
			$options = $field['options'];
			$select = array ();
			$selected = '';
			if ($options['none']) {
				$select[] = array ('value' => '', 'display' => 'n/a');
			} else {
				$select[] = array ('value' => '', 'display' => 'Please select:');
			}
			foreach ($canonical_file_fields as $ff) {
				if (in_array ($ff['type'], array_keys ($options))) {
					$ffval = $ff['type'] . '_' . $ff['value'];
					if ($ff['value'] == $this->columnMapping[$field['name']]) {
						$selected = $ffval;
					}
					$select[] = array ('value' => $ffval, 'display' => $ff['name']);
				}
			}
			$optns = $this->columnOptions[$field['name']];
			list ($vmap) = $optns ? array_keys ($optns) : Array ('');
			$vmapblk = '<table id="div_map_' . $field['name'] . '" style="display:' . ($vmap == 'map' ? 'block' : 'none') . '">' . '<tr><td>&nbsp;</td><td>mapped values, comma separated</td><td>Default</td></tr>' . '<tr><td>DB:</td><td><input type="text" name="option[' . $field['name'] . '][map][db]" value="' . htmlspecialchars ($optns['map']['db']) . '"></td><td><input type="text" name="option[' . $field['name'] . '][map][db_default]" value="' . htmlspecialchars ($optns['map']['db_default']) . '"></td></tr>' . '<tr><td>CSV:</td><td><input type="text" name="option[' . $field['name'] . '][map][csv]" value="' . htmlspecialchars ($optns['map']['csv']) . '"></td><td><input type="text" name="option[' . $field['name'] . '][map][csv_default]" value="' . htmlspecialchars ($optns['map']['csv_default']) . '"></td></tr>' . '</table>' . '<table id="div_regex_' . $field['name'] . '" style="display:' . ($vmap == 'regex' ? 'block' : 'none') . '">' . '<tr><td>&nbsp;</td><td>Regex</td><td>Replace</td></tr>' . '<tr><td>DB=&gt;CSV:</td><td><input type="text" name="option[' . $field['name'] . '][regex][db]" value="' . htmlspecialchars ($optns['regex']['db']) . '"></td><td><input type="text" name="option[' . $field['name'] . '][regex][db_replace]" value="' . htmlspecialchars ($optns['regex']['db_replace']) . '"></td></tr>' . '<tr><td>CSV=&gt;DB:</td><td><input type="text" name="option[' . $field['name'] . '][regex][csv]" value="' . htmlspecialchars ($optns['regex']['csv']) . '"></td><td><input type="text" name="option[' . $field['name'] . '][regex][csv_replace]" value="' . htmlspecialchars ($optns['regex']['csv_replace']) . '"></td></tr>' . '</table>';
			$table[] = array ($field['display'], $this->renderSelect ("mapping[" . $field['name'] . "]", $select, $selected), $this->renderSelect ("value_mapping[" . $field['name'] . "]", Array (Array ('value' => '', 'display' => 'as is'), Array ('value' => 'map', 'display' => 'Value Map'), Array ('value' => 'regex', 'display' => 'Regex')), $vmap, 'onChange="for (var k in {map:1,regex:1}) $(\'div_\'+k+\'_' . $field['name'] . '\').style.display=this.value==k?\'\':\'none\'; "') . $vmapblk);
		}
		return $this->renderPage ($this->renderFormTable ($table));
	}

	/**
	 * Private. Renders the field mapping confirmation screen during profile editing.
	 * There's not much logic in this method: it just renders that page
	 * and includes a confirmation flag as a passed value -- if the form is
	 * submitted, the confirmation flag is recognized by {@link editProfile}
	 * and this method is not called again.
	 * @return string the HTML page confirming the field mapping.
	 */
	function confirmEdit () {
		$table = array ();
		$ofi = $this->getOrderFieldInfo ();
		$names = array ();
		foreach ($ofi as $field) {
			$names[$field['name']] = $field['display'];
		}
		foreach ($this->columnMapping as $db_key => $csv_key) {
			$table[] = array ($names[$db_key], $csv_key);
		}
		$this->addPassValue ('confirm_edit', 1);

		return $this->renderPage ("<h1>Confirm profile</h1>\n" . $this->renderFormTable ($table));
	}

	/**
	 * Private. Performs the logic related to populating the profile name
	 * if {@link editProfile}() is called without the $profile parameter.
	 * It renders the form which allows the user to input a profile name,
	 * and populates {@link $profileName} if that form has already been
	 * submitted.
	 * @return boolean|string true if a profile name has been populated, or the HTML page with the profile name input otherwise
	 */
	function profileInput () {
		if ($_POST['profileName']) {
			$profileName = $_POST['profileName'];
			if (get_magic_quotes_gpc ()) {
				$profileName = stripslashes ($profileName);
			}
			$this->profileName = $profileName;
			return true;
		}
		$result = '';
		$result .= "<h1>Profile name</h1>\n";
		$result .= $this->renderFormTable (array (array ('Profile name', "<input type=text name='profileName'>")));
		return $this->renderPage ($result);
	}

	/**
	 * Public. Triggers all the logic related to editing a profile.
	 * The $file parameter is mandatory and must be the path to an existing,
	 * local, regular, readable CSV file (technically, it MAY also be remote or not regular, but
	 * expect calls to PHP's {@link http://www.php.net/manual/en/function.rewind.php rewind()}
	 * to take place on it -- if PHP supports calls to rewind() on your type of file, then it should work).
	 *
	 * The $profile parameter is optional -- if not passed, the class will render controls to
	 * gather it from the user.
	 *
	 * The method must be called as many times as required, until it returns boolean
	 * true; until then, its output must be shown in the browser.
	 *
	 * Example:
	 * <code>
	 * $data=$mod->editProfile($csv_file,$profile_name);
	 *
	 * if ($data===true) {
	 * $mod->saveProfile();
	 * echo "Done.";
	 * } else {
	 * echo $data;
	 * }
	 * </code>
	 *
	 * The method relies on flags passed as "passed variables" via {@link addPassValue}() to determine
	 * the current object's state. Depending on its state, it does the following:
	 * - if the profile name is not available, it calls {@link profileInput}()
	 * - if the above doesn't apply, and the delimiter hasn't been determined, it calls {@link findDelimiter}()
	 * - if none of the above applies, and the mapping hasn't been filled in, it calls {@link doColumnMapping}()
	 * - if none of the above applies, and the mapping hasn't been confirmed, it calls {@link confirmEdit}()
	 * - if none of the above applies, it returns boolean true (i.e. the object is all set)
	 *
	 * As shown in the example above, a successful call to this method is
	 * typically followed by a call to {@link saveProfile}() on the same object.
	 *
	 * If a profile named $profile doesn't already exist, this method provides the controls
	 * for creating it from scratch, and {@link saveProfile}() creates it in the database.
	 * If a profile named $profile already exists, this method provides the controls for
	 * editing it based on its existing form, and {@link saveProfile}() replaces it with
	 * the current settings in the database.
	 * If $profile isn't specified then the value specified by the user via interface
	 * is used instead, with the same effects as above.
	 *
	 * @param string $file the path to a CSV file
	 * @param string $profile the profile name
	 * @return boolean|string boolean true if the profile is complete and ready for saving,
	 * or an HTML string representing the controls to be rendered.
	 */
	function editProfile ($file, $profile = NULL) {
		if ($profile === NULL) {
			$profile = $this->profileName;
		} else {
			$this->profileName = $profile;
		}
		if (!$profile) {
			if (($result = $this->profileInput ()) !== true) {
				return $result;
			}
			$profile = $this->profileName;
		}
		$this->addPassValue ('profile', $profile);
		if (!$this->delimiterConfirmed) {
			$this->loadProfile ($profile);
			if (($result = $this->findDelimiter ($file)) !== true) {
				return $result;
			}
		}
		if (!$this->mappingConfirmed) {
			if (($result = $this->doColumnMapping ()) !== true) {
				return $result;
			}
		}
		if (!$this->editConfirmed) {
			if (($result = $this->confirmEdit ()) !== true) {
				return $result;
			}
		}
		return true;
	}

	/**
	 * Private. GUI -- renders a form around arbitrary HTML.
	 * The form point to PHP_SELF. This method is used internally
	 * to render the forms which pass values from one page to the
	 * next during profile editing.
	 * @param string $content the HTML to put inside the form
	 * @return string the form containing $content
	 */
	function renderForm ($content) {
		$result = "<form method='POST' action='" . $_SERVER['PHP_SELF'] . "'>\n";
		foreach ($this->passValues as $name => $value) {
			$result .= "<input type='hidden' name=\"importorders_csv[" . addslashes ($name) . "]\" value=\"" . htmlspecialchars ($value) . "\">\n";
		}
		$result .= $content;
		$result .= "</form>\n";
		return $result;
	}

	/**
	 * Private. GUI -- renders an HTML table.
	 * Used internally while editing profiles. It takes a 2D indexed array
	 * and returns an HTML table representing it.
	 * @param array $table the table data
	 * @return string the HTML table
	 */
	function renderTable ($table) {
		$result = '<table border=1>';
		$maxcount = 0;
		foreach ($table as $row) {
			$count = 0;
			$result .= "<tr>\n";
			$idxs = array_keys ($row);
			$idxs[] = $maxcount;
			foreach ($row as $cell) {
				$cspan = $idxs[$count + 1] - $idxs[$count];
				$count++;
				$result .= "  <td" . ($cspan > 1 ? " colspan=\"$cspan\"" : "") . ">\n";
				$result .= "    " . $cell . "\n";
				$result .= "  </td>\n";
			}
			$result .= "</tr>\n";
			$maxcount = max ($count, $maxcount);
		}
		$result .= "</table>\n";
		$this->_maxtablecount = $maxcount;
		return $result;
	}

	/**
	 * Private. GUI -- combines a call to {$link renderTable}() with a call
	 * to {@link renderForm}().
	 * It effectively renders an HTML form containing an HTML table
	 * based on the input array. Used while editing profiles.
	 * @param array $table the table data (see {$link renderTable}())
	 * @return string the HTML representation of a form wrapped around a table
	 */
	function renderFormTable ($table) {
		$result = $this->renderTable ($table);
		$result = substr ($result, 0, -9);
		$result .= "<tr>\n";
		$result .= "  <td colspan={$this->_maxtablecount} align=center><input type='submit' name='submit' value='Submit'></td>\n";
		$result .= "</tr>\n";
		$result .= "</table>\n";

		return $this->renderForm ($result);
	}

	/**
	 * Private. GUI -- renders the controls for manually providing a field delimiter.
	 * Called by {@link findDelimiter}() when it can't automatically determine
	 * the field separator while editing a profile.
	 * @return string the controls rendered as HTML
	 */
	function renderDelimiterSelector () {
		$return = "<h1>Delimiter</h1>\n";
		$return .= "<div><i>Please provide the field delimiter manually:</i></div>\n";
		$return .= "<div><small>" . "(For special characters, use &quot;(nnn)&quot; for character nnn (decimal), " . "or &quot;\\xNN&quot; for character NN (hex).)" . "</small></div>\n";
		$return .= $this->renderFormTable (array (array ('Delimiter', "<input type='text' name='delimiter' value='' size=5>")));
		return $return;
	}

	/**
	 * Private. GUI -- wraps the HTML page start, header boxes and page end around
	 * arbitrary HTML content.
	 * Used while editing profiles. It calls {@link renderPageStart}(),
	 * {@link renderHeaderBoxes}() and {@link renderPageEnd}(), sandwiching
	 * $content between them.
	 * @param string $content the HTML body of the page
	 * @return string a full HTML representation of the page
	 */
	function renderPage ($content, $title = NULL) {
		$result = '';
		$result .= $this->renderPageStart ($title);
		$result .= $this->renderHeaderBoxes ();
		$result .= $content;
		$result .= $this->renderPageEnd ();
		return $result;
	}

	/**
	 * Private. GUI -- renders the HTML page start (DOCTYPE, starts the HTML tag,
	 * renders the HEAD tag, and starts the BODY tag).
	 * Used by {@link renderPage}(), typically while editing profiles.
	 * @return string a static string representing the HTML page start
	 */
	function renderPageStart ($title = NULL) {
		if (!$title) {
			$title = "Edit CSV import profile";
		}
		$result = '';
		$result .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
		$result .= "<HTML>\n";
		$result .= "<HEAD>\n";
		$result .= "  <TITLE>$title</TITLE>\n";
		$result .= "<script type=\"text/javascript\" src=\"/js/prototype.lite.js\"></script>\n";
		$result .= "</HEAD>\n";
		$result .= "<BODY>\n";
		return $result;
	}

	/**
	 * Private. GUI -- renders the HTML page end (closes the BODY and HTML tags).
	 * Used by {@link renderPage}(), typically while editing profiles.
	 * @return string a static string representing the HTML page end
	 */
	function renderPageEnd () {
		$result = '';
		$result .= "</BODY>\n";
		$result .= "</HTML>\n";
		return $result;
	}

	/**
	 * Private. GUI -- renders the page header boxes showing the current status
	 * of a profile edit operation.
	 * Typically used by {@link renderPage}(), it conditionally
	 * calls {@link renderProfileNameBox}() and {@link renderDelimiterBox}(),
	 * depending on the current status of this object, runs {@link renderTable}()
	 * on the result of the previous calls, and returns the final result.
	 * @return string HTML string
	 */
	function renderHeaderBoxes () {
		$result = array ();
		if ($this->profileName) {
			$result = array_merge ($result, $this->renderProfileNameBox ());
		}
		if ($this->fdelimiter) {
			$result = array_merge ($result, $this->renderDelimiterBox ());
		}
		if ($result) {
			return $this->renderTable ($result) . "<br>";
		} else {
			return '';
		}
	}

	/**
	 * Private. GUI -- renders the current profile's name into an array
	 * suitable for use with {@link renderTable}().
	 * Typically called by {@link renderHeaderBoxes}() during
	 * profile editing.
	 * @return array indexed array, as described
	 */
	function renderProfileNameBox () {
		return array (array ("Profile name", $this->profileName));
	}

	/**
	 * Private. GUI -- renders the information related to delimiters into an array
	 * suitable for use with {@link renderTable}().
	 * This is used by {@link renderHeaderBoxes}() to render part of
	 * the page headers while editing profiles.
	 * @return array indexed array, as described
	 */
	function renderDelimiterBox () {
		$result = array (array ("Delimiter type", $this->ftype));
		if ($this->ftype == "Custom delimiter") {
			$result[] = array ("Delimiter", $this->fdelimiter . " (ASCII " . ord ($this->fdelimiter) . ")");
		}
		return $result;
	}

	/**
	 * Public. Saves the current profile into the database.
	 * Uses the inherited IXmodule::setExtra() to save all data related to
	 * this profile in the database.
	 * The method basically saves all of this object's passed variables
	 * (minus confirmation flags) into extra data, as follows:
	 * <code>
	 * $this->setExtra('profile',$this->profileName,$passed['name'],$passed['value']);
	 * </code>
	 * Since passed variables completely describe this profile following
	 * an edit confirmation (see {@link confirmEdit}()), we have a complete description
	 * of this object ready to be stored.
	 * To load a profile use {@link loadProfile}().
	 * On failure you can use {@link getErrors}() to determine the errors.
	 * @return boolean true on success, false on failure
	 */
	function saveProfile () {
		if (!$this->profileName || !$this->columnMapping || !$this->fdelimiter) {
			$this->addError ("Can't save incomplete profile.");
			return false;
		}
		foreach ($this->passValues as $name => $value) {
			$xname = explode ("_", $name);
			if ($xname[0] == 'confirm' || $passed['name'] == 'profile' || $xname[0] == 'edit') {
				continue;
			}
			$this->setExtra ('profile', $this->profileName, $name, $value);
		}
		return true;
	}

	/**
	 * Public. Loads a profile from the database.
	 * The opposite of {@link saveProfile}() -- it reads passed values
	 * from the database using the inherited IXmodule::getExtra()
	 * as follows:
	 * <code>
	 * $this->getExtra('profile',$profile);
	 * </code>
	 * The resulting values are passed to {@link addPassValue}(),
	 * and finally a call to {@link parsePassedValues}() is made.
	 * On failure you can use {@link getErrors}() to determine the errors.
	 * @return boolean true on success, false on failure
	 */
	function loadProfile ($profile) {
		if (!$profile) {
			$this->addError ("Can't load unnamed profile!");
			return false;
		}
		$data = $this->getExtra ('profile', $profile);
		if (!$data) {
			$this->addError ("Can't load profile \"$profile\" -- no data available.");
			return false;
		}
		foreach ($data as $key => $value) {
			$this->addPassValue ($key, $value);
		}
		return $this->parsePassedValues ();
	}

	/**
	 * Public. Returns an indexed array containing all existing profiles.
	 * To load a profile, use {@link loadProfile}().
	 * @return array indexed array listing all profiles currently stored in the database
	 */
	function getProfiles () {
		$prfs = $this->getExtra ('profile');
		return $prfs ? array_keys ($prfs) : array ();
	}

	/**
	 * Public. Imports orders from a CSV file based on an import profile.
	 * Profiles can be loaded with {@link loadProfile}(), saved with
	 * {@link saveProfile}(), modified using {@link editProfile}(),
	 * and listed with {@link getProfiles}().
	 * Parameter $file must be the path to a CSV file compatible with
	 * the profile identified by the second parameter.
	 * This method reads data from the file, correlates it with the
	 * mapping stored in the profile, aggregates multiple orders if
	 * needed and decides when to actually save the order, by calling
	 * {@link saveOrder}(), which then saves the order unconditionally.
	 * On failure you can use {@link getErrors}() to determine the errors.
	 * @param string $file the path to the CSV file
	 * @param string $profile the profile to use for importing
	 * @return boolean true on success, false on failure
	 */
	function importOrders ($file, $profile) {
		if (!$this->loadProfile ($profile)) {
			$this->addError ("Can't import orders -- failed loading profile \"$profile\".");
			return false;
		}
		if (!$this->initFile ($file)) {
			$this->addError ("Can't import orders -- failed opening file!");
			return false;
		}
		$header = $this->readCSVLine ();
		if (!$header) {
			$this->addError ("Can't import orders -- failed reading header line from file!");
			return false;
		}
		$heading_data = $this->parseHeaderFields ($header);

		$mapping = array ();
		$usedFields = array ();
		foreach ($heading_data as $CSV_key => $CSV_field) {
			foreach ($this->columnMapping as $mapKey => $mapVal) {
				if (($CSV_field['type'] == 'numbered' && $CSV_field['prefix'] == $mapVal) || (($CSV_field['type'] == 'unique' || $CSV_field['type'] == 'numbered') && $CSV_field['name'] == $mapVal)) {
					$mapping[$CSV_key] = $mapKey;
					if (!isset ($usedFields[$mapKey])) {
						$usedFields[$mapKey] = 1;
					} else {
						$usedFields[$mapKey]++;
					}

				// We do NOT break here, because we want to allow re-using columns
				//break;
				}
			}
		}

		$usedCount = 0;
		$ok = true;
		foreach ($this->columnMapping as $mapKey => $mapVal) {
			if (!isset ($usedFields[$mapKey])) {
				$this->addError ("Field $mapVal ($mapKey) required by the profile not found in this file!");
				ob_start ();
				echo "<pre>";
				var_dump ($usedFields);
				echo "</pre>";
				$this->addError (ob_get_clean ());
				$ok = false;
			}

			if ($usedFields[$mapKey] > 1) {
				if ($usedCount == 0) {
					$usedCount = $usedFields[$mapKey];
				} elseif ($usedCount != $usedFields[$mapKey]) {
					$this->addError ("Numbered fields used incorrectly! (The same number of numbered fields must be used for all such fields in each row.)");
					$ok = false;
				}
			}
		}

		if (!$ok) {
			return false;
		}

		$last_order_id = false;
		$lastorder = array ();
// echo "<table border=1>\n";
		$ofi = $this->getOrderFieldInfo ();
		$recordNo = 0;
		while ($line = $this->readCSVLine ()) {
//      print_r($mapping);
			$recordNo++;
// echo "<tr>\n";
			$corder = array ();
			$count = 0;
			foreach ($line as $k => $val) {
				$v = $this->mapValue ($mapping[$k], $val, false);
				$count++;
				$myofi = NULL;
				foreach ($ofi as $cofi) {
					if ($cofi['name'] == $mapping[$k]) {
						$myofi = $cofi;
						break;
					}
				}
				if (!$myofi) {
					$this->addError ("Internal error: internal mapping mismatch! ({$mapping[$k]})");
					continue;
				}
//echo "<td bgcolor='#F0F0F0'>";
//$cmapping=$mapping[$k]; // used only for debugging
//if (!$cmapping) {
//$cmapping='[ignored]';
//} elseif ($heading_data[$k]['type']=='numbered') {
//$cmapping.="[".$heading_data[$k]['no']."]";
//}
//echo "<small>".$cmapping."</small><br>";
//echo $v;
//echo "==";print_r($heading_data[$k]);
//print_r($myofi);
//echo $this->columnType[$mapKey];

				if ($myofi['options']['numbered']) {
					$no = $this->columnType[$mapKey] == 'numbered' ? $heading_data[$k]['no'] : '';
					if (substr ($mapping[$k], 0, 4) == 'dsc_') {
						// Discount-related
						$corder['numbered_discount']['r_' . $recordNo . '.' . $no][$mapping[$k]] = $v;
					} else if (substr ($mapping[$k], 0, 4) == 'pkg_') {
						// Package-related
						$corder['numbered_package']['r_' . $recordNo . '.' . $no][$mapping[$k]] = $v;
					} else {
						// Item-related
						$corder['numbered_item']['r_' . $recordNo . '.' . $no][$mapping[$k]] = $v;
					}
				} elseif ($mapping[$k]) {
					$corder[$mapping[$k]] = $v;
				}
//echo "</td>\n";
			}
			$saved = false;
//echo "</tr>\n";
//echo "<tr><td colspan=$count>";
			if (isset ($corder['ordernum']) && $last_order_id === $corder['ordernum']) {
				$lastorder['numbered_item'] = array_merge ($lastorder['numbered_item'], $corder['numbered_item']);

				// FagSoft: Added error checking to prevent warnings.
				if (!is_array ($lastorder['numbered_discount'])) {
					$lastorder['numbered_discount'] = array ();
				}
				if (is_array ($corder['numbered_discount'])) {
					$lastorder['numbered_discount'] = array_merge ($lastorder['numbered_discount'], $corder['numbered_discount']);
				}

				if (!is_array ($lastorder['numbered_package'])) {
					$lastorder['numbered_package'] = array ();
				}
				if (is_array ($corder['numbered_package'])) {
					$lastorder['numbered_package'] = array_merge ($lastorder['numbered_package'], $corder['numbered_package']);
				}
				$corder = $lastorder;
//echo "Same order ID as the previous one, aggregating data.";
//$debug_aggregated=" (with aggregated data from earlier records)";
			} else {
				if ($lastorder) {
					$order_object = $this->saveOrder ($lastorder);
//echo "<b>Different order ID, saving previous order$debug_aggregated:</b><pre>";
//var_dump($order_object);
//echo "</pre>";
					$saved = true;
//} else {
//echo "<b>First record, storing data.</b><br>Data is always saved when a record with a different order ID is encountered (otherwise data from multiple records is aggregated).<br> Since this is the first record in the CSV file, we simply store the data and move on.";
				}
				$lastorder = $corder;
				$last_order_id = $corder['ordernum'];
				$debug_aggregated = "";
			}
//echo "</td></tr>\n";
		}
		if ($lastorder) {
			$this->saveOrder ($lastorder);
//echo "<tr><td colspan=$count>";
//echo "<b>This was the last record, saving previous order$debug_aggregated:</b><pre>";
//var_dump($this->saveOrder($lastorder));
//echo "</pre>";
//echo "</table>\n";
		}

		return true;
	}

	/**
	 * Private. Saves an order described by an array built by {@link importOrders}().
	 * This method receives an array describing the order from a CSV/mapping
	 * point of view, and does all the logic related to saving the order in
	 * the database -- it uses {@link getOrderFieldMapping}() to determine
	 * how the internal keys are mapped on the order/product objects, and
	 * proceeds to saving the data in the database using order::create(),
	 * order::addProduct() and order::saveOrder().
	 * @param array $order_data an array describing the order
	 * @return object order the instantiated order object, after saving
	 */
	function saveOrder ($order_data) {
		$ofm = $this->getOrderFieldMapping ();
		$order_total_amount = false;
		$shipdata = $billdata = $itemdata = $orderinfo = $bname = $dname = $shipxtra = array ();
		$itemdata = $items = $discount = array ();
		$numbered_data = $numbered_discount = $numbered_package = array ();
/*
echo "<font color=red><b>order_data</b><pre>";
var_dump($order_data);
echo "</pre></font>";
*/

		foreach ($order_data as $field => $value) {
			if (!$ofm[$field]) {
				$this->addError ("Unknown field type \"$field\" (value=$value)");
				continue;
			}
			foreach ($ofm[$field] as $map) {
				switch ($map[0]) {
					case 'void' :
						break;
					case 'shipdata' :
						$shipdata[$map[1]] = $value;
						break;
					case 'billdata' :
						$billdata[$map[1]] = $value;
						break;
					case 'package' :
						$package[$map[1]] = $value;
						if ($map[1] == 'num' && !isset ($ord_no) && preg_match ('/(.*)-.*/', $value, $pkg_p))
							$ord_no = $pkg_p[1];
						break;
					case 'info' :
						$orderinfo[$map[1]] = $value;
						break;
					case 'bname' :
						$bname[$map[1]] = $value;
						break;
					case 'dname' :
						$dname[$map[1]] = $value;
						break;
					case 'itemdata' :
						$itemdata[$map[1]] = $value;
						break;
					case 'discount' :
						$discountdata[$map[1]] = $value;
						break;
					case 'numbered_item' :
						$numbered_data = $value;
						break;
					case 'numbered_discount' :
						$numbered_discount = $value;
						break;
					case 'numbered_package' :
						$numbered_package = $value;
						break;
					case 'total_amount' :
						$order_total_amount = $value;
						break;
					case 'shipxtra' :
						$shipxtra[$map[1]] = $value;
						break;
					case 'ordernum' :
						$ord_no = $value;
						break;
					case 'comments' :
						$comments = $value;
						break;
					// FagSoft: Add processing for additional fields from Amazon.
					case 'date_purch' :
						$datePurchased = $value;
						break;
					case 'payment_method' :
						$payMethod = $value;
						break;
				}
			}
		}
/*
echo "<b>Discount</b><pre>";
var_dump($numbered_discount);
echo "</pre>";
*/
		if ($itemdata) {
			$items = array ($itemdata);
		}

		foreach ($numbered_data as $itemdata) {
			$citemdata = array ();
			foreach ($itemdata as $field => $value) {
				if (!$ofm[$field]) {
					$this->addError ("Unknown item field type \"$field\" (value=$value)");
					continue;
				}

				foreach ($ofm[$field] as $map) {
					switch ($map[0]) {
						case 'itemdata' :
							$citemdata[$map[1]] = $value;
							break;
						default :
							$this->addError ("Only item data should be stored in numbered order data -- " . $map[1] . " (internal error, this error should never be issued)");
					}
				}
			}
			$items[] = $citemdata;
		}

		if ($discountdata) {
			$discount = array ($discountdata);
		}

		foreach ($numbered_discount as $discountitem) {
			$cdiscountitem = array ();
			foreach ($discountitem as $field => $value) {
				if (!$ofm[$field]) {
					$this->addError ("Unknown discount field type \"$field\" (value=$value)");
					continue;
				}
				foreach ($ofm[$field] as $map) {
					switch ($map[0]) {
						case 'discount' :
							$cdiscountitem[$map[1]] = $value;
							break;
						default :
							$this->addError ("Only discount data should be stored in numbered_discount order data -- " . $map[1] . " (internal error, this error should never be issued)");
					}
				}
			}
			$discount[] = $cdiscountitem;
		}

		if ($package) {
			$packages = array ($package);
		}

		foreach ($numbered_package as $pkg) {
			$cpkg = array ();
			foreach ($pkg as $field => $value) {
				if (!$ofm[$field]) {
					$this->addError ("Unknown discount field type \"$field\" (value=$value)");
					continue;
				}

				foreach ($ofm[$field] as $map) {
					switch ($map[0]) {
						case 'package' :
							$cpkg[$map[1]] = $value;
							if ($map[1] == 'num' && !isset ($ord_no) && preg_match ('/(.*)-.*/', $value, $pkg_p))
								$ord_no = $pkg_p[1];
							break;
						default :
							$this->addError ("Only package data should be stored in numbered_discount order data -- " . $map[1] . " (internal error, this error should never be issued)");
					}
				}
			}
			$packages[] = $cpkg;
		}

		$bname = $this->processName ($bname);
		if ($bname) {
			$billdata['name'] = $bname;
		}

		$dname = $this->processName ($dname);
		if ($dname) {
			$shipdata['name'] = $dname;
		}

		if ($ord_no) {
			if ($o_ids = order::queryInfoRefIDs ($this->getClass (), 'ord_no', $ord_no)) {
				$order = new order ($o_ids[0]);

				if ($packages && $order->info['orders_status'] >= 2) {
					foreach ($packages as $pkg) {
						print_r ($pkg);
						if (isset ($pkg['tracking']))
							$order->setTrackingNumber ($pkg['num'], $pkg['tracking']);
					}
					$order->setStatus (3);
					$order->saveOrder ();
				}
				return $o_ids[0];
			}
		}

		if (!$shipdata && !$billdata)
			return NULL;
		$order = new order ();
		$order->create (NULL, $shipdata, $billdata);
//      $order->info=$orderinfo;


		// These are initialized outside the loop in order to allow inheritance
		// (i.e. allowing any of these to be static fields, while any of the others
		// can be numbered)
		$price = NULL;
		$qty = 1;
		$item_id = 0;

		foreach ($items as $item) {
// print_r ($item);
			if ($item['qty']) {
				$qty = $item['qty'];
				unset ($item['qty']);
			}
			if ($item['price']) {
				$price = $item['price'];
				unset ($item['price']);
			}
			if ($item['upc']) {
				$prod = IXproduct::findByUPC ($item['upc']);
				$item_id = isset ($prod) ? $prod->getID () : 0;
				unset ($item['upc']);
			}

			// FagSoft: Hack to retrieve the proper product ID from the Amazon SKU.
			if ($item_id == 0 && $payMethod == 'payment_amazonSeller') {
				$query = "SELECT p.products_id, p.products_model FROM " . TABLE_DBFEEDS_PROD_EXTRA . " AS pe ".
						"INNER JOIN ".TABLE_PRODUCTS." AS p ON p.products_id = pe.products_id ".
						"WHERE pe.extra_value = '" . mysql_real_escape_string ($item['sku']) . "' && " .
						"pe.extra_field = 'sku' && pe.dbfeed_class LIKE 'dbfeed_amazon%'";
				if (!$res = mysql_query ($query)) {
					die ("Error while retrieving item ID: " . mysql_error () . "\nSQL: $query\n" . __FILE__ . ':' . __LINE__);
				}

				if (mysql_num_rows ($res) == 0) {
					trigger_error ("Amazon SKU not found for item $item_id, with sku: '{$item['sku']}", E_USER_WARNING);
				} else {
					$item_id = mysql_result ($res, 0, 0);
					$item['model'] = mysql_result ($res, 0, 1);
				}
				unset ($item['sku']);
			}

			if ($item['ship'])
				$shipxtra['cost'] += $item['ship'];
			if ($item['ship2'])
				$shipxtra['cost2'] += $item['ship2'];
			$order->addProduct ($item_id, $qty, Array (), $price, $item);
		}

		foreach ($discount as $key => $value) {
			if ($value['reason'] && $value['amount']) {
				$order->setPromo (NULL, Array ('reason' => $value['reason'], 'amount' => abs ($value['amount'])));
			}
		}
		if ($shipxtra) {
			$order->setShipping (NULL, $shipxtra['cost'] + $shipxtra['cost2'], $shipxtra['method']);
		}

		if ($order_total_amount !== false) {
			$cprice = $order->getSubTotal ();
			$diff = $order_total_amount - $cprice;
//$order->addProduct([fake id],1,array(),$diff);
		}

		if ($comments)
			$order->info['comments'] = $comments;

		// FagSoft: Added fields from Amazon feed.
		if ($datePurchased)
			$order->info['date_purchased'] = $datePurchased;
		if ($payMethod)
			$order->info['payment_method'] = $payMethod;

		$order->saveOrder ();

		// FagSoft: Save the order-item-id from Amazon in the orders_items_refs table.
		foreach ($items as $item) {
			if (!empty ($ord_no) || !empty ($item['order_item_id'])) {
				$amazonProdID = $item['order_item_id'];
				$ixProdID = $order->orderItemIDs[$amazonProdID];
				$order->addInfoRef ($this->getClass (), 'ord_no', $ord_no, $amazonProdID, $ixProdID);
			}
		}

		// FagSoft: If Amazon, add line to payments table.
		if ($payMethod == 'payment_amazonSeller') {
			$query = "INSERT INTO `payments` (`orders_id`, `method`, `status`, `amount`, ".
					"`date_created`, `date_processed`, `ref_id` ) VALUES ".
					"(%1\$d, 'payment_amazonSeller', 'complete', %2\$0.2f, '%3\$s', '%3\$s', '%4\$s')";
			$query = sprintf ($query, $order->orderid, $order->getSubTotal (),
					mysql_real_escape_string ($datePurchased),
					mysql_real_escape_string ($ord_no));
			if (!mysql_query($query)) {
				die ("Could not add payment information.\nError: ".mysql_error()."\nSQL: $query");
			}
		}

		return $order;
	}

	/**
	 * Private. Processes an array describing a name into a string.
	 * A simple method to parse an associative array of the
	 * form
	 * <code>
	 * array(
	 * 'fname'=>'John',
	 * 'lname'=>'Doe'
	 * )
	 * </code>
	 * into the string "John Doe", given that any of the
	 * two fields in the array may be missing.
	 * Typically used by {@link saveOrder}() for processing
	 * the billing/shipping person's name (in the CSV names come as
	 * two distinct fields, and in the database we store them as one string).
	 * @param array associative array describing a name
	 * @return string the string representation of the same name
	 */
	function processName ($name) {
		if (!$name) {
			return NULL;
		}
		if ($name['fname'] && $name['lname']) {
			$name = $name['fname'] . ' ' . $name['lname'];
		} elseif ($name['fname']) {
			$name = $name['fname'];
		} elseif ($name['lname']) {
			$name = $name['lname'];
		} else {
			$name = NULL;
		}
		return $name;
	}

	function writeCSVline ($data) {
		return fputcsv ($this->fp, $data, $this->fdelimiter, $this->enclosure);
	}

	function exportHeader ($ids, $options) {
		if ($this->exportMode == 'multiline') {
			$line = array_values ($this->columnMapping);
			$this->_headerMapping = array_keys ($this->columnMapping);
			return $this->writeCSVline ($line);
		}
		// wildcard -- must determine the number of columns
		$this->_itemDupes = max (array_values (order::batchItemCount ($ids, 'orderitem_product')));
		$this->_dscDupes = max (array_values (order::batchItemCount ($ids, 'orderitem_promo')));
		$line = $headers = array ();
		foreach ($this->columnMapping as $mapKey => $mapVal) {
			if (!$options[$mapKey]['numbered']) {
				$line[] = $mapVal;
				$headers[] = $mapKey;
				continue;
			}
			$keyX = explode ("_", $mapKey);
			switch ($keyX[0]) {
				case 'p' :
					$repeat = $this->_itemDupes;
					break;
				case 'dsc' :
					$repeat = $this->_dscDupes;
					break;
				default :
					$this->addError ("Unknown field type: $mapKey");
					continue;
			}
			if ($repeat < 2) {
				$line[] = $mapVal;
				$headers[] = $mapKey;
				continue;
			}
			for ($i = 1; $i <= $repeat; $i++) {
				$line[] = $mapVal . $i;
				$headers[] = $mapKey;
			}
		}
		$this->_headerMapping = $headers;
		return $this->writeCSVline ($line);
	}

	function exportOrdersStart ($file, $profile, $ids) {
		if (!$this->loadProfile ($profile)) {
			$this->addError ("Can't export orders -- failed loading profile \"$profile\"!");
			return false;
		}
		if (!$this->initFile ($file, 'w')) {
			return false;
		}
		$ofi = $this->getOrderFieldInfo ();
		$this->xoptions = array ();
		foreach ($ofi as $fi) {
			$this->xoptions[$fi['name']] = $fi['options'];
		}
		if (!$this->exportHeader ($ids, $this->xoptions)) {
			return false;
		}
		return true;
	}

	function exportOrdersProcess (&$order) {
		$line = $lines = $counters = array ();
		$items = $order->getProductList ();
		$discounts = $order->getPromoList ();
		$wts = $order->getShippingWeights ();
		$packages = Array ();
		foreach ($wts as $idx => $w)
			$packages[] = Array ('num' => $order->orderid . '-' . ($idx + 1), 'weight' => $w);

		// We need to increment at the beginning of the loop as to avoid messing around when we continue within the block
		$i = -1;
		foreach ($this->_headerMapping as $idx => $mapKey) {
			$i++;
			$line[$i] = '';
			if (!$this->xoptions[$mapKey]['numbered']) {
				$line[$i] = $this->mapValue ($mapKey, $this->getOrderData ($order, $mapKey), true);
				if ($mapKey == 'ordernum')
					$order->addInfoRef ($this->getClass (), 'ord_no', $line[$i]);
				continue;
			}
			if (!preg_match ('/^numbered_/', $this->columnMapping[$mapKey]))
				continue;

		//          if ($this->exportMode=='multiline') {
			//            $line[$i]='';
			//            continue;
			//          }
			$keyX = explode ('_', $mapKey);
			switch ($keyX[0]) {
				case 'p' :
					$beef = &$items;
					break;
				case 'dsc' :
					$beef = &$discounts;
					break;
				case 'pkg' :
					$beef = &$packages;
					break;
				default :
					$this->addError ("Unknown field $mapKey!");
					continue;
			}
			if (!isset ($counters[$mapKey])) {
				$counters[$mapKey] = 0;
			}
			if (isset ($beef[$counters[$mapKey]][$keyX[1]])) {
				$line[$i] = $beef[$counters[$mapKey]][$keyX[1]];
			} else {
				$line[$i] = '';
			}
			$counters[$mapKey]++;
		}

		$lines = Array ();
		for ($idx = 0;; $idx++) {
			$myLine = $line;
			$i = -1;
			$f = 0;
			foreach ($this->columnMapping as $mapKey => $mapVal) {
				$i++;
				if (preg_match ('/^numbered_/', $this->columnMapping[$mapKey]))
					continue;
				$keyX = explode ("_", $mapKey);
				unset ($ref);
				$ffld = 1;
				switch ($keyX[0]) {
					case 'p' :
						$ref = &$items[$idx];
						break;
					case 'dsc' :
						$ref = &$discounts[$idx];
						break;
					case 'pkg' :
						$ref = &$packages[$idx];
						break;
					default :
						$ffld = 0;
				}
				if ($ffld) {
					if (isset ($ref)) {
						$myLine[$i] = $ref[$keyX[1]];
						$f = 1;
					} else
						$myLine[$i] = '';
				}
			}
			if ($f)
				$lines[] = $myLine;
			else
				break;
		}
		if (!$lines)
			$lines[] = $line;

		foreach ($lines as $line) {
			if (!$this->writeCSVline ($line))
				return false;
		}
		return true;
	}

	function mapValue ($field, $value, $export = 0) {
		if ($this->columnOptions[$field])
			foreach ($this->columnOptions[$field] as $vmap => $optns)
				switch ($vmap) {
					case 'map' :
						$db = split (',', $optns['db']);
						$csv = split (',', $optns['csv']);
						if ($export) {
							$value = ($idx = array_search (trim ($value), $db)) === false ? $optns['csv_default'] : $csv[$idx];
						} else {
							$value = ($idx = array_search (trim ($value), $csv)) === false ? $optns['db_default'] : $db[$idx];
						}
						break;
					case 'regex' :
						$wh = $export ? 'db' : 'csv';
						$value = preg_replace ('/' . str_replace ('/', '\\/', $optns[$wh]) . '/', $optns[$wh . '_replace'], $value);
						break;
				}
		return $value;
	}

	function getOrderData (&$order, $field) {
		$keyX = explode ("_", $field);
		switch ($keyX[0]) {
			case 'ordernum' :
				return $order->orderid;
			case 'comments' :
				return $order->info['comments'];
			case 'custemail' :
				return $order->customer['email_address'];
			case 'phone' :
				return $order->customer['telephone'];
			case 'd' :
				$addr = $order->getShipTo ();
				if (!$addr) {
					return NULL;
				}
			case 'b' :
				if ($keyX[0] == 'b') {
					$addr = $order->getBillTo ();
					if (!$addr) {
						return NULL;
					}
				}
				switch ($keyX[1]) {
					case 'fname' :
						return $addr->getFirstName ();
					case 'lname' :
						return $addr->getLastName ();
					case 'addr1' :
						return $addr->getAddress ();
					case 'addr2' :
						return $addr->getAddress2 ();
					case 'city' :
						return $addr->getCity ();
					case 'state' :
						return $addr->getZoneName ();
					case 'pcode' :
						return $addr->getPostCode ();
					case 'country' :
						return $addr->getCountryName ();
					case 'company' :
						return $addr->getCompany (false);
					default :
						$this->addError ("Unknown address field $field!");
						return false;
				}
			case 's' :
				$shipdata = $order->getShipping ();
				switch ($keyX[1]) {
					case 'method' :
						return $shipdata['name'];
					case 'cost' :
						return $shipdata['cost'];
					default :
						$this->addError ("Unknown shipping field $field!");
						return false;
				}
			case 'total' :
				if ($field != 'total_amt') {
					$this->addError ("Unknown field $field!");
					return false;
				}
				return $order->getSubTotal ();
		}
		return $field . "#" . $orderID;
	}

	function doExport ($ids, $profile = NULL, $ftp = NULL, $force = false) {
		if (!$ids)
			return Array ('count' => 0);
		if (!$profile)
			$profile = $this->getConf ('export_profile');
		if (!isset ($ftp)) {
			$ftp = $this->getConf ('export_ftp');
			$fd = $this->getConf ('export_last_num') + 1;
			$gz = $this->getConf ('export_zone');
			$this->setConf ('export_last_num', $fd);
			// Fuck
			$this->saveConf ();
			if (preg_match ('|/$|', $ftp))
				$ftp .= sprintf ("%06d.csv", $fd);
			$ftp = str_replace ('*', sprintf ("%06d", $fd), $ftp);
			$ftp = preg_replace ('|(\\?+)|e', 'sprintf("%0".strlen("\1")."d",$fd);', $ftp);
		}
		$fname = tempnam ("csv_export_temp_", "non-existent-directory");
		$ct = 0;
		$init = false;
		$success = true;
		$refs = Array ();
		foreach ($ids as $id) {
			$order = new order ($id);
			$refs[$id] = '';
			if (!$force) {
				$addr = $order->getShipTo ();
				if (!$addr->matchGeoZone ($gz))
					continue;
			}
			if ($order->info['orders_status'] < 2)
				continue;
			if (!$init && !$this->exportOrdersStart ($fname, $profile, $ids))
				return false;
			$init = 1;
			if ($this->exportOrdersProcess ($order))
				$ct++;
			else
				$success = false;
			$refs[$id] = $ftp;
		}
		fclose ($this->fp);
		if ($success && $ct) {
			if ($ftp == '') {
				if ($fd = fopen ($fname, 'r')) {
					while (!feof ($fd))
						echo fgets ($fd, 65536);

		//."\r\n";
					fclose ($fd);
				} else
					$success = false;
			} else {
				$dst = parse_url ($ftp);
				if ($dst['scheme'] == 'ftp') {
					$dp = ftp_connect ($dst['host']);
					if ($dp) {
						if ($dst['user'])
							ftp_login ($dp, $dst['user'], $dst['pass']);
						$success = ftp_put ($dp, $dst['path'], $fname, FTP_BINARY);
						ftp_close ($dp);
					} else
						$success = 0;
				} else {
					$success = copy ($fname, $dst);
				}
			}
		}
		unlink ($fname);
		if (!$success) {
			$this->addError ($msg);
			return false;
		}
		if ($ftp)
			foreach ($ids as $id) {
				$order = new order ($id);
				$order->addInfoRef ($this->getClass (), 'export', $refs[$id]);
			}
		return Array ('count' => $ct, 'file' => $ftp);
	}

	function sendOrder (&$order) {}

	function isReady () {
		return true;
	}

	function listConf () {
		$prfs = Array ();
		return Array ('export_ftp' => Array ('title' => 'FTP upload url', 'type' => 'text', 'default' => 'ftp://user:pass@ftphost.com/path'), 'export_profile' => Array ('title' => 'Export Profile', 'type' => 'text', 'default' => ''), 'export_zone' => Array ('title' => 'Export Orders within Geo Zone', 'type' => 'select', 'default' => 0, 'options' => Array (0 => '[ANY]') + IXdb::read ("SELECT * FROM geo_zones", 'geo_zone_id', 'geo_zone_name')), 'export_wh' => Array ('title' => 'Export Orders for Warehouse', 'type' => 'select', 'default' => 0, 'options' => Array (0 => '[ANY]') + IXdb::read ("SELECT * FROM suppliers", 'suppliers_id', 'suppliers_group_name')));
	}

	function exportNewOrders ($profile = NULL, $dest = NULL) {
		return $this->doExport (order::queryInfoRefIDs ($this->getClass (), 'export', NULL), $profile, $dest);
	}

	function actionList () {
		return Array ('do_export' => 'Export New Orders');
	}

	function actionPerform ($ac) {
		switch ($ac) {
			case 'do_export' :
				$rs = $this->exportNewOrders ();
				if (!$rs)
					return 'An error occurred';
				if ($rs['error'])
					return 'Error: ' . $rs['error'];
				if ($rs['count'] == 0)
					return 'No orders to export';
				return Array ("{$rs['count']} orders exported", "File: {$rs['file']}");
		}
	}

}

if (!function_exists ('fputcsv')) {

	function fputcsv ($fp, $data, $delimiter = ",", $enclosure = '"') {
		return @fputs ($fp, $enclosure . implode ($data, $enclosure . $delimiter . $enclosure) . $enclosure . "\n");
	}
}
?>

