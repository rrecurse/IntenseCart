<?php
/**
 * @author Yang Yang
 * @link http://www.kavoir.com/2011/10/php-crontab-class-to-add-and-remove-cron-jobs.html
 */
class Crontab {
	// In this class, array instead of string would be the standard input / output format.

	static private function stringToArray ($jobs = '') {
		// FagSoft: Changed to work with *nix style of newlines.
		if (strpos ($jobs, "\r") !== false) {
			$jobs = str_replace ("\r", '', $jobs);
		}
		$array = explode ("\n", trim ($jobs)); // trim() gets rid of the last \r\n
		foreach ($array as $key => $item) {
			if ($item == '') {
				unset ($array[$key]);
			}
		}
		return $array;
	}

	static private function arrayToString ($jobs = array()) {
		// FagSoft: Changed to work with *nix style of newlines.
		$string = implode ("\n", $jobs);
		return $string;
	}

	static public function getJobs () {
		$output = shell_exec ('crontab -l');
		return self::stringToArray ($output);
	}

	static public function saveJobs ($jobs = array()) {
		$output = shell_exec ('echo "' . self::arrayToString ($jobs) . '" | crontab -');
		return $output;
	}

	static public function doesJobExist ($job = '') {
		$jobs = self::getJobs ();
		foreach ($jobs as $key => $line) {
			if (strpos ($line, $job) !== false) {
				return true;
			}
		}
		return false;
	}

	static public function addJob ($job = '') {
		if (self::doesJobExist ($job)) {
			return false;
		}

		$jobs = self::getJobs ();
		$jobs[] = $job;
		return self::saveJobs ($jobs);
	}

	static public function removeJob ($job = '') {
		if (!self::doesJobExist ($job)) {
			return false;
		}

		$jobs = self::getJobs ();
		foreach ($jobs as $key => $line) {
			if (strpos ($line, $job) !== false) {
				unset ($jobs[$key]);
			}
		}
		return self::saveJobs ($jobs);
	}
}