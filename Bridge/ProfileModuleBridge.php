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

namespace Zikula\ProfileModule\Bridge;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Zikula\Bundle\CoreBundle\HttpKernel\ZikulaHttpKernelInterface;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\ProfileModule\Helper\GravatarHelper;
use Zikula\ProfileModule\ProfileConstant;
use Zikula\UsersModule\Api\ApiInterface\CurrentUserApiInterface;
use Zikula\UsersModule\Constant as UsersConstant;
use Zikula\UsersModule\Entity\RepositoryInterface\UserRepositoryInterface;
use Zikula\UsersModule\Entity\UserEntity;
use Zikula\UsersModule\ProfileModule\ProfileModuleInterface;

class ProfileModuleBridge implements ProfileModuleInterface
{
    /**
     * @var ZikulaHttpKernelInterface
     */
    private $kernel;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var VariableApiInterface
     */
    private $variableApi;

    /**
     * @var CurrentUserApiInterface
     */
    private $currentUser;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var GravatarHelper
     */
    private $gravatarHelper;

    /**
     * @var string
     */
    private $prefix;

    public function __construct(
        ZikulaHttpKernelInterface $kernel,
        RouterInterface $router,
        RequestStack $requestStack,
        VariableApiInterface $variableApi,
        CurrentUserApiInterface $currentUser,
        UserRepositoryInterface $userRepository,
        GravatarHelper $gravatarHelper,
        $prefix
    ) {
        $this->kernel = $kernel;
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->variableApi = $variableApi;
        $this->currentUser = $currentUser;
        $this->userRepository = $userRepository;
        $this->gravatarHelper = $gravatarHelper;
        $this->prefix = $prefix;
    }

    public function getDisplayName($uid = null): string
    {
        $userEntity = $this->findUser($uid);
        if (!$userEntity) {
            throw new InvalidArgumentException('Invalid UID provided');
        }

        $key = $this->prefix . ':' . ProfileConstant::ATTRIBUTE_NAME_DISPLAY_NAME;
        if ($userEntity->getAttributes()->containsKey($key)) {
            return $userEntity->getAttributes()->get($key)->getValue();
        }

        return $userEntity->getUname();
    }

    public function getProfileUrl($uid = null): string
    {
        $userEntity = $this->findUser($uid);
        if (!$userEntity) {
            throw new InvalidArgumentException('Invalid UID provided');
        }

        return $this->router->generate('zikulaprofilemodule_profile_display', ['uid' => $userEntity->getUid()]);
    }

    public function getAvatar($uid = null, array $parameters = []): string
    {
        $userEntity = $this->findUser($uid);
        if (!$userEntity) {
            throw new InvalidArgumentException('Invalid UID provided');
        }

        $gravatarImage = $this->variableApi->get(UsersConstant::MODNAME, ProfileConstant::MODVAR_GRAVATAR_IMAGE, ProfileConstant::DEFAULT_GRAVATAR_IMAGE);
        $avatarPath = $this->variableApi->get(UsersConstant::MODNAME, ProfileConstant::MODVAR_AVATAR_IMAGE_PATH, ProfileConstant::DEFAULT_AVATAR_IMAGE_PATH);
        $allowGravatars = (bool) $this->variableApi->get(UsersConstant::MODNAME, ProfileConstant::MODVAR_GRAVATARS_ENABLED, ProfileConstant::DEFAULT_GRAVATARS_ENABLED);

        $userAttributes = $userEntity->getAttributes();
        $key = $this->prefix . ':avatar';
        $avatar = $userAttributes[$key] ?? $gravatarImage;

        $avatarUrl = '';
        if (!in_array($avatar, ['blank.gif', 'blank.jpg'], true)) {
            if (isset($avatar) && !empty($avatar) && $avatar !== $gravatarImage && file_exists($this->kernel->getProjectDir() . '/' . $avatarPath . '/' . $avatar)) {
                $request = $this->requestStack->getCurrentRequest();
                if (null !== $request) {
                    $avatarUrl = $request->getSchemeAndHttpHost() . $request->getBasePath() . '/' . $avatarPath . '/' . $avatar;
                }
            } elseif (true === $allowGravatars) {
                $parameters = $this->squareSize($parameters);
                $avatarUrl = $this->gravatarHelper->getGravatarUrl($userEntity->getEmail(), $parameters);
            }
        }

        if (empty($avatarUrl)) {
            // e.g. blank.gif or empty avatars
            return '';
        }

        if (!isset($parameters['class'])) {
            $parameters['class'] = 'img-fluid img-thumbnail';
        }
        $attributes = ' class="' . str_replace('"', '', htmlspecialchars($parameters['class'])) . '"';
        $attributes .= isset($parameters['width']) ? ' width="' . (int)$parameters['width'] . '"' : '';
        $attributes .= isset($parameters['height']) ? ' height="' . (int)$parameters['height'] . '"' : '';

        $result = '<img src="' . str_replace('"', '', htmlspecialchars($avatarUrl)) . '" title="' . str_replace('"', '', htmlspecialchars($userEntity->getUname())) . '" alt="' . str_replace('"', '', htmlspecialchars($userEntity->getUname())) . '"' . $attributes . ' />';

        return $result;
    }

    /**
     * Finds a certain user based on either it's id or it's name.
     *
     * @param int|string $uid The user's id or name
     */
    private function findUser($uid = null): UserEntity
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
     */
    private function squareSize(array $parameters = []): array
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

    public function getBundleName(): string
    {
        return 'ZikulaProfileModule';
    }
}
