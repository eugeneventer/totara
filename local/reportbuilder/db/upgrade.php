<?php

// This file keeps track of upgrades to
// the reportbuilder module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_local_reportbuilder_upgrade($oldversion=0) {

    global $CFG, $db;

    $result = true;

    if ($result && $oldversion < 2010081901) {
        // hack to get cron working via admin/cron.php
        // at some point we should create a local_modules table
        // based on data in version.php
        set_config('local_reportbuilder_cron', 60);
    }

    if ($result && $oldversion < 2010090200) {
        if($reports = get_records_select('report_builder', 'embeddedurl IS NOT NULL')) {
            foreach($reports as $report) {
                $url = $report->embeddedurl;
                // remove the wwwroot from the url
                if($CFG->wwwroot == substr($url, 0, strlen($CFG->wwwroot))) {
                    $url = substr($url, strlen($CFG->wwwroot));
                }
                // check to fix embedded urls with wrong host
                // this should fix all historical cases as up to now all embedded reports
                // have been in the /my/ directory
                // this does nothing if '/my/' not in url or
                // url already without wwwroot
                $url = substr($url, strpos($url, '/my/'));

                // do the update if needed
                if($report->embeddedurl != $url) {
                    $todb = new object();
                    $todb->id = $report->id;
                    $todb->embeddedurl = addslashes($url);
                    $result = $result && update_record('report_builder', $todb);
                }
            }
        }
    }


    return $result;
}