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

namespace Zikula\ProfileModule\Block\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class LastSeenBlockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('amount', IntegerType::class, [
                'label' => 'Number of recent visitors to display',
                'empty_data'  => 5,
                'constraints' => [
                    new NotBlank()
                ]
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'zikulaprofilemodule_lastseenblock';
    }
}
