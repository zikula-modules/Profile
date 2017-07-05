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

use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Zikula\Common\Translator\IdentityTranslator;
use Zikula\Common\Translator\TranslatorInterface;

class AvatarType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

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
     * @param array $modVars
     * @param string $avatarPath
     */
    public function __construct(
        TranslatorInterface $translator,
        $modVars = [],
        $avatarPath = ''
    ) {
        $this->translator = $translator;
        $this->modVars = $modVars;
        $this->avatarPath = $avatarPath;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaults = [
            'translator' => new IdentityTranslator(),
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
                'placeholder' => false,
                'choices_as_values' => true
            ]);
        } else {
            // upload mode
            $defaults['data_class'] = null; // allow string values instead of File objects

            $translator = $this->translator;
            $defaults['help'] = [
                $translator->__('Possible extensions') . ': ' . implode(', ', ['gif', 'jpeg', 'jpg', 'png'/*, 'swf'*/]),
                $translator->__('Max. file size') . ': ' . $this->modVars['maxSize'] . ' ' . $translator->__('bytes'),
                $translator->__('Max. dimensions') . ': ' . $this->modVars['maxWidth'] . 'x' . $this->modVars['maxHeight'] . ' ' . $translator->__('pixels')
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
        $allowUploads = isset($this->modVars['allowUploads']) && true === boolval($this->modVars['allowUploads']);
        if (!$allowUploads) {
            return false;
        }
        if (!file_exists($this->avatarPath) || !is_readable($this->avatarPath) || !is_writable($this->avatarPath)) {
            return false;
        }

        return true;
    }
}
