<?php
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Block\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class FeaturedUserBlockType.
 */
class FeaturedUserBlockType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', 'Symfony\Component\Form\Extension\Core\Type\TextType', [
                'label'       => __('User name'),
                'empty_data'  => '',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('fieldstoshow', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', [
                'label'      => __('Information to show'),
                'label_attr' => [
                    'class' => 'checkbox-inline',
                ],
                'expanded'          => true,
                'multiple'          => true,
                'choices_as_values' => true,
                'choices'           => $options['dudArray'],
            ])
            ->add('showregdate', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', [
                'label'      => __('Show registration date'),
                'empty_data' => false,
                'required'   => false,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'dudArray' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'zikulaprofilemodule_featureduserblock';
    }
}
