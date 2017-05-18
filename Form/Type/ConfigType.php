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
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;
use Zikula\Common\Translator\IdentityTranslator;

/**
 * Configuration form type class.
 */
class ConfigType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translator = $options['translator'];

        $builder
            ->add('viewregdate', CheckboxType::class, [
                'label' => $translator->__('Display the user\'s registration date'),
                'required' => false
            ])
            ->add('memberslistitemsperpage', IntegerType::class, [
                'constraints' => [new Range(['min' => 10, 'max' => 999])],
                'label' => $translator->__('Users per page in \'Registered users list\''),
                'scale' => 0,
                'attr' => [
                    'maxlength' => 3
                ]
            ])
            ->add('onlinemembersitemsperpage', IntegerType::class, [
                'constraints' => [new Range(['min' => 10, 'max' => 999])],
                'label' => $translator->__('Users per page in \'Users currently on-line\' page'),
                'scale' => 0,
                'attr' => [
                    'maxlength' => 3
                ]
            ])
            ->add('recentmembersitemsperpage', IntegerType::class, [
                'constraints' => [new Range(['min' => 10, 'max' => 999])],
                'label' => $translator->__('Users per page in \'Recent registrations\' page'),
                'scale' => 0,
                'attr' => [
                    'maxlength' => 3
                ]
            ])
            ->add('activeminutes', IntegerType::class, [
                'constraints' => [new Range(['min' => 1, 'max' => 99])],
                'label' => $translator->__('Minutes a user is considered online'),
                'scale' => 0,
                'attr' => [
                    'maxlength' => 2
                ]
            ])
            ->add('filterunverified', CheckboxType::class, [
                'label' => $translator->__('Filter unverified users from \'Registered users list\''),
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => $translator->__('Save'),
                'icon' => 'fa-check',
                'attr' => [
                    'class' => 'btn btn-success',
                ],
            ])
            ->add('cancel', SubmitType::class, [
                'label' => $translator->__('Cancel'),
                'icon' => 'fa-times',
                'attr' => [
                    'class' => 'btn btn-default',
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'zikulaprofilemodule_config';
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
