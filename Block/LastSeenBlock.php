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

namespace Zikula\ProfileModule\Block;

use Doctrine\Common\Collections\Criteria;
use Zikula\BlocksModule\AbstractBlockHandler;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\ProfileModule\Block\Form\Type\LastSeenBlockType;
use Zikula\SecurityCenterModule\Constant as SecCtrConstant;
use Zikula\UsersModule\Constant;
use Zikula\UsersModule\Entity\RepositoryInterface\UserSessionRepositoryInterface;

class LastSeenBlock extends AbstractBlockHandler
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
        if (!$this->hasPermission('ZikulaProfileModule:LastSeenblock:', $title.'::', ACCESS_READ)) {
            return '';
        }

        $sessionsToFile = SecCtrConstant::SESSION_STORAGE_FILE === $this->variableApi->getSystemVar('sessionstoretofile', SecCtrConstant::SESSION_STORAGE_FILE);
        if ($sessionsToFile) {
            $sessions = [];
        } else {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->neq('uid', Constant::USER_ID_ANONYMOUS))
                ->andWhere(Criteria::expr()->neq('uid', null))
                ->orderBy(['lastused' => 'DESC'])
                ->setMaxResults($properties['amount']);
            $sessions = $this->userSessionRepository->matching($criteria);
        }

        return $this->renderView('@ZikulaProfileModule/Block/lastSeen.html.twig', [
            'sessionsToFile' => $sessionsToFile,
            'sessions' => $sessions
        ]);
    }

    public function getFormClassName(): string
    {
        return LastSeenBlockType::class;
    }

    public function getFormTemplate(): string
    {
        return '@ZikulaProfileModule/Block/lastSeen_modify.html.twig';
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
