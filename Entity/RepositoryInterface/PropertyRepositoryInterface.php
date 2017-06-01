<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Entity\RepositoryInterface;

use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Persistence\ObjectRepository;
use Zikula\ProfileModule\Entity\PropertyEntity;

interface PropertyRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @return PropertyEntity[]
     */
    public function getIndexedActive();
}
