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
use Symfony\Contracts\Translation\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;

class MembersOnlineBlockType extends AbstractType
{
    use TranslatorTrait;

    public function __construct(TranslatorInterface $translator)
    {
        $this->setTranslator($translator);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lengthmax', IntegerType::class, [
                'label' => $this->trans('Maximum number of characters to display'),
                'empty_data'  => 30,
                'constraints' => [
                    new NotBlank()
                ],
            ])
            ->add('amount', IntegerType::class, [
                'label' => $this->trans('Number of users to display'),
                'constraints' => [
                    new NotBlank()
                ],
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'zikulaprofilemodule_membersonlineblock';
    }
}
