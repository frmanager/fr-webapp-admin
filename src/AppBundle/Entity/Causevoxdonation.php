<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
// DON'T forget this use statement!!!
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Causevoxdonation.
 *
 * @ORM\Entity
 * @ORM\Table(name="causevoxdonation",uniqueConstraints={@ORM\UniqueConstraint(columns={"donated_at", "teacher_id", "student_id", "donated_at"})})
 * @UniqueEntity(
 *     fields={"teacher", "student", "donatedAt", "donorEmail", "type"},
 *     errorPath="donorEmail",
 *     message="Already received a donation from this donor on this day...."
 * )
 */
class Causevoxdonation
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
     * @ORM\ManyToOne(targetEntity="Teacher", inversedBy="causevoxdonations")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $teacher;

    /**
     * @var Student
     *
     * @ORM\ManyToOne(targetEntity="Student", inversedBy="causevoxdonations")
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
     * @var float
     *
     * @ORM\Column(name="tip", type="float", precision=10, scale=2, nullable=false)
     */
    private $tip;

    /**
     * @var float
     *
     * @ORM\Column(name="estimated_cc_fee", type="float", precision=10, scale=2, nullable=false)
     */
    private $estimatedCcFee;

    /**
     * @var float
     *
     * @ORM\Column(name="causevox_fee", type="float", precision=10, scale=2, nullable=false)
     */
    private $causevoxFee;

    /**
     * @var string
     *
     * @ORM\Column(name="donation_page", type="string", length=100, nullable=false)
     */
    private $donationPage;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=100, nullable=false)
     */
    private $type;

    /**
     * @var datetime
     *
     * @ORM\Column(name="donated_at", type="datetime", nullable=false)
     * @Assert\Date()
     */
    private $donatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="donor_email", type="string", length=100, nullable=true)
     */
    private $donorEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="donor_comment", type="string", length=255, nullable=true)
     */
    private $donorComment;

    /**
     * @var string
     *
     * @ORM\Column(name="donor_first_name", type="string", length=100, nullable=true)
     */
    private $donorFirstName;

    /**
     * @var string
     *
     * @ORM\Column(name="donor_last_name", type="string", length=100, nullable=true)
     */
    private $donorLastName;

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
     * @return Causevoxdonation
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
     * Set tip.
     *
     * @param float $tip
     *
     * @return Causevoxdonation
     */
    public function setTip($tip)
    {
        $this->tip = $tip;

        return $this;
    }

    /**
     * Get tip.
     *
     * @return float
     */
    public function getTip()
    {
        return $this->tip;
    }

    /**
     * Set estimatedCcFee.
     *
     * @param float $estimatedCcFee
     *
     * @return Causevoxdonation
     */
    public function setEstimatedCcFee($estimatedCcFee)
    {
        $this->estimatedCcFee = $estimatedCcFee;

        return $this;
    }

    /**
     * Get estimatedCcFee.
     *
     * @return float
     */
    public function getEstimatedCcFee()
    {
        return $this->estimatedCcFee;
    }

    /**
     * Set causevoxFee.
     *
     * @param float $causevoxFee
     *
     * @return Causevoxdonation
     */
    public function setCausevoxFee($causevoxFee)
    {
        $this->causevoxFee = $causevoxFee;

        return $this;
    }

    /**
     * Get causevoxFee.
     *
     * @return float
     */
    public function getCausevoxFee()
    {
        return $this->causevoxFee;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return Causevoxdonation
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set donatedAt.
     *
     * @param \DateTime $donatedAt
     *
     * @return Causevoxdonation
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
     * Set donorEmail.
     *
     * @param string $donorEmail
     *
     * @return Causevoxdonation
     */
    public function setDonorEmail($donorEmail)
    {
        $this->donorEmail = $donorEmail;

        return $this;
    }

    /**
     * Get donorEmail.
     *
     * @return string
     */
    public function getDonorEmail()
    {
        return $this->donorEmail;
    }

    /**
     * Set donorComment.
     *
     * @param string $donorComment
     *
     * @return Causevoxdonation
     */
    public function setDonorComment($donorComment)
    {
        $this->donorComment = $donorComment;

        return $this;
    }

    /**
     * Get donorComment.
     *
     * @return string
     */
    public function getDonorComment()
    {
        return $this->donorComment;
    }

    /**
     * Set donorFirstName.
     *
     * @param string $donorFirstName
     *
     * @return Causevoxdonation
     */
    public function setDonorFirstName($donorFirstName)
    {
        $this->donorFirstName = $donorFirstName;

        return $this;
    }

    /**
     * Get donorFirstName.
     *
     * @return string
     */
    public function getDonorFirstName()
    {
        return $this->donorFirstName;
    }

    /**
     * Set donorLastName.
     *
     * @param string $donorLastName
     *
     * @return Causevoxdonation
     */
    public function setDonorLastName($donorLastName)
    {
        $this->donorLastName = $donorLastName;

        return $this;
    }

    /**
     * Get donorLastName.
     *
     * @return string
     */
    public function getDonorLastName()
    {
        return $this->donorLastName;
    }

    /**
     * Set teacher.
     *
     * @param \AppBundle\Entity\Teacher $teacher
     *
     * @return Causevoxdonation
     */
    public function setTeacher(\AppBundle\Entity\Teacher $teacher = null)
    {
        $this->teacher = $teacher;

        return $this;
    }

    /**
     * Get teacher.
     *
     * @return \AppBundle\Entity\Teacher
     */
    public function getTeacher()
    {
        return $this->teacher;
    }

    /**
     * Set student.
     *
     * @param \AppBundle\Entity\Student $student
     *
     * @return Causevoxdonation
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

    /**
     * Set donationPage.
     *
     * @param string $donationPage
     *
     * @return Causevoxdonation
     */
    public function setDonationPage($donationPage)
    {
        $this->donationPage = $donationPage;

        return $this;
    }

    /**
     * Get donationPage.
     *
     * @return string
     */
    public function getDonationPage()
    {
        return $this->donationPage;
    }
}
