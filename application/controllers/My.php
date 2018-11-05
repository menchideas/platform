<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class My extends CI_Controller {
    
    //This controller is usually accessed via the /my/ URL prefix via the Messenger Bot
    
	function __construct() {
		parent::__construct();
		
		//Load our buddies:
		$this->output->enable_profiler(FALSE);
	}

    function index(){
        //Nothing here:
        header( 'Location: /');
    }




    /* ******************************
     * Messenger Persistent Menu
     ****************************** */

    function actionplan($w_id=0, $c_id=0){
        $this->load->view('custom/shared/p_header' , array(
            'title' => '🚩 Action Plan',
        ));
        $this->load->view('custom/student/actionplan_frame' , array(
            'c_id' => $c_id,
            'w_id' => $w_id,
        ));
        $this->load->view('custom/shared/p_footer');
    }

    function display_actionplan($u_fb_psid, $w_id=0, $c_id=0){

        //Get session data in case user is doing a browser login:
        $udata = $this->session->userdata('user');

        //Fetch Bootcamps for this user:
        if(!$u_fb_psid && count($udata['u__ws'])<1){
            //There is an issue here!
            die('<div class="alert alert-danger" role="alert">Invalid Credentials</div>');
        } elseif(count($udata['u__ws'])<1 && !is_dev() && isset($_GET['sr']) && !parse_signed_request($_GET['sr'])){
            die('<div class="alert alert-danger" role="alert">Unable to authenticate your origin.</div>');
        }

        //Set subscription filters:
        $w_filter = array();

        //Do we have a use session?
        if($w_id>0){
            //Yes! It seems to be a desktop login:
            $w_filter['w_id'] = $w_id;
        } elseif(count($udata['u__ws'])>0){
            //Yes! It seems to be a desktop login:
            $w_filter['w_outbound_u_id'] = $udata['u__ws'][0]['w_outbound_u_id'];
            $w_filter['w_status >='] = 0;
        }

        if($u_fb_psid>0){
            //No, we should have a Facebook PSID to try to find them:
            $w_filter['u_fb_psid'] = $u_fb_psid;
            $w_filter['w_status >='] = 0;
        }

        //Try finding them:
        $subscriptions = $this->Db_model->w_fetch($w_filter, array('c','u'));

        if(count($subscriptions)==0){

            //No subscriptions found:
            die('<div class="alert alert-danger" role="alert">You have no active subscriptions yet. '.$this->lang->line('bot_lets_intro').'</div>');

        } elseif(count($subscriptions)>1){

            //Let them choose between subscriptions:
            echo '<div class="list-group" style="margin-top: 10px;">';
            foreach($subscriptions as $w){
                echo echo_w($w);
            }
            echo '</div>';

        } elseif(count($subscriptions)==1) {

            //We found a single subscription, load this by default:
            if(!$w_id || !$c_id){
                //User with a single subscription
                $w_id = $subscriptions[0]['w_id'];
                $c_id = $subscriptions[0]['c_id']; //TODO set to current/focused intent
            }

            //We have a single item to load:
            //Now we need to load the action plan:
            $k_ins = $this->Db_model->k_fetch(array(
                'w_id' => $w_id,
                'cr_status >=' => 1,
                'c_status >=' => 1,
                'cr_outbound_c_id' => $c_id,
            ), array('w','cr','cr_c_in'));

            $k_outs = $this->Db_model->k_fetch(array(
                'w_id' => $w_id,
                'cr_status >=' => 1,
                'c_status >=' => 1,
                'cr_inbound_c_id' => $c_id,
            ), array('w','cr','cr_c_out'));

            $cs = $this->Db_model->c_fetch(array(
                'c_status >=' => 1,
                'c_id' => $c_id,
            ));

            if(count($cs)<1 || (count($k_ins)<1 && count($k_outs)<1)){

                //Ooops, we had issues finding th is intent! Should not happen, report:
                $this->Db_model->e_create(array(
                    'e_inbound_u_id' => $subscriptions[0]['u_id'],
                    'e_json' => $subscriptions,
                    'e_text_value' => 'Unable to load a specific intent for the student Action Plan! Should not happen...',
                    'e_inbound_c_id' => 8,
                    'e_outbound_c_id' => $c_id,
                ));

                die('<div class="alert alert-danger" role="alert">Invalid Intent ID.</div>');
            }

            //All good, Load UI:
            $this->load->view('custom/student/actionplan_ui.php' , array(
                'w' => $subscriptions[0], //We must have 1 by now!
                'c' => $cs[0],
                'k_ins' => $k_ins,
                'k_outs' => $k_outs,
            ));

        }
    }

    function skip_tree($w_id, $c_id, $k_id){

        //User has requested to skip an intent starting from:
        $dwn_tree = $this->Db_model->k_recursive_fetch($w_id, $c_id, 1);
        $skip_ks = array_merge(array(intval($k_id)), $dwn_tree['k_flat']);

        //Now see how many should we actually skip based on current status:
        $skippable_ks = $this->Db_model->k_fetch(array(
            'k_status IN (1,0)' => null, //incomplete
            'k_id IN ('.join(',',$skip_ks).')' => null,
        ));

        //Now start skipping:
        foreach($skippable_ks as $k){
            $this->Db_model->k_update($k['k_id'], array(
                'k_last_updated' => date("Y-m-d H:i:s"),
                'k_status' => -1, //skip
            ));
        }

        //There is a chance that the subscription might be now completed due to this skipping, lets check:
        $ks = $this->Db_model->k_fetch(array(
            'k_id' => $k_id,
        ), array('w','cr','cr_c_in'));
        if(count($ks)>0){
            $this->Db_model->k_complete_recursive_up($ks[0],$ks[0],-1);
        }

        //Draft message:
        $message = '<div class="alert alert-success" role="alert">'.count($skippable_ks).' intent'.echo__s(count($skippable_ks)).' successfully skipped.</div>';

        //Find the next item to navigate them to:
        $ks_next = $this->Db_model->k_next_fetch($w_id);
        if(count($ks_next)>0){
            redirect_message('/my/actionplan/'.$ks_next[0]['k_w_id'].'/'.$ks_next[0]['c_id'],$message);
        } else {
            redirect_message('/my/actionplan',$message);
        }
    }

    function choose_any_path($w_id, $c_id, $cr_inbound_c_id, $w_key){

        if(md5($w_id.'kjaghksjha*(^'.$c_id.$cr_inbound_c_id)==$w_key){
            //Validated! Move on:
            //$c_id is the chosen path for the options of $cr_inbound_c_id
            //When a user chooses an answer to an ANY intent, this function would mark that answer as complete while marking all siblings as SKIPPED
            $chosen_path = $this->Db_model->k_fetch(array(
                'k_w_id' => $w_id,
                'cr_inbound_c_id' => $cr_inbound_c_id, //Fetch children of parent intent which are the siblings of current intent
                'cr_outbound_c_id' => $c_id, //The answer
                'cr_status >=' => 1,
                'c_status >=' => 1,
            ), array('w','cr','cr_c_in'));

            if(count($chosen_path)==1){

                //Also fetch outbound to see if we requires any notes/url to mark as complete:
                $path_requirements = $this->Db_model->k_fetch(array(
                    'k_w_id' => $w_id,
                    'cr_inbound_c_id' => $cr_inbound_c_id, //Fetch children of parent intent which are the siblings of current intent
                    'cr_outbound_c_id' => $c_id, //The answer
                    'cr_status >=' => 1,
                    'c_status >=' => 1,
                ), array('w','cr','cr_c_out'));

                if(count($path_requirements)==1){
                    //Determine status:
                    $force_working_on = ( (intval($path_requirements[0]['c_require_notes_to_complete']) || intval($path_requirements[0]['c_require_url_to_complete'])) ? 1 : null );

                    //Now mark intent as complete and move on:
                    $this->Db_model->k_complete_recursive_up($chosen_path[0], $chosen_path[0], $force_working_on);

                    //Successful, redirect and show message:
                    redirect_message('/my/actionplan/'.$w_id.'/'.$c_id,'<div class="alert alert-success" role="alert">Your answer was saved.</div>');
                }

            } else {
                //Oooopsi, we could not find it! Log error and return false:
                $this->Db_model->e_create(array(
                    'e_text_value' => 'Unable to locate OR selection for this subscription',
                    'e_inbound_c_id' => 8, //System error
                    'e_outbound_c_id' => $c_id,
                    'e_w_id' => $w_id,
                ));

                //Error, redirect:
                redirect_message('/my/actionplan/'.$w_id.'/'.$cr_inbound_c_id,'<div class="alert alert-danger" role="alert">There was an error saving your answer.</div>');
            }
        }
    }

    function update_k_save(){

        //Validate integrity of request:
        if(!isset($_POST['k_id']) || intval($_POST['k_id'])<=0 || !isset($_POST['k_notes'])){
            return redirect_message('/my/actionplan','<div class="alert alert-danger" role="alert">Error: Missing Core Data.</div>');
        }

        //Fetch student name and details:
        $udata = $this->session->userdata('user');
        $ks = $this->Db_model->k_fetch(array(
            'k_id' => $_POST['k_id'],
        ), array('w','cr','cr_c_out'));

        if(!(count($ks)==1)){
            return redirect_message('/my/actionplan','<div class="alert alert-danger" role="alert">Error: Invalid submission ID.</div>');
        }
        $k_url = '/my/actionplan/'.$ks[0]['k_w_id'].'/'.$ks[0]['c_id'];


        //Do we have what it takes to mark as complete?
        if($ks[0]['c_require_url_to_complete'] && count(extract_urls($_POST['k_notes']))<1){
            return redirect_message($k_url,'<div class="alert alert-danger" role="alert">Error: URL Required to mark ['.$ks[0]['c_outcome'].'] as complete.</div>');
        } elseif($ks[0]['c_require_notes_to_complete'] && strlen($_POST['k_notes'])<1){
            return redirect_message($k_url,'<div class="alert alert-danger" role="alert">Error: Notes Required to mark ['.$ks[0]['c_outcome'].'] as complete.</div>');
        }


        //Did anything change?
        $status_changed = ( $ks[0]['k_status']<=1 );
        $notes_changed = !($ks[0]['k_notes']==trim($_POST['k_notes']));
        if(!$notes_changed && !$status_changed){
            //Nothing seemed to change! Let them know:
            return redirect_message($k_url,'<div class="alert alert-info" role="alert">Note: Nothing saved because nothing was changed.</div>');
        }

        //All good, move forward with the update:
        //Save a copy of the student completion report:
        $this->Db_model->e_create(array(
            'e_inbound_u_id' => ( isset($udata['u_id']) ? $udata['u_id'] : $ks[0]['k_outbound_u_id'] ),
            'e_text_value' => ( $notes_changed ? trim($_POST['k_notes']) : '' ),
            'e_inbound_c_id' => 33, //Completion Report
            'e_outbound_c_id' => $ks[0]['c_id'],
            'e_json' => array(
                'input' => $_POST,
                'k' => $ks[0],
            ),
        ));

        if($notes_changed){
            //Updates k notes:
            $this->Db_model->k_update($ks[0]['k_id'], array(
                'k_last_updated' => date("Y-m-d H:i:s"),
                'k_notes' => trim($_POST['k_notes']),
            ));
        }

        if($status_changed){
            //Also update k_status, determine what it should be:
            $this->Db_model->k_complete_recursive_up($ks[0], $ks[0]);
        }


        //Redirect back to page with success message:
        if(isset($_POST['k_next_redirect']) && intval($_POST['k_next_redirect'])>0){
            //Go to next item:
            $ks_next = $this->Db_model->k_next_fetch($ks[0]['w_id'], ( intval($_POST['k_next_redirect'])>1 ? intval($_POST['k_next_redirect']) : 0 ));
            if(count($ks_next)>0){
                //Override original item:
                $k_url = '/my/actionplan/'.$ks_next[0]['k_w_id'].'/'.$ks_next[0]['c_id'];
            }
        }

        return redirect_message($k_url,'<div class="alert alert-success" role="alert"><i class="fal fa-check-circle"></i> Successfully Saved</div>');

        //TODO Update w__progress at this point based on intent data
        //TODO Update tree upwards and dispatch drip/instant message logic as needed!
        /*
        //See if we need to dispatch any messages:
        $on_complete_text_values = array();
        $drip_messages = array();

        //Dispatch messages for this Step:
        //$step_messages = extract_level($bs[0],$_POST['c_id']);

        foreach($step_messages['intent']['c__messages'] as $i){
            if($i['i_status']==2){
                array_push($drip_messages , $i);
            } elseif($i['i_status']==3){
                array_push($on_complete_text_values, array_merge($i , array(
                    'e_inbound_u_id' => 0,
                    'e_outbound_u_id' => $ks[0]['u_id'],
                    'i_outbound_c_id' => $i['i_outbound_c_id'],
                )));
            }
        }

        //Anything to be sent instantly?
        if(count($on_complete_text_values)>0){
            //Dispatch all Instant Messages, their engagements have already been logged:
            $this->Comm_model->send_message($on_complete_text_values);
        }

        //TODO Wire in drip messages
        if(0 && count($drip_messages)>0){

            $start_time = time();
            //TODO Adjust $drip_intervals = (class_ends($bs[0], $focus_class)-$start_time) / (count($drip_messages)+1);
            $drip_time = $start_time;

            foreach($drip_messages as $i){

                $drip_time += $drip_intervals;
                $this->Db_model->e_create(array(

                    'e_inbound_u_id' => 0, //System
                    'e_outbound_u_id' => $ks[0]['u_id'],
                    'e_timestamp' => date("Y-m-d H:i:s" , $drip_time ), //Used by Cron Job to fetch this Drip when due
                    'e_json' => array(
                        'created_time' => date("Y-m-d H:i:s" , $start_time ),
                        'drip_time' => date("Y-m-d H:i:s" , $drip_time ),
                        'i_drip_count' => count($drip_messages),
                        'i' => $i, //The actual message that would be sent
                    ),
                    'e_inbound_c_id' => 52, //Pending Drip e_inbound_c_id=52
                    'e_status' => 0, //Pending for the Drip Cron
                    'e_i_id' => $i['i_id'],
                    'e_outbound_c_id' => $i['i_outbound_c_id'],

                ));
            }
        }
        */
    }


    function reset_pass(){
        $data = array(
            'title' => 'Password Reset',
        );
        $this->load->view('custom/shared/p_header' , $data);
        $this->load->view('custom/student/password_reset');
        $this->load->view('custom/shared/p_footer');
    }
	
}
