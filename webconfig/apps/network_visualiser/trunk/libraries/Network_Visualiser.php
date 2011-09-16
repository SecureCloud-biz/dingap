<?php

/**
 * Network visualiser class.
 *
 * @category   Apps
 * @package    Network_Visualiser
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network_visualiser/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\network_visualiser;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('network_visualiser');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\File as File;
use \clearos\apps\network\Hostname as Hostname;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/File');
clearos_load_library('network/Hostname');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network visualiser class.
 *
 * @category   Apps
 * @package    Network_Visualiser
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network_visualiser/
 */

class Network_Visualiser
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

	const CMD_JNETTOP = '/usr/bin/jnettop';
	const FILE_CONFIG = '/etc/system/jnettop.conf';
	const FILE_DUMP = 'jnettop.dmp';

	protected $config = null;
	protected $is_loaded = FALSE;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Network_Visualiser constructor.
     */

    function __construct()
    {
    }

    /** Send a plain text message.
     *
     * @return void
     *
     */

    function get_fields()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

		try {
			$fields = array();

			if (!$this->is_loaded)
				$this->_load_config();

			$values = $this->config['fields'];
			$fields = explode(',', $values);
			return $fields;
		} catch (Exception $e) {
			// Return default entry
			return array('srcname');
		}
    }

    /* Executes a test to see if mail can be sent through the SMTP server.
     *
     * @param string $interface a valid NIC interface
     * @param int    $interval  interval, in seconds
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

	function initialize($interface, $interval)
	{
        clearos_profile(__METHOD__, __LINE__);

		try {
			$file = new File(CLEAROS_TEMP_DIR . "/" . self::FILE_DUMP);

			if ($file->Exists())
				$file->Delete();
		} catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
		}

		try {
			$shell = new Shell();
			$args = "-i $interface --display text -t $interval --format";
			$fields = $this->get_fields();
			$args .= " '";
			foreach ($fields as $field)
				$args .= "\$" . $field . "\$,"; 
			// Strip off the last comma separator and replace with single quote
			$args = preg_replace("/,$/", "'", $args);
			$options = array('env' => "LANG=en_US", 'background' => TRUE, 'log' => 'jnettop.dmp');
			$retval = $shell->execute(self::CMD_JNETTOP, $args, TRUE, $options);
		} catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
		}

		if ($retval != 0) {
			$errstr = $shell->get_last_output_line();
			throw new Engine_Exception($errstr, CLEAROS_ERROR);
		} else {
			$lines = $shell->get_output();
			foreach ($lines as $line) {
				echo $line;
			}
		}
	}

    /**
     * Set the interval.
     *
     * @param int $interval interval
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_interval($interval)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interval($interval));

        $this->_set_parameter('interval', $interval);
    }

    /**
     * Set the inteface.
     *
     * @param string $interface interface
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_interface($interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($interface));

        $this->_set_parameter('interface', $interface);
    }

    /**
     * Set the display.
     *
     * @param string $display display
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_display($display)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_display($display));

        $this->_set_parameter('display', $display);
    }

    /**
     * Returns the interval options.
     *
     * @return array
     */

    function get_interval_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array(
            5 => 5,
            10 => 10,
            15 => 15,
            30 => 30,
            60 => 60
        );
        return $options;
    }

    /**
     * Returns the display options.
     *
     * @return array
     */

    function get_interface_options()
    {
        clearos_profile(__METHOD__, __LINE__);

		$options = array(
            'eth0' => 'Eth0',
            'eth1' => 'Eth1'
        );

        return $options;
    }

    /**
     * Returns the interval options.
     *
     * @return array
     */

    function get_display_options()
    {
        clearos_profile(__METHOD__, __LINE__);

		$options = array(
            'totalbps' => lang('network_visualiser_field_total_bps'),
            'totalbytes' => lang('network_visualiser_field_total_bytes')
        );

        return $options;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads configuration files.
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $configfile = new Configuration_File(self::FILE_CONFIG);

        try {
            $this->config = $configfile->Load();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Generic set routine.
     *
     * @param string $key   key name
     * @param string $value value for the key
     *
     * @return  void
     * @throws Engine_Exception
     */

    function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_CONFIG, TRUE);
            $match = $file->replace_lines("/^$key\s*=\s*/", "$key=$value\n");

            if (!$match)
                $file->add_lines("$key=$value\n");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->is_loaded = FALSE;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for interval.
     *
     * @param string $interval interval
     *
     * @return mixed void if interval is valid, errmsg otherwise
     */

    public function validate_interval($interval)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (is_nan($interval) || $interval < 5 ||  $interval > 300)
            return lang('network_visualiser_interval_is_invalid');
    }

    /**
     * Validation routine for interface.
     *
     * @param string $interface interface
     *
     * @return mixed void if interface is valid, errmsg otherwise
     */

    public function validate_interface($interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (FALSE)
            return lang('network_visualiser_interface_is_invalid');
    }

    /**
     * Validation routine for display.
     *
     * @param int $display display
     *
     * @return mixed void if display is valid, errmsg otherwise
     */

    public function validate_display($display)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (FALSE)
            return lang('network_visualiser_display_is_invalid');
    }

}