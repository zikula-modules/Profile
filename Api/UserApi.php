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

use DateUtil;
use ModUtil;
use SecurityUtil;
use UserUtil;
use Zikula\Core\Event\GenericEvent;

/**
 * Operations accessible by non-administrative users.
 */
class UserApi extends \Zikula_AbstractApi
{
    /**
     * Get all Dynamic user data fields.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * int    startnum Starting record number for request; optional; default is to return items beginning with the first.
     * int    numitems Number of records to retrieve; optional; default is to return all items.
     * string index    The field to use as the array index for the returned items; one of 'prop_id', 'prop_label', or 'prop_attribute_name'; optional; default = 'prop_label'.
     *
     * @param array $args All parameters passed to this function
     *
     * @return array|bool Array of items, or false on failure
     */
    public function getall($args)
    {
        // Optional arguments.
        if (!isset($args['startnum'])) {
            $args['startnum'] = 1;
        }
        if (!isset($args['numitems'])) {
            $args['numitems'] = -1;
        }

        // Security check
        if (!SecurityUtil::checkPermission('ZikulaProfileModule::', '::', ACCESS_READ)) {
            return [];
        }

        $propertyRepository = $this->entityManager->getRepository('ZikulaProfileModule:PropertyEntity');
        $items = $propertyRepository->getAllByWeight($args['startnum'], $args['numitems']);

        // Put items into result array.
        foreach (array_keys($items) as $k) {
            if (!SecurityUtil::checkPermission('ZikulaProfileModule::', $items[$k]['prop_label'].'::'.$items[$k]['prop_id'], ACCESS_READ)) {
                unset($items[$k]);
                continue;
            }

            $validationInfo = @unserialize($items[$k]['prop_validation']);
            unset($items[$k]['prop_validation']);
            // Expand the item array
            foreach ((array) $validationInfo as $infoLabel => $infoField) {
                $items[$k]["prop_{$infoLabel}"] = $infoField;
            }
        }

        // Return the items
        return $items;
    }

    /**
     * Get a specific Dynamic user data item.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * integer propid        Id of the property to get; optional if proplabel or propattribute provided.
     * string  proplabel     Label of the property to get; optional if propid or propattribute provided; ignored if propid provided.
     * string  propattribute Attribute name of the property to get; optional if propid or proplabel provided; ignored if propid or proplabel provided.
     *
     * @param array $args All parameters passed to this function
     *
     * @throws \InvalidArgumentException if arguments are empty or not set as expected
     *
     * @return array|bool Item array, or false on failure
     */
    public function get($args)
    {
        // Argument check
        if (!isset($args['propid']) && !isset($args['proplabel']) && !isset($args['propattribute'])) {
            throw new \InvalidArgumentException();
        }

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

        // Extract the validation info array
        $validationinfo = unserialize($item->getProp_validation());
        $item = $item->toArray();

        // Expand the item array
        foreach ((array) $validationinfo as $infolabel => $infofield) {
            $item['prop_'.$infolabel] = $infofield;
        }

        // Return the item array
        return $item;
    }

