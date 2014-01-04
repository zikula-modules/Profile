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

use DataUtil;
use ModUtil;
use SecurityUtil;
use Zikula\Module\ProfileModule\Entity\PropertyEntity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
     *
     * @throws AccessDeniedException on failed permission check
     * @throws \InvalidArgumentException if arguments are empty or not set as expected
     */
    public function create($args)
    {
        // Argument check
        if (!isset($args['label']) || empty($args['label'])
            || (!isset($args['attribute_name']) || empty($args['attribute_name']))
            || (!isset($args['dtype']) || !is_numeric($args['dtype']))) {
            throw new \InvalidArgumentException();
        }
        // Security check
        if (!SecurityUtil::checkPermission($this->name.'::item', "{$args['label']}::", ACCESS_ADD)) {
            throw new AccessDeniedException();
        }
        // Check if the label or attribute name already exists
        //@todo The check needs to occur for both the label and fieldset.
        //$item = ModUtil::apiFunc($this->name, 'user', 'get', array('proplabel' => $args['label']));
        //if ($item) {
        //    $this->request->getSession()->getFlashBag()->add('error', $this->__f("Error! There is already an item with the label '%s'.", DataUtil::formatForDisplay($args['label'])));
        //    return false;
        //}
        $item = ModUtil::apiFunc($this->name, 'user', 'get', array('propattribute' => $args['attribute_name']));
        if ($item) {
            $this->request->getSession()->getFlashBag()->add('error', $this->__f('Error! There is already an item with the attribute name \'%s\'.', DataUtil::formatForDisplay($args['attribute_name'])));
            return false;
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
     *
     * @throws AccessDeniedException on failed permission check
     * @throws \InvalidArgumentException if arguments are empty or not set as expected
     */
    public function update($args)
    {
        // Argument check
        if ((!isset($args['label'])) || (!isset($args['dudid'])) || (!is_numeric($args['dudid']))) {
            throw new \InvalidArgumentException();
        }
        // The user API function is called.
        $item = ModUtil::apiFunc($this->name, 'user', 'get', array('propid' => $args['dudid']));
        if ($item == false) {
            $this->request->getSession()->getFlashBag()->add('error', $this->__('Error! No such personal info item found.'));
            return false;
        }
        // Security check
        if (!SecurityUtil::checkPermission($this->name.'::Item', "{$item['prop_label']}::{$args['dudid']}", ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }
        if (!SecurityUtil::checkPermission($this->name.'::Item', "{$args['label']}::{$args['dudid']}", ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }
        // If there's a new label, check if it already exists
        //@todo The check needs to occur for both the label and fieldset.
        //if ($args['label'] <> $item['prop_label']) {
        //    $vitem = ModUtil::apiFunc($this->name, 'user', 'get', array('proplabel' => $args['label']));
        //if ($vitem) {
        //    $this->request->getSession()->getFlashBag()->add('error', $this->__("Error! There is already an item with the label '%s'.", DataUtil::formatForDisplay($args['label'])));
        //    return false;
        //}
        //}
        if (isset($args['prop_weight'])) {
            if ($args['prop_weight'] == 0) {
                unset($args['prop_weight']);
            } elseif ($args['prop_weight'] != $item['prop_weight']) {
                /** @var $property \Zikula\Module\ProfileModule\Entity\PropertyEntity */
                $property = $this->entityManager->getRepository('ZikulaProfileModule:PropertyEntity')->findOneBy(array('prop_weight' => $args['prop_weight']));
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
        $property = $this->entityManager->getRepository('ZikulaProfileModule:PropertyEntity')->find($args['dudid']);
        $property->merge($obj);
        $this->entityManager->flush();
        return $property->getProp_id();
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
     *
     * @throws AccessDeniedException on failed permission check
     * @throws \InvalidArgumentException if arguments are empty or not set as expected
     */
    public function delete($args)
    {
        // Argument check
        if (!isset($args['dudid']) || !is_numeric($args['dudid'])) {
            throw new \InvalidArgumentException();
        }
        $dudid = $args['dudid'];
        unset($args);
        $item = ModUtil::apiFunc($this->name, 'user', 'get', array('propid' => $dudid));
        if ($item == false) {
            $this->request->getSession()->getFlashBag()->add('error', $this->__('Error! No such personal info item found.'));
            return false;
        }
        // normal type validation
        if ((int)$item['prop_dtype'] != 1) {
            $this->request->getSession()->getFlashBag()->add('error', $this->__('Error! You cannot delete this personal info item.'));
            return false;
        }
        // Security check
        if (!SecurityUtil::checkPermission($this->name.'::Item', "{$item['prop_label']}::{$dudid}", ACCESS_DELETE)) {
            throw new AccessDeniedException(); 
        }
        // delete the property data aka attributes
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Zikula\\Module\\UsersModule\\Entity\\UserAttributeEntity', 'a')
            ->where('a.name = :name')
            ->setParameter('name', $item['prop_attribute_name']);
        $qb->getQuery()->execute();
        // delete the property
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('ZikulaProfileModule:PropertyEntity', 'p')
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
     * @throws \InvalidArgumentException if arguments are empty or not set as expected
     *
     * @todo remove weight; can be got from get API
     */
    public function activate($args)
    {
        // Argument check
        if (!isset($args['dudid']) || !is_numeric($args['dudid'])) {
            throw new \InvalidArgumentException();
        }
        $weightlimits = ModUtil::apiFunc($this->name, 'user', 'getweightlimits');
        /** @var $prop \Zikula\Module\ProfileModule\Entity\PropertyEntity */
        $prop = $this->entityManager->find('ZikulaProfileModule:PropertyEntity', $args['dudid']);
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
     * @throws \InvalidArgumentException if arguments are empty or not set as expected
     *
     * @todo remove weight; can be got from get API.
     */
    public function deactivate($args)
    {
        // Argument check
        if (!isset($args['dudid']) || !is_numeric($args['dudid'])) {
            throw new \InvalidArgumentException();
        }
        $item = ModUtil::apiFunc($this->name, 'user', 'get', array('propid' => $args['dudid']));
        if ($item == false) {
            $this->request->getSession()->getFlashBag()->add('error', $this->__('Error! No such personal info item found.'));
            return false;
        }
        // type validation
        if ($item['prop_dtype'] < 1) {
            $this->request->getSession()->getFlashBag()->add('error', $this->__('Error! You cannot deactivate this personal info item.'));
            return false;
        }
        // Update the item
        /** @var $prop \Zikula\Module\ProfileModule\Entity\PropertyEntity */
        $prop = $this->entityManager->find('ZikulaProfileModule:PropertyEntity', $args['dudid']);
        $prop->setProp_weight(0);
        $this->entityManager->flush();
        // Update the other items
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update('ZikulaProfileModule:PropertyEntity', 'p')
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
                'url' => ModUtil::url($this->name, 'admin', 'edit'),
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