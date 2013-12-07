<?php/**
 * Copyright Zikula Foundation 2009 - Profile module for Zikula
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/GPLv3 (or at your option, any later version).
 * @package Profile
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */
use Profile_Entity_Property as PropertyEntity;

/**
 * API functions related to dynamic user data field management.
 */

namespace Zikula\Module\ProfileModule\Api;

use LogUtil;
use SecurityUtil;
use DataUtil;
use ModUtil;
use System;
use PropertyEntity;

class DudApi extends \Zikula_AbstractApi
{
    /**
     * Register a dynamic user data field.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * string  modname        Responsible module of the new field.
     * string  label          Label for the new account property.
     * string  attribute_name Name of the attribute to use inside the user's data.
     * string  dtype          Dud type to create {normal, mandatory, noneditable}.
     * array   validationinfo Validation info for the new field with the following fields:
     *                   'required'    => {0: no, 1: mandatory}
     *                   'viewby'      => viewable by {0: Everyone, 1: Registered users only, 2: Admins only}
     *                   'displaytype' => {0: text box, 1: textarea, 2: checkbox, 3: radio, 4: select, 5: date, 7: multi checkbox}
     *                   'listoptions' => options for the new field
     *                   'note'        => note to show in edit mode
     *					 'fieldset' => The fieldset to group the item.
     *                   and any other required data.
     * 
     * @param array $args All parameters passed to this function.
     * 
     * @return boolean True on success or false on failure.
     */
    public function register($args)
    {
        if (!isset($args['modname']) || empty($args['modname']) || !isset($args['label']) || empty($args['label']) || !isset($args['attribute_name']) || empty($args['attribute_name']) || !isset($args['dtype']) || empty($args['dtype']) || !isset($args['displaytype']) || !is_numeric($args['displaytype']) || (int) $args['displaytype'] < 0 || !isset($args['validationinfo']) || empty($args['validationinfo']) || !is_array($args['validationinfo'])) {
            return LogUtil::registerArgsError();
        }
        // Security check
        if (!SecurityUtil::checkPermission('Profile::item', "{$args['label']}::", ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }
        if (!ModUtil::getIdFromName($args['modname'])) {
            return LogUtil::registerError($this->__f('Error! Could not find the specified module (%s).', DataUtil::formatForDisplay($args['modname'])));
        }
        // parses the DUD type
        $dtypes = array(-1 => 'noneditable', 0 => 'mandatory', 2 => 'normal');
        if (!in_array($args['dtype'], $dtypes)) {
            return LogUtil::registerError($this->__f('Error! Invalid \'%s\' passed.', 'dtype'));
        }
        // Clean the label
        $permsep = System::getVar('shorturlsseparator', '-');
        $args['label'] = str_replace($permsep, '', DataUtil::formatPermalink($args['label']));
        $args['label'] = str_replace('-', '', DataUtil::formatPermalink($args['label']));
        // Check if the label or attribute name already exists
        $item = ModUtil::apiFunc('Profile', 'user', 'get', array('proplabel' => $args['label']));
        if ($item) {
            return LogUtil::registerError($this->__('Error! There is already an personal info item with the label \'%s\'.', DataUtil::formatForDisplay($args['label'])));
        }
        $item = ModUtil::apiFunc('Profile', 'user', 'get', array('propattribute' => $args['attribute_name']));
        if ($item) {
            return LogUtil::registerError($this->__('Error! There is already an personal info item with the attribute name \'%s\'.', DataUtil::formatForDisplay($args['attribute_name'])));
        }
        // Determine the new weight
        $weightlimits = ModUtil::apiFunc('Profile', 'user', 'getweightlimits');
        $weight = $weightlimits['max'] + 1;
        // insert the new field
        $obj = array();
        $obj['prop_label'] = $args['label'];
        $obj['prop_attribute_name'] = $args['attribute_name'];
        $obj['prop_dtype'] = array_search($args['dtype'], $dtypes);
        $obj['prop_modname'] = $args['modname'];
        $obj['prop_weight'] = $weight;
        $obj['prop_validation'] = serialize($args['validationinfo']);
        $prop = new PropertyEntity();
        $prop->merge($obj);
        $this->entityManager->persist($prop);
        $this->entityManager->flush();
        // Return the id of the newly created item to the calling process
        return $prop->getProp_id();
    }
    
    /**
     * Unregister a specific dynamic user data item.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * integer  propid        Id of property to unregister; required if proplabel and propattribute are not specified, must not be present if either is specified.
     * string   proplabel     Label of property to unregister; required if propid and propattribute are not specified, ignored if propid specified, must not be present if propattribute specified.
     * string   propattribute Attribute name(?) of property to unregister; required if propid and proplabel are not specified, ignored if propid or proplable specified.
     * 
     * @param array $args All parameters passed to this function.
     * 
     * @return boolean True on success or false on failure.
     */
    public function unregister($args)
    {
        // Argument check
        if (!isset($args['propid']) && !isset($args['proplabel']) && !isset($args['propattribute'])) {
            return LogUtil::registerArgsError();
        }
        // Get item with where clause
        /** @var $item Profile_Entity_Property */
        if (isset($args['propid'])) {
            $item = $this->entityManager->getRepository('Profile_Entity_Property')->find((int) $args['propid']);
        } elseif (isset($args['proplabel'])) {
            $item = $this->entityManager->getRepository('Profile_Entity_Property')->findOneBy(array('prop_label' => $args['proplabel']));
        } else {
            $item = $this->entityManager->getRepository('Profile_Entity_Property')->findOneBy(array('prop_attribute_name' => $args['propattribute']));
        }
        // Check for no rows found, and if so return
        if (!$item) {
            return false;
        }
        // Security check
        if (!SecurityUtil::checkPermission('Profile::', $item->getProp_label() . '::' . $item->getProp_id(), ACCESS_READ)) {
            return false;
        }
        // delete the property data aka attributes
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Zikula\\Module\\UsersModule\\Entity\\UserAttributeEntity', 'a')->where('a.name = :name')->setParameter('name', $item['prop_attribute_name']);
        $qb->getQuery()->execute();
        // delete the property
        $qb->delete('Profile_Entity_Property', 'p')->where('p.prop_id = :id')->setParameter('id', $item['prop_id']);
        $qb->getQuery()->execute();
        // Let the calling process know that we have finished successfully
        return true;
    }
    
    /**
     * Update users data
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * string   field     Serialized 'prop_validation' field of the DUD.
     * array    item      Array with the DUD information.
     * string   newfield  Serialized new 'prop_validation' field of the DUD.
     * array    newitem   Array with the new DUD information.
     * string   uservalue Current user value.
     * 
     * @param array $args All parameters passed to this function.
     * 
     * @return string Updated user value if there were id changes in the listoptions.
     */
    public function updatedata($args)
    {
        if (!isset($args['uservalue']) || empty($args['uservalue'])) {
            return '';
        }
        $uservalue = $args['uservalue'];
        if ((!isset($args['field']) || empty($args['field'])) && (!isset($args['item']) || empty($args['item']))) {
            return $uservalue;
        }
        // get both option arrays
        $oldoptions = $this->getoptions($args);
        $params = array('field' => isset($args['newfield']) ? $args['newfield'] : null, 'item' => isset($args['newitem']) ? $args['newitem'] : null);
        $newoptions = $this->getoptions($params);
        unset($params);
        unset($args);
        // get the old value(s)
        $value = $uservalue;
        if (is_array($uservalue)) {
            $value = array();
            foreach ($uservalue as $v) {
                // paranoic check
                if (empty($v)) {
                    $value[] = $v;
                    continue;
                }
                $value[] = isset($oldoptions[$v]) ? $oldoptions[$v] : $v;
            }
        } elseif (!empty($value) && isset($oldoptions[$value])) {
            $value = !empty($oldoptions[$value]) ? $oldoptions[$value] : $value;
        }
        // do not touch it if we do not get values
        if (empty($value)) {
            return $uservalue;
        }
        // flip the new options to have the new values as indexes
        // this required to have different labels in the listoptions
        $newoptions = array_flip($newoptions);
        $newvalue = '';
        if ($value) {
            $newvalue = array();
            foreach ($value as $v) {
                // paranoic check
                if (empty($v)) {
                    $value[] = $v;
                    continue;
                }
                $newvalue[] = isset($newoptions[$v]) ? $newoptions[$v] : $v;
            }
        } elseif (isset($newoptions[$value])) {
            $newvalue = !empty($newoptions[$value]) ? $newoptions[$value] : $value;
        }
        // return the updated item
        return $newvalue;
    }
    
    /**
     * Get the options of a DUD field.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * string field Serialized 'prop_validation' field of the DUD.
     * array  item  Array with the DUD information.
     * 
     * @param array $args All parameters passed to this function.
     * 
     * @return array Indexed id => label for the DUD field.
     */
    public function getoptions($args)
    {
        if ((!isset($args['field']) || empty($args['field'])) && (!isset($args['item']) || empty($args['item']))) {
            return array();
        }
        if (isset($args['field'])) {
            $args['field'] = @unserialize($args['field']);
            $args['item'] = array();
            foreach ($args['field'] as $k => $v) {
                $args['item']["prop_{$k}"] = $v;
            }
        }
        $item = $args['item'];
        unset($args);
        $options = array();
        switch ($item['prop_displaytype']) {
            case 3:
                // RADIO
                // extract the options
                $prop_listoptions = explode('@@', $item['prop_listoptions']);
                $list = array_splice($prop_listoptions, 1);
                // translate them if needed
                foreach ($list as $id => $value) {
                    $value = explode('@', $value);
                    $id = isset($value[1]) ? $value[1] : $id;
                    $options[$id] = !empty($value[0]) ? $this->__($value[0]) : '';
                }
                break;
            case 4:
                // SELECT
                $list = explode('@@', $item['prop_listoptions']);
                $list = array_splice($list, 1);
                // translate them if needed
                foreach ($list as $id => $value) {
                    $value = explode('@', $value);
                    $id = isset($value[1]) ? $value[1] : $id;
                    $options[$id] = !empty($value[0]) ? $this->__($value[0]) : '';
                }
                break;
            case 5:
            // DATE
            // Falls through to case 6 on purpose.
            case 6:
                // EXTDATE (deprecated)
                $options = $item['prop_listoptions'];
                // validate the option against core and %strftime options
                $coreformats = array('datelong', 'datebrief', 'datestring', 'datestring2', 'datetimebrief', 'datetimelong', 'timebrief', 'timelong');
                if (empty($options) || !in_array($options, $coreformats)) {
                    // check if it's a custom format and translate it
                    if (!empty($options) && strpos($options, '%') !== false) {
                        $options = $this->__($options);
                    } else {
                        //! This is from the core domain (datebrief)
                        $options = $this->__('%b %d, %Y');
                    }
                }
                break;
            case 7:
                // MULTICHECKBOX
                $combos = explode(';', $item['prop_listoptions']);
                $combos = array_filter($combos);
                foreach ($combos as $combo) {
                    list($id, $value) = explode(',', $combo);
                    $options[$id] = !empty($value) ? $this->__($value) : '';
                }
                break;
        }
        return $options;
    }

}