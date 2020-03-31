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

namespace Zikula\ProfileModule\Controller;

use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Zikula\Bundle\CoreBundle\Controller\AbstractController;
use Zikula\Bundle\CoreBundle\Filter\AlphaFilter;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\PermissionsModule\Annotation\PermissionCheck;
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
     * @Route("/list/{page}", methods = {"GET"}, requirements={"page" = "\d+"})
     * @PermissionCheck({"$_zkModule:Members:", "::", "read"})
     * @Template("@ZikulaProfileModule/Members/list.html.twig")
     */
    public function listAction(
        Request $request,
        PropertyRepositoryInterface $propertyRepository,
        UserRepositoryInterface $userRepository,
        UserSessionRepositoryInterface $userSessionRepository,
        VariableApiInterface $variableApi,
        int $page = 1
    ): array {
        $searchBy = $request->get('searchby');
        $sortBy = $request->get('sortby', 'uname');
        $sortOrder = $request->get('sortorder');
        $letter = $request->get('letter', '');
        $routeParameters = [
            'searchby' => $searchBy,
            'sortby' => $sortBy,
            'sortorder' => $sortOrder,
            'letter' => $letter
        ];

        $critera = [];
        if (isset($searchBy)) {
            $critera['uname'] = ['operator' => 'like', 'operand' => '%' . $searchBy . '%'];
        }
        if (isset($letter)) {
            $critera['uname'] = ['operator' => 'like', 'operand' => $letter . '%'];
        }

        $pageSize = $this->getVar('memberslistitemsperpage', 20);
        $users = $userRepository->query($critera, [$sortBy => $sortOrder], $pageSize, $page);
        $users->setRoute('zikulaprofilemodule_members_list');
        $users->setRouteParameters($routeParameters);
        unset($routeParameters['letter']);

        return [
            'prefix' => $this->getParameter('zikula_profile_module.property_prefix'),
            'amountOfRegisteredMembers' => $userRepository->count() - 1,
            'amountOfOnlineMembers' => count($this->getOnlineUids($userSessionRepository)),
            'newestMember' => $userRepository->findBy([], ['registrationDate' => 'DESC'], 1)[0],
            'paginator' => $users,
            'alpha' => new AlphaFilter('zikulaprofilemodule_members_list', $routeParameters, $letter),
            'sortby' => $sortBy,
            'sortorder' => $sortOrder,
            'activeProperties' => $this->getActiveProperties($propertyRepository),
            'messageModule' => $variableApi->getSystemVar(SettingsConstant::SYSTEM_VAR_MESSAGE_MODULE, '')
        ];
    }

    /**
     * @Route("/recent/{page}", methods = {"GET"}, requirements={"page" = "\d+"})
     * @PermissionCheck({"$_zkModule:Members:recent", "::", "read"})
     * @Template("@ZikulaProfileModule/Members/recent.html.twig")
     *
     * Displays last X registered users.
     */
    public function recentAction(
        PropertyRepositoryInterface $propertyRepository,
        UserRepositoryInterface $userRepository,
        VariableApiInterface $variableApi,
        int $page = 1
    ): array {
        $pageSize = $this->getVar('recentmembersitemsperpage');
        $users = $userRepository->query([], ['registrationDate' => 'DESC'], $pageSize, $page);
        $users->setRoute('zikulaprofilemodule_members_recent');

        return [
            'prefix' => $this->getParameter('zikula_profile_module.property_prefix'),
            'activeProperties' => $this->getActiveProperties($propertyRepository),
            'paginator' => $users,
            'messageModule' => $variableApi->getSystemVar(SettingsConstant::SYSTEM_VAR_MESSAGE_MODULE, '')
        ];
    }

    /**
     * @Route("/online/{page}", methods = {"GET"}, requirements={"page" = "\d+"})
     * @PermissionCheck({"$_zkModule:Members:online", "::", "read"})
     * @Template("@ZikulaProfileModule/Members/online.html.twig")
     *
     * View users online.
     */
    public function onlineAction(
        PropertyRepositoryInterface $propertyRepository,
        UserRepositoryInterface $userRepository,
        UserSessionRepositoryInterface $userSessionRepository,
        int $page = 1
    ): array {
        $criteria = ['uid' => ['operator' => 'in', 'operand' => $this->getOnlineUids($userSessionRepository)]];
        $pageSize = $this->getVar('onlinemembersitemsperpage');
        $users = $userRepository->query($criteria, [], $pageSize, $page);
        $users->setRoute('zikulaprofilemodule_members_online');

        return [
            'prefix' => $this->getParameter('zikula_profile_module.property_prefix'),
            'activeProperties' => $this->getActiveProperties($propertyRepository),
            'paginator' => $users
        ];
    }

    /**
     * Get uids of online users.
     */
    private function getOnlineUids(UserSessionRepositoryInterface $userSessionRepository): array
    {
        $activeMinutes = $this->getVar('activeminutes');
        $activeSince = new DateTime();
        $activeSince->modify("-${activeMinutes} minutes");

        return $userSessionRepository->getUsersSince($activeSince);
    }

    private function getActiveProperties(PropertyRepositoryInterface $propertyRepository): array
    {
        $properties = $propertyRepository->findBy(['active' => true]);
        $activeProperties = [];
        foreach ($properties as $property) {
            $activeProperties[]= $property->getId();
        }

        return $activeProperties;
    }
}
