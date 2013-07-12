<?php

/*
 * File: db.php
 * Encoding: UTF-8
 * @package voucher
 * 
 * @Version 1.0.0
 * @Since 11-jul-2013
 * @copyright Sebsoft.nl
 * @author Menno de Ridder <menno@sebsoft.nl>
 */

class voucher_Db
{

    /**
     * __construct() HIDE: WE'RE STATIC 
     */
    protected function __construct()
    {
        // static's only please!
    }

    static public final function GetBlockVersion()
    {
        static $currentVersion = null;
        if ($currentVersion === null)
        {
            global $DB;
            $currentVersion = $DB->get_field('block', 'version', array('name' => 'voucher'));
        }
        return $currentVersion;
    }

    /**
     * get profile field id
     * @global moodle_database $DB moodle_database
     * @param type $name field shortname
     * @return mixed
     * @throws Exception if not found
     */
    static public final function GetProfileFieldId($name)
    {
        if (isset(self::$_profileFieldIds[$name]))
        {
            return self::$_profileFieldIds[$name];
        }
        global $DB;
        $field = $DB->get_field('user_info_field', 'id', array('shortname' => $name), MUST_EXIST);
        self::$_profileFieldIds[$name] = $field;
        return self::$_profileFieldIds[$name];
    }

    /**
     * check whether or not a custom profile field is set for for a specified user
     * @param int $uid user id
     * @param string $field custom profile field SHORTNAME
     * @return mixed the field data
     * @throws Exception if not found
     */
    static public final function HasProfileField($uid, $field)
    {
        $fid = self::GetProfileFieldId($field);
        //
        global $DB;
        $fielddata = $DB->get_field('user_info_data', 'data', array('userid' => $uid, 'fieldid' => $fid), MUST_EXIST);
        return $fielddata;
    }

    /**
     * update a custom profile field for a user, or insert it if not yet present
     * @global moodle_database $DB
     * @param int $uid userid
     * @param string $field custom profile field name
     * @param mixed $value profile field data
     */
    static public final function UpdateProfileField($uid, $field, $value)
    {
        $fid = self::GetProfileFieldId($field);
        if ($fid <= 0)
        {
            return false;
        }
        //
        global $DB;
        $data = $DB->get_record('user_info_data', array('userid' => $uid, 'fieldid' => $fid));
        if ($data === false)
        {
            // add data, it doesn't exist
            $data = new stdClass();
            $data->fieldid = $fid;
            $data->userid = $uid;
            $data->data = $value;
            $DB->insert_record('user_info_data', $data);
        }
        else
        {
            if ($data->data != $value)
            {
                // update record
                $data->data = $value;
                $DB->update_record('user_info_data', $data);
            }
        }
    }

}