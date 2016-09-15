<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Causevoxfundraiser.
 *
 * @ORM\Table(name="causevoxfundraiser", indexes={@ORM\Index(name="IDX_A8B6A1ADCB944F1A", columns={"student_id"})})
 * @ORM\Entity
 */
class Causevoxfundraiser
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=100, nullable=false)
     */
    private $url;

    /**
     * @var float
     *
     * @ORM\Column(name="funds_raised", type="float", precision=10, scale=0, nullable=true)
     */
    private $fundsRaised;

    /**
     * @var float
     *
     * @ORM\Column(name="funds_needed", type="float", precision=10, scale=0, nullable=true)
     */
    private $fundsNeeded;

    /**
     * @var \Student
     *
     * @ORM\ManyToOne(targetEntity="Student", inversedBy="causevoxfundraisers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="student_id", referencedColumnName="id")
     * })
     */
    private $student;

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
     * Set email.
     *
     * @param string $email
     *
     * @return Causevoxfundraiser
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return Causevoxfundraiser
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
     * @param float $fundsRaised
     *
     * @return Causevoxfundraiser
     */
    public function setFundsRaised($fundsRaised)
    {
        $this->fundsRaised = $fundsRaised;

        return $this;
    }

    /**
     * Get fundsRaised.
     *
     * @return float
     */
    public function getFundsRaised()
    {
        return $this->fundsRaised;
    }

    /**
     * Set fundsNeeded.
     *
     * @param float $fundsNeeded
     *
     * @return Causevoxfundraiser
     */
    public function setFundsNeeded($fundsNeeded)
    {
        $this->fundsNeeded = $fundsNeeded;

        return $this;
    }

    /**
     * Get fundsNeeded.
     *
     * @return float
     */
    public function getFundsNeeded()
    {
        return $this->fundsNeeded;
    }

    /**
     * Set student.
     *
     * @param \AppBundle\Entity\Student $student
     *
     * @return Causevoxfundraiser
     */
    public function setStudent(\AppBundle\Entity\Student $student = null)
    {
        $this->student = $student;

        return $this;
    }

    /**
     * Get student.
     *
     * @return \AppBundle\Entity\Student
     */
    public function getStudent()
    {
        return $this->student;
    }
}
