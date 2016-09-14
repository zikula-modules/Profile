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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('viewregdate', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', [
                'label' => $translator->__('Display the user\'s registration date'),
                'required' => false
            ])
            ->add('memberslistitemsperpage', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', [
                'label' => $translator->__('Users per page in \'Registered users list\''),
                'empty_data' => 20,
                'scale' => 0,
                'max_length' => 3
            ])
            ->add('onlinemembersitemsperpage', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', [
                'label' => $translator->__('Users per page in \'Users currently on-line\' page'),
                'empty_data' => 20,
                'scale' => 0,
                'max_length' => 3
            ])
            ->add('recentmembersitemsperpage', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', [
                'label' => $translator->__('Users per page in \'Recent registrations\' page'),
                'empty_data' => 20,
                'scale' => 0,
                'max_length' => 3
            ])
            ->add('filterunverified', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', [
                'label' => $translator->__('Filter unverified users from \'Registered users list\''),
                'empty_data' => '1',
                'choices' => [
                    $translator->__('Yes') => '1',
                    $translator->__('No') => '0'
                ],
                'choices_as_values' => true,
                'required' => false,
                'expanded' => false,
                'multiple' => false
            ])
            ->add('save', 'Symfony\Component\Form\Extension\Core\Type\SubmitType', [
                'label' => $translator->__('Save'),
                'icon' => 'fa-check',
                'attr' => [
                    'class' => 'btn btn-success'
                ]
            ])
            ->add('cancel', 'Symfony\Component\Form\Extension\Core\Type\SubmitType', [
                'label' => $translator->__('Cancel'),
                'icon' => 'fa-times',
                'attr' => [
                    'class' => 'btn btn-default'
                ]
            ])
        ;
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
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translator' => null
        ]);
    }
}
