<?php

/**
 * @global moodle_database $DB
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_usp_ews_upgrade($oldversion, $block) {
    global $DB;

    // Fix bad filtering on posted_to values.
    if ($oldversion >= 2013073000 && $oldversion < 2013080500) {
        $configs = $DB->get_records('block_instances', array('blockname' => 'usp_ews'));
        foreach ($configs as $blockid => $blockrecord) {
            $config = (array)unserialize(base64_decode($blockrecord->configdata));
            foreach ($config as $key => $value) {
                if ($value == 'postedto') {
                    $config[$key] = 'posted_to';
                }
            }
            $configdata = base64_encode(serialize((object)$config));
            $DB->set_field('block_instances', 'configdata', $configdata, array('id' => $blockid));
        }
    }

    return true;
}