<?php

/**
 * @global moodle_database $DB
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_usp_ews_upgrade($oldversion, $block) {
    global $DB;

	$result = true;
    $dbman = $DB->get_manager();
	
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
	
    
	
	if ($oldversion < 2014030100) {

         // Define table usp_ews_config to be created.
        $table = new xmldb_table('usp_ews_config');

        // Adding fields to table usp_ews_config.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ewsinstanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('icon', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('now', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('loginweight', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('completionweight', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('interactionweight', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('minlogin', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('studentview', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('monitoreddata', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('coursestartdate', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastupdatetimestamp', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastlogid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('processnew', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table usp_ews_config.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for usp_ews_config.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
		
		
		$table = new xmldb_table('usp_ews_interaction');

        // Adding fields to table usp_ews_interaction.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastsevenlogin', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('myinteractcount', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('classinteractcount', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('interactindex', XMLDB_TYPE_FLOAT, '12', null, XMLDB_NOTNULL, null, null);
        $table->add_field('logindetail', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('interactiondetail', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table usp_ews_interaction.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for usp_ews_interaction.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Usp_ews savepoint reached.
        upgrade_block_savepoint(true, 2014030100, 'usp_ews');
    }

    return $result;
}