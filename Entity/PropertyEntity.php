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
use Zikula\Core\Doctrine\EntityAccess;

/**
 * Property entity class
 *
 * @ORM\Entity
 * @ORM\Table(name="user_property",indexes={@ORM\index(name="prop_label", columns={"label"}),@ORM\index(name="prop_attr", columns={"attributename"})})
 */
class PropertyEntity extends EntityAccess
{
    /**
     * id
     *
     * @ORM\Id
     * @ORM\Column(type="integer",name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $prop_id;
    /**
     * Label
     * @ORM\Column(type="string",name="label")
     */
    private $prop_label = '';
    /**
     * Dtype
     * @ORM\Column(type="integer",name="dtype")
     */
    private $prop_dtype = 0;
    /**
     * Modname
     * @ORM\Column(type="string",length=64,name="modname")
     */
    private $prop_modname = '';
    /**
     * Weight
     * @ORM\Column(type="integer",name="weight")
     */
    private $prop_weight = 0;
    /**
     * Validation
     * @ORM\Column(type="text",nullable=true,name="validation")
     */
    private $prop_validation = null;
    /**
     * Attribute name
     * @ORM\Column(type="string",length=80,name="attributename")
     */
    private $prop_attribute_name = '';

    /**
     * @param string $attributename
     */
    public function setProp_attribute_name($attributename)
    {
        $this->prop_attribute_name = $attributename;
    }

    /**
     * @return string
     */
    public function getProp_attribute_name()
    {
        return $this->prop_attribute_name;
    }

    /**
     * @param integer $dtype
     */
    public function setProp_dtype($dtype = 0)
    {
        $this->prop_dtype = $dtype;
    }

    /**
     * @return integer
     */
    public function getProp_dtype()
    {
        return $this->prop_dtype;
    }

    /**
     * @return integer
     */
    public function getProp_id()
    {
        return $this->prop_id;
    }

    /**
     * @param string $label
     */
    public function setProp_label($label)
    {
        $this->prop_label = $label;
    }

    /**
     * @return string
     */
    public function getProp_label()
    {
        return $this->prop_label;
    }

    /**
     * @param string $modname
     */
    public function setProp_modname($modname)
    {
        $this->prop_modname = $modname;
    }

    /**
     * @return string
     */
    public function getProp_modname()
    {
        return $this->prop_modname;
    }

    /**
     * @param string|null $validation
     */
    public function setProp_validation($validation = null)
    {
        $this->prop_validation = $validation;
    }

    /**
     * @return string
     */
    public function getProp_validation()
    {
        return $this->prop_validation;
    }

    /**
     * @param integer $weight
     */
    public function setProp_weight($weight = 0)
    {
        $this->prop_weight = $weight;
    }

    /**
     * @return integer
     */
    public function getProp_weight()
    {
        return $this->prop_weight;
    }

    public function incrementWeight()
    {
        $this->prop_weight++;
    }

    public function decrementWeight()
    {
        $this->prop_weight--;
    }

}
