<?php

/****
*  script to send the data to the backend
*
*/
	// Include required files
	require_once(dirname(__FILE__) . '/../../../config.php');   // moodle config file
	require_once($CFG->dirroot.'/blocks/usp_ews/lib.php'); // ews library file
	include_once($CFG->libdir.'/ddllib.php');				 // database library file

	
	require_once('class.push.php');
	
	// We are going to measure execution times
	$starttime =  microtime();
	$time =  time();
	$expection = false;
	$expectionall= '';
	
	mtrace('EWS Cron Started ................ ');
	
	$pushdata = new usp_ews_cron();
	
	$pushdata->pull_usp_ews_log_record();
/*		
	// checking if ews is used in some courses atleast
	$ews_used = $pushdata->check_mdl_usp_ews_config_not_empty();
	
	// only if ews is used
	if($ews_used == true){
		$statusbackend = false;
		try{
		    // check if backend is free
			$statusbackend = $pushdata->get_usp_ews_backend_status();
		} catch (Exception $e) {	
			// exception has occured and will be logged
			$expection = true;
			// exception occured
			$expectionall .= $e->getMessage(). get_string('in', 'block_usp_ews') . $e->getFile().  get_string('errorline', 'block_usp_ews') .$e->getLine() . get_string('cronbreakline', 'block_usp_ews');
		} 
		if($statusbackend){
			// disable the backend from process 
			// this will prevent the syncing problem if they run together
			$disableprocessbackend = false;
			try{
				$disableprocessbackend = $pushdata->usp_ews_en_disable_backend_process($time, time(), 0);
			} catch (Exception $e) {	
				$expection = true;
				$expectionall .= $e->getMessage(). get_string('in', 'block_usp_ews') . $e->getFile().  get_string('errorline', 'block_usp_ews') .$e->getLine() . get_string('cronbreakline', 'block_usp_ews');
			} 

			//  only if connection with backend possible
			if($disableprocessbackend){
			   // first syncing the data from the backend
				// getting interaction table to frontend
				// empty the intarction table in mdl server
				$DB->delete_records('usp_ews_interaction');
				mtrace('usp_ews_interaction table empty');
				
				// get the interaction table data from the backend
				try{
					$pullinteraction = $pushdata->pull_usp_ews_interaction_record();
					//$pullinteraction = $pushdata->pull_usp_ews_log_record();
				} catch (Exception $e) {	
					$expectionpull= $e->getMessage(). get_string('in', 'block_usp_ews') . $e->getFile().  get_string('errorline', 'block_usp_ews') .$e->getLine() . get_string('cronpullinteractionfail', 'block_usp_ews');
					$timetake = microtime_diff($starttime, microtime());
					$pushdata->usp_ews_pull_cron_log($time, time(), $timetake, 0, $expectionpull);
				} 	

				// if there are some records from the interaction table
				if(!empty($pullinteraction) && $pullinteraction != false){

					foreach($pullinteraction as $rs){
						$DB->insert_record('usp_ews_interaction', $rs, false, true);

					}

					
					
					mtrace('usp_ews_interaction table filled with new interaction');
					$timetake = microtime_diff($starttime, microtime());
					$pushdata->usp_ews_pull_cron_log($time, time(), $timetake, 1, get_string('cronpullinteraction', 'block_usp_ews'));
				}
				
				// first clean the config table
				// incase the block is removed and new blocks are added for the course
				$clean_config = $pushdata->clean_usp_ews_config();
				
				// if cleaned and updated then ok
				if($clean_config == true)
					mtrace('usp_ews_usp config table cleaned and updated');
				else
					mtrace('usp_ews_usp config table cleaned process failed');
					
				// Now push the config table to usp_ews backend
				$insertconfig= false;
				try{
					$insertconfig = $pushdata->insert_record_usp_ews_config();
				} catch (Exception $e) {	
					$expection = true;
					$expectionall .= $e->getMessage(). get_string('in', 'block_usp_ews') . $e->getFile().  get_string('errorline', 'block_usp_ews') .$e->getLine() . get_string('cronbreakline', 'block_usp_ews');
				} 
				// if pushed successfully then ok
				if($insertconfig == true){
					mtrace('Pushed config table');
				}
				else
					mtrace('failure sending config table');
			
			
				// Now push the students id with respected courses
				$insertstudents = false;
				try{
					$insertstudents = $pushdata->insert_record_usp_ews_student_course();
				} catch (Exception $e) {	
					$expection = true;
					$expectionall .= $e->getMessage(). get_string('in', 'block_usp_ews') . $e->getFile().  get_string('errorline', 'block_usp_ews') .$e->getLine() . get_string('cronbreakline', 'block_usp_ews');
				} 
				// for continuous cron, insert course by course
				// Now push the log data from last cron till now
				// it works with the id but can be changed to time accordingly
				$ewsconfig = $pushdata->get_mdl_usp_ews_config();
				
				// no courses with EWS
				if(empty($ewsconfig)){
					return false;
				}

				$lastupdatedid = 0;
				$lasttimestamp = 0;
					
				// array to check if the course data is processed
				$course_processed = array();
				
				// to fill array of course processed
				foreach($ewsconfig as $config){
					$course_processed[$config->courseid] = false;
					// update the time in caeses if it doesn't have new log and doesn't go through the process below
					// this is to show student that till that data things has been processed
					$ewsconfigupdate = $pushdata->update_config_lastupdate($config->id);
				}
				// counting the course to give average  records that should be sent
				$num_course =  count($ewsconfig);
				
				// maximum records that should be sent at a time
				$max_record = (EWS_DEFAULT_MAX_RECORD_PER_SEC * EWS_DEFAULT_MAX_CRON_TIME) / $num_course;	
					
				// if the records is more than default max record as courses maybe less
				// then fix it to maximum record only, this will handle maximum data a script can have
				if($max_record > EWS_DEFAULT_MAX_RECORD){
					$max_record = EWS_DEFAULT_MAX_RECORD;
				}
				
				$round = 0;
				//@set_time_limit(EWS_DEFAULT_MAX_CRON_TIME * 2); 

				// while still have the time allowed for the cron
				// and if there are some courses those are still to be processed
				while((microtime_diff($starttime, microtime()) < EWS_DEFAULT_MAX_CRON_TIME) && in_array(false, $course_processed, true)){
					foreach($ewsconfig as $config){

						// check if for this course all data processed allready
						if($course_processed[$config->courseid] == false){
						
							// gets previous log id till where the logs were pushed before
							$lastupdatedid = $pushdata->get_lastid_usp_ews_config($config->id);
							
							// get those log with maximum log limit that the script can handle and for it to fifnsh in a time frame
							$mdl_log = $pushdata->get_course_mdl_log($config->courseid, $lastupdatedid, $max_record);

							// if some new log in that course from last update then proceed
							if(!empty($mdl_log)){
								// last value of log in the array of log
								$lastlogprocessed = end($mdl_log);
								// last id in array
								$lastlogprocessedid = $lastlogprocessed->id;
								$lastlogprocessedtime = $lastlogprocessed->time;
								// pushing log to backend continues
								$processed = false;
								try{
									$processed = $pushdata->insert_record_usp_ews_log($config->courseid, $mdl_log);
								}catch (Exception $e) {	
									$expection = true;
									$expectionall .= $e->getMessage(). get_string('in', 'block_usp_ews') . $e->getFile().  get_string('errorline', 'block_usp_ews') .$e->getLine() . get_string('cronbreakline', 'block_usp_ews');
								} 

								// if successfully then update the config table details
								if($processed){
									// update the last update log id
									$updateid = $pushdata->update_config_table($config->id, $lastlogprocessedid, $lastlogprocessedtime);
									// print in console
									//mtrace('Processed course: ' . $config->courseid . '  -> Lastid: ' . $lastlogprocessedid . '  last time: ' . $lastlogprocessedtime . '<br/>');
								}
							}else{
								// if no log then upate the array of courses processed
								// course processed
								if($round == 0){
									$update = $pushdata->update_record_usp_ews_config($config->courseid);
								}
								$course_processed[$config->courseid] = true;
								mtrace('Processed completed Course id: ' . $config->courseid);
							
							}
							
						}
					}
					$round ++;
				}	
				// enable backend process again	
				try{
					$enable = $pushdata->usp_ews_en_disable_backend_process($time, time(), 1);
				}catch (Exception $e) {	
					$expection = true;
					$expectionall .= $e->getMessage(). get_string('in', 'block_usp_ews') . $e->getFile().  get_string('errorline', 'block_usp_ews') .$e->getLine() . get_string('cronbreakline', 'block_usp_ews');
				} 
			}else{	
				$expection = true;
				// if cant connect to backend then put the expection in the log
				$timetake = microtime_diff($starttime, microtime());
				$pushdata->usp_ews_cron_log($starttime, microtime(), $timetake, 0, get_string('cronerror', 'block_usp_ews'));
			}
		}else{
			// time taken to process the cron
			$timetake = microtime_diff($starttime, microtime());
			// status 2 is for waiting for either side to complete process
			$pushdata->usp_ews_cron_log($time, time(), $timetake, 2, get_string('cronwait', 'block_usp_ews'));
			// incase the backend was empty, this will enable the mdl free
			// since the above if will be skipped for enabling it
			try{
				$enable = $pushdata->usp_ews_en_disable_backend_process($time, time(), 1);
			}catch (Exception $e) {	
				$expection = true;
				$expectionall .= $e->getMessage(). get_string('in', 'block_usp_ews') . $e->getFile().  get_string('errorline', 'block_usp_ews') .$e->getLine() . get_string('cronbreakline', 'block_usp_ews');
			} 
		}
	}
	
 
	// time taken to process the cron
	$timetake = microtime_diff($starttime, microtime());

	// adding to log
	if(!$expection)
		$pushdata->usp_ews_cron_log($time, time(), $timetake, 1, get_string('cronsucess', 'block_usp_ews'));
	else{
		$pushdata->usp_ews_cron_log($time, time(), $timetake, 0, $expectionall);
	}	
*/
	mtrace('EWS Cron Completed ................ ');
