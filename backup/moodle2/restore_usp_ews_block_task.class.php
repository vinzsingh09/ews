<?php

 class usp_ews_block_task extends restore_block_task {

    /**
     * Translates the backed up configuration data for the target course modules
     *
     * @global type $DB
     */
    public function after_restore() {
        global $DB;

        // Get the blockid
        $id = $this->get_blockid();

        //Restored course id
        $courseid = $this->get_courseid();

		//new backup for version 2
		if ($configdata = $DB->get_field('usp_ews_config', 'monitoreddata', array('ewsinstanceid' => $id))) {
        //if ($configdata = $DB->get_field('block_instances', 'configdata', array('id' => $id))) {
            //$config = (array)unserialize(base64_decode($configdata));
            $config = json_decode($configdata);

            // Translate the old config information to the target course values
			foreach ($monitoreddata as $monitoritem){

				// Find a matching module in the target course
				if ($cm = get_coursemodule_from_instance($monitoritem->module, $monitoritem->instanceid)) {

					// Get new cm and instance
					$newitem = restore_dbops::get_backup_ids_record(
						$this->get_restoreid(), "course_module", $cm->id);
					$newcm = get_coursemodule_from_id($monitoritem->module, $newitem->newitemid);
					$newinstance = $newcm->instance;

					$fields = 'id';
					$cmid = $DB->get_record('course_modules', array('course'=>$courseid, 'module'=>$monitoritem->modid , 'instance'=>$newinstance), $fields);

					$monitoritem->cmid = $cmid->id;
					$monitoritem->instanceid = $newinstance;
					$monitoritem->action = $action;
					$monitoritem->critical = $critical;
					$monitoritem->expected = $expected;
					$monitoritem->locked = $locked;
		
				}
            }

            // Save everything back to DB
            $configdata = json_encode($config);
            //$DB->set_field('block_instances', 'configdata', $configdata, array('id' => $id));
            $DB->set_field('usp_ews_config', 'monitoreddata', $configdata, array('ewsinstanceid' => $id));
        }
    }

    /**
     * There are no unusual settings for this restore
     */
    protected function define_my_settings() {
    }

    /**
     * There are no unusual steps for this restore
     */
    protected function define_my_steps() {
    }

    /**
     * There are no files associated with this block
     *
     * @return array An empty array
     */
    public function get_fileareas() {
        return array();
    }

    /**
     * There are no specially encoded attributes
     *
     * @return array An empty array
     */
    public function get_configdata_encoded_attributes() {
        return array();
    }

    /**
     * There is no coded content in the backup
     *
     * @return array An empty array
     */
    static public function define_decode_contents() {
        return array();
    }

    /**
     * There are no coded links in the backup
     *
     * @return array An empty array
     */
    static public function define_decode_rules() {
        return array();
    }

}
