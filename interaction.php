<?php

/* 
* 3 items are shown here
* Interaction graph  
* Login trend graph
* popular activities
*/
	// Include required files
    require_once($CFG->dirroot.'/course/lib.php');
    require_once($CFG->dirroot.'/report/log/locallib.php');
    require_once($CFG->libdir.'/adminlib.php');

	// gather form data
	$id          = optional_param('cid', 0, PARAM_INT);// Course ID
	$group       = optional_param('group', 0, PARAM_INT); // Group to display
	$userid 		 = $USER->id; 
	$modname     = optional_param('modname', '', PARAM_CLEAN); // course_module->id
    $modid       = optional_param('modid', 0, PARAM_FILE); // number or 'site_errors'
    $modaction   = optional_param('modaction', '', PARAM_PATH); // an action as recorded in the logs
	$perpage     = optional_param('perpage', '1000', PARAM_INT); // how many per page
    $showcourses = optional_param('showcourses', 0, PARAM_INT); // whether to show courses if we're over our limit.
    $showusers   = optional_param('showusers', 0, PARAM_INT); // whether to show users if we're over our limit.
    $chooselog   = optional_param('chooselog', 0, PARAM_INT);
    $logformat   = optional_param('logformat', 'showashtml', PARAM_ALPHA);
	$option   = optional_param('option', 'none', PARAM_ALPHA);
	$week   = optional_param('week', -1, PARAM_INT);

    // update of v2.6
	//$context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);
	
	if (class_exists('context_course')) {
		$context = context_course::instance($course->id);
	} else {
		$context = get_context_instance(CONTEXT_COURSE, $course->id);
	}
	// getting configuration data from config table
	$configureddata = $DB->get_record('usp_ews_config', array('courseid' => $course->id, 'ewsinstanceid'=>$inst_id));
	// displaying message to student as when was data last updated
	$divoptions = array('id'=>'content', 'style'=>'margin: 1% 10%; background:#FF9009;font-size:12px; padding:5px; border:1px solid #C0311E; font-style:italic');
	$contenthtml = HTML_WRITER::start_tag('div', $divoptions);
	$contenthtml .= get_string('lastupdated', 'block_usp_ews') . userdate($configureddata->lastupdatetimestamp);
	$contenthtml .= $end_div;
	echo $contenthtml; 
		
	// start of content div <div>
	$divoptions = array('id'=>'content', 'style'=>'margin:0; padding:0;');
		
	/* counting the number of users to calculate the average
	* the number count is base on the role and only be counted 
	* according to the person logged in role
	**/
	$user_count = usp_ews_get_usercount($context->id, $userid);
	// setting base url
	$url = $PAGE->set_url('/blocks/usp_ews/mydashboard.php', array('cid'=>$courseid, 'mode'=>$mode));

	// getting interaction data from ews interaction table
	$interaction = $DB->get_record('usp_ews_interaction', array('userid' => $userid,'courseid' => $courseid));
	
	// if not data present then it means its not updated 
	if(empty($interaction)){
		// display information that the data is not processed yet
		$divoptions = array('id'=>'content', 'style'=>'margin: 20px 100px; background:#FF9009;font-size:12px; padding:5px; border:1px solid #C0311E;');
		$contenthtml = HTML_WRITER::start_tag('div', $divoptions);
		$contenthtml .= get_string('interaction_processing', 'block_usp_ews');
		$contenthtml .= $end_div;
		
		// start of content div <div>
		$divoptions = array('id'=>'content', 'style'=>'margin:0; padding:0;');
		$contenthtml .= HTML_WRITER::start_tag('div', $divoptions);
		$contenthtml .= HTML_WRITER::start_tag('table') // <table>		
		. $start_tr; // <tr>
	
	
	}else{
	// if interaction detail there
		// get login trend detail and decode it
		$logindetail = json_decode($interaction->logindetail);
		// get interaction graph detail and decode it
		$interactdetail = json_decode($interaction->interactiondetail);

		// to determine in which week graph shown as the arrows are clicked
		if($week==-1){
			$currentsection = $interactdetail[0];
		}else if($week < 1){
			$currentsection = 1;
		}
		else if($week >= $interactdetail[0]){
			$currentsection = $interactdetail[0];
		}
		else{
			$currentsection = $week;
		}
		// which weeks data is gethered	
		$currentdetail = $interactdetail[$currentsection];

		// start of content div <div>
		$divoptions = array('id'=>'content');
		$contenthtml = HTML_WRITER::start_tag('div', $divoptions);
		// tables properties
		$tableoptions = array(				 
				 'id' => 'layout-table',	
				 'style' => 'width:90%;');
					 
		$contenthtml .= HTML_WRITER::start_tag('table', $tableoptions); // <table>
		
		$troption = array('style' => 'margin-top: 50px;');
		$contenthtml .= HTML_WRITER::start_tag('tr', $troption);
		
		/*** 1st Column 
		* Bar graph is placed here
		* made using highchart/jquery lib and information from this script
		*/

		// left arrow
		// previous arrow img
		$tdoption = array('style'=>'width:10px; height:60px; margin:0; padding:0;');
		$contenthtml .= HTML_WRITER::start_tag('td', $tdoption); // <td>
		$linkoption = array('id' => 'prev_content', 'href'=>'');
		$contenthtml .= HTML_WRITER::start_tag('a', $linkoption) . '<img src="'. $OUTPUT->pix_url('arrow_prev', 'block_usp_ews'). '" class="
		usp_ews_arrow" alt="'. get_string('previous','block_usp_ews') .'" />'; 
		$contenthtml .= HTML_WRITER::end_tag('a'); 
		$contenthtml .= HTML_WRITER::end_tag('td'); // <td>
		
		// css properties
		$celloptions = array('id' => 'left-column',						
							'style' => 'width:auto;');
		$contenthtml .= HTML_WRITER::start_tag('td', $celloptions); // <td>

		$divoptions = array('class' => 'block_usp_ews sideblock usp_ews_border-bottom-radius',
							'id'=>'usp_ews_online_participation_graph', 'style' => 'margin-bottom: 10px;');
		$contenthtml .= HTML_WRITER::start_tag('div', $divoptions); // <div>

		// header of the graph
		$divoptions2 = array('class' => 'usp_ews_header');
		$contenthtml .= HTML_WRITER::start_tag('div', $divoptions2); // <div>			
		$contenthtml .= get_string('online_part', 'block_usp_ews') . $end_div; // </div>
			
		$divoptions3 = array('class' => 'usp_ews_onlinepart_graphcontent', 'style' => 'padding-bottom: 10px;');
		$contenthtml .= HTML_WRITER::start_tag('div', $divoptions3); // <div>

		// div content for graph
		// the highchart below populates the graph
		$divoptions3 = array('id' => 'usp_ews_interactionchart');
		$contenthtml .= HTML_WRITER::start_tag('div', $divoptions3) . $end_div . $end_div . $end_div; // end of div of whole graph with title	
		
		$contenthtml .= $end_td;

		// Right arrow
		$contenthtml .= HTML_WRITER::start_tag('td', $tdoption); // <td>
		$linkoption = array('id' => 'next_content', 'href'=>'');
		$contenthtml .= HTML_WRITER::start_tag('a', $linkoption) . '<img'. ' src="'. $OUTPUT->pix_url('arrow_next', 'block_usp_ews') . '" class="usp_ews_arrow" alt="'. get_string('next','block_usp_ews') .'" />'; 
		$contenthtml .= HTML_WRITER::end_tag('a'); 
		$contenthtml .= HTML_WRITER::end_tag('td'); // <td>
		$contenthtml .= $end_tr . $end_table;
		echo $contenthtml;
		
		// second table
		// this contains login graph and popular activity
		$tableoptions1 = array(				 
			 'id' => 'login-table',	
			 'style' => 'width:90%;');
				 
		$contenthtml = HTML_WRITER::start_tag('table', $tableoptions1) // <table>		
			. $start_tr; // <tr>
		
		/** 
		*  content area for the login trend graph
		*  div that show table
		*/
		$celloptions = array('id' => 'left-column2',
							'class' => 'no-padding',
							'valign' => 'top',
							'style' => 'width:68%;');
		$contenthtml .= HTML_WRITER::start_tag('td', $celloptions); // <td>
		
		$divoptions = array('class' => 'block_usp_ews sideblock usp_ews_border-bottom-radius',
							'id'=>'usp_ews_participation_breakdown');
		$contenthtml .= HTML_WRITER::start_tag('div', $divoptions); // <div>

		$divoptions2 = array('class' => 'usp_ews_header');
		$contenthtml .= HTML_WRITER::start_tag('div', $divoptions2)  // <div>			
			. get_string('weekly_login', 'block_usp_ews') . $end_div; //  </div>
			
		$divoptions3 = array('class' => 'usp_ews_login_graphcontent', 'style' => 'padding-bottom:10px;');
		$contenthtml .= HTML_WRITER::start_tag('div', $divoptions3); // <div>

		// div content for login graph
		$divoptions4 = array('id' => 'usp_ews_loginchart');
		$contenthtml .= HTML_WRITER::start_tag('div', $divoptions4) . $end_div;
		$contenthtml .= $end_div . $end_div;	
		$contenthtml .= $end_td;
		echo $contenthtml; 
		
		// giving 10px space between columns
		$tdoptions1 = array('style' => 'width:2%;');
		$contenthtml = HTML_WRITER::start_tag('td', $tdoptions1) . $end_td;
	}
	
	// popular activities content
	// html content
	$celloptions = array('id' => 'right-column',
						'class' => 'no-padding',
						'valign' => 'top',
						'style' => 'width:20%;');
	$contenthtml .= HTML_WRITER::start_tag('td', $celloptions); // <td>
	
	$divoptions = array('class' => 'block_news_items usp_ews_border-bottom-radius',
						'id'=>'usp_ews_popular_activity');
	$contenthtml .= HTML_WRITER::start_tag('div', $divoptions); // <div>

	$divoptions2 = array('class' => 'usp_ews_header');
	$contenthtml .= HTML_WRITER::start_tag('div', $divoptions2)  // <div>			
		. get_string('popular_activity', 'block_usp_ews') . $end_div;  // </div>
		
	$divoptions3 = array('class' => 'content');
	$contenthtml .= HTML_WRITER::start_tag('div', $divoptions3); // <div>
	
	$paraoptions = array('style' => 'text-align:center;margin:3px 1px; font-size:10px;');
	$contenthtml .= HTML_WRITER::start_tag('p', $paraoptions)  // <p>
		. get_string('most_viewed_activites', 'block_usp_ews') . $end_p; // </p>
	
	// function gets reference to full info about modules in course (including visibility).
	$modinfo = get_fast_modinfo($course, $userid);		

	// gets list of 5 popular activities in past 7 days
	// get popular activities of last 7 days
	$views = usp_ews_get_popularActivities($course->id, $context->id);
	
	// puts in the list formate
	$uloptions = array('id' => 'usp_ews_news');
	$contenthtml .= HTML_WRITER::start_tag('ul', $uloptions);
	// builds html content on page
	echo $contenthtml;
	
	$sectionnum = 0;
	// for each of 5 popular activities
	foreach ($views as $v){
		// module id
		$cmid = $v->mid;
		// module's information
		$cm = $modinfo->cms[$cmid];
		
		if($cm->uservisible == 1){
			// type of module
			if ($cm->modname == 'label') {
					continue;
			}
			if (!$cm->uservisible) {
					continue;
				}
			// get picture for that activities
			$icon = $cm->modname;
			if ($cm->modname == 'resources') {
				$icon = $OUTPUT->pix_icon('f/html', '');
			}
			else{
				$icon = $OUTPUT->pix_icon('icon', '', $cm->modname);	
			}
			// which section
			foreach ($modinfo->sections as $sectnum=>$section) {
				if ($cmid == $section[0])
				{
					$sectionnum = $sectnum;
				}
			}
			// <li> creating list
			echo '<li class="arr">';
			
			if ($sectionnum == 0)
			{
					// printing heading as General Course Block 
					echo $start_h3 . get_string('general_course_block', 'block_usp_ews')  . $end_h3;
			}
			else{
				// printing heading as topic and section
				echo $start_h3;
				switch ($course->format) {
					case 'weeks': print_string('week'); break;
					case 'topics': print_string('topic'); break;
					default: print_string('section'); break;
				}
				echo ' ' . $sectionnum . $end_h3;
			}

			$dimmed = $cm->visible ? '' : 'class="dimmed"';
			$modulename = get_string('modulename', $cm->modname);
			
			// printing module name, which is hyper linked to the activity
			echo "<p class='usp_ews_popular_modname'>$icon";
			echo "<a title=\"$modulename\" href=\"$CFG->wwwroot/mod/$cm->modname/view.php?id=$cm->id\">".format_string($cm->name).'</a><br/><span style="float:right; margin-right:15px">[';

			if (!empty($v->numviews)) {
				echo $v->numviews;
			} else {
				echo '-';
			}
			// view now, allows to view that activity now
			echo ' ' .  get_string('views', 'block_usp_ews') . ']</span></p>' 
				. "<br/><a class=\"more\" title=\"$modulename\" href=\"$CFG->wwwroot/mod/$cm->modname/view.php?id=$cm->id\">"
				.  get_string('viewnow', 'block_usp_ews') . "</a>";
			echo '</li>';
		
		}
	}// foreach
	// end of list
	echo HTML_WRITER::end_tag('ul');
	
	// closing the html tags
	$contenthtml = $end_div . $end_div;	
	$contenthtml .= $end_td;
	$contenthtml .= $end_tr . $end_table . $end_div;
	echo $contenthtml;

	
