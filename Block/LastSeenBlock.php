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

    /**
     * {@inheritdoc}
     */
    public function display(array $properties)
    {
        $title = !empty($properties['title']) ? $properties['title'] : '';
        if (!$this->hasPermission('ZikulaProfileModule:LastSeenblock:', $title.'::', ACCESS_READ)) {
            return '';
        }

        $sessionsToFile = SecCtrConstant::SESSION_STORAGE_FILE == $this->variableApi->getSystemVar('sessionstoretofile', SecCtrConstant::SESSION_STORAGE_FILE);
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
            'sessions' => $sessions,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormClassName()
    {
        return LastSeenBlockType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormTemplate()
    {
        return '@ZikulaProfileModule/Block/lastSeen_modify.html.twig';
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
