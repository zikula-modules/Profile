<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Block;

use Doctrine\Common\Collections\Criteria;
use Zikula\BlocksModule\AbstractBlockHandler;
use Zikula\ProfileModule\Block\Form\Type\LastSeenBlockType;
use Zikula\SecurityCenterModule\Constant as SecCtrConstant;
use Zikula\UsersModule\Constant;

class LastSeenBlock extends AbstractBlockHandler
{
    /**
     * {@inheritdoc}
     */
    public function display(array $properties)
    {
        $title = !empty($properties['title']) ? $properties['title'] : '';
        if (!$this->hasPermission('ZikulaProfileModule:LastSeenblock:', $title.'::', ACCESS_READ)) {
            return '';
        }

        $sessionsToFile = SecCtrConstant::SESSION_STORAGE_FILE == $this->get('zikula_extensions_module.api.variable')->getSystemVar('sessionstoretofile', SecCtrConstant::SESSION_STORAGE_FILE);
        if ($sessionsToFile) {
            $sessions = [];
        } else {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->neq('uid', Constant::USER_ID_ANONYMOUS))
                ->andWhere(Criteria::expr()->neq('uid', null))
                ->orderBy(['lastused' => 'DESC'])
                ->setMaxResults($properties['amount']);
            $sessions = $this->get('zikula_users_module.user_session_repository')->matching($criteria);
        }

        return $this->renderView('@ZikulaProfileModule/Block/lastSeen.html.twig', [
            'sessionsToFile' => $sessionsToFile,
            'sessions' => $sessions,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions()
    {
        return [
            'translator' => $this->get('translator.default'),
        ];
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
}