    /**
     * Get all active Dynamic user data fields.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * integer startnum Starting record number for request; optional; default is to start with the first record.
     * integer numitems Number of records to retrieve; optional; default is to return all records.
     * string  index    The field to use as the array index for the returned items; one of 'prop_id', 'prop_label', or 'prop_attribute_name';
     *                      optional; default = 'prop_attribute_name'.
     * string  get      Which subset of fields to return; one of 'all', 'editable', 'viewable'; optional; default = 'all'.
     * integer uid      The user id of the user for which data fields are being retrieved, in order to filter the result based on ownership of the data;
     *                      optional; defaults to -1, which will not match any user id (i.e., all owner-only fields are not returned unless the current user
     *                      has ADMIN access).
     *
     * @param array $args All parameters passed to this function
     *
     * @return array|bool Array of items, or false on failure
     */
    public function getallactive($args)
    {
        // Optional arguments.
        if (!isset($args['startnum'])) {
            $args['startnum'] = -1;
        }
        if (!isset($args['numitems']) || $args['numitems'] <= 0) {
            $args['numitems'] = 0;
        }
        if (!isset($args['index']) || !in_array($args['index'], ['prop_id', 'prop_label', 'prop_attribute_name'])) {
            $args['index'] = 'prop_attribute_name';
        }
        if (!isset($args['get']) || !in_array($args['get'], ['editable', 'viewable', 'all'])) {
            $args['get'] = 'all';
        }
        if (!isset($args['uid']) || !is_numeric($args['uid'])) {
            $args['uid'] = 0;
        }

        // Security check
        if (!SecurityUtil::checkPermission('ZikulaProfileModule::', '::', ACCESS_READ)) {
            return [];
        }

        static $items;
        if (!isset($items)) {
            $propertyRepository = $this->entityManager->getRepository('ZikulaProfileModule:PropertyEntity');
            $items = $propertyRepository->getAllActive();

            foreach (array_keys($items) as $k) {
                if (!SecurityUtil::checkPermission('ZikulaProfileModule::', $items[$k]['prop_label'].'::'.$items[$k]['prop_id'], ACCESS_READ)) {
                    unset($items[$k]);
                    continue;
                }

                // Extract the validation info array
                $validationinfo = @unserialize($items[$k]['prop_validation']);
                unset($items[$k]['prop_validation']);
                foreach ((array) $validationinfo as $infolabel => $infofield) {
                    $items[$k]["prop_{$infolabel}"] = $infofield;
                }
            }
        }

        // process the startnum and numitems
        if ($args['numitems']) {
            $items = array_splice($items, $args['startnum'] + 1, $args['numitems']);
        } else {
            $items = array_splice($items, $args['startnum'] + 1);
        }

        // Put items into result array and filter if needed
        $currentUser = (int) UserUtil::getVar('uid');
        $isMember = $currentUser >= 2;
        $isOwner = $currentUser == (int) $args['uid'];
        $isAdmin = SecurityUtil::checkPermission('ZikulaProfileModule::', '::', ACCESS_ADMIN);

        $result = [];
        foreach ($items as $item) {
            switch ($args['get']) {
                case 'editable':
                    // check the display type
                    if ($item['prop_dtype'] < 0) {
                        break;
                    }
                // Fall through to next case on purpose, handle editable and viewable the same at this point.
                case 'viewable':
                    $isAllowed = true;
                    // check the item visibility
                    switch ($item['prop_viewby']) {
                        // everyone, do nothing
                        case '0':
                            break;
                        // members only or higher
                        case '1':
                            $isAllowed = $isOwner || $isMember;
                            break;
                        // account owner or admin
                        case '2':
                            $isAllowed = $isOwner || $isAdmin;
                            break;
                        // admins only
                        case '3':
                            $isAllowed = $isAdmin;
                            break;
                    }
                    // break if it's not viewable
                    if (!$isAllowed) {
                        break;
                    }
                case 'all':
                    $result[$item[$args['index']]] = $item;
            }
        }

        $event_subject = UserUtil::getVars($args['uid']);
        $event_args = [
            'get'      => $args['get'],
            'index'    => $args['index'],
            'numitems' => $args['numitems'],
            'startnum' => $args['startnum'],
            'uid'      => $args['uid'],
        ];
        $event_data = $result;

        $event = new GenericEvent($event_subject, $event_args, $event_data);
        $event = $this->getDispatcher()->dispatch('module.profile.get_all_active', $event);

        if ($event->isPropagationStopped()) {
            $result = $event->getData();
        }

        // Return the items
        return $result;
    }

    /**
     * Utility function to count the number of items held by this module.
     *
     * @return int Number of items held by this module
     */
    public function countitems()
    {
        $propertyRepository = $this->entityManager->getRepository('ZikulaProfileModule:PropertyEntity');

        return $propertyRepository->getTotalAmount();
    }

    /**
     * Utility function to get the weight limits.
     *
     * @return array|bool Array of weight limits (min and max), or false on failure
     */
    public function getweightlimits()
    {
        $propertyRepository = $this->entityManager->getRepository('ZikulaProfileModule:PropertyEntity');

        return [
            'min' => $propertyRepository->getMinimumWeight(),
            'max' => $propertyRepository->getMaximumWeight(),
        ];
    }

