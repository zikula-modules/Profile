<?php
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for the property entity.
 */
class PropertyRepository extends EntityRepository
{
    /**
     * Returns all properties sorted by weight as array.
     *
     * @param integer $startOffset   The starting record number to retrieve
     * @param integer $amountOfItems The amount of items to retrieve
     *
     * @return array List of fetched properties
     */
    public function getAllByWeight($startOffset = 0, $amountOfItems = 0)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('p')
            ->from('ZikulaProfileModule:PropertyEntity', 'p')
            ->orderBy('p.prop_weight');
        if ($startOffset > 0) {
            $qb->setFirstResult($startOffset - 1);
        }
        if ($amountOfItems > 0) {
            $qb->setMaxResults($amountOfItems);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Returns all active properties.
     *
     * @return array List of fetched properties
     */
    public function getAllActive()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('p')
            ->from('ZikulaProfileModule:PropertyEntity', 'p')
            ->where('p.prop_weight > 0')
            ->andWhere('p.prop_dtype >= 0')
            ->orderBy('p.prop_weight');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Returns the amount of all properties.
     *
     * @return integer The amount of counted properties
     */
    public function getTotalAmount()
    {
        $query = $this->_em->createQuery('
            SELECT COUNT(p.prop_id)
            FROM Zikula\ProfileModule\Entity\PropertyEntity p
        ');

        return $query->getSingleScalarResult();
    }

    /**
     * Returns the minimum weight of all properties.
     *
     * @return integer The minimum weight value
     */
    public function getMinimumWeight()
    {
        $query = $this->_em->createQuery('
            SELECT MIN(p.prop_weight)
            FROM Zikula\ProfileModule\Entity\PropertyEntity p
        ');

        return $query->getSingleScalarResult();
    }

    /**
     * Returns the maximum weight of all properties.
     *
     * @return integer The maximum weight value
     */
    public function getMaximumWeight()
    {
        $query = $this->_em->createQuery('
            SELECT MAX(p.prop_weight)
            FROM Zikula\ProfileModule\Entity\PropertyEntity p
        ');

        return $query->getSingleScalarResult();
    }

    /**
     * Deletes a single property.
     *
     * @param integer $id The property id
     *
     * @return bool True on success, false otherwise
     */
    public function deleteProperty($id)
    {
        $qb = $this->_em->createQueryBuilder()
            ->delete('Zikula\ProfileModule\Entity\PropertyEntity', 'p')
            ->where('p.prop_id = :id')
            ->setParameter('id', $id);
        $result = $qb->getQuery()->execute();

        return (bool)$result;
    }

    /**
     * Activates a single property.
     *
     * @param integer $id        The property id
     * @param integer $newWeight The new weight value
     */
    public function activateProperty($id, $newWeight)
    {
        $property = $this->find($id);
        $property->setProp_weight($newWeight);
        $this->_em->flush();
    }

    /**
     * Deactivates a single property.
     *
     * @param integer $id     The property id
     * @param integer $weight The item's weight value
     */
    public function deactivateProperty($id, $weight)
    {
        // Update the item
        $property = $this->find($id);
        $property->setProp_weight(0);
        $this->_em->flush();

        // Update the other items
        $qb = $this->_em->createQueryBuilder()
            ->update('ZikulaProfileModule:PropertyEntity', 'p')
            ->set('p.prop_weight', 'p.prop_weight - 1')
            ->where('p.prop_weight > :weight')
            ->setParameter('weight', $weight);
        $qb->getQuery()->execute();
    }
}
