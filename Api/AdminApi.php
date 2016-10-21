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

use ModUtil;
use SecurityUtil;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\ProfileModule\Entity\PropertyEntity;

/**
 * Administration-related api.
 */
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
     * @param array $args All parameters passed to this function
     *
     * @throws AccessDeniedException     on failed permission check
     * @throws \InvalidArgumentException if arguments are empty or not set as expected
     *
     * @return bool|int dud item ID on success, false on failure
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
        if (!SecurityUtil::checkPermission('ZikulaProfileModule::item', "{$args['label']}::", ACCESS_ADD)) {
            throw new AccessDeniedException();
        }

        // Check if the label already exists
        $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['proplabel' => $args['label']]);
        if ($item) {
            throw new \Exception($this->__('Error! There is already an item with the label \'%s\'.', DataUtil::formatForDisplay($args['label'])));
        }

        // Check if the attribute name already exists
        $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propattribute' => $args['attribute_name']]);
        if ($item) {
            throw new \Exception($this->__('Error! There is already an item with the attribute name \'%s\'.', DataUtil::formatForDisplay($args['attribute_name'])));
        }

        // Determine the new weight
        $weightLimits = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getweightlimits');
        $weight = $weightLimits['max'] + 1;
        // a checkbox can't be required
        if ($args['displaytype'] == 2 && $args['required']) {
            $args['required'] = 0;
        }
        // produce the validation array
        $args['listoptions'] = str_replace(chr(10), '', str_replace(chr(13), '', $args['listoptions']));

        $validationinfo = [
            'required'    => $args['required'],
            'viewby'      => $args['viewby'],
            'displaytype' => $args['displaytype'],
            'listoptions' => $args['listoptions'],
            'note'        => $args['note'],
            'fieldset'    => ((isset($args['fieldset']) && !empty($args['fieldset'])) ? $args['fieldset'] : $this->__('User Information')),
            'pattern'     => ((isset($args['pattern']) && !empty($args['pattern'])) ? $args['pattern'] : null),
        ];

        $obj = [];
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
     * @param array $args All parameters passed to this function
     *
     * @throws AccessDeniedException     on failed permission check
     * @throws \InvalidArgumentException if arguments are empty or not set as expected
     *
     * @return bool True on success, false on failure
     */
    public function update($args)
    {
        // Argument check
        if ((!isset($args['label'])) || (!isset($args['dudid'])) || (!is_numeric($args['dudid']))) {
            throw new \InvalidArgumentException();
        }

        // The user API function is called.
        $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propid' => $args['dudid']]);
        if ($item == false) {
            throw new \Exception($this->__('Error! No such item found.'));
        }

        // Security check
        if (!SecurityUtil::checkPermission('ZikulaProfileModule::Item', "{$item['prop_label']}::{$args['dudid']}", ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }
        if (!SecurityUtil::checkPermission('ZikulaProfileModule::Item', "{$args['label']}::{$args['dudid']}", ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }

        $propertyRepository = $this->entityManager->getRepository('ZikulaProfileModule:PropertyEntity');

        // If there's a new label, check if it already exists
        //@todo The check needs to occur for both the label and fieldset.
        if ($args['label'] != $item['prop_label']) {
            $vitem = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['proplabel' => $args['label']]);
            if ($vitem) {
                throw new \Exception($this->__('Error! There is already an item with the label \'%s\'.', DataUtil::formatForDisplay($args['label'])));
            }
        }

        if (isset($args['prop_weight'])) {
            if ($args['prop_weight'] == 0) {
                unset($args['prop_weight']);
            } elseif ($args['prop_weight'] != $item['prop_weight']) {
                /** @var $property \Zikula\ProfileModule\Entity\PropertyEntity */
                $property = $propertyRepository->findOneBy(['prop_weight' => $args['prop_weight']]);
                $property->setProp_weight($item['prop_weight']);
                $this->entityManager->flush($property);
            }
        }
        // create the object to update
        $obj = [];
        $obj['prop_dtype'] = isset($args['dtype']) ? $args['dtype'] : $item['prop_dtype'];
        $obj['prop_weight'] = isset($args['prop_weight']) ? $args['prop_weight'] : $item['prop_weight'];

        // assumes if displaytype is set, all the validation info is
        if (isset($args['displaytype'])) {
            // a checkbox can't be required
            if ($args['displaytype'] == 2 && $args['required']) {
                $args['required'] = 0;
            }
            // Produce the validation array
            $args['listoptions'] = str_replace(chr(10), '', str_replace(chr(13), '', $args['listoptions']));
            $validationinfo = [
                'required'    => $args['required'],
                'viewby'      => $args['viewby'],
                'displaytype' => $args['displaytype'],
                'listoptions' => $args['listoptions'],
                'note'        => $args['note'],
                'fieldset'    => ((isset($args['fieldset']) && !empty($args['fieldset'])) ? $args['fieldset'] : $this->__('User Information')),
                'pattern'     => ((isset($args['pattern']) && !empty($args['pattern'])) ? $args['pattern'] : null),
            ];

            $obj['prop_validation'] = serialize($validationinfo);
        }

        // let to modify the label for normal fields only
        if ($item['prop_dtype'] == 1) {
            $obj['prop_label'] = $args['label'];
        }

        // before update it search for option ID change
        // to update the respective user's data
        if ($obj['prop_validation'] != $item['prop_validation']) {
            ModUtil::apiFunc('ZikulaProfileModule', 'dud', 'updatedata', ['item' => $item['prop_validation'], 'newitem' => $obj['prop_validation']]);
        }

        /** @var $property \Zikula\ProfileModule\Entity\PropertyEntity */
        $property = $propertyRepository->find($args['dudid']);
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
     * @param array $args All parameters passed to this function
     *
     * @throws AccessDeniedException     on failed permission check
     * @throws \InvalidArgumentException if arguments are empty or not set as expected
     *
     * @return bool true on success, false on failure
     */
    public function delete($args)
    {
        // Argument check
        if (!isset($args['dudid']) || !is_numeric($args['dudid'])) {
            throw new \InvalidArgumentException();
        }
        $dudid = $args['dudid'];
        unset($args);

        $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propid' => $dudid]);
        if (false === $item) {
            throw new \Exception($this->__('Error! No such item found.'));
        }

        // normal type validation
        if ((int) $item['prop_dtype'] != 1) {
            throw new \Exception($this->__('Error! You cannot delete this item.'));
        }

        // Security check
        if (!SecurityUtil::checkPermission('ZikulaProfileModule::Item', "{$item['prop_label']}::{$dudid}", ACCESS_DELETE)) {
            throw new AccessDeniedException();
        }

        // delete the property data aka attributes
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Zikula\\UsersModule\\Entity\\UserAttributeEntity', 'a')
            ->where('a.name = :name')
            ->setParameter('name', $item['prop_attribute_name']);
        $qb->getQuery()->execute();

        $propertyRepository = $this->entityManager->getRepository('ZikulaProfileModule:PropertyEntity');
        $propertyRepository->deleteProperty($dudid);

        return true;
    }

    /**
     * Activate a dynamic user data item.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * int dudid The id of the item to be activated.
     *
     * @param array $args All parameters passed to this function
     *
     * @throws \InvalidArgumentException if arguments are empty or not set as expected
     *
     * @return bool true on success, false on failure
     *
     * @todo remove weight; can be got from get API
     */
    public function activate($args)
    {
        // Argument check
        if (!isset($args['dudid']) || !is_numeric($args['dudid'])) {
            throw new \InvalidArgumentException();
        }

        $weightLimits = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getweightlimits');
        $propertyRepository = $this->entityManager->getRepository('ZikulaProfileModule:PropertyEntity');
        $propertyRepository->activateProperty($args['dudid'], $weightLimits['max'] + 1);

        return true;
    }

    /**
     * Deactivate a dynamic user data item.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * int dudid The id of the item to be deactivated.
     *
     * @param array $args All parameters passed to this function
     *
     * @throws \InvalidArgumentException if arguments are empty or not set as expected
     *
     * @return bool true on success, false on failure
     *
     * @todo remove weight; can be got from get API
     */
    public function deactivate($args)
    {
        // Argument check
        if (!isset($args['dudid']) || !is_numeric($args['dudid'])) {
            throw new \InvalidArgumentException();
        }

        $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propid' => $args['dudid']]);
        if (false === $item) {
            throw new \Exception($this->__('Error! No such item found.'));
        }

        // type validation
        if ($item['prop_dtype'] < 1) {
            throw new \Exception($this->__('Error! You cannot deactivate this item.'));
        }

        /*
         * Return TRUE, if the item is already deactivated.
         */
        if ($item['prop_weight'] == 0) {
            return true;
        }

        $propertyRepository = $this->entityManager->getRepository('ZikulaProfileModule:PropertyEntity');
        $propertyRepository->deactivateProperty($args['dudid'], $item['prop_weight']);

        return true;
    }
}
