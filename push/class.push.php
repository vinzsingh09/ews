<?php

	require_once(dirname(__FILE__) . '/../../../config.php');   // moodle config file
	include_once($CFG->libdir.'/ddllib.php');				 // database library file

	require_once('class.DBConnection.php'); 

	// push class used to send the log and config data to the usp_ews backend
	class usp_ews_cron extends DBConn{	
       private $db;
	   
	   // constructor
	   // sets the moodle databce configuration
       function usp_ews_cron(){      
			$this->setDB();
		}
		
		// checking if usp_ews_config not empty
		public function check_mdl_usp_ews_config_not_empty(){
			$DB = $this->getDB();
			$config = $DB->get_records('usp_ews_config');
			
			if ($config != null)
				return true;
			else 
				return false;
		}

	 
	  // cleaning the usp_ews_config table
	  // cleaning the removed instance ids and updating course startdate
	   public function clean_usp_ews_config() {
			$DB = $this->getDB();
			// deleting the instances which were deleted
			$deletedinstance = $this->deleted_usp_ews_config_instance();
			
			if(!empty($deletedinstance)){
				
				$deleteinst = '';
				foreach ($deletedinstance as $rs=>$detail){
					$deleteinst .= $detail->ewsinstanceid . ',';
				}
				$deleteinst = trim($deleteinst, ',');
				
				$table = 'usp_ews_config';
				$select = "ewsinstanceid IN ($deleteinst)";
				$DB->delete_records_select($table, $select);
			}
			
			$config = $this->get_mdl_usp_ews_config();
			foreach($config as $cnf){
				$configitem = new stdclass;
				$configitem->id = $cnf->id;
				$configitem->processnew = 1;
				$update = $DB->update_record('usp_ews_config', $configitem, true);
			}

			return true;

        }

		// pulling interaction table from backend
		function pull_usp_ews_interaction() {
			// moodle db connection
			$DB = $this->getDB();
			
            // if there are courses with EWS
            // then fore each course
            $qry = "SELECT * FROM usp_ews_interaction";

            $rs = $this->execute_query($qry);
           
            if(mysqli_num_rows($rs) == 0){
                throw new Exception(get_string('cronpullinteractionfail', 'block_usp_ews'));
                return false;
            }
            
            $maxinsertpoint = EWS_DEFAULT_MAX_INSERT_RECORD;
            
			$sql = "INSERT INTO {usp_ews_interaction} (id, userid, courseid, lastsevenlogin, myinteractcount, classinteractcount, interactindex, logindetail, interactiondetail, timestamp) VALUES";
            for($i = 0; $line = mysqli_fetch_array($rs, MYSQL_ASSOC); $i++){
               
                if($i == $maxinsertpoint){
                    $mdl_query = rtrim($sql, " ,");
					$result = $DB->execute($mdl_query);
                    $sql = "INSERT INTO {usp_ews_interaction} (id, userid, courseid, lastsevenlogin, myinteractcount, classinteractcount, interactindex, logindetail, interactiondetail, timestamp) VALUES";
                    
                    $maxinsertpoint += EWS_DEFAULT_MAX_INSERT_RECORD;
                }
            
                
                $sql .= "(" . $line['id'] . "," . $line['userid'] . "," . $line['courseid'] . "," . $line['lastsevenlogin'] . "," . $line['myinteractcount'] . "," . $line['classinteractcount'] . "," . $line['interactindex'] .  ",'" . $line['logindetail'] . "','" . $line['interactiondetail'] . "'," . $line['timestamp'] . ")," ;
            }
             
            $mdl_query = rtrim($sql, " ,");
			$result = $DB->execute($mdl_query);
            
            return true;
        }
		
		/**
		 *  Disable the backend from processing while the data is sent o backend
		 *  setting the sataus to disable backend process
		 *  set the value to 0 to tell backend that mdl is busy sending data
		 * after process enable using same function
		 */
		 
		function usp_ews_en_disable_backend_process($timestart=0, $timestop=0, $processnow=1){

			$now = time();
			$table='usp_ews_cron_mdl';
			$this->trancate_table($table);
			$qry = "INSERT INTO $table(timecronstart, timecronend, processnow) VALUES($timestart, $timestop, $processnow)";

            $rs = $this->execute_query($qry);
			
            if(!trim($rs)==""){
                return true;
            }else{
				throw new Exception(get_string('cronerrordisable', 'block_usp_ews'));
			}
            return false;
			
		}

		// get processnow value
		// if backend can proceed or not
		public function get_usp_ews_backend_status(){
			
			$qry = "SELECT * FROM usp_ews_cron_ews";
			
			$rs = $this->execute_query($qry);
		   
		    if(mysqli_num_rows($rs) == 0){
                return false;
            }
			
			$line = mysqli_fetch_array($rs, MYSQL_ASSOC);
			
			if($line['processnow'] == 1){
				return true;
			}else{
				throw new Exception(get_string('cronerrordisable', 'block_usp_ews'));
			}
			 return false;

		}
		
		/**
		 *  Insert log processing while the data is sent o backend
		 */ 
		function usp_ews_cron_log($cronstart, $cronstop, $timetaken, $status, $extras){

			$table='usp_ews_cron_log';
			$qry = "INSERT INTO $table(crontimestart, crontimestop, timetaken, status, extras) VALUES($cronstart, $cronstop, $timetaken, $status, '$extras')";

            $rs = $this->execute_query($qry);
			
            if(!trim($rs)==""){
                return true;
            }
            return false;
			
		}
		
		// deleting the instances from the usp_ews_config if the block was deleted
		//the sql can be improved using more joins
		public function deleted_usp_ews_config_instance() {
			$DB = $this->getDB();
			$sql = "SELECT ewsinstanceid 
					 FROM {usp_ews_config}
					WHERE 
					  ewsinstanceid NOT IN (SELECT id FROM {block_instances});";
	
			$result = $DB->get_records_sql($sql);

			if ($result != null)
				return $result;
			else 
				return null;	
        }

		// sending configuration table to backend
        public function insert_record_usp_ews_config() {
            
			$qry = "INSERT INTO usp_ews_config(id, courseid, contextid, ewsinstanceid, title, icon, now, loginweight, completionweight, interactionweight, minlogin, studentview, monitoreddata, coursestartdate, lastupdatetimestamp, lastlogid, processnew) VALUES";
		
			$mdl_ews_config = $this->get_mdl_usp_ews_config();

			foreach($mdl_ews_config as $config){
				$qry .= "($config->id, $config->courseid, $config->contextid, $config->ewsinstanceid, '$config->title', $config->icon, $config->now, $config->loginweight, $config->completionweight, $config->interactionweight, $config->minlogin, $config->studentview, '$config->monitoreddata', $config->coursestartdate, $config->lastupdatetimestamp, $config->lastlogid, $config->processnew),";
			}
			
			$mdl_query = rtrim($qry, " ,");

		  // first truncate the table
		  $table = 'usp_ews_config';
		  $this->trancate_table($table);

            $rs = $this->execute_query($mdl_query);
            if(!trim($rs)==""){
                return true;
            }else{
				throw new Exception(get_string('cronerrorconfig', 'block_usp_ews'));
			}
            return false;
			
        }

		// sending configuration table to backend
        public function update_record_usp_ews_config($courseid) {
			$qry = "UPDATE usp_ews_config SET processnew=0 WHERE courseid=$courseid";
			
			$rs = $this->execute_query($qry);
            if(!trim($rs)==""){
                return true;
            }

            return false;
			
		}
		// getting configuration detail for each course
		public function get_mdl_usp_ews_config(){
			$DB = $this->getDB();
			$config = $DB->get_records('usp_ews_config');
			
			if ($config != null)
				return $config;
			else 
				return false;
		}		
		
		// other scenairios to be added here and then work or update query
		// getting list of students and updating the table
		public function insert_record_usp_ews_student_course() {
          
			$qry = "INSERT INTO usp_ews_student_course(uniqueid, course, userid) VALUES";
			$ewscourses = $this->get_mdl_usp_ews_courses();
			
			// for different courses
			$courses = '';
			foreach ($ewscourses as $course=>$detail){
				$courses .= $detail . ',';
			}
			$courses = trim($courses, ',');
			
			// getting students of courses
			$student_course = $this->get_mdl_usp_ews_student_course($courses);

			foreach($student_course as $students){
				$qry .= "($students->uniqueid, $students->course, $students->userid),";
			}
			
			// empty the table then add all the new records of students
			$table = 'usp_ews_student_course';
			$this->trancate_table($table);
			$mdl_query = rtrim($qry, " ,");

            $rs = $this->execute_query($mdl_query);
            if(!trim($rs)==""){
                return true;
            }else{
				throw new Exception(get_string('cronerrorstudent', 'block_usp_ews'));
			}
            return false;
			
        }

		// gets the courses id using usp_ews block
		public function get_mdl_usp_ews_courses(){

			$DB = $this->getDB();
			
			$table = 'usp_ews_config';
			$config = $DB->get_records($table);

			if ($config != null){
				$courses = array();
				foreach($config as $con){
					$courses[] = $con->courseid;
				}
				return $courses;
			}	
			else 
				return null;		
		}	

		// get all the students of different usp_ews configured courses
		// send the course with respective students to the usp_ews backend for processing each students
		// only the students are sent
		public function get_mdl_usp_ews_student_course($courses){

			$DB = $this->getDB();
			
			$sql = "
				SELECT CONCAT(ra.id, usr.id) AS uniqueid, c.id as course, usr.id as userid, usr.idnumber
							FROM {course} c
							INNER JOIN mdl_context cx ON c.id = cx.instanceid 
							INNER JOIN mdl_role_assignments ra ON cx.id = ra.contextid 
							INNER JOIN mdl_user usr ON ra.userid = usr.id
							WHERE 
							(select MIN(roleid) FROM mdl_role_assignments WHERE contextid=cx.id AND userid=usr.id) = :roleid 
							AND c.id IN($courses)
					ORDER BY c.id, usr.id;";
			$params = array('roleid'=>5);
	
			$result = $DB->get_records_sql($sql, $params);

			if ($result != null)
				return $result;
			else 
				return false;		
				
		}
		
		// push the log to the backend
        public function insert_record_usp_ews_log($course, $mdl_log) {

			// if not empty
			// then isert to backend
			// this can be changed if it is not able to push all data
			// then it can be breaked into set of recored and inserted...did in the backend
			if(!empty($mdl_log)){
				$qry = "INSERT INTO usp_ews_log(time, userid, ip, course, module, cmid, action) VALUES";

				foreach($mdl_log as $log){
					$qry .= "($log->time, $log->userid, '$log->ip', $log->course, '$log->module', $log->cmid, '$log->action'),";
				}
				
				$mdl_query = rtrim($qry, " ,");
					//echo $mdl_query;

				$rs = $this->execute_query($mdl_query);
				if(!trim($rs)==""){
					return true;
				}else{
					throw new Exception(get_string('cronerrorlog', 'block_usp_ews'));
				}
			}
			return false;
        }

		// trigger the last id from the log table
		// using id as timestamp are repeated
		// to the instant the id is triggered then use that id to get the range of logs and push to backend
		public function get_lastid_cron_mdl_log(){
			$DB = $this->getDB();
			//$sql = "SELECT id, time  FROM {log} ORDER BY id DESC Limit 0, 1";
			$sql = "SELECT id, time  FROM {log} WHERE id=(SELECT MAX(id) FROM {log});";
			$lastid = $DB->get_record_sql($sql);

			if ($lastid != null){
				return $lastid;
			}
			else{ 
				return 0;
			}
		}
		
		// get lastid from config table
		// using id as timestamp are repeated
		// to the instant the id is triggered then use that id to get the range of logs and push to backend
		public function get_lastid_usp_ews_config($id){
			$DB = $this->getDB();
			//$sql = "SELECT id, time  FROM {log} ORDER BY id DESC Limit 0, 1";
			$rs = $DB->get_record('usp_ews_config', array('id'=>$id), 'lastlogid');

			if ($rs->lastlogid != null){
				return $rs->lastlogid;
			}
			else{ 
				return 0;
			}
		}
		
		
		/*
		* getting the log to send to back
		// @ param int $course    			courseid
		// @ param int $lastid				previous cron log id
		// @ param int $max_record		    maximum record to send
		*/
		public function get_course_mdl_log($course, $lastid=0, $max_record){
			
			$DB = $this->getDB();		
			$table = 'log';

			$select = "id > $lastid AND course=$course AND cmid != 0 ORDER BY id LIMIT $max_record";
				
			$logs = $DB->get_records_select($table, $select); 
			
			if ($logs != null)
				return $logs;
			else 
				return false; 
		}
		
		// logging pull cron
		function usp_ews_pull_cron_log($cronstart, $cronstop, $timetaken, $status, $extras){

			$table='usp_ews_cron_ews_mdl_log';
			$qry = "INSERT INTO $table(crontimestart, crontimestop, timetaken, status, extras) VALUES($cronstart, $cronstop, $timetaken, $status, '$extras')";

            $rs = $this->execute_query($qry);

            if(!trim($rs)==""){
                return true;
            }
            return false;

		}

		// updates the last log id till when the logs are being pushed
		// gets the last timestamp and lastlogid sent and updates the config table
		public function update_config_table($id, $lastlogid, $time){
		
			$DB = $this->getDB();
			
			$updatedata = new stdclass;
			$updatedata->id = $id;
			$updatedata->lastlogid = $lastlogid;
			$updatedata->lastupdatetimestamp = $time;
						
			$update = $DB->update_record('usp_ews_config', $updatedata, true);
			
			return true;

		}
	
		// Sometimes their are no records so this will update config after sending config to backend
		public function update_config_lastupdate($id){
		
			$DB = $this->getDB();

			$updatedata = new stdclass;
			$updatedata->id = $id;
			$updatedata->lastupdatetimestamp = time();
						
			$update = $DB->update_record('usp_ews_config', $updatedata, true);
			
			return true;

		}	
		
		// function used to empty the given table
		//  @param string $table   The table that needs to be emptyed 
		public function trancate_table($table) {

		  // first truncate the table
			$trun_qry = "TRUNCATE TABLE $table";
			$rs = $this->execute_query($trun_qry);

            if(!trim($rs)==""){
                return true;
            }
            return false;
			
        }
		
		// getter
		// gets the database configuration
		public function getDB() {
            return $this->db;
        }
		
		// sets the moodle datababe configuration
		// uses moodle global variable $DB
		// avoids declaration of global $DB all the function
		// setter
		public function setDB() {
			global $DB;
            $this->db = $DB;
        }
		
    };
?>