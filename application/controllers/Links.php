<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Links extends CI_Controller
{

    function __construct()
    {
        parent::__construct();

        //Load our buddies:
        $this->output->enable_profiler(FALSE);
    }


    function index()
    {
        /*
         *
         * List all Links on reverse chronological order
         * and Display statuses for intents, entities and
         * links.
         *
         * */

        $session_en = en_auth(array(1308)); //Just be logged in to browse

        //Load header:
        $this->load->view(($session_en ? 'view_shared/platform_header' : 'view_shared/public_header'), array(
            'title' => 'Mench Links',
        ));

        //Load main:
        $this->load->view('view_links/links_ui');

        //Load footer:
        $this->load->view(($session_en ? 'view_shared/platform_footer' : 'view_shared/public_footer'));

    }



    function load_link_list(){

        /*
         * Loads the list of links based on the
         * filters passed on.
         *
         * */

        $filters = unserialize($_POST['link_filters']);
        $join_by = unserialize($_POST['link_join_by']);
        $message = '';

        //Fetch links and total link counts:
        $lns = $this->Database_model->ln_fetch($filters, $join_by, (is_dev() ? 20 : $this->config->item('items_per_page')));
        $lns_count = $this->Database_model->ln_fetch($filters, $join_by, 0, 0, array(), 'COUNT(ln_id) as trs_count, SUM(ln_points) as points_sum');


        //Display filter notes:
        $message .= '<p style="margin: 10px 0 0 0;">Showing '.count($lns) . ( $lns_count[0]['trs_count'] > count($lns) ? ' of '. number_format($lns_count[0]['trs_count'] , 0) : '' ) .' links with '.number_format($lns_count[0]['points_sum'], 0).' awarded points:</p>';


        if(count($lns)>0){

            $message .= '<div class="list-group list-grey">';
            foreach ($lns as $ln) {
                $message .= echo_tr_row($ln);
            }
            $message .= '</div>';

        } else {

            //Show no link warning:
            $message .= '<div class="alert alert-info" role="alert" style="margin-top: 0;"><i class="fas fa-exclamation-triangle"></i> No Links found with the selected filters. Modify filters and try again.</div>';

        }


        return echo_json(array(
            'status' => 1,
            'message' => $message,
        ));


    }


    function add_search_item(){

        //Authenticate Miner:
        $session_en = en_auth(array(1308));

        if (!$session_en) {

            return echo_json(array(
                'status' => 0,
                'message' => 'Session Expired. Sign In and try again',
            ));

        } elseif (!isset($_POST['raw_string'])) {

            return echo_json(array(
                'status' => 0,
                'message' => 'Missing Link ID',
            ));

        }

        //See if intent or entity:
        if(substr($_POST['raw_string'], 0, 1)=='#'){

            $in_outcome = trim(substr($_POST['raw_string'], 1));
            if(strlen($in_outcome)<2){
                return echo_json(array(
                    'status' => 0,
                    'message' => 'Intent outcome must be at-least 2 characters long.',
                ));
            }

            //Create Intent:
            $added_in = $this->Platform_model->in_verify_create($in_outcome, $session_en['en_id']);
            if(!$added_in['status']){
                //We had an error, return it:
                return echo_json($added_in);
            } else {
                return echo_json(array(
                    'status' => 1,
                    'new_item_url' => '/intents/' . $added_in['in']['in_id'],
                ));
            }

        } elseif(substr($_POST['raw_string'], 0, 1)=='@'){

            //Create entity:
            $added_en = $this->Platform_model->en_verify_create(trim(substr($_POST['raw_string'], 1)), $session_en['en_id']);
            if(!$added_en['status']){
                //We had an error, return it:
                return echo_json($added_en);
            } else {
                //Assign new entity:
                return echo_json(array(
                    'status' => 1,
                    'new_item_url' => '/entities/' . $added_en['en']['en_id'],
                ));
            }

        } else {
            return echo_json(array(
                'status' => 0,
                'message' => 'Invalid string. Must start with either # or @.',
            ));
        }
    }



    function link_json($ln_id)
    {

        //Fetch link metadata and display it:
        $lns = $this->Database_model->ln_fetch(array(
            'ln_id' => $ln_id,
        ));

        if (count($lns) < 1) {
            return echo_json(array(
                'status' => 0,
                'message' => 'Invalid Link ID',
            ));
        } elseif(in_array($lns[0]['ln_type_entity_id'] , $this->config->item('en_ids_4755')) /* Link Type is locked */ && !en_auth(array(1281)) /* Viewer NOT a moderator */){
            return echo_json(array(
                'status' => 0,
                'message' => 'Link content visible to moderators only',
            ));
        } elseif(!en_auth(array(1308)) /* Viewer NOT a miner */) {
            return echo_json(array(
                'status' => 0,
                'message' => 'Link metadata visible to miners only',
            ));
        } else {

            //unserialize metadata if needed:
            if(strlen($lns[0]['ln_metadata']) > 0){
                $lns[0]['ln_metadata'] = unserialize($lns[0]['ln_metadata']);
            }

            //Print on scree:
            echo_json($lns[0]);

        }
    }


    function process_assessment_score(){

        //TODO to view the student's history and issue a certificate

        //TODO Add a new link to enable the certificate to be created/issued when a min number of student's (20?) take the assessment

        //Validate the inputs:
        if(isset($_POST['recipient_en']['en_id']) && isset($_POST['actionplan_in']['in_id'])){

            $this->Chat_model->dispatch_message(
                'MENCH ASSESSMENT for intent #'.$_POST['actionplan_in']['in_id'],
                $_POST['recipient_en'],
                true,
                array(),
                array()
            );

        } else {

            $this->Chat_model->dispatch_message(
                'ERROR: MENCH ASSESSMENT missing info... '.print_r($_POST, true),
                array('en_id' => 1),
                true,
                array(),
                array()
            );

        }

    }



    function cron__sync_algolia($input_obj_type = null, $input_obj_id = null){
        //Call the update function and passon possible values:
        echo_json($this->Database_model->update_algolia($input_obj_type, $input_obj_id));
    }


    function dev__reset_points(){

        exit; //Maybe use to update all rates if needed?

        //Issue points for each link type:
        $all_engs = $this->Database_model->ln_fetch(array(), array('en_type'), 0, 0, array('trs_count' => 'DESC'), 'COUNT(ln_type_entity_id) as trs_count, en_name, ln_type_entity_id', 'ln_type_entity_id, en_name');

        //return echo_json($all_engs);

        //Give option to select:
        foreach ($all_engs as $ln) {

            //DOes it have a rate?
            $rate_trs = $this->Database_model->ln_fetch(array(
                'ln_status' => 2, //Published
                'en_status' => 2, //Published
                'ln_type_entity_id' => 4319, //Number
                'ln_parent_entity_id' => 4595, //Link Points
                'ln_child_entity_id' => $ln['ln_type_entity_id'],
            ), array('en_child'), 1);

            if(count($rate_trs) > 0){
                //Issue points at this rate:
                $this->db->query("UPDATE table_links SET ln_points = '".$rate_trs[0]['ln_content']."' WHERE ln_type_entity_id = " . $ln['ln_type_entity_id']);
            }

        }

        echo 'done';

    }

    function cron__sync_gephi(){

        /*
         *
         * Populates the nodes and edges table for
         * Gephi https://gephi.org network visualizer
         *
         * */


        //Boost processing power:
        boost_power();

        //Empty both tables:
        $this->db->query("TRUNCATE TABLE public.gephi_edges CONTINUE IDENTITY RESTRICT;");
        $this->db->query("TRUNCATE TABLE public.gephi_nodes CONTINUE IDENTITY RESTRICT;");

        //Load Intent Link Connectors:
        $en_all_4593 = $this->config->item('en_all_4593');

        //To make sure intent/entity IDs are unique:
        $id_prefix = array(
            'in' => 100,
            'en' => 200,
        );

        //Size of nodes:
        $node_size = array(
            'in' => 3,
            'en' => 2,
            'msg' => 1,
        );

        //Add intents:
        $ins = $this->Database_model->in_fetch(array('in_status >=' => 0));
        foreach($ins as $in){

            //Prep metadata:
            $in_metadata = ( strlen($in['in_metadata']) > 0 ? unserialize($in['in_metadata']) : array());

            //Add intent node:
            $this->db->insert('gephi_nodes', array(
                'id' => $id_prefix['in'].$in['in_id'],
                'label' => $in['in_outcome'],
                //'size' => ( isset($in_metadata['in__metadata_max_seconds']) ? round(($in_metadata['in__metadata_max_seconds']/3600),0) : 0 ), //Max time
                'size' => ( $in['in_id']==$this->config->item('in_mission_id') ? 3 * $node_size['in'] : $node_size['in'] ),
                'node_type' => 1, //Intent
                'node_status' => $in['in_status'],
            ));

            //Fetch children:
            foreach($this->Database_model->ln_fetch(array(
                'ln_status >=' => 0, //New+
                'in_status >=' => 0, //New+
                'ln_type_entity_id IN (' . join(',', $this->config->item('en_ids_4486')) . ')' => null, //Intent Link Connectors
                'ln_parent_intent_id' => $in['in_id'],
            ), array('in_child'), 0, 0) as $in_child){

                $this->db->insert('gephi_edges', array(
                    'source' => $id_prefix['in'].$in_child['ln_parent_intent_id'],
                    'target' => $id_prefix['in'].$in_child['ln_child_intent_id'],
                    'label' => $en_all_4593[$in_child['ln_type_entity_id']]['m_name'], //TODO maybe give visibility to points/condition here?
                    'weight' => 1, //TODO Maybe update later?
                    'edge_type_en_id' => $in_child['ln_type_entity_id'],
                    'edge_status' => $in_child['ln_status'],
                ));

            }
        }


        //Add entities:
        $ens = $this->Database_model->en_fetch(array('en_status >=' => 0));
        foreach($ens as $en){

            //Add entity node:
            $this->db->insert('gephi_nodes', array(
                'id' => $id_prefix['en'].$en['en_id'],
                'label' => $en['en_name'],
                'size' => ( $en['en_id']==$this->config->item('en_top_focus_id') ? 3 * $node_size['en'] : $node_size['en'] ),
                'node_type' => 2, //Entity
                'node_status' => $en['en_status'],
            ));

            //Fetch children:
            foreach($this->Database_model->ln_fetch(array(
                'ln_status >=' => 0, //New+
                'en_status >=' => 0, //New+
                'ln_type_entity_id IN (' . join(',', $this->config->item('en_ids_4592')) . ')' => null, //Entity Link Connectors
                'ln_parent_entity_id' => $en['en_id'],
            ), array('en_child'), 0, 0) as $en_child){

                $this->db->insert('gephi_edges', array(
                    'source' => $id_prefix['en'].$en_child['ln_parent_entity_id'],
                    'target' => $id_prefix['en'].$en_child['ln_child_entity_id'],
                    'label' => $en_all_4593[$en_child['ln_type_entity_id']]['m_name'].': '.$en_child['ln_content'],
                    'weight' => 1, //TODO Maybe update later?
                    'edge_type_en_id' => $en_child['ln_type_entity_id'],
                    'edge_status' => $en_child['ln_status'],
                ));

            }
        }

        //Add messages:
        $messages = $this->Database_model->ln_fetch(array(
            'ln_status >=' => 0, //New+
            'in_status >=' => 0, //New+
            'ln_type_entity_id IN (' . join(',', $this->config->item('en_ids_4485')) . ')' => null, //All Intent Notes
            //'ln_type_entity_id' => 4231, //Intent Messages only
        ), array('in_child'), 0, 0);
        foreach($messages as $message) {

            //Add message node:
            $this->db->insert('gephi_nodes', array(
                'id' => $message['ln_id'],
                'label' => $en_all_4593[$message['ln_type_entity_id']]['m_name'] . ': ' . $message['ln_content'],
                'size' => $node_size['msg'],
                'node_type' => $message['ln_type_entity_id'], //Message type
                'node_status' => $message['ln_status'],
            ));

            //Add child intent link:
            $this->db->insert('gephi_edges', array(
                'source' => $message['ln_id'],
                'target' => $id_prefix['in'].$message['ln_child_intent_id'],
                'label' => 'Child Intent',
                'weight' => 1, //TODO Maybe update later?
            ));

            //Add parent intent link?
            if ($message['ln_parent_intent_id'] > 0) {
                $this->db->insert('gephi_edges', array(
                    'source' => $id_prefix['in'].$message['ln_parent_intent_id'],
                    'target' => $message['ln_id'],
                    'label' => 'Parent Intent',
                    'weight' => 1, //TODO Maybe update later?
                ));
            }

            //Add parent entity link?
            if ($message['ln_parent_entity_id'] > 0) {
                $this->db->insert('gephi_edges', array(
                    'source' => $id_prefix['en'].$message['ln_parent_entity_id'],
                    'target' => $message['ln_id'],
                    'label' => 'Parent Entity',
                    'weight' => 1, //TODO Maybe update later?
                ));
            }

        }

        echo count($ins).' intents & '.count($ens).' entities & '.count($messages).' messages synced.';
    }



    function toggle_advance(){

        //Toggles the advance session variable for the miner on/off for logged-in miners:
        $session_en = en_auth(array(1308));

        if($session_en){

            //Figure out new toggle state:
            $toggled_setting = ( $this->session->userdata('advance_view_enabled')==1 ? 0 : 1 );

            //Set session variable:
            $this->session->set_userdata('advance_view_enabled', $toggled_setting);

            //Log Link:
            $this->Database_model->ln_create(array(
                'ln_miner_entity_id' => $session_en['en_id'],
                'ln_type_entity_id' => 5007, //Toggled Advance Mode
                'ln_content' => 'Toggled '.( $toggled_setting ? 'ON' : 'OFF' ), //To be used when miner logs in again
            ));

            //Return to JS function:
            return echo_json(array(
                'status' => 1,
                'message' => 'Success',
            ));

        } else {

            //Show error:
            return echo_json(array(
                'status' => 0,
                'message' => 'Session expired. Login and try again.',
            ));

        }
    }


}