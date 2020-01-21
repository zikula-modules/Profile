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

namespace Zikula\ProfileModule\Block\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MembersOnlineBlockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lengthmax', IntegerType::class, [
                'label' => 'Maximum number of characters to display',
                'empty_data'  => 30,
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('amount', IntegerType::class, [
                'label' => 'Number of users to display',
                'constraints' => [
                    new NotBlank()
                ]
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'zikulaprofilemodule_membersonlineblock';
    }
}
