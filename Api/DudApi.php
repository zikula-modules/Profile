<?php
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Api;

use DataUtil;
use ModUtil;
use SecurityUtil;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use System;
use Zikula\ProfileModule\Entity\PropertyEntity;

/**
 * Dynamic user data field management api.
 */
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
     *                          'required' => {0: no, 1: mandatory}
     *                          'viewby' => viewable by {0: Everyone, 1: Registered users only, 2: Admins only}
     *                          'displaytype' => {0: text box, 1: textarea, 2: checkbox, 3: radio, 4: select, 5: date, 7: multi checkbox}
     *                          'listoptions' => options for the new field
     *                          'note' => note to show in edit mode
     *                          'fieldset' => The fieldset to group the item.
     *                          'pattern' => The pattern attribute specifies a regular expression that the <input> element's value is checked against.
     *
     * @param array $args All parameters passed to this function
     *
     * @throws AccessDeniedException     on failed permission check
     * @throws \InvalidArgumentException if arguments are empty or not set as expected
     *
     * @return bool True on success or false on failure
     */
    public function register($args)
    {
        if (!isset($args['modname']) || empty($args['modname'])
            || !isset($args['label']) || empty($args['label'])
            || !isset($args['attribute_name']) || empty($args['attribute_name'])
            || !isset($args['dtype']) || empty($args['dtype'])
            || !isset($args['displaytype']) || !is_numeric($args['displaytype']) || (int) $args['displaytype'] < 0
            || !isset($args['validationinfo']) || empty($args['validationinfo']) || !is_array($args['validationinfo'])
        ) {
            throw new \InvalidArgumentException();
        }
        // Security check
        if (!SecurityUtil::checkPermission('ZikulaProfileModule::item', "{$args['label']}::", ACCESS_ADD)) {
            throw new AccessDeniedException();
        }

        if (!ModUtil::getIdFromName($args['modname'])) {
            throw new \Exception($this->__f('Error! Could not find the specified module (%s).', ['%s' => DataUtil::formatForDisplay($args['modname'])]));
        }

        // parses the DUD type
        $dtypes = [
            -1 => 'noneditable',
            0  => 'mandatory',
            2  => 'normal',
        ];
        if (!in_array($args['dtype'], $dtypes)) {
            throw new \Exception($this->__f('Error! Invalid \'%s\' passed.', 'dtype'));
        }

        // Clean the label
        $permsep = System::getVar('shorturlsseparator', '-');
        $args['label'] = str_replace($permsep, '', DataUtil::formatPermalink($args['label']));
        $args['label'] = str_replace('-', '', DataUtil::formatPermalink($args['label']));

        // Check if the label already exists
        $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['proplabel' => $args['label']]);
        if ($item) {
            throw new \Exception($this->__f('Error! There is already an item with the label \'%s\'.', ['%s' => DataUtil::formatForDisplay($args['label'])]));
        }

        // Check if the attribute name already exists
        $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propattribute' => $args['attribute_name']]);
        if ($item) {
            throw new \Exception($this->__f('Error! There is already an item with the attribute name \'%s\'.', ['%s' => DataUtil::formatForDisplay($args['attribute_name'])]));
        }

        // Determine the new weight
        $weightlimits = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getweightlimits');
        $weight = $weightlimits['max'] + 1;

        // insert the new field
        $obj = [];
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
     * @param array $args All parameters passed to this function
     *
     * @throws \InvalidArgumentException if arguments are empty or not set as expected
     *
     * @return bool True on success or false on failure
     */
    public function unregister($args)
    {
        // Argument check
        if (!isset($args['propid']) && !isset($args['proplabel']) && !isset($args['propattribute'])) {
            throw new \InvalidArgumentException();
        }

        // Get item with where clause
        $propertyRepository = $this->entityManager->getRepository('ZikulaProfileModule:PropertyEntity');
        /* @var $item \Zikula\ProfileModule\Entity\PropertyEntity */
        if (isset($args['propid'])) {
            $item = $propertyRepository->find((int) $args['propid']);
        } elseif (isset($args['proplabel'])) {
            $item = $propertyRepository->findOneBy(['prop_label' => $args['proplabel']]);
        } else {
            $item = $propertyRepository->findOneBy(['prop_attribute_name' => $args['propattribute']]);
        }

        // Check for no rows found, and if so return
        if (!$item) {
            return false;
        }

        // Security check
        if (!SecurityUtil::checkPermission('ZikulaProfileModule::', $item->getProp_label().'::'.$item->getProp_id(), ACCESS_READ)) {
            return false;
        }

        // delete the property data aka attributes
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Zikula\\UsersModule\\Entity\\UserAttributeEntity', 'a')
            ->where('a.name = :name')
            ->setParameter('name', $item['prop_attribute_name']);
        $qb->getQuery()->execute();

        $propertyRepository = $this->entityManager->getRepository('ZikulaProfileModule:PropertyEntity');
        $propertyRepository->deleteProperty($item['prop_id']);

        // Let the calling process know that we have finished successfully
        return true;
    }

    /**
     * Update users data.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * string   field     Serialized 'prop_validation' field of the DUD.
     * array    item      Array with the DUD information.
     * string   newfield  Serialized new 'prop_validation' field of the DUD.
     * array    newitem   Array with the new DUD information.
     * string   uservalue Current user value.
     *
     * @param array $args All parameters passed to this function
     *
     * @return string Updated user value if there were id changes in the listoptions
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
        $params = [
            'field' => isset($args['newfield']) ? $args['newfield'] : null,
            'item'  => isset($args['newitem']) ? $args['newitem'] : null,
        ];
        $newoptions = $this->getoptions($params);
        unset($params);
        unset($args);
        // get the old value(s)
        $value = $uservalue;
        if (is_array($uservalue)) {
            $value = [];
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
            $newvalue = [];
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
     * @param array $args All parameters passed to this function
     *
     * @return array Indexed id => label for the DUD field
     */
    public function getoptions($args)
    {
        if ((!isset($args['field']) || empty($args['field'])) && (!isset($args['item']) || empty($args['item']))) {
            return [];
        }
        if (isset($args['field'])) {
            $args['field'] = @unserialize($args['field']);
            $args['item'] = [];
            foreach ($args['field'] as $k => $v) {
                $args['item']["prop_{$k}"] = $v;
            }
        }
        $item = $args['item'];
        unset($args);
        $options = [];
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
                    $options[$id] = !empty($value[0]) ? $this->__(/** @Ignore */$value[0]) : '';
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
                    $options[$id] = !empty($value[0]) ? $this->__(/** @Ignore */$value[0]) : '';
                }
                break;
            case 5:
                // DATE
                // Falls through to case 6 on purpose.
            case 6:
                // EXTDATE (deprecated)
                $options = $item['prop_listoptions'];
                break;
            case 7:
                // MULTICHECKBOX
                $combos = explode(';', $item['prop_listoptions']);
                $combos = array_filter($combos);
                foreach ($combos as $combo) {
                    list($id, $value) = explode(',', $combo);
                    $options[$id] = !empty($value) ? $this->__(/** @Ignore */$value) : '';
                }
                break;
        }

        return $options;
    }
}
