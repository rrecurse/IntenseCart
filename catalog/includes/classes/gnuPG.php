<?php

/**
 * Class to interact with the gnuPG.
 *
 * @package   gnuPG_class
 * @author    Enrique Garcia Molina <egarcia@egm.as>
 * @copyright Copyright (c) 2004-2005, EGM :: Ingenieria sin fronteras
 * @license   GNU LGPL (http://www.gnu.org/copyleft/lesser.html)
 * @since     Viernes, Enero 30, 2004
 * @version   $Id: gnuPG_class.inc,v 1.0.9 2005-07-20 11:59:00-05 egarcia Exp $
 * @see       readme.txt
 */
class gnuPG
{
	/**
	* the path to gpg executable (default: /usr/local/bin/gpg)
	* @access private
	* @var string
	*/
	var $program_path;
	
	/**
	* The path to directory where personal gnupg files (keyrings, etc) are stored (default: ~/.gnupg)
	* @access private
	* @var string
	*/
	var $home_directory;
	
	/**
	* Error and status messages
	* @var string
	*/
	var $error;
	
	/**
	* Output message
	* @var string
	*/
	var $output;
	
	/**
	* Create the gnuPG object.
	*
	* Set the program path for the GNUPG and the home directory of the keyring.
	* If this parameters are not specified, according to the OS the function derive the values.
	*
	* @param  string $program_path   Full program path for the GNUPG
	* @param  string $home_directory Home directory of the keyring
	* @return void
	*/
	function gnuPG($program_path = false, $home_directory = false)
	{
		// if is empty then assume the path based in the OS
		if (empty($program_path)) {
			if ( strstr(PHP_OS, 'WIN') )
				$program_path = 'C:\gnupg\gpg';
			else
				$program_path = '/usr/local/bin/gpg';
		}
		$this->program_path = $program_path;
		
		// if is empty the home directory then assume based in the OS
		if (empty($home_directory)) {
			if ( strstr(PHP_OS, 'WIN') )
				$home_directory = 'C:\gnupg';
			else
				$home_directory = '~/.gnupg';
		}
		$this->home_directory = $home_directory;
	}
	
	/**
	* Call a subprogram redirecting the standard pipes
	*
	* @access private
	* @param  string $command The full command to execute
	* @param  string $input   The input data
	* @param  string $output  The output data
	* @return bool   true on success, false on error
	*/
	function _fork_process($command, $input = false, &$output)
	{
		// define the redirection pipes
		$descriptorspec = array(
			0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
			1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
			2 => array("pipe", "w")   // stderr is a pipe that the child will write to
		);
		$pipes = null;
		
		// calls the process
		$process = proc_open($command, $descriptorspec, $pipes);
		if (is_resource($process)) {
			// writes the input
			if (!empty($input)) fwrite($pipes[0], $input);
			fclose($pipes[0]);
			
			// reads the output
			while (!feof($pipes[1])) {
				$data = fread($pipes[1], 1024);
				if (strlen($data) == 0) break;
				$output .= $data;
			}
			fclose($pipes[1]);
			
			// reads the error message
			$result = '';
			while (!feof($pipes[2])) {
				$data = fread($pipes[2], 1024);
				if (strlen($data) == 0) break;
				$result .= $data;
			}
			fclose($pipes[2]);
			
			// close the process
			$status = proc_close($process);
			
			// returns the contents
			$this->error = $result;
			return ($status == 0);
		} else {
			$this->error = 'Unable to fork the command';
			return false;
		}
	}
	
