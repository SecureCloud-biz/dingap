<?php

/**
 * Nework visualiser controller.
 *
 * @category   Apps
 * @package    Nework_Visualiser
 * @subpackage Views
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/Company/terms.html ClearSDN license
 * @link       http://www.clearcenter.com/support/documentation/clearos/network_visualiser/
 */

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('network_visualiser');

///////////////////////////////////////////////////////////////////////////////
// Anchors
///////////////////////////////////////////////////////////////////////////////

$anchors = array(anchor_custom('/app/network_visualiser/edit/', lang('network_visualiser_modify')));

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('network_visualiser_source'),
    lang('network_visualiser_source_port'),
    lang('network_visualiser_protocol'),
    lang('network_visualiser_destination'),
    lang('network_visualiser_destination_port'),
    ($display == 'totalbps' ? lang('network_visualiser_bandwidth') : lang('network_visualiser_total_transfer'))
);

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

echo form_open('network_visualiser_report');

echo summary_table(
    lang('network_visualiser_traffic_summary'),
    $anchors,
    $headers,
    NULL,
    array(
        'id' => 'report',
        'no_action' => TRUE,
        'sorting-type' => array(
            null,
            null,
            null,
            null,
            null,
            'title-numeric'
        )
    )
);

echo form_close();
echo "<input id='report_display' type='hidden' value='$display'>";
