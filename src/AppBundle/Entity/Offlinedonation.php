<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
// DON'T forget this use statement!!!
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Offlinedonation.
 *
 * @ORM\Entity
 * @ORM\Table(name="0fflinedonation",uniqueConstraints={@ORM\UniqueConstraint(columns={"donated_at", "student_id", "donated_at"})})
 * @UniqueEntity(
 *     fields={"student", "donatedAt"},
 *     errorPath="student",
 *     message="Already received a donation from this student on this day...."
 * )
 */
class Offlinedonation
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
     * @var Student
     *
     * @ORM\ManyToOne(targetEntity="Student", inversedBy="offlinedonations")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $student;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float", precision=10, scale=2, nullable=false)
     * @Assert\Type(
     *     type="float",
     *     message="The value {{ value }} is not a valid {{ type }}."
     * )
     */
    private $amount;

    /**
     * @var datetime
     *
     * @ORM\Column(name="donated_at", type="datetime", nullable=false)
     * @Assert\Date()
     */
    private $donatedAt;

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
     * Set amount.
     *
     * @param float $amount
     *
     * @return Offlinedonation
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
     * Set donatedAt.
     *
     * @param \DateTime $donatedAt
     *
     * @return Offlinedonation
     */
    public function setDonatedAt($donatedAt)
    {
        $this->donatedAt = $donatedAt;

        return $this;
    }

    /**
     * Get donatedAt.
     *
     * @return \DateTime
     */
    public function getDonatedAt()
    {
        return $this->donatedAt;
    }

    /**
     * Set student.
     *
     * @param \AppBundle\Entity\Student $student
     *
     * @return Offlinedonation
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