	/**
	* Get the keys from the KeyRing.
	*
	* The returned array get the following elements:
	* [RecordType, CalculatedTrust, KeyLength, Algorithm,
	*  KeyID, CreationDate, ExpirationDate, LocalID,
	*  Ownertrust, UserID]
	*
	* @param  string $KeyKind the kind of the keys, can be secret or public
	* @return mixed  false on error, the array with the keys in the keyring in success
	*/
	function ListKeys($KeyKind = 'public')
	{
		// validate the KeyKind
		$KeyKind = strtolower(substr($KeyKind, 0, 3));
		if (($KeyKind != 'pub') && ($KeyKind != 'sec')) {
			$this->error = 'The Key kind must be public or secret';
			return false;
		}
		
		// initialize the output
		$contents = '';
		
		// execute the GPG command
		if ( $this->_fork_process($this->program_path . ' --homedir ' . $this->home_directory .
				' --with-colons ' . (($KeyKind == 'pub') ? '--list-public-keys': '--list-secret-keys'),
			false, $contents) ) {
			
			// initialize the array data
			$returned_keys = array();
			
			// the keys are \n separated
			$contents = explode("\n", $contents);
			
			// find each key
			foreach ($contents as $data) {
				// read the fields to get the : separated, the sub record is dismiss
				$fields = explode(':', $data);
				if (count($fields) <= 3) continue;
				
				// verify the that the record is valid
				if (($fields[0] == 'pub') || ($fields[0] == 'sec')) {
					array_push($returned_keys, array(
						'RecordType' => $fields[0],
						'CalculatedTrust' => $fields[1],
						'KeyLength' => $fields[2],
						'Algorithm' => $fields[3],
						'KeyID' => $fields[4],
						'CreationDate' => $fields[5],
						'ExpirationDate' => $fields[6],
						'LocalID' => $fields[7],
						'Ownertrust' => $fields[8],
						'UserID' => $fields[9]
						)
					);
				}
			}
			return $returned_keys;
		} else
			return false;
	}
	
	/**
	* Export a key.
	*
	* Export all keys from all keyrings, or if at least one name is given, those of the given name.
	*
	* @param  string $KeyID  The Key ID to export
	* @return mixed  false on error, the key block with the exported keys
	*/
	function Export($KeyID = false)
	{
		$KeyID = empty($KeyID) ? '': $KeyID;
		
		// initialize the output
		$contents = '';
		
		// execute the GPG command
		if ( $this->_fork_process($this->program_path . ' --homedir ' . $this->home_directory .
				' --armor --export ' . $KeyID,
			false, $contents) )
			return (empty($contents) ? false: $contents);
		else
			return false;
	}
	
	/**
	* Import/merge keys.
	*
	* This adds the given keys to the keyring. New keys are appended to your
	* keyring and already existing keys are updated. Note that GnuPG does not
	* import keys that are not self-signed.
	*
	* @param  string $KeyBlock  The PGP block with the key(s).
	* @return mixed  false on error, the array with [KeyID, UserID] elements of imported keys on success.
	*/
	function Import($KeyBlock)
	{
		// Verify for the Key block contents
		if (empty($KeyBlock)) {
			$this->error = 'No valid key block was specified.';
			return false;
		}
		
		// initialize the output
		$contents = '';
		
		// execute the GPG command
		if ( $this->_fork_process($this->program_path . ' --homedir ' . $this->home_directory .
				' --status-fd 1 --import',
			$KeyBlock, $contents) ) {
			// initialize the array data
			$imported_keys = array();
			
			// parse the imported keys
			$contents = explode("\n", $contents);
			foreach ($contents as $data) {
				$matches = false;
				if (preg_match('/\[GNUPG:\]\sIMPORTED\s(\w+)\s(.+)/', $data, $matches))
					array_push($imported_keys, array(
						'KeyID' => $matches[1],
						'UserID' => $matches[2]));
			}
			return $imported_keys;
		} else
			return false;
	}
	
