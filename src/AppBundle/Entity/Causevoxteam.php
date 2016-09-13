<?php

namespace AppBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @UniqueEntity("name")
 */
class Causevoxteam
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotNull()
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $fundsRaised;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $fundNeeded;

    /**
     * @ORM\ManyToOne(targetEntity="Teacher",inversedBy="causevoxteams")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    private $teacher;

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
     * Set name.
     *
     * @param string $name
     *
     * @return CauseVoxTeam
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
     * Set url.
     *
     * @param string $url
     *
     * @return CauseVoxTeam
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set fundsRaised.
     *
     * @param string $fundsRaised
     *
     * @return CauseVoxTeam
     */
    public function setFundsRaised($fundsRaised)
    {
        $this->fundsRaised = $fundsRaised;

        return $this;
    }

    /**
     * Get fundsRaised.
     *
     * @return string
     */
    public function getFundsRaised()
    {
        return $this->fundsRaised;
    }

    /**
     * Set fundNeeded.
     *
     * @param string $fundNeeded
     *
     * @return CauseVoxTeam
     */
    public function setFundNeeded($fundNeeded)
    {
        $this->fundNeeded = $fundNeeded;

        return $this;
    }

    /**
     * Get fundNeeded.
     *
     * @return string
     */
    public function getFundNeeded()
    {
        return $this->fundNeeded;
    }

    /**
     * Set teacher.
     *
     * @param string $teacher
     *
     * @return CauseVoxTeam
     */
    public function setTeacher($teacher)
    {
        $this->teacher = $teacher;

        return $this;
    }

    /**
     * Get teacher.
     *
     * @return string
     */
    public function getTeacher()
    {
        return $this->teacher;
    }
}
