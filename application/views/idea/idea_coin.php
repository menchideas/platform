
<?php
$en_all_6201 = $this->config->item('en_all_6201'); //Idea Table
$en_all_4485 = $this->config->item('en_all_4485'); //Idea Pads
$en_all_2738 = $this->config->item('en_all_2738');

$is_author = in_is_author($in['in_id']);
$is_active = in_array($in['in_status_source_id'], $this->config->item('en_ids_7356'));
?>

<style>
    .in_child_icon_<?= $in['in_id'] ?> { display:none; }
    <?= ( !$is_author ? '.pads-edit {display:none;}' : '' ) ?>
</style>


<script>
    //Include some cached sources:
    var in_loaded_id = <?= $in['in_id'] ?>;
</script>
<script src="/application/views/idea/idea_coin.js?v=v<?= config_var(11060) ?>" type="text/javascript"></script>
<script src="/application/views/idea/idea_shared.js?v=v<?= config_var(11060) ?>" type="text/javascript"></script>

<?php

$source_focus_found = false; //Used to determine the first tab to be opened


//IDEA NEXT
$in__children = $this->READ_model->ln_fetch(array(
    'ln_status_source_id IN (' . join(',', $this->config->item('en_ids_7360')) . ')' => null, //Transaction Status Active
    'in_status_source_id IN (' . join(',', $this->config->item('en_ids_7356')) . ')' => null, //Idea Status Active
    'ln_type_source_id IN (' . join(',', $this->config->item('en_ids_4486')) . ')' => null, //Idea-to-Idea Links
    'ln_previous_idea_id' => $in['in_id'],
), array('in_child'), 0, 0, array('ln_order' => 'ASC'));

//IDEA PREVIOUS
$in__parents = $this->READ_model->ln_fetch(array(
    'ln_status_source_id IN (' . join(',', $this->config->item('en_ids_7360')) . ')' => null, //Transaction Status Active
    'in_status_source_id IN (' . join(',', $this->config->item('en_ids_7356')) . ')' => null, //Idea Status Active
    'ln_type_source_id IN (' . join(',', $this->config->item('en_ids_4486')) . ')' => null, //Idea-to-Idea Links
    'ln_next_idea_id' => $in['in_id'],
), array('in_parent'), 0);




echo '<div class="container" style="padding-bottom:42px;">';



if(!$is_author){
    echo '<div class="alert alert-warning no-margin"><span class="icon-block"><i class="fad fa-exclamation-triangle"></i></span>You are not an author of this idea, yet. <a href="/idea/in_request_invite/'.$in['in_id'].'" class="inline-block montserrat">REQUEST INVITE</a><span class="inline-block '.superpower_active(12674).'">&nbsp;or <a href="/idea/in_become_author/'.$in['in_id'].'" class="montserrat">BECOME AUTHOR</a></span></div>';
}





//IDEA PREVIOUS
echo '<div id="list-in-' . $in['in_id'] . '-1" class="list-group previous_ins">';
foreach ($in__parents as $parent_in) {
    echo echo_in($parent_in, 0, true, in_is_author($parent_in['in_id']));
}
if( $is_author && $is_active && $in['in_id']!=config_var(12156)){
    echo '<div class="list-group-item itemidea '.superpower_active(10984).'" style="padding:5px 0;">
                <div class="input-group border">
                    <span class="input-group-addon addon-lean" style="margin-top: 6px;"><span class="icon-block">'.$en_all_2738[4535]['m_icon'].'</span></span>
                    <input type="text"
                           class="form-control ideaadder-level-2-parent form-control-thick algolia_search dotransparent"
                           maxlength="' . config_var(11071) . '"
                           idea-id="' . $in['in_id'] . '"
                           id="addidea-c-' . $in['in_id'] . '-1"
                           style="margin-bottom: 0; padding: 5px 0;"
                           placeholder="PREVIOUS IDEA">
                </div><div class="algolia_pad_search hidden in_pad_top"></div></div>';
}
echo '</div>';





//IDEA TITLE
echo '<div class="itemidea">';
echo echo_in_text(4736, $in['in_title'], $in['in_id'], ($is_author && $is_active), 0, true);
echo '<div class="title_counter hidden grey montserrat doupper" style="text-align: right;"><span id="charTitleNum">0</span>/'.config_var(11071).' CHARACTERS</div>';
echo '</div>';