	/**
	* Generate a new key pair.
	*
	* @param  string $RealName     The real name of the user or key.
	* @param  string $Comment      Any explanatory commentary.
	* @param  string $Email        The e-mail for the user.
	* @param  string $Passphrase   Passphrase for the secret key, default is not to use any passphrase.
	* @param  string $ExpireDate   Set the expiration date for the key (and the subkey).  It may either be entered in ISO date format (2000-08-15) or as number of days, weeks, month or years (<number>[d|w|m|y]). Without a letter days are assumed.
	* @param  string $KeyType      Set the type of the key, the allowed values are DSA and RSA, default is DSA.
	* @param  int    $KeyLength    Length of the key in bits, default is 1024.
	* @param  string $SubkeyType   This generates a secondary key, currently only one subkey can be handled ELG-E.
	* @param  int    $SubkeyLength Length of the subkey in bits, default is 1024.
	* @return mixed  false on error, the fingerprint of the created key pair in success
	*/
	function GenKey($RealName, $Comment, $Email, $Passphrase = '', $ExpireDate = 0, $KeyType = 'DSA', $KeyLength = 1024, $SubkeyType = 'ELG-E', $SubkeyLength = 1024)
	{
		// validates the keytype
		if (($KeyType != 'DSA') && ($KeyType != 'RSA')) {
			$this->error = 'Invalid Key-Type, the allowed are DSA and RSA';
			return false;
		}
		
		// validates the subkey
		if ((!empty($SubkeyType)) && ($SubkeyType != 'ELG-E')) {
			$this->error = 'Invalid Subkey-Type, the allowed is ELG-E';
			return false;
		}
		
		// validate the expiration date
		if (!preg_match('/^(([0-9]+[dwmy]?)|([0-9]{4}-[0-9]{2}-[0-9]{2}))$/', $ExpireDate)) {
			$this->error = 'Invalid Expire Date, the allowed values are <iso-date>|(<number>[d|w|m|y])';
			return false;
		}
		
		// generates the batch configuration script
		$batch_script  = "Key-Type: $KeyType\n" .
			"Key-Length: $KeyLength\n";
		if (($KeyType == 'DSA') && ($SubkeyType == 'ELG-E'))
			$batch_script .= "Subkey-Type: $SubkeyType\n" .
				"Subkey-Length: $SubkeyLength\n";
		$batch_script .= "Name-Real: $RealName\n" .
			"Name-Comment: $Comment\n" .
			"Name-Email: $Email\n" .
			"Expire-Date: $ExpireDate\n" .
			"Passphrase: $Passphrase\n" .
			"%commit\n" .
			"%echo done with success\n";
		
		// initialize the output
		$contents = '';
		
		// execute the GPG command
		if ( $this->_fork_process($this->program_path . ' --homedir ' . $this->home_directory .
				' --batch --status-fd 1 --gen-key',
			$batch_script, $contents) ) {
			$matches = false;
			if ( preg_match('/\[GNUPG:\]\sKEY_CREATED\s(\w+)\s(\w+)/', $contents, $matches) )
				return $matches[2];
			else
				return true;
		} else
			return false;
	}
	
	/**
	* Encrypt and sign data.
	*
	* @param  string $KeyID          the key id used to encrypt
	* @param  string $Passphrase     the passphrase to open the key used to encrypt
	* @param  string $RecipientKeyID the recipient key id
	* @param  string $Text           data to encrypt
	* @param  bool   $Armor          ascii output
	* @return mixed  false on error, the encrypted data on success
	*/
	function Encrypt($KeyID, $Passphrase, $RecipientKeyID, $Text, $Armor=true)
	{
		// initialize the output
		$contents = '';
		
		// execute the GPG command
		if ( $this->_fork_process($this->program_path . ' --homedir ' . $this->home_directory .
				($Armor?' --armor':'').' --passphrase-fd 0 --yes --batch --force-v3-sigs --trust-model classic' .
				" --local-user $KeyID --default-key $KeyID --recipient $RecipientKeyID --sign --encrypt",
			$Passphrase . "\n" . $Text, $contents) )
			return $contents;
		else
			return false;
	}
	
