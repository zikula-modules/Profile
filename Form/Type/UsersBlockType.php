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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Zikula\Common\Translator\IdentityTranslator;

class UsersBlockType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translator = $options['translator'];

        $builder
            ->add('ublockon', CheckboxType::class, [
                'label'    => $translator->__('Enable your personal custom block'),
                'required' => false,
            ])
            ->add('ublock', TextareaType::class, [
                'label'    => $translator->__('Content of your custom block'),
                'required' => false,
                'attr'     => [
                    'cols' => 80,
                    'rows' => 10,
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => $translator->__('Save'),
                'icon'  => 'fa-check',
                'attr'  => [
                    'class' => 'btn btn-success',
                ],
            ])
            ->add('cancel', SubmitType::class, [
                'label' => $translator->__('Cancel'),
                'icon'  => 'fa-times',
                'attr'  => [
                    'class'          => 'btn btn-default',
                    'formnovalidate' => 'formnovalidate',
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'zikulaprofilemodule_usersblock';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translator' => new IdentityTranslator(),
        ]);
    }
}
