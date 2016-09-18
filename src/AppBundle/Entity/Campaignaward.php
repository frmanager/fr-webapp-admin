<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaignaward",uniqueConstraints={@ORM\UniqueConstraint(columns={"award_type","award_style", "place", "amount"})})
 */
class Campaignaward
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Assert\NotNull()
     */
    private $awardType;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Assert\NotNull()
     */
    private $awardStyle;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Assert\NotNull()
     */
    private $name;

    /**
     * @ORM\Column(type="integer", length=100, nullable=true)
     */
    private $place;

    /**
     * @ORM\Column(type="float", length=100, nullable=true)
     */
    private $amount;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return Campaignawards
     */
    public function setAwardType($awardType)
    {
        $this->awardType = $awardType;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getAwardType()
    {
        return $this->awardType;
    }

    /**
     * Set awardStyle.
     *
     * @param string $awardStyle
     *
     * @return Campaignawards
     */
    public function setAwardStyle($awardStyle)
    {
        $this->awardStyle = $awardStyle;

        return $this;
    }

    /**
     * Get awardStyle.
     *
     * @return string
     */
    public function getAwardStyle()
    {
        return $this->awardStyle;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Campaignawards
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set place.
     *
     * @param int $place
     *
     * @return Campaignawards
     */
    public function setPlace($place)
    {
        $this->place = $place;

        return $this;
    }

    /**
     * Get place.
     *
     * @return int
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Set amount.
     *
     * @param float $amount
     *
     * @return Campaignawards
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount.
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Campaignawards
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
