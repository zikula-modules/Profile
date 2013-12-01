<?php

/**
 * Copyright Zikula Foundation 2013 - Profile module for Zikula
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/GPLv3 (or at your option, any later version).
 * @package Profile
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

use Zikula\Core\Doctrine\EntityAccess;
use Doctrine\ORM\Mapping as ORM;

/**
 * Property entity class
 *
 * @ORM\Entity
 * @ORM\Table(name="user_property",indexes={@ORM\index(name="prop_label", columns={"label"}),@ORM\index(name="prop_attr", columns={"attributename"})})
 */
class Profile_Entity_Property extends EntityAccess
{

    /**
     * id
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Label
     * @ORM\Column(type="string")
     */
    private $label;

    /**
     * Dtype
     * @ORM\Column(type="integer")
     */
    private $dtype = 0;

    /**
     * Modname
     * @ORM\Column(type="string", length=64)
     */
    private $modname;

    /**
     * Weight
     * @ORM\Column(type="integer")
     */
    private $weight = 0;

    /**
     * Validation
     * @ORM\Column(type="text", nullable=true)
     */
    private $validation = null;

    /**
     * Attribute name
     * @ORM\Column(type="string", length=80)
     */
    private $attributename;

    /**
     * @param string $attributename
     */
    public function setAttributename($attributename)
    {
        $this->attributename = $attributename;
    }

    /**
     * @return string
     */
    public function getAttributename()
    {
        return $this->attributename;
    }

    /**
     * @param integer $dtype
     */
    public function setDtype($dtype = 0)
    {
        $this->dtype = $dtype;
    }

    /**
     * @return integer
     */
    public function getDtype()
    {
        return $this->dtype;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $modname
     */
    public function setModname($modname)
    {
        $this->modname = $modname;
    }

    /**
     * @return string
     */
    public function getModname()
    {
        return $this->modname;
    }

    /**
     * @param string|null $validation
     */
    public function setValidation($validation = null)
    {
        $this->validation = $validation;
    }

    /**
     * @return string
     */
    public function getValidation()
    {
        return $this->validation;
    }

    /**
     * @param integer $weight
     */
    public function setWeight($weight = 0)
    {
        $this->weight = $weight;
    }

    /**
     * @return integer
     */
    public function getWeight()
    {
        return $this->weight;
    }

}
