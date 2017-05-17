<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Bridge;

use Symfony\Component\Routing\RouterInterface;
use Zikula\ProfileModule\ProfileConstant;
use Zikula\UsersModule\Api\CurrentUserApi;
use Zikula\UsersModule\Entity\Repository\UserRepository;
use Zikula\UsersModule\Entity\RepositoryInterface\UserRepositoryInterface;
use Zikula\UsersModule\Entity\UserEntity;
use Zikula\UsersModule\ProfileModule\ProfileModuleInterface;

class ProfileModuleBridge implements ProfileModuleInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var CurrentUserApi
     */
    private $currentUser;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var string
     */
    private $prefix;

    /**
     * ProfileModuleBridge constructor.
     * @param RouterInterface $router
     * @param CurrentUserApi $currentUser
     * @param UserRepositoryInterface $userRepository
     * @param string $prefix
     */
    public function __construct(
        RouterInterface $router,
        CurrentUserApi $currentUser,
        UserRepositoryInterface $userRepository,
        $prefix
    ) {
        $this->router = $router;
        $this->currentUser = $currentUser;
        $this->userRepository = $userRepository;
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName($uid = null)
    {
        if (empty($uid) && $this->currentUser->isLoggedIn()) {
            $uid = $this->currentUser->get('uid');
        }
        /** @var UserEntity $userEntity */
        $userEntity = $this->userRepository->find($uid);
        if ($userEntity) {
            $key = $this->prefix . ':' . ProfileConstant::ATTRIBUTE_NAME_DISPLAY_NAME;
            if ($userEntity->getAttributes()->containsKey($key)) {
                return $userEntity->getAttributes()->get($key)->getValue();
            }

            return $userEntity->getUname();
        }
        throw new \InvalidArgumentException('Invalid UID provided');
    }

    /**
     * {@inheritdoc}
     */
    public function getProfileUrl($uid = null)
    {
        if (empty($uid) && $this->currentUser->isLoggedIn()) {
            $uid = $this->currentUser->get('uid');
        }
        /** @var UserEntity $userEntity */
        $userEntity = $this->userRepository->find($uid); // this just validates that a user is available
        if ($userEntity) {
            return $this->router->generate('zikulaprofilemodule_profile_display', ['uid' => $userEntity->getUid()]);
        }
        throw new \InvalidArgumentException('Invalid UID provided');
    }
}
