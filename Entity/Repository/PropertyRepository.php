<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Zikula\ProfileModule\Entity\RepositoryInterface\PropertyRepositoryInterface;

class PropertyRepository extends EntityRepository implements PropertyRepositoryInterface
{
    public function getIndexedActive()
    {
        $qb = $this->createQueryBuilder('p', 'p.id')
            ->where('p.active = true');

        return $qb->getQuery()->getArrayResult();
    }
}
