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
     * @Route("/list")
     * @PermissionCheck({"$_zkModule:Members:", "::", "read"})
     * @Template("@ZikulaProfileModule/Members/list.html.twig")
     */
    public function listAction(
        Request $request,
        PropertyRepositoryInterface $propertyRepository,
        UserRepositoryInterface $userRepository,
        UserSessionRepositoryInterface $userSessionRepository,
        VariableApiInterface $variableApi
    ): array {
        $startNum = $request->query->getInt('startnum');
        $sortBy = $request->get('sortby', 'uname');
        $searchby = $request->get('searchby');
        $sortOrder = $request->get('sortorder');
        $letter = $request->get('letter');

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
            'prefix' => $this->getParameter('zikula_profile_module.property_prefix'),
            'amountOfRegisteredMembers' => $amountOfUsers - 1,
            'amountOfOnlineMembers' => count($this->getOnlineUids($userSessionRepository)),
            'newestMember' => $userRepository->findBy([], ['registrationDate' => 'DESC'], 1)[0],
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
     * @PermissionCheck({"$_zkModule:Members:recent", "::", "read"})
     * @Template("@ZikulaProfileModule/Members/recent.html.twig")
     *
     * Displays last X registered users.
     */
    public function recentAction(
        PropertyRepositoryInterface $propertyRepository,
        UserRepositoryInterface $userRepository,
        VariableApiInterface $variableApi
    ): array {
        return [
            'prefix' => $this->getParameter('zikula_profile_module.property_prefix'),
            'activeProperties' => $this->getActiveProperties($propertyRepository),
            'users' => $userRepository->findBy([], ['registrationDate' => 'DESC'], $this->getVar('recentmembersitemsperpage')),
            'messageModule' => $variableApi->getSystemVar(SettingsConstant::SYSTEM_VAR_MESSAGE_MODULE, '')
        ];
    }

    /**
     * @Route("/online")
     * @PermissionCheck({"$_zkModule:Members:online", "::", "read"})
     * @Template("@ZikulaProfileModule/Members/online.html.twig")
     *
     * View users online.
     */
    public function onlineAction(
        PropertyRepositoryInterface $propertyRepository,
        UserRepositoryInterface $userRepository,
        UserSessionRepositoryInterface $userSessionRepository
    ): array {
        return [
            'prefix' => $this->getParameter('zikula_profile_module.property_prefix'),
            'activeProperties' => $this->getActiveProperties($propertyRepository),
            'users' => $userRepository->findBy(['uid' => $this->getOnlineUids($userSessionRepository)], [], $this->getVar('onlinemembersitemsperpage'))
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
