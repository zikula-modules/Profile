<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Core\Controller\AbstractController;
use Zikula\SettingsModule\SettingsConstant;

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
     * @throws AccessDeniedException on failed permission check
     * @return array
     */
    public function listAction(Request $request)
    {
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
        $users = $this->get('zikula_users_module.user_repository')->query($critera, [$sortBy => $sortOrder], $itemsPerPage, $startNum);
        $amountOfUsers = $this->get('zikula_users_module.user_repository')->count();

        return [
            'prefix' => $this->getParameter('zikula_profile_module.property_prefix'),
            'amountOfRegisteredMembers' => $amountOfUsers - 1,
            'amountOfOnlineMembers' => count($this->getOnlineUids()),
            'newestMember' => $this->get('zikula_users_module.user_repository')->findBy([], ['user_regdate' => 'DESC'], 1)[0],
            'users' => $users,
            'letter' => $letter,
            'sortby' => $sortBy,
            'sortorder' => $sortOrder,
            'activeProperties' => $this->getActiveProperties(),
            'messageModule' => $this->get('zikula_extensions_module.api.variable')->getSystemVar(SettingsConstant::SYSTEM_VAR_MESSAGE_MODULE, ''),
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
     * @throws AccessDeniedException on failed permission check
     * @return array
     */
    public function recentAction()
    {
        if (!$this->hasPermission('ZikulaProfileModule:Members:recent', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }

        return [
            'prefix' => $this->getParameter('zikula_profile_module.property_prefix'),
            'activeProperties' => $this->getActiveProperties(),
            'users' => $this->get('zikula_users_module.user_repository')->findBy([], ['user_regdate' => 'DESC'], $this->getVar('recentmembersitemsperpage')),
            'messageModule' => $this->get('zikula_extensions_module.api.variable')->getSystemVar(SettingsConstant::SYSTEM_VAR_MESSAGE_MODULE, '')
        ];
    }

    /**
     * @Route("/online")
     * @Template("ZikulaProfileModule:Members:online.html.twig")
     *
     * View users online.
     *
     * @throws AccessDeniedException on failed permission check
     * @return array
     */
    public function onlineAction()
    {
        if (!$this->hasPermission('ZikulaProfileModule:Members:online', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }

        return [
            'prefix' => $this->getParameter('zikula_profile_module.property_prefix'),
            'activeProperties' => $this->getActiveProperties(),
            'users' => $this->getDoctrine()->getRepository('ZikulaUsersModule:UserEntity')->findBy(['uid' => $this->getOnlineUids()]),
        ];
    }

    /**
     * Get uids of online users
     * @return array
     */
    private function getOnlineUids()
    {
        $activeMinutes = $this->getVar('activeminutes');
        $activeSince = new \DateTime();
        $activeSince->modify("-$activeMinutes minutes");

        return $this->getDoctrine()->getRepository('ZikulaUsersModule:UserSessionEntity')->getUsersSince($activeSince);
    }

    /**
     * @return array
     */
    private function getActiveProperties()
    {
        $properties = $this->getDoctrine()->getRepository('ZikulaProfileModule:PropertyEntity')->findBy(['active' => true]);
        $activeProperties = [];
        foreach ($properties as $property) {
            $activeProperties[]= $property->getId();
        }

        return $activeProperties;
    }
}
