<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id$
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004-2006 Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

include_once(SM_PATH . 'plugins/avelsieve/include/managesieve.lib.php');

/**
 * Backend for ManageSieve script management interface.
 * @todo Convert the various connection options into arguments for the
 *  initialization of the class.
 */
class DO_Sieve_PearManageSieve extends DO_Sieve {
    var $loggedin = false;

    var $sieveServerAddress;
    var $sievePort;
    var $sieveUsername;
    var $sieveAuthZ = '';
    var $sievePreferredSaslMech;
    var $sieveImapProxyMode;
    var $sieveCyrusAdminsMap;

    function DO_Sieve_PearManageSieve() {
        $this->DO_Sieve();

        require_once('Net/Sieve.php');
        sqgetGlobalVar('sieve_capabilities', $sieve_capabilities, SQ_SESSION);
        if(isset($sieve_capabilities)) {
            $this->capabilities = $sieve_capabilities;
        }
        sqgetGlobalVar('rules', $rules, SQ_SESSION);
        if(isset($rules)) {
            $this->rules = $rules;
        }
        
        global $imapServerAddress, $username, $avelsieve_imapproxymode,
            $avelsieve_cyrusadmins_map, $sieveport, $sieve_preferred_sasl_mech,
            $avelsieve_imapproxyserv;

        $this->sieveServerAddress = $imapServerAddress;
        $this->sieveUsername = $username;
        $this->sieveImapProxyMode = $avelsieve_imapproxymode;
        $this->sieveCyrusAdminsMap = $avelsieve_cyrusadmins_map;
        $this->sievePort = $sieveport;
        $this->sievePreferredSaslMech = $sieve_preferred_sasl_mech;

        sqgetGlobalVar('authz', $authz, SQ_SESSION);
        if(isset($authz)) {
            $this->sieveAuthZ = $authz;
        }
        
        if(isset($authz)) {
            $this->sieveServerAddress =  sqimap_get_user_server ($imapServerAddress, $authz);
        } else {
            $this->sieveServerAddress = sqimap_get_user_server ($imapServerAddress, $username);
        }
        if ($avelsieve_imapproxymode == true) {
            /* Need to do mapping so as to connect directly to server */
            $this->sieveServerAddress = $avelsieve_imapproxyserv[$this->sieveServerAddress];
        }
    }

