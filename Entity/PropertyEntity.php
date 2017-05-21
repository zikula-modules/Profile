<?php
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Zikula\Core\Doctrine\EntityAccess;

/**
 * Property entity class.
 *
 * @ORM\Entity(repositoryClass="Zikula\ProfileModule\Entity\Repository\PropertyRepository")
 * @ORM\Table(name="user_property")
 * @UniqueEntity("id")
 */
class PropertyEntity extends EntityAccess
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
     * @ORM\Column(type="string")
     * @param string
     * @Assert\NotBlank()
     */
    private $label = '';

    /**
     * @ORM\Column(type="text")
     * @param string
     * @Assert\NotBlank()
     */
    private $formType = '';

    /**
     * @ORM\Column(type="array")
     * @param array
     * @Assert\NotBlank()
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

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getFormType()
    {
        return $this->formType;
    }

    public function setFormType($formType)
    {
        $this->formType = $formType;
    }

    public function getFormOptions()
    {
        if (!isset($this->formOptions['required'])) {
            $this->formOptions['required'] = false;
        }

        return $this->formOptions;
    }

    public function setFormOptions(array $formOptions)
    {
        $this->formOptions = $formOptions;
    }

    public function getWeight()
    {
        return $this->weight;
    }

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

    public function getActive()
    {
        return $this->active;
    }

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
}
