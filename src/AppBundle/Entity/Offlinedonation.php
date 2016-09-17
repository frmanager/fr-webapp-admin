<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
// DON'T forget this use statement!!!
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Offlinedonation.
 *
 * @ORM\Entity
 * @ORM\Table(name="0fflinedonation",uniqueConstraints={@ORM\UniqueConstraint(columns={"teacher_id", "student_id", "donated_at"})})
 * @UniqueEntity(
 *     fields={"teacher", "student", "donatedAt"},
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
     * @var Teacher
     *
     * @ORM\ManyToOne(targetEntity="Teacher", inversedBy="offlinedonations")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $teacher;

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
     */
    private $amount;

    /**
     * @var datetime
     *
     * @ORM\Column(name="donated_at", type="datetime", nullable=false)
     */
    private $donatedAt;

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
     * Set amount
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
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set donatedAt
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
     * Get donatedAt
     *
     * @return \DateTime
     */
    public function getDonatedAt()
    {
        return $this->donatedAt;
    }

    /**
     * Set teacher
     *
     * @param \AppBundle\Entity\Teacher $teacher
     *
     * @return Offlinedonation
     */
    public function setTeacher(\AppBundle\Entity\Teacher $teacher = null)
    {
        $this->teacher = $teacher;

        return $this;
    }

    /**
     * Get teacher
     *
     * @return \AppBundle\Entity\Teacher
     */
    public function getTeacher()
    {
        return $this->teacher;
    }

    /**
     * Set student
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
     * Get student
     *
     * @return \AppBundle\Entity\Student
     */
    public function getStudent()
    {
        return $this->student;
    }
}
