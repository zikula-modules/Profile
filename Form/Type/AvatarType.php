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

use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;

class AvatarType extends AbstractType
{
    use TranslatorTrait;

    /**
     * @var array
     */
    private $modVars;

    /**
     * @var string
     */
    private $avatarPath;

    /**
     * AvatarType constructor.
     *
     * @param TranslatorInterface $translator
     * @param VariableApiInterface $variableApi
     */
    public function __construct(
        TranslatorInterface $translator,
        VariableApiInterface $variableApi
    ) {
        $this->setTranslator($translator);
        $this->modVars = $variableApi->getAll('ZikulaProfileModule');
        $this->avatarPath = $variableApi->get('ZikulaUsersModule', 'avatarpath', 'images/avatar');
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaults = [
            'required' => false
        ];

        if (!$this->allowUploads()) {
            // choice mode
            $choices = [
                'Blank' => 'blank.jpg',
                'Gravatar' => 'gravatar.jpg',
            ];

            if (file_exists($this->avatarPath)) {
                $finder = new Finder();
                $finder->files()->in($this->avatarPath)->notName('blank.jpg')->notName('gravatar.jpg')->sortByName();

                foreach ($finder as $file) {
                    $choices[$file->getFilename()] = $file->getFilename();
                }
            }

            $defaults = array_merge($defaults, [
                'choices' => $choices,
                'attr' => [
                    'class' => 'avatar-selector'
                ],
                'placeholder' => false
            ]);
        } else {
            // upload mode
            $defaults['data_class'] = null; // allow string values instead of File objects

            $defaults['help'] = [
                $this->__('Possible extensions') . ': ' . implode(', ', ['gif', 'jpeg', 'jpg', 'png'/*, 'swf'*/]),
                $this->__('Max. file size') . ': ' . $this->modVars['maxSize'] . ' ' . $this->__('bytes'),
                $this->__('Max. dimensions') . ': ' . $this->modVars['maxWidth'] . 'x' . $this->modVars['maxHeight'] . ' ' . $this->__('pixels')
            ];
        }

        $resolver->setDefaults($defaults);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'zikula_profile_module_avatar';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->allowUploads() ? FileType::class : ChoiceType::class;
    }

    /**
     * Checks if uploads or choices should be used.
     *
     * @return boolean
     */
    private function allowUploads()
    {
        $allowUploads = isset($this->modVars['allowUploads']) && true === (bool) ($this->modVars['allowUploads']);
        if (!$allowUploads) {
            return false;
        }
        if (!file_exists($this->avatarPath) || !is_readable($this->avatarPath) || !is_writable($this->avatarPath)) {
            return false;
        }

        return true;
    }
}
