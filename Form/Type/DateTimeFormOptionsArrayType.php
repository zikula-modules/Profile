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

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;

class DateTimeFormOptionsArrayType extends FormOptionsArrayType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $translator = $options['translator'];

        $builder
            ->add('html5', CheckboxType::class, [
                'label' => $translator->__('Html5'),
                'required' => false,
            ])
            ->add('widget', ChoiceType::class, [
                'label' => $translator->__('Widget'),
                'choices' => [
                    $translator->__('Choice') => 'choice',
                    $translator->__('Text') => 'text',
                    $translator->__('Single Text') => 'single_text',
                ],
                'choices_as_values' => true,
                'data' => 'choice'
            ])
            ->add('input', ChoiceType::class, [
                'label' => $translator->__('Input'),
                'choices' => [
                    $translator->__('String') => 'string',
                    $translator->__('DateTime Object') => 'datetime',
                    $translator->__('Array') => 'array',
                    $translator->__('Timestamp') => 'timestamp',
                ],
                'choices_as_values' => true,
                'data' => 'string'
            ])
            ->add('format', TextType::class, [
                'label' => $translator->__('Format'),
                'help' => $translator->__('e.g. yyyy-MM-dd'),
                'required' => false,
            ])
            ->add('model_timezone', TimezoneType::class, [
//                'data' =>
            ])
            ->add('choices_as_values', HiddenType::class, [ // not needed in Core-2.0
                'data' => true
            ])
        ;
    }
}
