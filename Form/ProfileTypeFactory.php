<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Form;

use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\ProfileModule\Entity\RepositoryInterface\PropertyRepositoryInterface;
use Zikula\ProfileModule\ProfileConstant;
use Zikula\Bundle\FormExtensionBundle\Form\Type\InlineFormDefinitionType;

class ProfileTypeFactory
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var PropertyRepositoryInterface
     */
    private $propertyRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $prefix;

    /**
     * PropertyTypeFactory constructor.
     * @param FormFactoryInterface $formFactory
     * @param PropertyRepositoryInterface $propertyRepository
     * @param TranslatorInterface $translator
     * @param RequestStack $requestStack
     * @param string $prefix
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        PropertyRepositoryInterface $propertyRepository,
        TranslatorInterface $translator,
        RequestStack $requestStack,
        $prefix
    ) {
        $this->formFactory = $formFactory;
        $this->propertyRepository = $propertyRepository;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->prefix = $prefix;
    }

    /**
     * @param PersistentCollection $attributes
     * @param bool $includeButtons
     * @return FormInterface
     */
    public function createForm(PersistentCollection $attributes, $includeButtons = true)
    {
        $attributeValues = [];
        foreach ($attributes as $attribute) {
            if (0 === strpos($attribute->getName(), $this->prefix)) {
                $attributeValues[$attribute->getName()] = $attribute->getValue();
            }
        }

        $formBuilder = $this->formFactory->createNamedBuilder(ProfileConstant::FORM_BLOCK_PREFIX, FormType::class, $attributeValues, [
            'auto_initialize' => false,
            'error_bubbling' => true,
            'mapped' => false
        ]);
        $formBuilder->add('dynamicFields', InlineFormDefinitionType::class, [
            'dynamicFieldsContainer' => $this->propertyRepository,
            'translator' => $this->translator,
            'label' => false,
            'inherit_data' => true
        ]);

        if ($includeButtons) {
            $formBuilder->add('save', SubmitType::class, [
                'label' => $this->translator->__('Save'),
                'icon' => 'fa-check',
                'attr' => [
                    'class' => 'btn btn-success',
                ],
            ]);
            $formBuilder->add('cancel', SubmitType::class, [
                'label' => $this->translator->__('Cancel'),
                'icon' => 'fa-times',
                'attr' => [
                    'class' => 'btn btn-default',
                ],
            ]);
        }

        return $formBuilder->getForm();
    }
}
