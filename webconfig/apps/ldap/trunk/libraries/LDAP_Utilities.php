<?php

/**
 * LDAP utilities class.
 *
 * @category   Apps
 * @package    LDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ldap/
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
// TODO: remove duplicate function definitions and use this utility class

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\ldap;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('ldap');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;

clearos_load_library('base/Engine');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * LDAP utilities class.
 *
 * @category   Apps
 * @package    LDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ldap/
 */

class LDAP_Utilities extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * LDAP Utilities constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Calculates NT password.
     *
     * @param string $password clear text password
     *
     * @return string NT password
     */

    public static function calculate_nt_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        $unicode = '';

        for ($i = 0; $i < strlen($password); $i++) {
            $a = ord($password{$i}) << 8;
            $unicode .= sprintf("%X", $a);
        }

        $packed_unicode = pack('H*', $unicode);

        return strtoupper(bin2hex(hash('md4', $packed_unicode)));
    }

    /**
     * Calculates SHA password.
     *
     * @param string $password clear text password
     *
     * @return string SHA password
     */

    public static function calculate_sha_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        return base64_encode(pack('H*', sha1($password)));
    }

    /**
     * Converts SHA password to SHA1.
     *
     * @param string $sha_password SHA password
     *
     * @return string SHA1 password
     */

    public static function convert_sha_to_sha1($sha_password)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Strip out prefix if it exists
        $sha_password = preg_replace("/^{sha}/", "", $sha_password);

        $sha1 = unpack("H*", base64_decode($sha_password));

        return $sha1[1];
    }
}
