<?php

declare(strict_types=1);

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula - https://ziku.la/
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

    public function display(array $properties): string
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
            'activeProperties' => $this->propertyRepository->findBy(['active' => true])
        ]);
    }

    public function getFormOptions(): array
    {
        return [
            'activeProperties' => $this->propertyRepository->findBy(['active' => true])
        ];
    }

    public function getFormClassName(): string
    {
        return FeaturedUserBlockType::class;
    }

    public function getFormTemplate(): string
    {
        return '@ZikulaProfileModule/Block/featuredUser_modify.html.twig';
    }

    /**
     * @required
     */
    public function setUserRepository(UserRepositoryInterface $userRepository): void
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @required
     */
    public function setPropertyRepository(PropertyRepositoryInterface $propertyRepository): void
    {
        $this->propertyRepository = $propertyRepository;
    }
}
