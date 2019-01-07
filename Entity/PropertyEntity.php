<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Zikula\Bundle\FormExtensionBundle\DynamicFieldInterface;
use Zikula\Core\Doctrine\EntityAccess;

/**
 * @ORM\Entity(repositoryClass="Zikula\ProfileModule\Entity\Repository\PropertyRepository")
 * @ORM\Table(name="user_property")
 * @UniqueEntity("id")
 */
class PropertyEntity extends EntityAccess implements DynamicFieldInterface
{
    /**
     * Note this value is NOT auto-generated and must be manually created!
     * @ORM\Id
     * @ORM\Column(type="string", unique=true)
     * @param string
     * @Assert\Regex("/^[a-zA-Z0-9\-\_]+$/")
     */
    private $id;

    /**
     * @ORM\Column(type="array")
     * @param string
     * @Assert\NotNull()
     */
    private $labels = [];

    /**
     * @ORM\Column(type="text")
     * @param string
     * @Assert\NotBlank()
     */
    private $formType = '';

    /**
     * @ORM\Column(type="array")
     * @param array
     * @Assert\NotNull()
     */
    private $formOptions = [];

    /**
     * @ORM\Column(type="integer")
     * @param integer
     * @Assert\GreaterThan(0)
     */
    private $weight = 0;

    /**
     * @ORM\Column(type="boolean")
     * @param boolean
     */
    private $active = true;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return array
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @return string
     */
    public function getLabel($locale = '', $default = 'en')
    {
        if (!empty($locale) && isset($this->labels[$locale])) {
            return $this->labels[$locale];
        }
        if (!empty($default) && isset($this->labels[$default])) {
            return $this->labels[$default];
        }
        $values = array_values($this->labels);

        return !empty($values[0]) ? $values[0] : $this->id;
    }

    /**
     * @param array $labels
     */
    public function setLabels(array $labels)
    {
        $this->labels = $labels;
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return $this->formType;
    }

    /**
     * @return string $formType
     */
    public function setFormType($formType)
    {
        $this->formType = $formType;
    }

    /**
     * @return array
     */
    public function getFormOptions()
    {
        if (!isset($this->formOptions['required'])) {
            $this->formOptions['required'] = false;
        }

        return $this->formOptions;
    }

    /**
     * @param array $formOptions
     */
    public function setFormOptions(array $formOptions)
    {
        $this->formOptions = $formOptions;
    }

    /**
     * @return array
     */
    public function getFieldInfo()
    {
        return [
            'formType' => $this->getFormType(),
            'formOptions' => $this->getFormOptions()
        ];
    }

    /**
     * @param array $fieldInfo
     */
    public function setFieldInfo(array $fieldInfo)
    {
        $this->setFormType($fieldInfo['formType']);
        $this->setFormOptions($fieldInfo['formOptions']);
    }

    /**
     * @return integer
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param integer $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    public function incrementWeight()
    {
        $this->weight++;
    }

    public function decrementWeight()
    {
        $this->weight--;
    }

    /**
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getId();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getId();
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return 'zpmpp';
    }

    /**
     * @return array
     */
    public function getGroupNames()
    {
        return [];
    }
}
