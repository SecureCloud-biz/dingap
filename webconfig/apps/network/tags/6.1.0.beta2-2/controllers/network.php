<?php

/**
 * Network controller.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network controller.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */
 
class Network extends ClearOS_Controller
{
    /**
     * Network overview.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('network');

        // Load views
        //-----------

        if (clearos_console())
            $options['type'] = MY_Page::TYPE_CONSOLE;

        $views = array('network/settings', 'network/dns', 'network/iface');
        
        $this->page->view_forms($views, lang('network_network'), $options);
    }

    /**
     * Network information.
     *
     * @return JSON network information
     */

    function get_all_info()
    {
        // Load libraries
        //---------------

        $this->load->library('network/Iface_Manager');

        // Dump JSON information
        //----------------------

        $network = $this->iface_manager->get_interface_details();

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        echo json_encode($network);
    }

    /**
     * Network information.
     *
     * @return JSON network information
     */

    function get_info($iface)
    {
        // Load libraries
        //---------------

        $this->load->library('network/Iface', $iface);

        // Dump JSON information
        //----------------------

        $network = $this->iface->get_info();

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        echo json_encode($network);
    }
}