    /**
     * Utility function to save the data of the user.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * integer uid      The user id of the user for which the data should be saved; required.
     * array   dynadata The data for the user to be saved, indexed by prop_attribute_name; required.
     *
     * @param array $args All parameters passed to this function
     *
     * @throws \InvalidArgumentException if arguments are empty or not set as expected
     *
     * @return bool True on success; otherwise false
     */
    public function savedata($args)
    {
        // Argument check
        if (!isset($args['uid'])) {
            throw new \InvalidArgumentException();
        }
        $fields = $args['dynadata'];
        $duds = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive', ['get' => 'editable', 'uid' => $args['uid']]);
        foreach ($duds as $attributeName => $dud) {
            // exclude avatar update when Avatar module is present
            if ($attributeName == 'avatar' && ModUtil::available('Avatar')) {
                continue;
            }

            /*
             * Only set the user var, if the attribute name is within the array of fields (dynadata).
             */
            $array_keys = array_keys($fields);

            if (in_array($attributeName, $array_keys)) {
                $fieldValue = '';

                if (isset($fields[$attributeName])) {
                    // Process the Date DUD separately
                    if ($dud['prop_displaytype'] == 5 && !empty($fields[$attributeName])) {
                        $fieldValue = $this->parseDate($fields[$attributeName]);
                        $fieldValue = DateUtil::transformInternalDate($fieldValue);
                    } elseif (is_array($fields[$attributeName])) {
                        $fieldValue = serialize(array_values($fields[$attributeName]));
                    } else {
                        $fieldValue = $fields[$attributeName];
                    }
                }

                UserUtil::setVar($attributeName, $fieldValue, $args['uid']);
            }
        }

        // Return the result (true = success, false = failure
        // At this point, the result is true.
        return true;
    }

    /**
     * Parses and reformats a date for user entry validation.
     *
     * @param string &$dateString The entered date string to be parsed; NOTE: passed by reference, the value will be changed to a date reformatted with
     *                            the "%d.%m.%Y" date format string; required
     *
     * @return string The parsed date string, as returned by {@link DateUtil::parseUIDate()}
     */
    protected function parseDate(&$dateString)
    {
        $dateFormats = [null, '%d.%m.%Y', '%Y-%m-%d', '%e.%n.%Y', '%e.%n.%y', '%Y/%m/%d', '%y/%m/%d'];
        $result = null;
        foreach ($dateFormats as $format) {
            $result = DateUtil::parseUIDate($dateString, $format);
            if (null !== $result) {
                $dateString = DateUtil::formatDatetime($result, '%d.%m.%Y', false);
                break;
            }
        }

        return $result;
    }

    /**
     * Profile_Manager function to check the required missing.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * array dynadata The array of user data, index by prop_attribute_name, to check; required, but may be passed in a GET, POST, REQUEST, or SESSION variable.
     *
     * @param array $args All parameters passed to this function
     *
     * @return array|bool False on success (no errors); otherwise an array in the form ['result' => true, 'fields' => array of field names]
     */
    public function checkrequired($args)
    {
        // Argument check
        if (!isset($args['dynadata'])) {
            throw new \Exception($this->__f('Missing dynamic data array in call to %s', ['checkrequired']));
        }

        // The API function is called.
        $items = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive', ['get' => 'editable']);

        // Initializing Error check
        $error = false;
        foreach ($items as $item) {
            if ($item['prop_required'] == 1) {
                // exclude the checkboxes from required check
                if (in_array($item['prop_displaytype'], [2, 7])) {
                    continue;
                } elseif (!isset($args['dynadata'][$item['prop_attribute_name']])) {
                    $error['result'] = true;
                    $error['fields'][] = $item['prop_attribute_name'];
                    $error['translatedFields'][] = $this->__($item['prop_label']);
                } elseif (is_array($args['dynadata'][$item['prop_attribute_name']])) {
                    while (list(, $value) = each($args['dynadata'][$item['prop_attribute_name']])) {
                        if ($this->profileIsEmptyValue($value)) {
                            $error['result'] = true;
                            $error['fields'][] = $item['prop_attribute_name'];
                            $error['translatedFields'][] = $this->__($item['prop_label']);
                        }
                    }
                } elseif ($item['prop_displaytype'] == 5 && $this->parseDate($args['dynadata'][$item['prop_attribute_name']]) == null) {
                    // not empty, check if date is correct
                    $error['result'] = true;
                    $error['fields'][] = $item['prop_attribute_name'];
                    $error['translatedFields'][] = $this->__($item['prop_label']);
                } elseif ($this->profileIsEmptyValue($args['dynadata'][$item['prop_attribute_name']])) {
                    $error['result'] = true;
                    $error['fields'][] = $item['prop_attribute_name'];
                    $error['translatedFields'][] = $this->__($item['prop_label']);
                }
            }
        }
        if (!empty($error)) {
            $error['translatedFieldsStr'] = implode(', ', $error['translatedFields']);
        }

        // Return the result
        return $error;
    }

