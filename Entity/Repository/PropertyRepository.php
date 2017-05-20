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
use Zikula\ProfileModule\Entity\RepositoryInterface\PropertyRepositoryInterface;

/**
 * Repository for the property entity.
 */
class PropertyRepository extends EntityRepository implements PropertyRepositoryInterface
{
}
