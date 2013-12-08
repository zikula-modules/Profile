<?php
/**
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

/**
 * Administrative API functions for the Profile module.
 */

namespace Zikula\Module\ProfileModule\Api;

use LogUtil;
use SecurityUtil;
use ModUtil;
use DataUtil;
use Zikula\Module\ProfileModule\Entity\PropertyEntity;

class AdminApi extends \Zikula_AbstractApi
{
    /**
     * Create a new dynamic user data item.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * string label          The name of the item to be created.
     * string attribute_name The attribute name of the item to be created.
     * string dtype          The DUD type of the item to be created.
     *
     * @param array $args All parameters passed to this function.
     *
     * @return boolean|integer dud item ID on success, false on failure
     */
    public function create($args)
    {
        // Argument check
        if (!isset($args['label']) || empty($args['label'])
            || (!isset($args['attribute_name']) || empty($args['attribute_name']))
            || (!isset($args['dtype']) || !is_numeric($args['dtype']))) {
            return LogUtil::registerArgsError();
        }
        // Security check
        if (!SecurityUtil::checkPermission($this->name.'::item', "{$args['label']}::", ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }
        // Check if the label or attribute name already exists
        //@todo The check needs to occur for both the label and fieldset.
        //$item = ModUtil::apiFunc($this->name, 'user', 'get', array('proplabel' => $args['label']));
        //if ($item) {
        //    return LogUtil::registerError($this->__f("Error! There is already an item with the label '%s'.", DataUtil::formatForDisplay($args['label'])));
        //}
        $item = ModUtil::apiFunc($this->name, 'user', 'get', array('propattribute' => $args['attribute_name']));
        if ($item) {
            return LogUtil::registerError($this->__f('Error! There is already an item with the attribute name \'%s\'.', DataUtil::formatForDisplay($args['attribute_name'])));
        }
        // Determine the new weight
        $weightlimits = ModUtil::apiFunc($this->name, 'user', 'getweightlimits');
        $weight = $weightlimits['max'] + 1;
        // a checkbox can't be required
        if ($args['displaytype'] == 2 && $args['required']) {
            $args['required'] = 0;
        }
        // produce the validation array
        $args['listoptions'] = str_replace(Chr(10), '', str_replace(Chr(13), '', $args['listoptions']));
        $validationinfo = array(
            'required' => $args['required'],
            'viewby' => $args['viewby'],
            'displaytype' => $args['displaytype'],
            'listoptions' => $args['listoptions'],
            'note' => $args['note'],
            'fieldset' => (((isset($args['fieldset'])) && (!empty($args['fieldset']))) ? $args['fieldset'] : $this->__('User Information'))
        );
        $obj = array();
        $obj['prop_label'] = $args['label'];
        $obj['prop_attribute_name'] = $args['attribute_name'];
        $obj['prop_dtype'] = $args['dtype'];
        $obj['prop_weight'] = $weight;
        $obj['prop_validation'] = serialize($validationinfo);
        $prop = new PropertyEntity();
        $prop->merge($obj);
        $this->entityManager->persist($prop);
        $this->entityManager->flush();
        // Return the id of the newly created item to the calling process
        return $prop->getProp_id();
    }

    /**
     * Update a dynamic user data item.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * int    dudid The id of the item to be updated.
     * string label The name of the item to be updated.
     *
     * @param array $args All parameters passed to this function.
     *
     * @return bool True on success, false on failure.
     */
    public function update($args)
    {

        // Argument check
        if ((!isset($args['label'])) || (!isset($args['dudid'])) || (!is_numeric($args['dudid']))) {
            return LogUtil::registerArgsError();
        }
        // The user API function is called.
        $item = ModUtil::apiFunc($this->name, 'user', 'get', array('propid' => $args['dudid']));
        if ($item == false) {
            return LogUtil::registerError($this->__('Error! No such personal info item found.'));
        }
        // Security check
        if (!SecurityUtil::checkPermission($this->name.'::Item', "{$item['prop_label']}::{$args['dudid']}", ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }
        if (!SecurityUtil::checkPermission($this->name.'::Item', "{$args['label']}::{$args['dudid']}", ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }
        // If there's a new label, check if it already exists
        //@todo The check needs to occur for both the label and fieldset.
        //if ($args['label'] <> $item['prop_label']) {
        //  $vitem = ModUtil::apiFunc($this->name, 'user', 'get', array('proplabel' => $args['label']));
        //if ($vitem) {
        //  return LogUtil::registerError($this->__("Error! There is already an item with the label '%s'.", DataUtil::formatForDisplay($args['label'])));
        //}
        //}
        if (isset($args['prop_weight'])) {
            if ($args['prop_weight'] == 0) {
                unset($args['prop_weight']);
            } elseif ($args['prop_weight'] != $item['prop_weight']) {
                /** @var $property \Zikula\Module\ProfileModule\Entity\PropertyEntity */
                $property = $this->entityManager->getRepository('Zikula\Module\ProfileModule\Entity\PropertyEntity')->findOneBy(array('prop_weight' => $args['prop_weight']));
                $property->setProp_weight($item['prop_weight']);
                $this->entityManager->flush($property);
            }
        }
        // create the object to update
        $obj = array();
        $obj['prop_dtype'] = isset($args['dtype']) ? $args['dtype'] : $item['prop_dtype'];
        $obj['prop_weight'] = isset($args['prop_weight']) ? $args['prop_weight'] : $item['prop_weight'];
        // assumes if displaytype is set, all the validation info is
        if (isset($args['displaytype'])) {
            // a checkbox can't be required
            if ($args['displaytype'] == 2 && $args['required']) {
                $args['required'] = 0;
            }
            // Produce the validation array
            $args['listoptions'] = str_replace(Chr(10), '', str_replace(Chr(13), '', $args['listoptions']));
            $validationinfo = array(
                'required' => $args['required'],
                'viewby' => $args['viewby'],
                'displaytype' => $args['displaytype'],
                'listoptions' => $args['listoptions'],
                'note' => $args['note'],
                'fieldset' => (((isset($args['fieldset'])) && (!empty($args['fieldset']))) ? $args['fieldset'] : $this->__('User Information'))
            );
            $obj['prop_validation'] = serialize($validationinfo);
        }
        // let to modify the label for normal fields only
        if ($item['prop_dtype'] == 1) {
            $obj['prop_label'] = $args['label'];
        }
        // before update it search for option ID change
        // to update the respective user's data
        if ($obj['prop_validation'] != $item['prop_validation']) {
            ModUtil::apiFunc($this->name, 'dud', 'updatedata', array('item' => $item['prop_validation'], 'newitem' => $obj['prop_validation']));
        }
        $property = $this->entityManager->getRepository('Zikula\Module\ProfileModule\Entity\PropertyEntity')->find($args['dudid']);
        $property->merge($obj);
        $this->entityManager->flush();
        return true;
    }

    /**
     * Delete a dynamic user data item.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * int dudid ID of the item to delete.
     *
     * @param array $args All parameters passed to this function.
     *
     * @return bool true on success, false on failure
     */
    public function delete($args)
    {
        // Argument check
        if (!isset($args['dudid']) || !is_numeric($args['dudid'])) {
            return LogUtil::registerArgsError();
        }
        $dudid = $args['dudid'];
        unset($args);
        $item = ModUtil::apiFunc($this->name, 'user', 'get', array('propid' => $dudid));
        if ($item == false) {
            return LogUtil::registerError($this->__('Error! No such personal info item found.'));
        }
        // normal type validation
        if ((int)$item['prop_dtype'] != 1) {
            return LogUtil::registerError($this->__('Error! You cannot delete this personal info item.'), 404);
        }
        // Security check
        if (!SecurityUtil::checkPermission($this->name.'::Item', "{$item['prop_label']}::{$dudid}", ACCESS_DELETE)) {
            return LogUtil::registerPermissionError();
        }
        // delete the property data aka attributes
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Zikula\\Module\\UsersModule\\Entity\\UserAttributeEntity', 'a')
            ->where('a.name = :name')
            ->setParameter('name', $item['prop_attribute_name']);
        $qb->getQuery()->execute();
        // delete the property
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Zikula\Module\ProfileModule\Entity\PropertyEntity', 'p')
            ->where('p.prop_id = :id')
            ->setParameter('id', $dudid);
        $qb->getQuery()->execute();
        return true;
    }

    /**
     * Activate a dynamic user data item.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * int dudid The id of the item to be activated.
     *
     * @param array $args All parameters passed to this function.
     *
     * @return bool true on success, false on failure
     *
     * @todo remove weight; can be got from get API
     */
    public function activate($args)
    {
        // Argument check
        if (!isset($args['dudid']) || !is_numeric($args['dudid'])) {
            return LogUtil::registerArgsError();
        }
        $weightlimits = ModUtil::apiFunc($this->name, 'user', 'getweightlimits');
        /** @var $prop \Zikula\Module\ProfileModule\Entity\PropertyEntity */
        $prop = $this->entityManager->find('Zikula\Module\ProfileModule\Entity\PropertyEntity', $args['dudid']);
        $prop->setProp_weight($weightlimits['max'] + 1);
        $this->entityManager->flush();
        return true;
    }

    /**
     * Deactivate a dynamic user data item.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * int dudid The id of the item to be deactivated.
     *
     * @param array $args All parameters passed to this function.
     *
     * @return bool true on success, false on failure.
     *
     * @todo remove weight; can be got from get API.
     */
    public function deactivate($args)
    {
        // Argument check
        if (!isset($args['dudid']) || !is_numeric($args['dudid'])) {
            return LogUtil::registerArgsError();
        }
        $item = ModUtil::apiFunc($this->name, 'user', 'get', array('propid' => $args['dudid']));
        if ($item == false) {
            return LogUtil::registerError($this->__('Error! No such personal info item found.'), 404);
        }
        // type validation
        if ($item['prop_dtype'] < 1) {
            return LogUtil::registerError($this->__('Error! You cannot deactivate this personal info item.'), 404);
        }
        // Update the item
        /** @var $prop \Zikula\Module\ProfileModule\Entity\PropertyEntity */
        $prop = $this->entityManager->find('Zikula\Module\ProfileModule\Entity\PropertyEntity', $args['dudid']);
        $prop->setProp_weight(0);
        $this->entityManager->flush();
        // Update the other items
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update('Zikula\Module\ProfileModule\Entity\PropertyEntity', 'p')
            ->set('p.prop_weight', 'p.prop_weight - 1')
            ->where('p.prop_weight > :weight')
            ->setParameter('weight', $item['prop_weight']);
        $qb->getQuery()->execute();
        return true;
    }

    /**
     * Get available admin panel links.
     *
     * @return array An array of admin links.
     */
    public function getlinks()
    {
        $links = array();
        // Add User module links
        if (SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_EDIT)) {
            $links[] = array(
                'url' => ModUtil::url($this->name, 'admin', 'view'),
                'text' => $this->__('Fields'),
                'icon' => 'list');
        }
        if (SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_ADD)) {
            $links[] = array(
                'url' => ModUtil::url($this->name, 'admin', 'newdud'),
                'text' => $this->__('Create new field'),
                'icon' => 'plus text-success');
        }
        if (SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_ADMIN)) {
            $links[] = array(
                'url' => ModUtil::url($this->name, 'admin', 'modifyconfig'),
                'text' => $this->__('Settings'),
                'icon' => 'wrench');
        }
        $links[] = array(
            'url' => ModUtil::url('ZikulaUsersModule', 'admin', 'view'),
            'text' => $this->__('Users Module'),
            'icon' => 'user',
            'links' => ModUtil::apiFunc('ZikulaUsersModule', 'admin', 'getlinks'));
        if (SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_EDIT)) {
            $links[] = array(
                'url' => ModUtil::url($this->name, 'admin', 'help'),
                'text' => $this->__('Help'),
                'icon' => 'ambulance text-danger');
        }
        return $links;
    }

}