?>
<!---Javascript to generate graph---->
<script type="text/javascript">
    $(document).ready(function() {
		// clicking previous button
		$('#prev_content').click(function(){ 
			  var week = <?php echo $currentsection - 1;?>;
			  var courseid = <?php echo $courseid;?>;
			  var mode = "myint";
			  
			  var id = <?php echo $inst_id;?>;
			  window.location.href = "mydashboard.php?week=" + week + "&inst_id=" + id + "&cid=" + courseid + "&modetab=" + mode;
			  
			  return false; 
		});
		// clicking next button
		$('#next_content').click(function(){ 
			  var week = <?php echo $currentsection + 1;?>;
			  var courseid = <?php echo $courseid;?>;
			  var mode = "myint";
			  
			  var id = <?php echo $inst_id;?>;
			  window.location.href = "mydashboard.php?week=" + week + "&inst_id=" + id + "&cid=" + courseid + "&modetab=" + mode;
			
			  return false; 
		});
 
    });
</script>

<script type="text/javascript">
(function() {
	
	YUI().use('charts-legend', function (Y) 
    { 
        var myDataValues = [ 
		
		<?php   
		if(!empty($currentdetail) || $currentdetail != ""){	
			foreach($currentdetail as $interact=>$detail){
				if(isset($modinfo->cms[$detail->cmid])){
					$cm = $modinfo->cms[$detail->cmid];
					
					if($cm->uservisible == 1){
						$url_link = '<a href="' . $CFG->wwwroot . '/mod/' . $cm->modname . '/view.php?id=' . $cm->id .'">' . $modinfo->cms[$detail->cmid]->name . '</a>';
						
						echo '{activity:"' . $modinfo->cms[$detail->cmid]->name . '", myinteract:' . $detail->my . ', classaverage:' . $detail->cls . '},';
					}
				}
			}
		}	
		?>	
	
			
        ];
		
        //Define our axes for the chart.
        var myAxes = {
            interact_graph:{
                keys:["myinteract", "classaverage"],
                position:"left",
                type:"numeric",
			
				minimum: 0,

				title: '<?php print_string('gp_yaxis', 'block_usp_ews'); ?>',
                styles:{
                    majorTicks:{
                        display: "none"
                    }
                }
            },
            activityList:{
                keys:["activity"],
                position:"bottom",
                type:"category",
				title: 'Week <?php echo $currentsection; ?>: Activities/Resourses', 
                styles:{
                    majorTicks:{
                        display: "none"
                    },
                    label: {
                       // rotation:-45,
                        margin:{top:5}
                    }
                }
            }
        };
       
        //define the series 
        var seriesCollection = [
         {
                type:"column",
                xAxis:"activityList",
                yAxis:"interact_graph",
                xKey:"activity",
                xDisplayName: '<?php print_string('activity', 'block_usp_ews'); ?>',
                yKey:"myinteract",
                yDisplayName: '<?php print_string('leg_parti', 'block_usp_ews'); ?>',
                styles: {
				    fill: {
                            color: "#4572A7" 
                    },
                    border: {
                        weight: 1,
                        color: "#cbc8ba"
                    },
                    over: {
                        fill: {
                            alpha: 0.7
                        }
                    }
                }
            },
            {
                type:"column",
                xAxis:"activityList",
                yAxis:"interact_graph",
                xKey:"activity",
                xDisplayName:'<?php print_string('activity', 'block_usp_ews'); ?>',
                yKey:"classaverage",
                yDisplayName:'<?php print_string('leg_class', 'block_usp_ews'); ?>',
                styles: {
                    marker:{
                        fill: {
                            color: "#C35F5C" 
                        },
                        border: {
                            weight: 1,
                            color: "#cbc8ba"
                        },
                        over: {
                            fill: {
                                alpha: 0.7
                            }
                        }
                    }
                }
            }
        ];
		
		var legend = {
			position: "bottom",
		};
		

        //instantiate the chart
        var myChart = new Y.Chart({
                            dataProvider:myDataValues, 
							legend:legend,					
                            axes:myAxes, 
                            seriesCollection:seriesCollection, 
                            horizontalGridlines: true,
                            verticalGridlines: true,
                            render:"#usp_ews_interactionchart"
        });
    });
	

	YUI().use('charts-legend', function (Y) 
    { 
        var myDataValues = [ 
		
		<?php   

						for($i=0; $i < count($logindetail); $i ++){
							$week = $i+1;			
							echo '{week:"' . $week . '", mylogin:' . $logindetail[$i]->my . ', classlogin:' . $logindetail[$i]->cls . ', mylogintrend:' . $logindetail[$i]->my . '},';
							
						}		
		?>	
	
			
        ];
		
        //Define our axes for the chart.
        var myAxes = {
            login_graph:{
                keys:["mylogin", "classlogin", "mylogintrend"],
                position:"left",
                type:"numeric",
				//maximum: 5,
				minimum: 0,

				title: '<?php print_string('gp_loginyaxis', 'block_usp_ews'); ?>',
                styles:{
                    majorTicks:{
                        display: "Day"
                    }
                }
            },
            dateRange:{
                keys:["week"],
                position:"bottom",
                type:"category",
				title: '<?php print_string('gp_loginxaxis', 'block_usp_ews'); ?>',
                styles:{
                    majorTicks:{
                        display: "none"
                    },
                    label: {
                       // rotation:-45,
                        margin:{top:5}
                    }
                }
            }
        };
       
        //define the series 
        var seriesCollection = [
         {
                type:"column",
                xAxis:"dateRange",
                yAxis:"login_graph",
                xKey:"week",
                xDisplayName: 'Week',
                yKey:"mylogin",
                yDisplayName: '<?php print_string('leg_loginparti', 'block_usp_ews'); ?>',
                styles: {
				    fill: {
                            color: "#4572A7" 
                    },
                    border: {
                        weight: 1,
                        color: "#cbc8ba"
                    },
                    over: {
                        fill: {
                            alpha: 0.7
                        }
                    }
                }
            },
            {
                type:"column",
                xAxis:"dateRange",
                yAxis:"login_graph",
                xKey:"week",
                xDisplayName:'Week',
                yKey:"classlogin",
                yDisplayName:'<?php print_string('leg_loginclass', 'block_usp_ews'); ?>',
                styles: {
                    marker:{
                        fill: {
                            color: "#C35F5C" 
                        },
                        border: {
                            weight: 1,
                            color: "#cbc8ba"
                        },
                        over: {
                            fill: {
                                alpha: 0.7
                            }
                        }
                    }
                }
            },
			{
                type:"combo",
               // xAxis:"dateRange",
               // yAxis:"login_graph",
               // xKey:"week",
               // xDisplayName:"Date",
                yKey:"mylogintrend",
                yDisplayName:'<?php print_string('leg_loginparti', 'block_usp_ews'); ?>',
                    line: {
                        color: "#9AB26A"
                    },
                marker: {
                    fill: {
                        color: "#9AB26A"
                    },
                    border: {
                        color: "#80699B",
                        weight: 0.5
                    },
                    over: {
                        fill: {
                                alpha: 0.7
                        }
                    },
                    //width:9,
                    //height:9
                }
            }
        ];
		
		var legend = {
			position: "bottom",
		};
		

        //instantiate the chart
        var myChart = new Y.Chart({
                            dataProvider:myDataValues, 
							legend:legend,					
                            axes:myAxes, 
                            seriesCollection:seriesCollection, 
                            horizontalGridlines: true,
                            verticalGridlines: true,
                            render:"#usp_ews_loginchart"
        });
    });
	
})();
</script>

