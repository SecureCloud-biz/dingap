<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2009 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * NTP time class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Cron.class.php');
require_once('File.class.php');
require_once('Network.class.php');
require_once('Time.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * NTP time extension to the Time class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

class NtpTime extends Time
{
	const FILE_CROND = "app-ntp";
	const FILE_CONFIG = "/etc/system/ntpdate";
	const DEFAULT_SERVER = "time.clearsdn.com";
	const DEFAULT_CRONTAB_TIME = "2 2 * * *";
	const CMD_NTPDATE = "/usr/sbin/ntpdate";
	const CRON_COMMAND = "/usr/sbin/timesync";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * NtpTime constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns the time server to be used on the system.
	 *
	 * This will return the default self::DEFAULT_SERVER if a 
	 * time server has not been specified.
	 *
	 * @return string current auto-sync NTP server
	 * @throws EngineException
	 */

	function GetAutoSyncServer()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$timeserver = "";

		try {
			$config = new File(self::FILE_CONFIG);
			$timeserver = $config->LookupValue("/^ntp_syncserver\s*=\s*/");
		} catch (FileNoMatchException $e) {
			$timeserver = NtpTime::DEFAULT_SERVER;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		if (! $timeserver)
			$timeserver = NtpTime::DEFAULT_SERVER;

		$network = new Network();

		if (!($network->IsValidHostname($timeserver) || $network->IsValidIp($timeserver)))
			throw new EngineException(NTPTIME_LANG_ERRMSG_TIMESERVER_INVALID, COMMON_ERROR);

		return $timeserver;
	}

	/**
	 * Sets the time server to be used on the system.
	 *
	 * @param string $timeserver (optional) auto-sync NTP server, if empty the default is set
	 * @return boolean true on successful update
	 * @throws EngineException
	 */

	function SetAutoSyncServer($timeserver=null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (empty($timeserver)){
		    $timeserver = NtpTime::DEFAULT_SERVER;
		}
		if ($timeserver == $this->GetAutoSyncServer()){
		    return false;
		}
		if (! $this->IsValidTimeServer($timeserver)){
		    throw new EngineException(NTPTIME_LANG_ERRMSG_TIMESERVER_INVALID, COMMON_WARNING);
		}
		try {
			$config = new File(self::FILE_CONFIG);
			$config->ReplaceLines("/^ntp_syncserver\s*=\s*/","ntp_syncserver = {$timeserver}\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
		return true;
	}

	/**
	 * Returns the status of the auto-sync feature.
	 *
	 * @return boolean true if auto-sync is on
	 * @throws EngineException
	 */

	function GetAutoSyncStatus()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$cron = new Cron();
			return $cron->ExistsCrondConfiglet(self::FILE_CROND);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Returns the time configuration in the auto-synchronize cron entry. 
	 *
	 * Returns the default if an entry does not exist.
	 *
	 * @return string current auto-sync cron time
	 * @throws EngineException
	 */

	function GetAutoSyncTime()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$crontab = new Cron();
			$contents = $crontab->GetCrondConfiglet(self::FILE_CROND);
		} catch (CronConfigletNotFoundException $e) {
			return self::DEFAULT_CRONTAB_TIME;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$lines = explode("\n", $contents);
		foreach ($lines as $line) {
			$matches = array();

			if (preg_match("/([\d\*]+\s+[\d\*]+\s+[\d\*]+\s+[\d\*]+\s+[\d\*]+\s+)/", $line, $matches))
				return $matches[0];
		}

		throw new EngineException(NTPTIME_LANG_ERRMSG_CRONTIME_INVALID, COMMON_WARNING);
	}

	/**
	 * Deletes the cron entry for auto-synchronizing with an NTP server.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function DeleteAutoSync()
	{
		try {
			$crontab = new Cron();
			if ($crontab->ExistsCrondConfiglet(self::FILE_CROND))
				$crontab->DeleteCrondConfiglet(self::FILE_CROND);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}


	/**
	 * Creates a cron file for auto-synchronizng the system clock.
	 *
	 * The crontime parameter ist optional -- the system will select
	 * a defaults if non is specified.
	 *
	 * @param string $crontime crontab time
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetAutoSync($crontime = self::DEFAULT_CRONTAB_TIME)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Validate
		//---------

		$validtime = false;
		$crontab = new Cron();

		try {
			$validtime = $crontab->IsValidTime($crontime);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		if (! $validtime)
			throw new ValidationException(NTPTIME_LANG_ERRMSG_CRONTIME_INVALID);

		// Set auto sync
		//--------------

		try {
			$cron = new Cron();

			if ($cron->ExistsCrondConfiglet(self::FILE_CROND))
				$this->DeleteAutoSync();

			$payload  = "# Created by API\n";

			if (file_exists(self::CRON_COMMAND))
				$payload .= "$crontime root " . self::CRON_COMMAND;
			else
				throw new EngineException(LOCALE_LANG_MISSING . " - " . self::CRON_COMMAND, COMMON_WARNING);

			$crontab->AddCrondConfiglet(self::FILE_CROND, $payload);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}


	/**
	 * Synchronizes the clock. 
	 *
	 * @param string $timeserver time server (optional)
	 * @return string offset time
	 * @throws EngineException, ValidationException
	 */

	function Synchronize($timeserver = null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_null($timeserver))
			$timeserver = $this->GetAutoSyncServer();

		// Validate
		//---------

		if (! $this->IsValidTimeServer($timeserver))
			throw new ValidationException(NTPTIME_LANG_ERRMSG_TIMESERVER_INVALID);

		// Synchronize
		//------------

		$output = "";

		try {
			$shell = new ShellExec();

			$options['env'] = "LANG=fr_FR";

			if ($shell->Execute(self::CMD_NTPDATE, "-u $timeserver", true, $options) != 0)
				throw new EngineException(NTPTIME_LANG_ERRMSG_SYNCHRONIZE_FAILED, COMMON_ERROR);

			$output = $shell->GetFirstOutputLine();
			$output = preg_replace("/.*offset/", "", $output);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $output;
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   R O U T I N E S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validation routine for time server.
	 *
	 * @param string $timeserver time server
	 * @return boolean true if time server is valid
	 */

	function IsValidTimeServer($timeserver)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^([\.\-\w]*)$/", $timeserver))
			return true;

		return false;
	}
}

// vim: syntax=php ts=4
?>
