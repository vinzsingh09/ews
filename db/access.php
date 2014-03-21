<?php

/**
 * usp_ews capability setup
 *
 * @package    contrib
 * @subpackage block_usp_ews
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");

//Accessing the overall course report
//Access only giving to the lecturer, course coordinator
$capabilities = array (
    'block/usp_ews:overview' => array (
        'riskbitmask'   => RISK_PERSONAL,
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => array (
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'manager'           => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW
        )
    ),
	'block/usp_ews:viewdetail' => array(
        'captype' 		=> 'read',
        'contextlevel' 	=> CONTEXT_COURSE,
        'archetypes' 	=> array(
            'student' 			=> CAP_ALLOW,
            'teacher' 			=> CAP_ALLOW,
            'editingteacher' 	=> CAP_ALLOW,
            'manager' 			=> CAP_ALLOW,
			'coursecreator'     => CAP_ALLOW
        )
    ),
	    'block/usp_ews:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    )
);
