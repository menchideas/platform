<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class My extends CI_Controller
{

    //This controller is usually accessed via the /my/ URL prefix via the Messenger Bot

    function __construct()
    {
        parent::__construct();

        //Load our buddies:
        $this->output->enable_profiler(FALSE);
    }

    function index()
    {
        //Nothing here:
        header('Location: /');
    }

    function fb_profile($en_id)
    {

        $udata = fn___en_auth(array(1308));
        $current_us = $this->Database_model->en_fetch(array(
            'en_id' => $en_id,
        ));

        if (!$udata) {
            return fn___echo_json(array(
                'status' => 0,
                'message' => 'Session Expired. Login and Try again.',
            ));
        } elseif (count($current_us) == 0) {
            return fn___echo_json(array(
                'status' => 0,
                'message' => 'User not found!',
            ));
        } elseif (strlen($current_us[0]['u_fb_psid']) < 10) {
            return fn___echo_json(array(
                'status' => 0,
                'message' => 'User does not seem to be connected to Mench, so profile data cannot be fetched',
            ));
        } else {

            //Fetch results and show:
            return fn___echo_json(array(
                'fb_profile' => $this->Chat_model->fn___facebook_graph('GET', '/'.$current_us[0]['u_fb_psid'], array()),
                'en' => $current_us[0],
            ));

        }

    }


    function actionplan($actionplan_tr_id = 0, $in_id = 0)
    {

        $this->load->view('view_shared/messenger_header', array(
            'title' => '🚩 Action Plan',
        ));
        //include main body:
        $this->load->view('view_ledger/tr_actionplan_messenger_frame', array(
            'in_id' => $in_id,
            'actionplan_tr_id' => $actionplan_tr_id,
        ));
        $this->load->view('view_shared/messenger_footer');
    }

    function fn___display_actionplan($psid, $actionplan_tr_id = 0, $in_id = 0)
    {

        //Get session data in case user is doing a browser login:
        $udata = $this->session->userdata('user');
        $empty_session = (!isset($udata['en__actionplans']) || count($udata['en__actionplans']) < 1);
        $is_miner = fn___filter_array($udata['en__parents'], 'en_id', 1308);

        //Authenticate user:
        if (!$psid && $empty_session && !$is_miner) {
            die('<div class="alert alert-danger" role="alert">Invalid Credentials</div>');
        } elseif ($empty_session && !fn___is_dev() && isset($_GET['sr']) && !fn___parse_signed_request($_GET['sr'])) {
            die('<div class="alert alert-danger" role="alert">Unable to authenticate your origin.</div>');
        }

        if($empty_session && $psid > 0){
            //Authenticate this user:
            $udata = $this->Matrix_model->fn___authenticate_messenger_user($psid);
        }

        //Set Action Plan filters:
        $filters = array();
        $filters['tr_en_type_id'] = 4235; //Action Plan Intent

        //Do we have a use session?
        if ($actionplan_tr_id > 0 && $in_id > 0) {
            //Yes! It seems to be a desktop login:
            $filters['tr_tr_parent_id'] = $actionplan_tr_id;
            $filters['tr_in_child_id'] = $in_id;
        } elseif (!$empty_session) {
            //Yes! It seems to be a desktop login (versus Facebook Messenger)
            $filters['tr_en_parent_id'] = $udata['en_id'];
            $filters['tr_in_parent_id'] = 0; //Top-level Action Plans
            $filters['tr_status >='] = 0;
        }

        //Try finding them:
        $trs = $this->Database_model->tr_fetch($filters, array('in_child'));

        if (count($trs) < 1) {

            //No Action Plans found:
            die('<div class="alert alert-danger" role="alert">You have no active Action Plans yet.</div>');

        } elseif (count($trs) > 1) {

            //Determine Action Plan IDs if not provided:
            if(!$actionplan_tr_id || !$in_id){
                $actionplan_tr_id = ( $trs[0]['tr_tr_parent_id'] == 0 ? $trs[0]['tr_id'] : $trs[0]['tr_tr_parent_id'] );
                $in_id = $trs[0]['tr_in_child_id'];
            }

            //Log action plan view transaction:
            $this->Database_model->tr_create(array(
                'tr_en_type_id' => 4283,
                'tr_en_credit_id' => $trs[0]['tr_en_parent_id'],
                'tr_en_parent_id' => $trs[0]['tr_en_parent_id'],
                'tr_tr_parent_id' => $actionplan_tr_id,
                'tr_in_child_id' => $in_id,
            ));

            if(count($trs) > 1) {

                //List all Action Plans to allow Master to choose:
                echo '<h3 class="master-h3 primary-title">My Action Plan</h3>';
                echo '<div class="list-group" style="margin-top: 10px;">';
                foreach ($trs as $tr) {
                    //Prepare metadata:
                    $metadata = unserialize($tr['in_metadata']);
                    //Display row:
                    echo '<a href="/my/actionplan/' . $tr['tr_id'] . '/' . $tr['tr_in_child_id'] . '" class="list-group-item">';
                    echo '<span class="pull-right">';
                    echo '<span class="badge badge-primary"><i class="fas fa-angle-right"></i></span>';
                    echo '</span>';
                    echo echo_status('tr_status', $tr['tr_status'], 1, 'right');
                    echo ' ' . $tr['in_outcome'];
                    echo ' ' . $metadata['in__tree_in_count'];
                    echo ' &nbsp;<i class="fas fa-clock"></i> ' . fn___echo_time_range($tr, true);
                    echo '</a>';
                }
                echo '</div>';

            } elseif(count($trs)==1){

                //We have a single item to load:
                //Now we need to load the action plan:
                $actionplan_parents = $this->Database_model->tr_fetch(array(
                    'tr_en_type_id' => 4235, //Action Plan Intent
                    'tr_tr_parent_id' => $actionplan_tr_id,
                    'in_status >=' => 2, //Published+ Intents
                    'tr_in_child_id' => $in_id,
                ), array('in_parent'));

                $actionplan_children = $this->Database_model->tr_fetch(array(
                    'tr_en_type_id' => 4235, //Action Plan Intent
                    'tr_tr_parent_id' => $actionplan_tr_id,
                    'in_status >=' => 2, //Published+ Intents
                    'tr_in_parent_id' => $in_id,
                ), array('in_child'));


                $ins = $this->Database_model->in_fetch(array(
                    'in_status >=' => 2,
                    'in_id' => $in_id,
                ));

                if (count($ins) < 1 || (!count($actionplan_parents) && !count($actionplan_children))) {

                    //Ooops, we had issues finding th is intent! Should not happen, report:
                    $this->Database_model->tr_create(array(
                        'tr_en_credit_id' => $trs[0]['en_id'],
                        'tr_metadata' => $trs,
                        'tr_content' => 'Unable to load a specific intent for the master Action Plan! Should not happen...',
                        'tr_en_type_id' => 4246, //Platform Error
                        'tr_tr_parent_id' => $actionplan_tr_id,
                        'tr_in_child_id' => $in_id,
                    ));

                    die('<div class="alert alert-danger" role="alert">Invalid Intent ID.</div>');
                }

                //All good, Load UI:
                $this->load->view('view_ledger/tr_actionplan_messenger_ui.php', array(
                    'actionplan' => $trs[0], //We must have 1 by now!
                    'in' => $ins[0],
                    'actionplan_parents' => $actionplan_parents,
                    'actionplan_children' => $actionplan_children,
                ));

            }
        }
    }


    function load_w_actionplan()
    {

        //Auth user and check required variables:
        $udata = fn___en_auth(array(1308)); //miners

        if (!$udata) {
            return fn___echo_json(array(
                'status' => 0,
                'message' => 'Session Expired',
            ));
        } elseif (!isset($_POST['tr_id']) || intval($_POST['tr_id']) < 1) {
            return fn___echo_json(array(
                'status' => 0,
                'message' => 'Missing Action Plan ID',
            ));
        }

        //Fetch Action Plan
        $actionplans = $this->Database_model->w_fetch(array(
            'tr_id' => $_POST['tr_id'], //Other than this one...
        ));
        if (!(count($actionplans) == 1)) {
            return fn___echo_json(array(
                'status' => 0,
                'message' => 'Invalid Action Plan ID',
            ));
        }
        $w = $actionplans[0];

        //Load Action Plan iFrame:
        return fn___echo_json(array(
            'status' => 1,
            'url' => '/my/actionplan/' . $w['tr_id'] . '/' . $w['tr_in_child_id'],
        ));

    }


    function load_u_trs($en_id)
    {

        //Auth user and check required variables:
        $udata = fn___en_auth(array(1308)); //miners

        if (!$udata) {
            die('<div class="alert alert-danger" role="alert">Session Expired</div>');
        } elseif (intval($en_id) < 1) {
            die('<div class="alert alert-danger" role="alert">Missing User ID</div>');
        }

        //Load view for this iFrame:
        $this->load->view('view_shared/messenger_header', array(
            'title' => 'User Transactions',
        ));
        $this->load->view('view_ledger/tr_entity_history', array(
            'en_id' => $en_id,
        ));
        $this->load->view('view_shared/messenger_footer');
    }

    function skip_tree($tr_id, $in_id, $tr_id)
    {
        //Start skipping:
        $total_skipped = count($this->Database_model->k_skip_recursive_down($tr_id));

        //Draft message:
        $message = '<div class="alert alert-success" role="alert">' . $total_skipped . ' concept' . fn___echo__s($total_skipped) . ' successfully skipped.</div>';

        //Find the next item to navigate them to:
        $next_ins = $this->Matrix_model->fn___in_next_actionplan($tr_id);
        if ($next_ins) {
            return fn___redirect_message('/my/actionplan/' . $next_ins[0]['tr_tr_parent_id'] . '/' . $next_ins[0]['in_id'], $message);
        } else {
            return fn___redirect_message('/my/actionplan', $message);
        }
    }

    function choose_any_path($tr_id, $tr_in_parent_id, $in_id, $w_key)
    {
        if (md5($tr_id . 'kjaghksjha*(^' . $in_id . $tr_in_parent_id) == $w_key) {
            if ($this->Database_model->k_choose_or($tr_id, $tr_in_parent_id, $in_id)) {
                return fn___redirect_message('/my/actionplan/' . $tr_id . '/' . $in_id, '<div class="alert alert-success" role="alert">Your answer was saved.</div>');
            } else {
                //We had some sort of an error:
                return fn___redirect_message('/my/actionplan/' . $tr_id . '/' . $tr_in_parent_id, '<div class="alert alert-danger" role="alert">There was an error saving your answer.</div>');
            }
        }
    }

    function update_k_save()
    {

        //Validate integrity of request:
        if (!isset($_POST['tr_id']) || intval($_POST['tr_id']) < 1 || !isset($_POST['tr_content'])) {
            return fn___redirect_message('/my/actionplan', '<div class="alert alert-danger" role="alert">Error: Missing Core Data.</div>');
        }

        //Fetch master name and details:
        $udata = $this->session->userdata('user');
        $trs = $this->Database_model->tr_fetch(array(
            'tr_id' => $_POST['tr_id'],
        ), array('w', 'cr', 'cr_c_child'));

        if (!(count($trs) == 1)) {
            return fn___redirect_message('/my/actionplan', '<div class="alert alert-danger" role="alert">Error: Invalid submission ID.</div>');
        }
        $k_url = '/my/actionplan/' . $trs[0]['tr_tr_parent_id'] . '/' . $trs[0]['in_id'];


        //Do we have what it takes to mark as complete?
        $obj_breakdown = fn___extract_message_references($_POST['tr_content']);

        if ($trs[0]['c_require_url_to_complete'] && count($obj_breakdown['en_urls']) < 1) {
            return fn___redirect_message($k_url, '<div class="alert alert-danger" role="alert">Error: URL Required to mark [' . $trs[0]['in_outcome'] . '] as complete.</div>');
        } elseif ($trs[0]['c_require_notes_to_complete'] && strlen($_POST['tr_content']) < 1) {
            return fn___redirect_message($k_url, '<div class="alert alert-danger" role="alert">Error: Notes Required to mark [' . $trs[0]['in_outcome'] . '] as complete.</div>');
        }


        //Did anything change?
        $status_changed = ($trs[0]['tr_status'] <= 1);
        $notes_changed = !($trs[0]['tr_content'] == trim($_POST['tr_content']));
        if (!$notes_changed && !$status_changed) {
            //Nothing seemed to change! Let them know:
            return fn___redirect_message($k_url, '<div class="alert alert-info" role="alert">Note: Nothing saved because nothing was changed.</div>');
        }

        //Has anything changed?
        if ($notes_changed) {
            //Updates k notes:
            $this->Database_model->tr_update($trs[0]['tr_id'], array(
                'tr_content' => trim($_POST['tr_content']),
                'tr_en_type_id' => fn___detect_tr_en_type_id($_POST['tr_content']),
            ), (isset($udata['en_id']) ? $udata['en_id'] : $trs[0]['k_children_en_id']));
        }

        if ($status_changed) {
            //Also update tr_status, determine what it should be:
            $this->Matrix_model->in_actionplan_complete_up($trs[0], $trs[0]);
        }


        //Redirect back to page with success message:
        if (isset($_POST['fn___in_next_actionplan'])) {
            //Go to next item:
            $next_ins = $this->Matrix_model->fn___in_next_actionplan($trs[0]['tr_id']);
            if ($next_ins) {
                //Override original item:
                $k_url = '/my/actionplan/' . $next_ins[0]['tr_tr_parent_id'] . '/' . $next_ins[0]['in_id'];

                if (intval($_POST['is_from_messenger'])) {
                    //Also send confirmation messages via messenger:
                    $this->Matrix_model->compose_messages(array(
                        'tr_en_child_id' => $trs[0]['k_children_en_id'],
                        'tr_in_child_id' => $next_ins[0]['in_id'],
                        'tr_tr_parent_id' => $trs[0]['tr_tr_parent_id'],
                    ));
                }
            }
        }

        return fn___redirect_message($k_url, '<div class="alert alert-success" role="alert"><i class="fal fa-check-circle"></i> Successfully Saved</div>');
    }


    function reset_pass()
    {
        $data = array(
            'title' => 'Password Reset',
        );
        $this->load->view('view_shared/messenger_header', $data);
        $this->load->view('view_entities/en_pass_reset_ui');
        $this->load->view('view_shared/messenger_footer');
    }

}