	/**
	* Decrypt the data.
	*
	* If the decrypted file is signed, the signature is also verified.
	*
	* @param  string $KeyID      the key id to decrypt
	* @param  string $Passphrase the passphrase to open the key used to decrypt
	* @param  string $Text       data to decrypt
	* @return mixed  false on error, the clear (decrypted) data on success
	*/
	function Decrypt($KeyID, $Passphrase, $Text)
	{
		// the text to decrypt from another platforms can has a bad sequence
		// this line removes the bad date and converts to line returns
		$Text = preg_replace("/\x0D\x0D\x0A/s", "\n", $Text);
		
		// we generate an array and add a new line after the PGP header
		$Text = explode("\n", $Text);
		if (count($Text) > 1) $Text[1] .= "\n";
		$Text = implode("\n", $Text);
		
		// initialize the output
		$contents = '';
		
		// execute the GPG command
		if ( $this->_fork_process($this->program_path . ' --homedir ' . $this->home_directory .
				' --passphrase-fd 0 --yes --batch --trust-model classic' .
				" --local-user $KeyID --default-key $KeyID --decrypt",
			$Passphrase . "\n" . $Text, $contents) )
			return $contents;
		else
			return false;
	}
	
	/**
	* Remove key from the public keyring.
	*
	* If secret is specified it try to remove the key from from the secret
	* and public keyring.
	* The returned error codes are:
	* 1 = no such key
	* 2 = must delete secret key first
	* 3 = ambiguos specification
	*
	* @param  string $KeyID   the key id to be removed, if this is the secret key you must specify the fingerprint
	* @param  string $KeyKind the kind of the keys, can be secret or public
	* @return mixed  true on success, otherwise false or the delete error code
	*/
	function DeleteKey($KeyID, $KeyKind = 'public')
	{
		if (empty($KeyID)) {
			$this->error = 'You must specify the KeyID to delete';
			return false;
		}
		
		// validate the KeyKind
		$KeyKind = strtolower(substr($KeyKind, 0, 3));
		if (($KeyKind != 'pub') && ($KeyKind != 'sec')) {
			$this->error = 'The Key kind must be public or secret';
			return false;
		}
		
		// initialize the output
		$contents = '';
		
		// execute the GPG command
		if ( $this->_fork_process($this->program_path . ' --homedir ' . $this->home_directory .
				' --batch --yes --status-fd 1 ' .
				(($KeyKind == 'pub') ? '--delete-key ': '--delete-secret-keys ') . $KeyID,
			false, $contents) )
			return true;
		else {
			$matches = false;
			if ( preg_match('/\[GNUPG:\]\DELETE_PROBLEM\s(\w+)/', $contents, $matches) )
				return $matches[1];
			else
				return false;
		}
	}
	
	/**
	* Make a signature on key.
	*
	* If the key is not yet signed by the specified user.
	*
	* @param  string $KeyID       the key id used to sign
	* @param  string $Passphrase  the passphrase to open the key used to sign
	* @param  string $KeyIDToSign the key to be signed
	* @param  int    $CheckLevel  the check level (0, 1, 2, 3 -casual to extensive-)
	* @return bool   true on success, otherwise false
	*/
	function SignKey($KeyID, $Passphrase, $KeyIDToSign, $CheckLevel = 0)
	{
		$contents = '';
		
		// validates the check level
		$CheckLevel = intval($CheckLevel);
		if (($CheckLevel < 0) || ($CheckLevel > 3)) {
			$this->error = 'Invalid Check-Level, the allowed are 0, 1, 2, 3';
			return false;
		}
		
		// execute the GPG command
		if ( $this->_fork_process($this->program_path . ' --homedir ' . $this->home_directory .
				' --passphrase-fd 0 --status-fd 1 --yes --batch' .
				" --default-cert-check-level $CheckLevel --default-key $KeyID --edit-key $KeyIDToSign sign save",
			$Passphrase . "\n", $contents) ) {
			$matches = false;
			if ( preg_match('/\[GNUPG:\]\s[ALREADY_SIGNED|GOOD_PASSPHRASE]/', $contents, $matches) )
				return true;
			else
				return false;
		} else
			return false;
	}
}

?>