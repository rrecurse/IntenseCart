<?php

define ('DEBUG', false);

// Mail addresses
define ('MAIL_FS_ABUSE', "chrisd@zwaveproducts.com");
define ('MAIL_FS_ERROR', 'chrisd@zwaveproducts.com');

/**
 * Custom error handler for SoftFrame.
 *
 * @param int $ErrNo
 * @param mixed $ErrMsg
 * @param string $File
 * @param int $Line
 * @param array $Context
 *
 * @return void
 */
function error_handler ($ErrNo, $ErrMsg, $File, $Line, $Context) {
	// By default we do not want to show users the errors.
	$Die = false;
	$Type = 'error';

	switch ($ErrNo) {
		// Non-recoverable errors.
		case E_USER_ERROR:
		case E_ERROR:
		case E_PARSE:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
			$Die = true;
			break;

		// Recoverable errors.
		case E_USER_WARNING:
		case E_USER_NOTICE:
		case E_WARNING:
		case E_CORE_WARNING:
		case E_COMPILE_WARNING:
		case E_DEPRECATED:
		case E_USER_DEPRECATED:
			$Type = 'warning';
		case E_RECOVERABLE_ERROR:
			break;
		case E_STRICT:
		default:
			// Let the default PHP error handler take care of any remaining errors.
			return false;
	}

	// Translate error code to a readable string.
	switch ($ErrNo) {
		case 1:     $ErrType = 'E_ERROR'; break;
		case 2:     $ErrType = 'E_WARNING'; break;
		case 4:     $ErrType = 'E_PARSE'; break;
		case 8:     $ErrType = 'E_NOTICE'; break;
		case 9:     $ErrType = 'E_USER_ERROR'; break;
		case 16:    $ErrType = 'E_CORE_ERROR'; break;
		case 32:    $ErrType = 'E_CORE_WARNING'; break;
		case 64:    $ErrType = 'E_COMPILE_ERROR'; break;
		case 128:   $ErrType = 'E_COMPILE_WARNING'; break;
		case 256:   $ErrType = 'E_USER_ERROR'; break;
		case 512:   $ErrType = 'E_USER_WARNING'; break;
		case 1024:  $ErrType = 'E_USER_NOTICE'; break;
		case 2048:  $ErrType = 'E_STRICT'; break;
		case 4096:  $ErrType = 'E_RECOVERABLE_ERROR'; break;
		case 8192:  $ErrType = 'E_DEPRECATED'; break;
		case 16384: $ErrType = 'E_USER_DEPRECATED'; break;
		case 30719: $ErrType = 'E_ALL'; break;
		default:    $ErrType = 'E_UNKNOWN'; break;
	}

	// Generate the stack trace, and drop the trace for the error handler.
	$StackTrace = '';
	$Temp = debug_backtrace ();
	array_shift ($Temp);
	foreach ($Temp as $StackLine) {
		$StackTrace .= print_r ($StackLine, true);
	}

	// Read the e-mail/debug template.
	$Template = <<<OutMail
An error of level %1\$s was generated in file %2\$s on line %3\$d.

** Requested URI:
%4\$s

** The error message was:
%5\$s

** Post data:
%6\$s

** Stacktrace:
%8\$s

** The following variables were set in the scope that the error occurred in:
%7\$s
OutMail;

	// Get the variable context and contents of the $_POST array.
	$Context = print_r ($Context, true);
	$PostVal = print_r ($_POST, true);

	// Are we in a debug environment?
	if (DEBUG) {
		// Ready output for HTML printing.
		$Context = "<pre>".htmlspecialchars ($Context, ENT_QUOTES, 'utf-8')."</pre>";
		$PostVal = "<pre>".htmlspecialchars ($PostVal, ENT_QUOTES, 'utf-8')."</pre>";
		$StackTrace = "<pre>".htmlspecialchars ($StackTrace, ENT_QUOTES, 'utf-8')."</pre>";
		$Template = nl2br ($Template);
		$ErrMsg = nl2br ($ErrMsg);
	}

	// Build error message.
	if (isset ($_SERVER['argv']) && is_array ($_SERVER['argv'])) {
		$Cmd = getcwd().'/'.implode (' ', $_SERVER['argv']);
	} else {
		$Cmd = '';
	}

	$Message = sprintf ($Template, $ErrType, $File, $Line, $Cmd, $ErrMsg, $PostVal, $Context, $StackTrace);

	// Mail error message if script is in production, otherwise display on screen.
	if (!DEBUG) {
		Send_Mail ("IntenseCart Amazon API error handler", MAIL_FS_ERROR, "Error level $ErrNo", $Message, MAIL_FS_ERROR);
	}
return false;

	// Kill script with message if error is unrecoverable.
	if ($Die) {
		// Temp hack: Let PHP deal with errors
		return false;
		die ($Message);
	}

	// Prevent standard error handler from taking over.
	return true;
}

/**
 * Sends mail to selected recipient, $WebAuthor by default.
 * Also creates headers, and MIME-encodes $Subject
 *
 * @param string $Sender
 * @param string $FromMail
 * @param string $Subject
 * @param string $Content
 * @param string $Rcpt
 *
 * @package 3._Function_library
 */
function Send_Mail ($Sender, $FromMail, $Subject, $Content, $Rcpt = '') {
	// Check if recipient is selected.
	if (empty ($Rcpt)) {
		// Wasn't, set to $WebAuthor.
		$Rcpt = "IntenseCart eCommerce <".WEBAUTHOR.">";
	}

	// Create mail headers.
	$Headers = "From: $Sender <$FromMail>\n".
		"Reply-To: $Sender <$FromMail>\n".
		"Return-Path: ".MAIL_FS_ABUSE."\n".
		"Organization: IntenseCart eCommerce\n".
		"Content-Type: text/plain; format=flowed; delsp=yes; charset=utf-8\n".
		"MIME-Version: 1.0\n".
		"Content-Transfer-Encoding: 7bit\n".
		"User-Agent: Opera Mail/9.25 (Linux)\n";

	// Send mail.
	if (!mail ($Rcpt, EncodeSubject ($Subject), $Content, $Headers)) {
		if ($Rcpt == MAIL_FS_ERROR) {
			echo "FATAL ERROR! -- Could not send e-mail to $Rcpt";
			return;
		}

		// Failed, show error.
		trigger_error ("Could not send mail to $Rcpt", E_USER_ERROR);
	}
}

function EncodeSubject ($Subject) {
	return '=?UTF-8?B?'.base64_encode ($Subject).'?=';
}

// Register as error handler.
set_error_handler ("error_handler");
//register_shutdown_function ('error_handler');