    /**
     * Checks if a value is empty, however if the $value is 0, it is not considered empty.
     *
     * @param mixed $value The value to check for empty
     *
     * @return bool True if the value is empty (according to the PHP function) and is not 0; otherwise false
     */
    protected function profileIsEmptyValue($value)
    {
        $empty = false;
        if (empty($value)) {
            $empty = true;
        }
        if (!$empty && trim($value) == '') {
            $empty = true;
        }
        if ($empty && is_numeric($value) && $value == 0) {
            $empty = false;
        }

        return $empty;
    }

    /**
     * Profile_Manager function to retrieve the dynamic data to the user object.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * numeric uid      The user id of the user for which the data is to be inserted.
     * array   dynadata The user data to insert, indexed by prop_attribute_name; required, however can be passed by a GET, POST, REQUEST, COOKIE, or SESSION variable.
     *
     * @param array $args All parameters passed to this function
     *
     * @return array The dynadata array as an array element in the '__ATTRIBUTES__' index of a new array, merged with existing user
     *               attributes if the uid is supplied and is a valid user, unchanged (not merged) if the uid is not supplied or does
     *               not refer to an existing user, or an empty array if the dynadata is not supplied or is empty
     */
    public function insertdyndata($args)
    {
        if (!isset($args['dynadata'])) {
            throw new \Exception($this->__f('Missing dynamic data array in call to %s', ['checkrequired']));
        }
        $dynadata = $args['dynadata'];
        // Validate if there's no dynadata
        // do not touch the __ATTRIBUTES__ field
        if (empty($dynadata)) {
            return [];
        }

        // Validate if it's an existing user
        if (!isset($args['uid'])) {
            return ['__ATTRIBUTES__' => $dynadata];
        }

        // Needs to merge the existing attributes to not delete any of them
        //        $user = DBUtil::selectObjectByID('users', $args['uid'], 'uid');
        $user = UserUtil::getVars($args['uid']);
        if (false === $user || !isset($user['__ATTRIBUTES__'])) {
            return ['__ATTRIBUTES__' => $dynadata];
        }

        // attach the dynadata as attributes to the user object
        return ['__ATTRIBUTES__' => array_merge($user['__ATTRIBUTES__'], $dynadata)];
    }

    /**
     * Search the input values through the dynadata.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * array dynadata An array of data on which to search; required, but may be passed as a GET, POST, REQUEST, COOKIE, or SESSION variable.
     *
     * @param array $args All parameters passed to this function
     *
     * @return array An array of uids for users who have matching attributes as specified by the dynadata array; an empty array if there are no matching users
     */
    public function searchdynadata($args)
    {
        $uids = [];
        if (!isset($args['dynadata'])) {
            throw new \Exception($this->__f('Missing dynamic data array in call to %s', ['checkrequired']));
        }
        $dynadata = $args['dynadata'];

        // Validate if there's any dynamic data
        if (empty($dynadata) || !is_array($dynadata)) {
            return $uids;
        }

        $params = ['returnUids' => true];
        if (count($dynadata) == 1 && in_array('all', array_keys($dynadata))) {
            $params['searchby'] = $dynadata;
        } else {
            $duditems = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getall');
            $params['searchby'] = [];
            foreach ($duditems as $item) {
                if (isset($dynadata[$item['prop_attribute_name']]) && !empty($dynadata[$item['prop_attribute_name']])) {
                    $params['searchby'][$item['prop_id']] = $dynadata[$item['prop_attribute_name']];
                }
            }
        }
        if (!empty($params['searchby'])) {
            $uids = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getall', $params);
        }

        return $uids;
    }
}
