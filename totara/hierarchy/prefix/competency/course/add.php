<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 - 2013 Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage totara_hierarchy
 */

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_hierarchy.class.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');


///
/// Setup / loading data
///

// Course id
$id = required_param('id', PARAM_INT);

// Parent id
$parentid = optional_param('parentid', 0, PARAM_INT);

// Framework id
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);

// Only return generated tree html
$treeonly = optional_param('treeonly', false, PARAM_BOOL);

// should we show hidden frameworks?
$showhidden = optional_param('showhidden', false, PARAM_BOOL);

// check they have permissions on hidden frameworks in case parameter is changed manually
$context = context_system::instance();
if ($showhidden && !has_capability('totara/hierarchy:updatecompetencyframeworks', $context)) {
    print_error('nopermviewhiddenframeworks', 'totara_hierarchy');
}

// No javascript parameters
$nojs = optional_param('nojs', false, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$s = optional_param('s', '', PARAM_TEXT);

// string of params needed in non-js url strings
$urlparams = array(
               'id'          => $id,
               'frameworkid' => $frameworkid,
               'nojs'        => $nojs,
               'returnurl'   => $returnurl,
               's'           => $s
           );

if (empty($CFG->competencyuseresourcelevelevidence)) {
    $hierarchy = new competency();
    $selected = $hierarchy->get_course_evidence($id);
}

// Setup page
admin_externalpage_setup('competencymanage', '', array(), '/totara/hierarchy/prefix/competency/course/add.php');

///
/// Display page
///

if (!$nojs) {
    // Load dialog content generator
    $dialog = new totara_dialog_content_hierarchy_multi('competency', $frameworkid, $showhidden);

    // Override error message
    $dialog->string_nothingtodisplay = 'competencyerror:dialognotreeitems';

    // Toggle treeview only display
    $dialog->show_treeview_only = $treeonly;

    // Load items to display
    $dialog->load_items($parentid);

    if (empty($CFG->competencyuseresourcelevelevidence)) {
        // Set disabled/selected items
        $dialog->selected_items = $selected;
    } else {
        // Set selected id
        $dialog->selected_id = 'available-evidence';
    }

    // Selected title
    $dialog->selected_title = 'itemstoadd';
    // Addition url parameters
    $dialog->urlparams = array('id' => $id);
    // Display
    echo $dialog->generate_markup();


} else {
    // Check permissions
    $sitecontext = context_system::instance();
    require_capability('totara/hierarchy:updatecompetency', $sitecontext);

    // Setup hierarchy object
    $hierarchy = new competency();

    // If parentid, load correct framework
    if ($parentid) {
        $parent = $hierarchy->get_item($parentid);
        $frameworkid = $parent->frameworkid;
    }

    // Load framework
    if (!$framework = $hierarchy->get_framework($frameworkid, $showhidden)) {
        print_error('competencyframeworknotfound', 'totara_hierarchy');
    }

    // Load competencies to display
    $competencies = $hierarchy->get_items_by_parent($parentid);

    // non JS version of page
    echo $OUTPUT->header();
    $out = html_writer::tag('h2', get_string('assigncompetency', 'totara_hierarchy'));
    $link = html_writer::link($returnurl, get_string('cancelwithoutassigning','totara_hierarchy'));
    $out .= html_writer::tag('p', $link);

    if (empty($frameworkid) || $frameworkid == 0) {

        $out .= build_nojs_frameworkpicker(
            $hierarchy,
            '/totara/hierarchy/prefix/competency/course/add.php',
            array(
                'returnurl' => $returnurl,
                's' => $s,
                'nojs' => 1,
                'id' => $id,
                'frameworkid' => $frameworkid,
            )
        );

    } else {
        $out .= html_writer::start_tag('div', array('id' => 'nojsinstructions'));
        $out .= build_nojs_breadcrumbs($hierarchy,
            $parentid,
            '/totara/hierarchy/prefix/competency/course/add.php',
            array(
                'id' => $id,
                'returnurl' => $returnurl,
                's' => $s,
                'nojs' => $nojs,
                'frameworkid' => $frameworkid,
            )
        );
        $out .= html_writer::tag('p', get_string('clicktoassign', 'totara_hierarchy') . ' ' . get_string('clicktoviewchildren', 'totara_hierarchy'));
        $out .= html_writer::end_tag('div');

        $out .= html_writer::start_tag('div', array('class' => 'nojsselect'));

        $out .= build_nojs_treeview(
            $competencies,
            get_string('nochildcompetenciesfound', 'totara_hierarchy'),
            '/totara/hierarchy/prefix/competency/course/evidence.php',
            array(
                's' => $s,
                'returnurl' => $returnurl,
                'nojs' => 1,
                'frameworkid' => $frameworkid,
                'id' => $id,
            ),
            '/totara/hierarchy/prefix/competency/course/add.php',
            $urlparams,
            $hierarchy->get_all_parents()
        );
        $out .= html_writer::end_tag('div');
    }
    echo $out;
    echo $OUTPUT->footer();
}
