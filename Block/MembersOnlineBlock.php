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

    public function display(array $properties): string
    {
        $title = !empty($properties['title']) ? $properties['title'] : '';
        if (!$this->hasPermission('ZikulaProfileModule:MembersOnlineblock:', $title . '::', ACCESS_READ)) {
            return '';
        }

        $sessionsToFile = SecCtrConstant::SESSION_STORAGE_FILE === $this->variableApi->getSystemVar('sessionstoretofile', SecCtrConstant::SESSION_STORAGE_FILE);
        if ($sessionsToFile) {
            $sessions = [];
            $guestCount = 0;
        } else {
            $activeMinutes = $this->variableApi->get('ZikulaProfileModule', 'activeminutes');
            $activeSince = new \DateTime();
            $activeSince->modify("-$activeMinutes minutes");

            $criteria = Criteria::create()
                ->where(Criteria::expr()->neq('uid', Constant::USER_ID_ANONYMOUS))
                ->andWhere(Criteria::expr()->neq('uid', null))
                ->andWhere(Criteria::expr()->gt('lastused', $activeSince))
                ->orderBy(['lastused' => 'DESC'])
                //->setMaxResults($properties['amount'])
            ;
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
            'amountOfOnlineGuests' => $guestCount
        ]);
    }

    public function getFormClassName(): string
    {
        return MembersOnlineBlockType::class;
    }

    public function getFormTemplate(): string
    {
        return '@ZikulaProfileModule/Block/membersOnline_modify.html.twig';
    }

    /**
     * @required
     */
    public function setVariableApi(VariableApiInterface $variableApi): void
    {
        $this->variableApi = $variableApi;
    }

    /**
     * @required
     */
    public function setUserSessionRepository(UserSessionRepositoryInterface $userSessionRepository): void
    {
        $this->userSessionRepository = $userSessionRepository;
    }
}
