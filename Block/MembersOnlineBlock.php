<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Block;

use Doctrine\Common\Collections\Criteria;
use Zikula\BlocksModule\AbstractBlockHandler;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\ProfileModule\Block\Form\Type\MembersOnlineBlockType;
use Zikula\SecurityCenterModule\Constant as SecCtrConstant;
use Zikula\SettingsModule\SettingsConstant;
use Zikula\UsersModule\Constant;
use Zikula\UsersModule\Entity\RepositoryInterface\UserSessionRepositoryInterface;

class MembersOnlineBlock extends AbstractBlockHandler
{
    /**
     * @var VariableApiInterface
     */
    private $variableApi;

    /**
     * @var UserSessionRepositoryInterface
     */
    private $userSessionRepository;

    /**
     * {@inheritdoc}
     */
    public function display(array $properties)
    {
        $title = !empty($properties['title']) ? $properties['title'] : '';
        if (!$this->hasPermission('ZikulaProfileModule:MembersOnlineblock:', $title . '::', ACCESS_READ)) {
            return '';
        }

        $sessionsToFile = SecCtrConstant::SESSION_STORAGE_FILE == $this->variableApi->getSystemVar('sessionstoretofile', SecCtrConstant::SESSION_STORAGE_FILE);
        if ($sessionsToFile) {
            $sessions = [];
            $guestCount = 0;
        } else {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->neq('uid', Constant::USER_ID_ANONYMOUS))
                ->andWhere(Criteria::expr()->neq('uid', null))
                ->orderBy(['lastused' => 'DESC'])
                ->setMaxResults($properties['amount']);
            $sessions = $this->userSessionRepository->matching($criteria);
            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq('uid', Constant::USER_ID_ANONYMOUS));
            $guestCount = $this->userSessionRepository->matching($criteria)->count();
        }

        return $this->renderView('@ZikulaProfileModule/Block/membersOnline.html.twig', [
            'sessionsToFile' => $sessionsToFile,
            'sessions' => $sessions,
            'maxLength' => $properties['lengthmax'],
            'messageModule' => $this->variableApi->getSystemVar(SettingsConstant::SYSTEM_VAR_MESSAGE_MODULE, ''),
            'amountOfOnlineGuests' => (int) $guestCount,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormClassName()
    {
        return MembersOnlineBlockType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormTemplate()
    {
        return '@ZikulaProfileModule/Block/membersOnline_modify.html.twig';
    }

    /**
     * @required
     * @param VariableApiInterface $variableApi
     */
    public function setVariableApi(VariableApiInterface $variableApi)
    {
        $this->variableApi = $variableApi;
    }

    /**
     * @required
     * @param UserSessionRepositoryInterface $userSessionRepository
     */
    public function setUserSessionRepository(UserSessionRepositoryInterface $userSessionRepository)
    {
        $this->userSessionRepository = $userSessionRepository;
    }
}
