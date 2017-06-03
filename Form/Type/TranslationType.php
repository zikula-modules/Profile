<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Zikula\SettingsModule\Api\ApiInterface\LocaleApiInterface;

class TranslationType extends AbstractType
{
    /**
     * @var LocaleApiInterface
     */
    private $localeApi;

    /**
     * TranslationType constructor.
     * @param LocaleApiInterface $localeApi
     */
    public function __construct(LocaleApiInterface $localeApi)
    {
        $this->localeApi = $localeApi;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['label'] = array_search($view->vars['name'], $this->localeApi->getSupportedLocaleNames());
    }

    public function getParent()
    {
        return TextType::class;
    }
}