    /**
    * This function initializes the avelsieve environment. Basically, it makes
    * sure that there is a valid sieve_capability array.
    *
    * Important: If a valid rules array is needed, then avelsieve_getrules()
    * should be used.
    *
    * @return void
    */
    function init() {
        if(!is_object($this->sieve)) {
            sqgetGlobalVar('key', $key, SQ_COOKIE);
            sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);
        

            if($this->sieveAuthZ) {
                if(isset($this->sieveCyrusAdminsMap[$username])) {
                    $this->bind_username = $this->sieveCyrusAdminsMap[$username];
                } else {
                    $this->bind_username = $this->sieveUsername;
                }
            } else {
                $this->bind_username = $this->sieveUsername;
            }
            
            /* Need the cleartext password to login to timsieved */
            $acctpass = OneTimePadDecrypt($key, $onetimepad);
            $this->sieve = new Net_Sieve($this->bind_username, $acctpass, $this->sieveServerAddress, $this->sievePort,
                '', $this->sieveAuthZ, true);
                // $this->sievePreferredSaslMech);
            $this->login();
        }
    }

    /**
     * Login to SIEVE server. Also saves the capabilities in Session.
     *
     * @param object $sieve Sieve class connection handler.
     * @return boolean
     */
    function login() {
        if(is_object($this->sieve) && $this->loggedin) {
            return true;
        }
        if ($this->sieve->login($this->)){
            if(!isset($this->sieve_capabilities)) {
                $this->capabilities = $sieve_capabilities = $this->sieve->sieve_get_capability();
                $_SESSION['sieve_capabilities'] = $sieve_capabilities;
            }
            $this->loggedin = true;
            return true;
        } else {
            $errormsg = _("Could not log on to timsieved daemon on your IMAP server") . 
                    " " . $this->sieve->host.':'.$this->sieve->port.'.<br/>';
            if(!empty($this->sieve->error)) {
                $errormsg .= _("Error Encountered:") . ' ' . $this->sieve->error . '</br>';
            }
            $errormsg .= _("Please contact your administrator.");
    
            if(AVELSIEVE_DEBUG == 1) {
                print "<pre>(Debug Mode). Login failed. Capabilities:\n";
                print_r($this->sieve_capabilities);
                if(!empty($this->sieve->error)) {
                    print "\nError Message returned:\n";
                    print_r($this->sieve->error);
                }
                print '</pre>';
            }
            print_errormsg($errormsg);
            exit;
        }
    }

    /**
     * Get scripts list from SIEVE server.
     * @return array
     */
    function listscripts() {
        $scripts = array();
        if($this->sieve->sieve_listscripts()) {
            if(is_array($this->sieve->response)){
                $i = 0;
                foreach($this->sieve->response as $line){
                    $scripts[$i] = $line;
                    $i++;
                }
            }
        }
        return $scripts;
    }

    /**
     * Get rules from specified script of Sieve server
     *
     * @param object $sieve Sieve class connection handler.
     * @param string $scriptname
     * @param array $scriptinfo
     * @return array
     */
    function load($scriptname = 'phpscript', &$rules, &$scriptinfo) {
        sqgetGlobalVar('sieve_capabilities', $sieve_capabilities, SQ_SESSION);
        
        $rules = array();
        $scriptinfo = array();
    
        if(!$this->loggedin) {
            $this->login();
        }
    
        $scripts = $this->listscripts($this->sieve);
    
        if(!in_array($scriptname, $scripts)) {
            /* No avelsieve script. */
            return false;
        }
        
        /* Get actual script from Sieve server. */
        unset($this->sieve->response);
        $sievescript = '';
        if($this->sieve->sieve_getscript($scriptname)){
            foreach($this->sieve->response as $line){
                $sievescript .= $line;
            }
        } else {
            $prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
            textdomain ('avelsieve');
            $errormsg = _("Could not get SIEVE script from your IMAP server");
            $errormsg .= " " . $this->sieveServerAddress.".<br />";
            
            if(!empty($this->sieve->error)) {
                $errormsg .= _("Error Encountered:") . ' ' . $this->sieve->error . '</br>';
                $errormsg .= _("Please contact your administrator.");
                print_errormsg($errormsg);
                exit;
            }
        }
    
        /* Extract rules from $sievescript. */
        $rules = avelsieve_extract_rules($sievescript, $scriptinfo);
        return true;
    }

    /**
    * Upload script
    *
    * @param string $newscript The SIEVE script to be uploaded
    * @param string $scriptname Name of script
    * @return true on success, false upon failure
    */
    function save($newscript, $scriptname = 'phpscript') {
        if(isset($this->sieve->error_raw)) {
            unset($this->sieve->error_raw);
        }
    
        if($this->sieve->sieve_sendscript($scriptname, stripslashes($newscript))) {
            if(!($this->sieve->sieve_setactivescript($scriptname))){
                /* Just to be safe. */
                $errormsg = _("Could not set active script on your IMAP server");
                $errormsg .= " " . $imapServerAddress.".<br />";
                $errormsg .= _("Please contact your administrator.");
                print_errormsg($errormsg);
                return false;
            }
            return true;
    
        } else {
            $errormsg = '<p>'. _("Unable to load script to server.") . '</p>';
    
            if(isset($this->sieve->error_raw)) {
                $errormsg .= '<p>'. _("Server responded with:") . '<br />';
                if (is_array($this->sieve->error_raw)) {
                    foreach($this->sieve->error_raw as $error_raw) {
                        $errormsg .= $error_raw . "<br />";
                    }
                } else {
                    $errormsg .= $this->sieve->error_raw . "<br />";
                }
                $errormsg .= _("Please contact your administrator.");
            
                /* The following serves for viewing the script that
                * tried to be uploaded, for debugging purposes. */
                if(AVELSIEVE_DEBUG == 1) {
                    $errormsg .= '<br />(Debug mode) <strong>avelsieve
                    bug</strong> <br /> Script that probably is buggy
                    follows.<br /> Please copy/paste it, together with the
                    error message above, and email it to <a
                    href="mailto:'.AVELSIEVE_BUGREPORT_EMAIL.'">'.AVELSIEVE_BUGREPORT_EMAIL.'</a>.
                    <br /><br />
                    <div style="font-size:8px;"><pre>' . $newscript. "</pre></div>";
                }
            }
            print_errormsg($errormsg);
            return false;
        }
    }
    
    /**
    * Deletes a script on SIEVE server.
    *
    * @param object $sieve Sieve class connection handler.
    * @param string $script 
    * @return true on success, false upon failure
    */
    function delete($script = 'phpscript') {
        if(empty($script)) {
            return false;
        }
        if($this->sieve->sieve_deletescript($script)) {
            return true;
        } else {
            
            $errormsg = sprintf( _("Could not delete script from server %s."), $sieve->host.':'.$sieve->port) .
                '<br/>';
            if(!empty($this->sieve->error)) {
                $errormsg .= _("Error Encountered:") . ' ' . $this->sieve->error . '</br>';
            }
            $errormsg .= _("Please contact your administrator.");
            print_errormsg($errormsg);
    
            /*
            if(is_array($this->sieve->error_raw)) {
                foreach($this->sieve->error_raw as $error_raw)
                    print $error_raw."<br>";
            } else {
                print $this->sieve->error_raw."<br>";
            }
            */
            return false;
        }
    }

    function setactive($script) {
        $this->sieve->sieve_setactivescript($script);
        return true;
    }

    function logout() {
        $this->sieve->sieve_logout();
    }

}

?>
