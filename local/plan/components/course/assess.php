<?php

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot . '/local/plan/lib.php');

$id = required_param('id', PARAM_INT); // plan id
$caid = required_param('itemid', PARAM_INT); // course assignment id

$plan = new development_plan($id);
$componentname = 'course';
$component = $plan->get_component($componentname);
$returnurl = $CFG->wwwroot . '/local/plan/components/course/view.php?id='.$id.'&amp;itemid='.$caid;
$currenturl = $CFG->wwwroot . '/local/plan/components/course/assess.php?id='.$id.'&amp;itemid='.$caid;

$fullname = $plan->name;
$pagetitle = format_string(get_string('developmentplan','local_plan').': '.$fullname);
$navlinks = array();
$plan->get_plan_base_navlinks($navlinks);
$navlinks[] = array('name' => $fullname, 'link'=> $CFG->wwwroot . '/local/plan/view.php?id='.$id, 'type'=>'title');
$navlinks[] = array('name' => $component->get_setting('name'), 'link' => $CFG->wwwroot . '/local/plan/components/course/index.php?id='.$id, 'type' => 'title');
$navlinks[] = array('name' => get_string('assessitem','local_plan'), 'link' => '', 'type' => 'title');

$navigation = build_navigation($navlinks);

print_header_simple($pagetitle, '', $navigation, '', null, true, '');

print $plan->display_plan_message_box();

print_heading($fullname);

print $plan->display_tabs($componentname);

print $component->display_back_to_index_link();


print_footer();


?>