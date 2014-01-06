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
 * API functions related to member list management.
 */

namespace Zikula\Module\ProfileModule\Api;

use SecurityUtil;
use Zikula\Module\UsersModule\Constant as UsersConstant;
use ModUtil;
use System;
use DateTime;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Debug\Exception\FatalErrorException;

class MemberslistApi extends \Zikula_AbstractApi
{
    /**
     * Get or count users that match the given criteria.
     *
     * @param boolean $countOnly True to only return a count, if false the matching uids are returned in an array.
     * @param mixed $searchBy Selection criteria for the query that retrieves the member list; one of 'uname' to select by user name, 'all' to select on all
     *                              available dynamic user data properites, a numeric value indicating the property id of the property on which to select,
     *                              an array indexed by property id containing values for each property on which to select, or a string containing the name of
     *                              a property on which to select.
     * @param string $letter If searchby is 'uname' then either a letter on which to match the beginning of a user name or a non-letter indicating that
     *                              selection should include user names beginning with numbers and/or other symbols, if searchby is a numeric propery id or
     *                              is a string containing the name of a property then the string on which to match the begining of the value for that property.
     * @param string $letter Letter to filter by.
     * @param string $sortBy A comma-separated list of fields on which the list of members should be sorted.
     * @param string $sortOrder One of 'ASC' or 'DESC' indicating whether sorting should be in ascending order or descending order.
     * @param integer $startNum Start number for recordset; ignored if $countOnly is true.
     * @param integer $numItems Number of items to return; ignored if $countOnly is true.
     * @param boolean $returnUids Return an array of uids if true, otherwise return an array of user records; ignored if $countOnly is true.
     *
     * @return array|integer Matching user ids or a count of the matching integers.
     */
    protected function getOrCountAll($countOnly, $searchBy, $letter, $sortBy, $sortOrder, $startNum = -1, $numItems = -1, $returnUids = false)
    {
        if (!isset($startNum) || !is_numeric($startNum) || $startNum != (string)(int)$startNum || $startNum < -1) {
            throw new FatalErrorException($this->__f('Invalid %1$s.', array('startNum')));
        } elseif ($startNum <= 0) {
            $startNum = -1;
        }
        if (!isset($numItems) || !is_numeric($numItems) || $numItems != (string)(int)$numItems || $numItems != -1 && $numItems < 1) {
            throw new FatalErrorException($this->__f('Invalid %1$s.', array('startNum')));
        }
        if (!isset($sortBy) || empty($sortBy)) {
            $sortBy = 'uname';
        }
        if (!isset($sortOrder) || empty($sortOrder)) {
            $sortOrder = 'ASC';
        }
        if (!isset($searchBy) || empty($searchBy)) {
            $searchBy = 'uname';
        }
        if (!isset($letter)) {
            $letter = null;
        }
        // Security check
        if (!SecurityUtil::checkPermission($this->name.':Members:', '::', ACCESS_READ)) {
            return array();
        }
        // begin the construction of the query
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select(array('u', 'a'))
            ->from('Zikula\\Module\\UsersModule\\Entity\\UserEntity', 'u')
            ->leftJoin('u.attributes', 'a')
            ->andWhere('u.uid > 1');

        if ($searchBy == 'uname') {
            if (!empty($letter) && preg_match('/[a-z]/i', $letter)) {
                $qb->andWhere($qb->expr()->like('u.uname', ':letter'))->setParameter('letter', $letter . '%');
            } else {
                if (!empty($letter)) {
                    $otherList = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '-', '.', '@', '$');
                    $or = $qb->expr()->orX();
                    foreach ($otherList as $other) {
                        $or->add($qb->expr()->like('u.uname', $qb->expr()->literal($other . '%')));
                    }
                    $qb->andWhere($or->getParts());
                }
            }
        } else {
            if (is_array($searchBy)) {
                // searching via search module when this module is set as profile module
                if (count($searchBy) == 1 && in_array('all', array_keys($searchBy))) {
                    // args.searchby is all => search_value to loop all the user attributes
                    $qb->andWhere($qb->expr()->like('a.value', ':value'))
                        ->setParameter('value', "%{$searchBy['all']}%");
                } else {
                    /**
                     * @todo
                     * This section is unused when the form uses radio buttons as it currently does
                     * If the form were converted to checkmarks or a multiselect then this section would be needed
                     * in fact the 'p' table alias below will break it. and the logic below is flawed and based on the
                     * existence of the property table which is no longer in the query
                     */
                    // args.searchby is an array of the form prop_id => value
                    $and = $qb->expr()->andX();
                    foreach ($searchBy as $prop_id => $value) {
                        $and->add($qb->expr()->andX($qb->expr()->eq('p.prop_id', $prop_id), $qb->expr()->like('a.value', $qb->expr()->literal('%' . $value . '%'))));
                    }
                    // check if there where conditionals
                    if ($and->count() > 0) {
                        $qb->andWhere($and->getParts());
                    }
                }
            } else {
                $activePropertiesByName = ModUtil::apiFunc($this->name, 'user', 'getallactive');
                if (is_numeric($searchBy)) {
                    $activeProperties = ModUtil::apiFunc($this->name, 'user', 'getallactive', array('index' => 'prop_id'));
                    $qb->andWhere('a.name = :searchby')
                        ->setParameter('searchby', $activeProperties[$searchBy]['prop_attribute_name'])
                        ->andWhere($qb->expr()->like('a.value', $qb->expr()->literal('%' . $letter . '%')));
                } elseif (array_key_exists($searchBy, $activePropertiesByName)) {
                    $qb->andWhere('a.name = :searchby')
                        ->setParameter('searchby', $searchBy)
                        ->andWhere($qb->expr()->like('a.value', $qb->expr()->literal('%' . $letter . '%')));
                }
            }
        }
        if (ModUtil::getVar($this->name, 'filterunverified')) {
            $qb->andWhere('u.activated = ' . UsersConstant::ACTIVATED_ACTIVE);
        }
        $orderBy = false;
        if (property_exists('Zikula\\Module\\UsersModule\\Entity\\UserEntity', $sortBy)) {
            $qb->orderBy('u.' . $sortBy, $sortOrder);
            $orderBy = true;
        }
        if ($orderBy && $sortBy != 'uname') {
            $qb->addOrderBy('u.uname', 'ASC');
        }
        try {
            $users = $qb->getQuery()->getArrayResult();
        } catch (\Exception $e) {
            // remove when tested
            \System::dump($e->getMessage());
            \System::dump($qb->getQuery()->getDQL());
            \System::dump($qb->getQuery()->getSQL());
            \System::dump($qb->getParameters());
            throw new FatalErrorException($this->__('Query failed.'));
        }
        if ($countOnly) {
            return count($users);
        } else {
            $usersArray = array();
            foreach ($users as $k => $user) {
                if ($returnUids) {
                    $usersArray[$k] = $user['uid'];
                } else {
                    $usersArray[$user['uid']] = $user;
                    // reformat attributes array
                    foreach($user['attributes'] as $name => $attr) {
                        $usersArray[$user['uid']]['attributes'][$name] = $attr['value'];
                    }
                }
            }
            return $usersArray;
        }
    }

    /**
     * Get users that match the given criteria.
     *
     * This API function returns all users ids. This function allows for filtering and for paged selection.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * mixed   searchby   Selection criteria for the query that retrieves the member list; one of 'uname' to select by user name, 'all' to select on all
     *                      available dynamic user data properites, a numeric value indicating the property id of the property on which to select,
     *                      an array indexed by property id containing values for each property on which to select, or a string containing the name of
     *                      a property on which to select.
     * string  letter     If searchby is 'uname' then either a letter on which to match the beginning of a user name or a non-letter indicating that
     *                      selection should include user names beginning with numbers and/or other symbols, if searchby is a numeric propery id or
     *                      is a string containing the name of a property then the string on which to match the begining of the value for that property.
     * string  sortby     A comma-separated list of fields on which the list of members should be sorted.
     * string  sortorder  One of 'ASC' or 'DESC' indicating whether sorting should be in ascending order or descending order.
     * numeric startnum   Start number for recordset.
     * numeric numitems   Number of items to return.
     * boolean returnUids If true then a simple array containing only uids is returned, if false then an array containing full user records is returned.
     *
     * @param array $args All parameters passed to this function.
     *
     * @return array Matching user ids.
     */
    public function getall($args)
    {
        // Optional arguments.
        if (!isset($args['startnum'])) {
            $args['startnum'] = -1;
        }
        if (!isset($args['numitems'])) {
            $args['numitems'] = -1;
        }
        if (!isset($args['sortby']) || empty($args['sortby'])) {
            $args['sortby'] = 'uname';
        }
        if (!isset($args['sortorder']) || empty($args['sortorder'])) {
            $args['sortorder'] = 'ASC';
        }
        if (!isset($args['searchby']) || empty($args['searchby'])) {
            $args['searchby'] = 'uname';
        }
        if (!isset($args['letter'])) {
            $args['letter'] = null;
        }
        if (!isset($args['returnUids'])) {
            $args['returnUids'] = false;
        } else {
            $args['returnUids'] = (bool)$args['returnUids'];
        }
        return $this->getOrCountAll(
            false,
            $args['searchby'],
            $args['letter'],
            $args['sortby'],
            $args['sortorder'],
            $args['startnum'],
            $args['numitems'],
            $args['returnUids']
        );
    }

    /**
     * Count users that match the given criteria.
     *
     * This API function returns all users ids. This function allows for filtering and for paged selection.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * mixed   searchby  Selection criteria for the query that retrieves the member list; one of 'uname' to select by user name, 'all' to select on all
     *                              available dynamic user data properites, a numeric value indicating the property id of the property on which to select,
     *                              an array indexed by property id containing values for each property on which to select, or a string containing the name of
     *                              a property on which to select.
     * string  letter    If searchby is 'uname' then either a letter on which to match the beginning of a user name or a non-letter indicating that
     *                              selection should include user names beginning with numbers and/or other symbols, if searchby is a numeric propery id or
     *                              is a string containing the name of a property then the string on which to match the begining of the value for that property.
     *
     * @param array $args All parameters passed to this function.
     *
     * @return array Count of matching users.
     */
    public function countitems($args)
    {
        if (!isset($args['searchby']) || empty($args['searchby'])) {
            $args['searchby'] = 'uname';
        }
        if (!isset($args['letter'])) {
            $args['letter'] = null;
        }
        $sortBy = 'uname';
        $sortOrder = 'ASC';
        return $this->getOrCountAll(
            true,
            $args['searchby'],
            $args['letter'],
            $sortBy,
            $sortOrder
        );
    }

    /**
     * Counts the number of users online.
     *
     * @return integer Count of registered users online.
     */
    public function getregisteredonline()
    {
        $dql = 'SELECT COUNT(s.uid)
            FROM Zikula\\Module\\UsersModule\\Entity\\UserSessionEntity s
            WHERE s.lastused > :activetime
            AND s.uid >= 2';
        $query = $this->entityManager->createQuery($dql);
        $activetime = new DateTime();
        // @todo maybe need to check TZ here
        $activetime->modify('-' . System::getVar('secinactivemins') . ' minutes');
        $query->setParameter('activetime', $activetime);
        $numusers = $query->getSingleScalarResult();
        // Return the number of items
        return $numusers;
    }

    /**
     * Get the latest registered user.
     *
     * @return integer Latest registered user id.
     */
    public function getlatestuser()
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')->from('Zikula\\Module\\UsersModule\\Entity\\UserEntity', 'u')->where('u.uid <> 1');
        if (ModUtil::getVar($this->name, 'filterunverified')) {
            $qb->andWhere('u.activated = ' . UsersConstant::ACTIVATED_ACTIVE);
        }
        $qb->orderBy('u.uid', 'DESC')->setMaxResults(1);
        $user = $qb->getQuery()->getSingleResult();
        if ($user) {
            return $user->getUid();
        } else {
            $this->request->getSession()->getFlashBag()->add('error', $this->__('Error! Could not load data.'));
            return false;
        }
    }

    /**
     * Determine if a user is online.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * numeric userid The uid of the user for whom a determination should be made; required.
     *
     * @param array $args All parameters passed to this function.
     *
     * @return bool True if the specified user is online; false otherwise.
     */
    public function isonline($args)
    {
        // check arguments
        if (!isset($args['userid']) || empty($args['userid']) || !is_numeric($args['userid'])) {
            return false;
        }
        $dql = 'SELECT s.uid
                FROM Zikula\\Module\\UsersModule\\Entity\\UserSessionEntity s
                WHERE s.lastused > :activetime
                AND s.uid = :uid';
        $query = $this->entityManager->createQuery($dql);
        $activetime = new DateTime();
        // @todo maybe need to check TZ here
        $activetime->modify('-' . System::getVar('secinactivemins') . ' minutes');
        $query->setParameter('activetime', $activetime);
        $query->setParameter('uid', $args['userid']);
        try {
            $uid = $query->getSingleScalarResult();
        } catch (NoResultException $e) {
            return false;
        }
        return true;
    }

    /**
     * Return registered users online.
     *
     * @return array Registered users who are online.
     */
    public function whosonline()
    {
        $dql = 'SELECT u, a
            FROM Zikula\\Module\\UsersModule\\Entity\\UserSessionEntity s, Zikula\\Module\\UsersModule\\Entity\\UserEntity u
            LEFT JOIN u.attributes a
            WHERE s.lastused > :activetime
            AND (s.uid >= 2
            AND s.uid = u.uid)';
        $query = $this->entityManager->createQuery($dql);
        $activetime = new DateTime();
        // @todo maybe need to check TZ here
        $activetime->modify('-' . System::getVar('secinactivemins') . ' minutes');
        $query->setParameter('activetime', $activetime);
        $onlineusers = $query->getArrayResult();
        foreach ($onlineusers as $k => $user) {
            // reformat attributes array
            foreach($user['attributes'] as $name => $attr) {
                $onlineusers[$k]['attributes'][$name] = $attr['value'];
            }
        }
        return $onlineusers;
    }

    /**
     * Returns all users online.
     *
     * @return array All online visitors (including anonymous).
     */
    public function getallonline()
    {
        $dql = 'SELECT u
            FROM Zikula\\Module\\UsersModule\\Entity\\UserSessionEntity s, Zikula\\Module\\UsersModule\\Entity\\UserEntity u
            WHERE s.lastused > :activetime
            AND (s.uid >= 2
            AND s.uid = u.uid)
            OR s.uid = 0
            GROUP BY s.ipaddr, s.uid';
        $query = $this->entityManager->createQuery($dql);
        $activetime = new DateTime();
        // @todo maybe need to check TZ here
        $activetime->modify('-' . System::getVar('secinactivemins') . ' minutes');
        $query->setParameter('activetime', $activetime);
        $onlineusers = $query->getArrayResult();
        $numguests = 0;
        $unames = array();
        foreach ($onlineusers as $key => $user) {
            if ($user['uid'] != 1) {
                $unames[$user['uname']] = $user;
            } else {
                $numguests++;
            }
        }
        ksort($unames);
        $unames = array_values($unames);
        $numusers = count($unames);
        $items = array(
            'unames' => $unames,
            'numusers' => $numusers,
            'numguests' => $numguests,
            'total' => $numguests + $numusers);
        return $items;
    }

    /**
     * Find out which messages module is installed.
     *
     * @return string Name of the messaging module found, empty if none.
     */
    public function getmessagingmodule()
    {
        $msgmodule = System::getVar('messagemodule', '');
        if (!ModUtil::available($msgmodule)) {
            $msgmodule = '';
        }
        return $msgmodule;
    }

}