<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Bridge;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Zikula\ProfileModule\ProfileConstant;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\ExtensionsModule\Api\VariableApi;
use Zikula\UsersModule\Api\CurrentUserApi;
use Zikula\UsersModule\Constant as UsersConstant;
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
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var VariableApi
     */
    private $variableApi;

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
     *
     * @param RouterInterface $router
     * @param RequestStack $requestStack
     * @param VariableApiInterface $variableApi
     * @param CurrentUserApi $currentUser
     * @param UserRepositoryInterface $userRepository
     * @param string $prefix
     */
    public function __construct(
        RouterInterface $router,
        RequestStack $requestStack,
        VariableApiInterface $variableApi,
        CurrentUserApi $currentUser,
        UserRepositoryInterface $userRepository,
        $prefix
    ) {
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->variableApi = $variableApi;
        $this->currentUser = $currentUser;
        $this->userRepository = $userRepository;
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName($uid = null)
    {
        /** @var UserEntity $userEntity */
        $userEntity = $this->findUser($uid);
        if (!$userEntity) {
            throw new \InvalidArgumentException('Invalid UID provided');
        }

        $key = $this->prefix . ':' . ProfileConstant::ATTRIBUTE_NAME_DISPLAY_NAME;
        if ($userEntity->getAttributes()->containsKey($key)) {
            return $userEntity->getAttributes()->get($key)->getValue();
        }

        return $userEntity->getUname();
    }

    /**
     * {@inheritdoc}
     */
    public function getProfileUrl($uid = null)
    {
        /** @var UserEntity $userEntity */
        $userEntity = $this->findUser($uid);
        if (!$userEntity) {
            throw new \InvalidArgumentException('Invalid UID provided');
        }

        return $this->router->generate('zikulaprofilemodule_profile_display', ['uid' => $userEntity->getUid()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvatar($uid = null, array $parameters = [])
    {
        /** @var UserEntity $userEntity */
        $userEntity = $this->findUser($uid);
        if (!$userEntity) {
            throw new \InvalidArgumentException('Invalid UID provided');
        }

        $gravatarImage = $this->variableApi->get(UsersConstant::MODNAME, ProfileConstant::MODVAR_GRAVATAR_IMAGE, ProfileConstant::DEFAULT_GRAVATAR_IMAGE);
        $avatarPath = $this->variableApi->get(UsersConstant::MODNAME, ProfileConstant::MODVAR_AVATAR_IMAGE_PATH, ProfileConstant::DEFAULT_AVATAR_IMAGE_PATH);
        $allowGravatars = (bool) $this->variableApi->get(UsersConstant::MODNAME, ProfileConstant::MODVAR_GRAVATARS_ENABLED, ProfileConstant::DEFAULT_GRAVATARS_ENABLED);

        $userAttributes = $userEntity->getAttributes();
        $key = $this->prefix . ':avatar';
        $avatar = isset($userAttributes[$key]) ? $userAttributes[$key] : $gravatarImage;

        $avatarUrl = '';
        if (!in_array($avatar, ['blank.gif', 'blank.jpg'])) {
            if (isset($avatar) && !empty($avatar) && $avatar != $gravatarImage && file_exists($avatarPath . '/' . $avatar)) {
                $request = $this->requestStack->getCurrentRequest();
                $avatarUrl = $request->getSchemeAndHttpHost() . $request->getBasePath() . '/' . $avatarPath . '/' . $avatar;
            } elseif (true === $allowGravatars) {
                $parameters = $this->makeAvatarSquare($parameters);
                $avatarUrl = $this->getGravatarUrl($userEntity->getEmail(), $parameters);
            }
        }

        if (empty($avatarUrl)) {
            // e.g. blank.gif or empty avatars
            return '';
        }

        if (!isset($parameters['class'])) {
            $parameters['class'] = 'img-responsive img-thumbnail';
        }
        $attributes = ' class="' . str_replace('"', '', htmlspecialchars($parameters['class'])) . '"';
        $attributes .= isset($parameters['width']) ? ' width="' . intval($parameters['width']) . '"' : '';
        $attributes .= isset($parameters['height']) ? ' height="' . intval($parameters['height']) . '"' : '';

        $result = '<img src="' . str_replace('"', '', htmlspecialchars($avatarUrl)) . '" title="' . str_replace('"', '', htmlspecialchars($userEntity->getUname())) . '" alt="' . str_replace('"', '', htmlspecialchars($userEntity->getUname())) . '"' . $attributes . ' />';

        return $result;
    }

    /**
     * Finds a certain user based on either it's id or it's name.
     *
     * @param int|string $uid The user's id or name
     * @return UserEntity
     */
    private function findUser($uid = null)
    {
        if (empty($uid) && $this->currentUser->isLoggedIn()) {
            $uid = $this->currentUser->get('uid');
        }
        if (is_numeric($uid)) {
            return $this->userRepository->find($uid);
        }

        // select user id by user name
        $results = $this->userRepository->searchActiveUser(['operator' => '=', 'operand' => $uid], 1);
        if (!count($results)) {
            return '';
        }

        return $results->getIterator()->getArrayCopy()[0];
    }

    /**
     * Checks and updates the avatar image size parameters.
     *
     * @param array $parameters
     * @return array
     */
    private function makeAvatarSquare(array $parameters = [])
    {
        if (!isset($parameters['size'])) {
            if (isset($parameters['width']) || isset($parameters['height'])) {
                $hasWidth = isset($parameters['width']);
                $hasHeight = isset($parameters['height']);
                if (($hasWidth && !$hasHeight) || ($hasWidth && $hasHeight && $parameters['width'] < $parameters['height'])) {
                    $parameters['size'] = $parameters['width'];
                } elseif ((!$hasWidth && $hasHeight) || ($hasWidth && $hasHeight && $parameters['width'] > $parameters['height'])) {
                    $parameters['size'] = $parameters['height'];
                } else {
                    $parameters['size'] = 80;
                }
            } else {
                $parameters['size'] = 80;
            }
        }
        $parameters['width'] = $parameters['size'];
        $parameters['height'] = $parameters['size'];

        return $parameters;
    }

    /**
     * Returns the URL to a gravatar image.
     *
     * @see http://en.gravatar.com/site/implement/images/php/
     *
     * @param string $emailAddress
     * @param array $parameters
     * @return string
     */
    private function getGravatarUrl($emailAddress = '', array $parameters = [])
    {
        $url = $this->requestStack->getCurrentRequest()->isSecure() ? 'https://secure.gravatar.com/avatar/' : 'http://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($emailAddress))).'.jpg';

        $url .= '?s=' . (isset($parameters['size']) ? intval($parameters['size']) : 80);
        $url .= '&amp;d=' . (isset($parameters['imageset']) ? $parameters['imageset'] : 'mm');
        $url .= '&amp;r=' . (isset($parameters['rating']) ? $parameters['rating'] : 'g');

        return $url;
    }
}
