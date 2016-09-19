<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

// DON'T forget this use statement!!!
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaignawardtype", uniqueConstraints={@ORM\UniqueConstraint(columns={"value"}), @ORM\UniqueConstraint(columns={"display_name"})})
 * @UniqueEntity(fields={"displayName"})
 * @UniqueEntity(fields={"value"})
 */
class Campaignawardtype
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
    private $displayName;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Assert\NotNull()
     */
    private $value;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="Campaignaward", mappedBy="campaignawardtype", cascade={"remove"})
     */
    private $campaignawards;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->campaignawards = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set displayName
     *
     * @param string $displayName
     *
     * @return Campaignawardtype
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Get displayName
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return Campaignawardtype
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Campaignawardtype
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Add campaignaward
     *
     * @param \AppBundle\Entity\Campaignaward $campaignaward
     *
     * @return Campaignawardtype
     */
    public function addCampaignaward(\AppBundle\Entity\Campaignaward $campaignaward)
    {
        $this->campaignawards[] = $campaignaward;

        return $this;
    }

    /**
     * Remove campaignaward
     *
     * @param \AppBundle\Entity\Campaignaward $campaignaward
     */
    public function removeCampaignaward(\AppBundle\Entity\Campaignaward $campaignaward)
    {
        $this->campaignawards->removeElement($campaignaward);
    }

    /**
     * Get campaignawards
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCampaignawards()
    {
        return $this->campaignawards;
    }
}