//IDEA MESSAGES:
echo echo_idea_pad_body(4231, $this->READ_model->ln_fetch(array(
    'ln_status_source_id IN (' . join(',', $this->config->item('en_ids_7360')) . ')' => null, //Transaction Status Active
    'ln_type_source_id' => 4231,
    'ln_next_idea_id' => $in['in_id'],
), array(), 0, 0, array('ln_order' => 'ASC')), ($is_author && $is_active));



//IDEA STATUS
echo '<div class="inline-block both-margin left-margin">'.echo_in_dropdown(4737, $in['in_status_source_id'], 'btn-idea', $is_author, true, $in['in_id']).'</div>';

//IDEA TYPE
echo '<span class="inline-block both-margin left-half-margin">'.echo_in_dropdown(7585, $in['in_type_source_id'], 'btn-idea', $is_author && $is_active, true, $in['in_id']).'</span>';

//IDEA TIME
echo '<div class="inline-block both-margin left-half-margin '.superpower_active(10984).'">'.echo_in_text(4356, $in['in_read_time'], $in['in_id'], $is_author && $is_active, 0).'</div>';






//IDEA LAYOUT
$tab_group = 1;
$tab_content = '';
echo '<ul class="nav nav-tabs nav-sm">';
foreach ($this->config->item('en_all_11018') as $en_id => $m){


    //Is this a caret menu?
    if(in_array(11040 , $m['m_parents'])){
        echo echo_caret($en_id, $m, $in['in_id']);
        continue;
    }

    //Have Needed Superpowers?
    $superpower_actives = array_intersect($this->config->item('en_ids_10957'), $m['m_parents']);
    if(count($superpower_actives) && !superpower_assigned(end($superpower_actives))){
        continue;
    }



    $counter = null; //Assume no counters
    $this_tab = '';


    if($en_id==11020){

        //CHILD IDEAS
        $counter = count($in__children);

        //List child ideas:
        //$this_tab .= '<div class="read-topic"><span class="icon-block">&nbsp;</span>NEXT:</div>';
        $this_tab .= '<div id="list-in-' . $in['in_id'] . '-0" class="list-group next_ins">';
        foreach ($in__children as $child_in) {
            $this_tab .= echo_in($child_in, $in['in_id'], false, $is_author);
        }

        if($is_author && $is_active){
            $this_tab .= '<div class="list-group-item itemidea '.superpower_active(10939).'" style="padding:5px 0;">
                <div class="input-group border">
                    <span class="input-group-addon addon-lean" style="margin-top: 6px;"><span class="icon-block">'.$en_all_2738[4535]['m_icon'].'</span></span>
                    <input type="text"
                           class="form-control ideaadder-level-2-child form-control-thick algolia_search dotransparent"
                           maxlength="' . config_var(11071) . '"
                           idea-id="' . $in['in_id'] . '"
                           id="addidea-c-' . $in['in_id'] . '-0"
                           style="margin-bottom: 0; padding: 5px 0;"
                           placeholder="NEXT IDEA">
                </div><div class="algolia_pad_search hidden in_pad_bottom"></div></div>';
        }

    } elseif(in_array($en_id, $this->config->item('en_ids_4485'))){

        //IDEA PADS
        $in_pads = $this->READ_model->ln_fetch(array(
            'ln_status_source_id IN (' . join(',', $this->config->item('en_ids_7360')) . ')' => null, //Transaction Status Active
            'ln_type_source_id' => $en_id,
            'ln_next_idea_id' => $in['in_id'],
        ), array(), 0, 0, array('ln_order' => 'ASC'));

        $counter = count($in_pads);
        $this_tab .= echo_idea_pad_body($en_id, $in_pads, ($is_author && $is_active));

    } elseif(in_array($en_id, $this->config->item('en_ids_12410'))){

        //READER READS & BOOKMARKS
        $item_counters = $this->READ_model->ln_fetch(array(
            'ln_status_source_id IN (' . join(',', $this->config->item('en_ids_7359')) . ')' => null, //Transaction Status Public
            'ln_type_source_id IN (' . join(',', $this->config->item('en_ids_'.$en_id)) . ')' => null,
            'ln_previous_idea_id' => $in['in_id'],
        ), array(), 1, 0, array(), 'COUNT(ln_id) as totals');

        $counter = $item_counters[0]['totals'];

        if($counter > 0){

            //Dynamic Loading when clicked:
            $this_tab .= '<div class="dynamic-reads"></div>';

        } else {

            //Inform that nothing was found:
            $en_all_12410 = $this->config->item('en_all_12410');
            $this_tab .= '<div class="alert alert-warning"><span class="icon-block">'.$en_all_12410[$en_id]['m_icon'].'</span><span class="montserrat '.extract_icon_color($en_all_12410[$en_id]['m_icon']).'">'.$en_all_12410[$en_id]['m_name'].'</span> is not added yet.</div>';

        }

    } elseif($en_id==12589){

        //NEXT EDITOR

        $dropdown_options = '';
        $input_options = '';
        $counter = 0;

        foreach ($this->config->item('en_all_12589') as $action_en_id => $mass_action_en) {

            $counter++;
            $dropdown_options .= '<option value="' . $action_en_id . '">' .$mass_action_en['m_name'] . '</option>';
            $is_upper = false;


            //Start with the input wrapper:
            $input_options .= '<span id="mass_id_'.$action_en_id.'" title="'.$mass_action_en['m_desc'].'" class="inline-block '. ( $counter > 1 ? ' hidden ' : '' ) .' mass_action_item">';

            if(in_array($action_en_id, array(12591, 12592))){

                //Source search box:

                //String command:
                $input_options .= '<input type="text" name="mass_value1_'.$action_en_id.'"  placeholder="Search Sources..." class="form-control algolia_search en_quick_search border montserrat '.$is_upper.'">';

                //We don't need the second value field here:
                $input_options .= '<input type="hidden" name="mass_value2_'.$action_en_id.'" value="" />';

            } elseif(in_array($action_en_id, array(12611, 12612))){

                //Idea search box:

                //String command:
                $input_options .= '<input type="text" name="mass_value1_'.$action_en_id.'"  placeholder="Search Ideas..." class="form-control algolia_search in_quick_search border montserrat '.$is_upper.'">';

                //We don't need the second value field here:
                $input_options .= '<input type="hidden" name="mass_value2_'.$action_en_id.'" value="" />';

            } elseif(in_array($action_en_id, array(12611, 12612))){

                $input_options .= '<div class="alert alert-warning" role="alert"><span class="icon-block"><i class="fad fa-exclamation-triangle"></i></span>Ideas will be archived.</div>';

                //No values for this:
                $input_options .= '<input type="hidden" name="mass_value1_'.$action_en_id.'" value="" />';
                $input_options .= '<input type="hidden" name="mass_value2_'.$action_en_id.'" value="" />';

            }

            $input_options .= '</span>';

        }

        $this_tab .= '<form class="mass_modify" method="POST" action="" style="width: 100% !important; margin-left: 33px;">';
        $this_tab .= '<div class="inline-box">';

        //Drop Down
        $this_tab .= '<select class="form-control border" name="mass_action_en_id" id="set_mass_action">';
        $this_tab .= $dropdown_options;
        $this_tab .= '</select>';

        $this_tab .= $input_options;

        $this_tab .= '<div><input type="submit" value="APPLY" class="btn btn-idea inline-block"></div>';

        $this_tab .= '</div>';
        $this_tab .= '</form>';

    } else {

        //Not supported via here:
        continue;

    }

    if(!$counter && in_array($en_id, $this->config->item('en_ids_12677'))){
        //Hide since Zero:
        continue;
    }


    $default_active = in_array($en_id, $this->config->item('en_ids_12675'));

    echo '<li class="nav-item '.( count($superpower_actives) ? superpower_active(end($superpower_actives)) : '' ).'"><a class="nav-link tab-nav-'.$tab_group.' tab-head-'.$en_id.' '.( $default_active ? ' active ' : '' ).extract_icon_color($m['m_icon']).'" href="javascript:void(0);" onclick="loadtab('.$tab_group.','.$en_id.', '.$in['in_id'].', 0)" data-toggle="tooltip" data-placement="top" title="'.$m['m_name'].'">'.$m['m_icon'].( is_null($counter) ? '' : ' <span class="counter-'.$en_id.'">'.echo_number($counter).'</span>' ).'</a></li>';


    $tab_content .= '<div class="tab-content tab-group-'.$tab_group.' tab-data-'.$en_id.( $default_active ? '' : ' hidden ' ).'">';
    $tab_content .= $this_tab;
    $tab_content .= '</div>';

}
echo '</ul>';


//Show All Tab Content:
echo $tab_content;

echo '</div>';

?>