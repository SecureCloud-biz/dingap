<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
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
 * System locale manager.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Daemon.class.php');
require_once('File.class.php');

/**
 * Cron.d configlet not found exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class CronConfigletNotFoundException extends EngineException
{
	/**
	 * CronConfigletNotFoundException constructor.
	 *
	 * @param string $errmsg error message
	 * @param int $code error code
	 */

	public function __construct($errmsg, $code)
	{
		parent::__construct($errmsg, $code);
	}
}

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Cron server and crontab configuration.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Cron extends Daemon
{
	const FILE_CRONTAB = "/etc/crontab";
	const PATH_CROND = "/etc/cron.d";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Cron constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("crond");
	}

	/**
	 * Add a configlet to cron.d.
	 *
	 * @param string $name configlet name
	 * @param string $payload valid crond payload
	 * @returns void
	 * @throws EngineException, ValidationException
	 */

	function AddCrondConfiglet($name, $payload)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO -- validate payload

		try {
			$file = new File(self::PATH_CROND . "/" . $name, true);

			if ($file->Exists())
				throw new ValidationException(FILE_LANG_ERRMSG_EXISTS . " - " . $name);

			$file->Create("root", "root", "0644");

			$file->AddLines("$payload\n");

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}


	/**
	 * Add a configlet to cron.d.
	 * 
	 * @param string $name configlet name
	 * @param integer $minute minute of the day
	 * @param integer $hour hour of the day
	 * @param integer $dayofmonth day of the month
	 * @param integer $month month
	 * @param integer $dayofweek day of week
	 * @param string $user user that will run cron command
	 * @param string $command command
	 * @returns void
	 * @throws EngineException, ValidationException
	 */

	function AddCrondConfigletByParts($name, $minute, $hour, $dayofmonth, $month, $dayofweek, $user, $command)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO: validate variables

		try {
			$file = new File(self::PATH_CROND . "/" . $name, true);

			if ($file->Exists())
				throw new ValidationException(FILE_LANG_ERRMSG_EXISTS . " - " . $name);

			$file->Create("root", "root", "0644");

			$file->AddLines("$minute $hour $dayofmonth $month $dayofweek $user $command\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}


	/**
	 * Get contents of a cron.d configlet.
	 *
	 * @param string $name configlet
	 * @return string contents of a cron.d file
	 * @throws CronConfigletNotFoundException, EngineException, ValidationException
	 */

	function GetCrondConfiglet($name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO: validate filename, do not allow .. or leading /

		$contents = "";

		try {
			$file = new File(self::PATH_CROND . "/" . $name, true);
			$contents = $file->GetContents();
		} catch (FileNotFoundException $e) {
			throw new CronConfigletNotFoundException($e->GetMessage(), COMMON_INFO);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return $contents;
	}


	/**
	 * Deletes cron.d configlet.
	 *
	 * @param string $name cron.d configlet
	 * @returns void
	 * @throws EngineException, ValidationException
	 */

	function DeleteCrondConfiglet($name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO: validate filename, do not allow .. or leading /

		try {
			$file = new File(self::PATH_CROND . "/" . $name, true);

			if (! $file->Exists())
				throw new ValidationException(FILE_LANG_ERRMSG_NOTEXIST . " - " . $name);

			$file->Delete();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}


	/**
	 * Checks to see if cron.d configlet exists.
	 *
	 * @param string $name configlet
	 * @return boolean true if file exists
	 * @throws EngineException, ValidationException
	 */

	function ExistsCrondConfiglet($name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::PATH_CROND . "/" . $name, true);

			if ($file->Exists())
				return true;
			else
				return false;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   R O U T I N E S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validation routine for crontab time.
	 *
	 * @param string $time crontab time
	 * @return boolean true if time entry is valid
	 */

	function IsValidTime($time)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Could do more validation here...

		$time = preg_replace("/\s+/", " ", $time);

		$parts = explode(" ", $time);

		if (sizeof($parts) != 5)
			return false;

		return true;
	}
}

// vim: syntax=php ts=4
?>
