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

    /**
     * Display block.
     *
     * @param array $properties
     *
     * @return string the rendered block
     */
    public function display(array $properties)
    {
        $title = !empty($properties['title']) ? $properties['title'] : '';
        if (!$this->hasPermission('ZikulaProfileModule:LastXUsersblock:', $title.'::', ACCESS_READ)) {
            return '';
        }

        return $this->renderView('@ZikulaProfileModule/Block/lastXUsers.html.twig', [
            'users' => $this->userRepository->findBy([], ['user_regdate' => 'DESC'], $properties['amount']),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormClassName()
    {
        return LastXUsersBlockType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormTemplate()
    {
        return '@ZikulaProfileModule/Block/lastXUsers_modify.html.twig';
    }

    /**
     * @required
     * @param UserRepositoryInterface $userRepository
     */
    public function setUserRepository(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
}
