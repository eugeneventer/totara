<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010, 2011 Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage totara_sync
 */

require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/classes/source.class.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/elements/user.php');

abstract class totara_sync_source_user extends totara_sync_source {

    protected $fields;
    protected $customfields;
    protected $element;

    abstract function import_data($temptable);

    function __construct() {
        global $DB;

        parent::__construct();

        $this->fields = array(
            'idnumber',
            'timemodified',
            'username',
            'delete',
            'firstname',
            'lastname',
            'email',
            'city',
            'country',
            'timezone',
            'lang',
            'description',
            'url',
            'institution',
            'department',
            'phone1',
            'phone2',
            'address',
            'orgidnumber',
            'postitle',
            'posidnumber',
            'manageridnumber',
            'auth',
            'password',
        );

        // Custom fields
        $this->customfields = array();
        $cfields = $DB->get_records('user_info_field');
        foreach ($cfields as $cf) {
            $this->customfields['customfield_'.$cf->shortname] = $cf->name;
        }

        $this->element = new totara_sync_element_user();
    }

    function get_element_name() {
        return 'user';
    }

    /**
     * Override in child classes
     */
    function uses_files() {
        return true;
    }

    /**
     * Override in child classes
     */
    function get_filepath() {}

    function has_config() {
        return true;
    }

    function config_form(&$mform) {
        // Fields to import
        $mform->addElement('header', 'importheader', get_string('importfields', 'tool_totara_sync'));

        foreach ($this->fields as $f) {
            if (in_array($f, array('idnumber', 'username', 'timemodified'))) {
                $mform->addElement('hidden', 'import_'.$f, '1');
            } elseif ($f == 'delete') {
                $mform->addElement('hidden', 'import_'.$f, empty($this->element->config->sourceallrecords));
            } else {
                $mform->addElement('checkbox', 'import_'.$f, get_string($f, 'tool_totara_sync'));
            }
        }
        foreach ($this->customfields as $field => $name) {
            $mform->addElement('checkbox', 'import_'.$field, $name);
        }

        // Field mappings
        $mform->addElement('header', 'mappingshdr', get_string('fieldmappings', 'tool_totara_sync'));

        foreach ($this->fields as $f) {
            $mform->addElement('text', 'fieldmapping_'.$f, $f);
            $mform->setType('fieldmapping_'.$f, PARAM_TEXT);
        }
    }

    function config_save($data) {
        foreach ($this->fields as $f) {
            $this->set_config('import_'.$f, !empty($data->{'import_'.$f}));
        }
        foreach (array_keys($this->customfields) as $f) {
            $this->set_config('import_'.$f, !empty($data->{'import_'.$f}));
        }
        foreach ($this->fields as $f) {
            $this->set_config('fieldmapping_'.$f, $data->{'fieldmapping_'.$f});
        }
    }

    function get_sync_table() {

        if (!$temptable = $this->prepare_temp_table()) {
            $this->addlog(get_string('temptableprepfail', 'tool_totara_sync'), 'error', 'importdata');
            return false;
        }
        if (!$this->import_data($temptable)) {
            $this->addlog(get_string('dataimportaborted', 'tool_totara_sync'), 'error', 'importdata');
            return false;
        }

        return $temptable;
    }

    function prepare_temp_table() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/lib/ddllib.php');

        /// Instantiate table
        $this->temptable = 'totara_sync_user';
        $dbman = $DB->get_manager();
        $table = new xmldb_table($this->temptable);

        /// Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, null, null);
        if (!empty($this->config->import_delete)) {
            $table->add_field('delete', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0');
        }
        if (!empty($this->config->import_firstname)) {
            $table->add_field('firstname', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_lastname)) {
            $table->add_field('lastname', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_email)) {
            $table->add_field('email', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_city)) {
            $table->add_field('city', XMLDB_TYPE_CHAR, '20', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_country)) {
            $table->add_field('country', XMLDB_TYPE_CHAR, '2', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_timezone)) {
            $table->add_field('timezone', XMLDB_TYPE_CHAR, '100', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_lang)) {
            $table->add_field('lang', XMLDB_TYPE_CHAR, '30', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_description)) {
            $table->add_field('description', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_url)) {
            $table->add_field('url', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_institution)) {
            $table->add_field('institution', XMLDB_TYPE_CHAR, '40', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_department)) {
            $table->add_field('department', XMLDB_TYPE_CHAR, '30', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_phone1)) {
            $table->add_field('phone1', XMLDB_TYPE_CHAR, '20', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_phone2)) {
            $table->add_field('phone2', XMLDB_TYPE_CHAR, '20', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_address)) {
            $table->add_field('address', XMLDB_TYPE_CHAR, '70', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_orgidnumber)) {
            $table->add_field('orgidnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_postitle)) {
            $table->add_field('postitle', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_posidnumber)) {
            $table->add_field('posidnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_manageridnumber)) {
            $table->add_field('manageridnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_auth)) {
            $table->add_field('auth', XMLDB_TYPE_CHAR, '20', null, null, null, null, null, null);
        }
        if (!empty($this->config->import_password)) {
            $table->add_field('password', XMLDB_TYPE_CHAR, '32', null, null, null, null, null, null);
        }
        $table->add_field('customfields', XMLDB_TYPE_TEXT, 'big', null, null, null, null, null, null);

        /// Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Add indexes
        $table->add_index('username', XMLDB_INDEX_NOTUNIQUE, array('username'));
        $table->add_index('idnumber', XMLDB_INDEX_NOTUNIQUE, array('idnumber'));
        if (!empty($this->config->import_delete)) {
            $table->add_index('delete', XMLDB_INDEX_NOTUNIQUE, array('delete'));
        }
        if (!empty($this->config->import_email)) {
            $table->add_index('email', XMLDB_INDEX_NOTUNIQUE, array('email'));
        }
        if (!empty($this->config->import_orgidnumber)) {
            $table->add_index('orgidnumber', XMLDB_INDEX_NOTUNIQUE, array('orgidnumber'));
        }

        if (!empty($this->config->import_posidnumber)) {
            $table->add_index('posidnumber', XMLDB_INDEX_NOTUNIQUE, array('posidnumber'));
        }
        if (!empty($this->config->import_manageridnumber)) {
            $table->add_index('manageridnumber', XMLDB_INDEX_NOTUNIQUE, array('manageridnumber'));
        }

        /// Create and truncate the table
        $dbman->create_temp_table($table, false, false);
        $DB->execute("TRUNCATE {{$this->temptable}}");

        return $this->temptable;
    }
}