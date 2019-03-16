<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Core\Controller\AbstractController;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\ProfileModule\Entity\RepositoryInterface\PropertyRepositoryInterface;
use Zikula\SettingsModule\SettingsConstant;
use Zikula\UsersModule\Entity\RepositoryInterface\UserRepositoryInterface;
use Zikula\UsersModule\Entity\RepositoryInterface\UserSessionRepositoryInterface;

/**
 * @Route("/members")
 */
class MembersController extends AbstractController
{
    /**
     * @Route("/list")
     * @Template("ZikulaProfileModule:Members:list.html.twig")
     *
     * @param Request $request
     * @param PropertyRepositoryInterface $propertyRepository
     * @param UserRepositoryInterface $userRepository
     * @param UserSessionRepositoryInterface $userSessionRepository
     * @param VariableApiInterface $variableApi
     *
     * @return array
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function listAction(
        Request $request,
        PropertyRepositoryInterface $propertyRepository,
        UserRepositoryInterface $userRepository,
        UserSessionRepositoryInterface $userSessionRepository,
        VariableApiInterface $variableApi
    ) {
        if (!$this->hasPermission('ZikulaProfileModule:Members:', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }

        $startNum = $request->query->get('startnum', null);
        $sortBy = $request->get('sortby', 'uname');
        $searchby = $request->get('searchby', null);
        $sortOrder = $request->get('sortorder', null);
        $letter = $request->get('letter', null);

        $itemsPerPage = $this->getVar('memberslistitemsperpage', 20);
        $critera = [];
        if (isset($searchby)) {
            $critera['uname'] = ['operator' => 'like', 'operand' => '%' . $searchby . '%'];
        }
        if (isset($letter)) {
            $critera['uname'] = ['operator' => 'like', 'operand' => $letter . '%'];
        }
        $users = $userRepository->query($critera, [$sortBy => $sortOrder], $itemsPerPage, $startNum);
        $amountOfUsers = $userRepository->count();

        return [
            'prefix' => $this->container->getParameter('zikula_profile_module.property_prefix'),
            'amountOfRegisteredMembers' => $amountOfUsers - 1,
            'amountOfOnlineMembers' => count($this->getOnlineUids($userSessionRepository)),
            'newestMember' => $userRepository->findBy([], ['user_regdate' => 'DESC'], 1)[0],
            'users' => $users,
            'letter' => $letter,
            'sortby' => $sortBy,
            'sortorder' => $sortOrder,
            'activeProperties' => $this->getActiveProperties($propertyRepository),
            'messageModule' => $variableApi->getSystemVar(SettingsConstant::SYSTEM_VAR_MESSAGE_MODULE, ''),
            'pager' => [
                'amountOfItems' => $amountOfUsers,
                'itemsPerPage' => $itemsPerPage,
            ],
        ];
    }

    /**
     * @Route("/recent")
     * @Template("ZikulaProfileModule:Members:recent.html.twig")
     *
     * Displays last X registered users.
     *
     * @param PropertyRepositoryInterface $propertyRepository
     * @param UserRepositoryInterface $userRepository
     * @param VariableApiInterface $variableApi
     *
     * @return array
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function recentAction(
        PropertyRepositoryInterface $propertyRepository,
        UserRepositoryInterface $userRepository,
        VariableApiInterface $variableApi
    ) {
        if (!$this->hasPermission('ZikulaProfileModule:Members:recent', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }

        return [
            'prefix' => $this->container->getParameter('zikula_profile_module.property_prefix'),
            'activeProperties' => $this->getActiveProperties($propertyRepository),
            'users' => $userRepository->findBy([], ['user_regdate' => 'DESC'], $this->getVar('recentmembersitemsperpage')),
            'messageModule' => $variableApi->getSystemVar(SettingsConstant::SYSTEM_VAR_MESSAGE_MODULE, '')
        ];
    }

    /**
     * @Route("/online")
     * @Template("ZikulaProfileModule:Members:online.html.twig")
     *
     * View users online.
     *
     * @param PropertyRepositoryInterface $propertyRepository
     * @param UserRepositoryInterface $userRepository
     * @param UserSessionRepositoryInterface $userSessionRepository
     *
     * @return array
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function onlineAction(
        PropertyRepositoryInterface $propertyRepository,
        UserRepositoryInterface $userRepository,
        UserSessionRepositoryInterface $userSessionRepository
    ) {
        if (!$this->hasPermission('ZikulaProfileModule:Members:online', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }

        return [
            'prefix' => $this->container->getParameter('zikula_profile_module.property_prefix'),
            'activeProperties' => $this->getActiveProperties($propertyRepository),
            'users' => $userRepository->findBy(['uid' => $this->getOnlineUids($userSessionRepository)]),
        ];
    }

    /**
     * Get uids of online users
     *
     * @param UserSessionRepositoryInterface $userSessionRepository
     *
     * @return array
     */
    private function getOnlineUids(UserSessionRepositoryInterface $userSessionRepository)
    {
        $activeMinutes = $this->getVar('activeminutes');
        $activeSince = new \DateTime();
        $activeSince->modify("-$activeMinutes minutes");

        return $userSessionRepository->getUsersSince($activeSince);
    }

    /**
     * @param PropertyRepositoryInterface $propertyRepository
     * @return array
     */
    private function getActiveProperties(PropertyRepositoryInterface $propertyRepository)
    {
        $properties = $propertyRepository->findBy(['active' => true]);
        $activeProperties = [];
        foreach ($properties as $property) {
            $activeProperties[]= $property->getId();
        }

        return $activeProperties;
    }
}
