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
use Zikula\ProfileModule\Block\Form\Type\LastXUsersBlockType;
use Zikula\UsersModule\Entity\RepositoryInterface\UserRepositoryInterface;

class LastXUsersBlock extends AbstractBlockHandler
{
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    public function display(array $properties): string
    {
        $title = !empty($properties['title']) ? $properties['title'] : '';
        if (!$this->hasPermission('ZikulaProfileModule:LastXUsersblock:', $title.'::', ACCESS_READ)) {
            return '';
        }

        return $this->renderView('@ZikulaProfileModule/Block/lastXUsers.html.twig', [
            'users' => $this->userRepository->findBy([], ['registrationDate' => 'DESC'], $properties['amount'])
        ]);
    }

    public function getFormClassName(): string
    {
        return LastXUsersBlockType::class;
    }

    public function getFormTemplate(): string
    {
        return '@ZikulaProfileModule/Block/lastXUsers_modify.html.twig';
    }

    public function setUserRepository(UserRepositoryInterface $userRepository): void
    {
        $this->userRepository = $userRepository;
    }
}
