<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Causevoxfundraiser
 *
 * @ORM\Table(name="causevoxfundraiser", indexes={@ORM\Index(name="IDX_A8B6A1ADCB944F1A", columns={"student_id"})})
 * @ORM\Entity
 */
class Causevoxfundraiser
{
    /**
     * @var integer
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
     * @ORM\ManyToOne(targetEntity="Student")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="student_id", referencedColumnName="id")
     * })
     */
    private $student;


}

