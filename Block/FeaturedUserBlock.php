<?php

declare(strict_types=1);
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Block;

use Zikula\BlocksModule\AbstractBlockHandler;
use Zikula\ProfileModule\Block\Form\Type\FeaturedUserBlockType;
use Zikula\ProfileModule\Entity\RepositoryInterface\PropertyRepositoryInterface;
use Zikula\UsersModule\Entity\RepositoryInterface\UserRepositoryInterface;

class FeaturedUserBlock extends AbstractBlockHandler
{
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var PropertyRepositoryInterface
     */
    private $propertyRepository;

    /**
     * {@inheritdoc}
     */
    public function display(array $properties)
    {
        if (!$this->hasPermission('ZikulaProfileModule:FeaturedUserblock:', $properties['title'] . '::', ACCESS_READ)) {
            return '';
        }
        $user = $this->userRepository->findOneBy(['uname' => $properties['username']]);
        if (empty($user)) {
            return '';
        }

        return $this->renderView('@ZikulaProfileModule/Block/featuredUser.html.twig', [
            'prefix' => 'zpmpp', // TODO $this->getParameter('zikula_profile_module.property_prefix'),
            'user' => $user,
            'blockProperties' => $properties,
            'activeProperties' => $this->propertyRepository->findBy(['active' => true]),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions()
    {
        return [
            'activeProperties' => $this->propertyRepository->findBy(['active' => true])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormClassName()
    {
        return FeaturedUserBlockType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormTemplate()
    {
        return '@ZikulaProfileModule/Block/featuredUser_modify.html.twig';
    }

    /**
     * @required
     * @param UserRepositoryInterface $userRepository
     */
    public function setUserRepository(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @required
     * @param PropertyRepositoryInterface $propertyRepository
     */
    public function setPropertyRepository(PropertyRepositoryInterface $propertyRepository)
    {
        $this->propertyRepository = $propertyRepository;
    }
}
