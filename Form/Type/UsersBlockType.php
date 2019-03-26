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

namespace Zikula\ProfileModule\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;

class UsersBlockType extends AbstractType
{
    use TranslatorTrait;

    public function __construct(TranslatorInterface $translator)
    {
        $this->setTranslator($translator);
    }

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('ublockon', CheckboxType::class, [
                'label'    => $this->__('Enable your personal custom block'),
                'required' => false,
            ])
            ->add('ublock', TextareaType::class, [
                'label'    => $this->__('Content of your custom block'),
                'required' => false,
                'attr'     => [
                    'cols' => 80,
                    'rows' => 10,
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => $this->__('Save'),
                'icon'  => 'fa-check',
                'attr'  => [
                    'class' => 'btn btn-success',
                ],
            ])
            ->add('cancel', SubmitType::class, [
                'label' => $this->__('Cancel'),
                'icon'  => 'fa-times',
                'attr'  => [
                    'class'          => 'btn btn-default',
                    'formnovalidate' => 'formnovalidate',
                ],
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'zikulaprofilemodule_usersblock';
    }
}
