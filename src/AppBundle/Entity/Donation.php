<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
// DON'T forget this use statement!!!
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * donation.
 *
 * @ORM\Entity
 * @ORM\Table(name="donation",uniqueConstraints={@ORM\UniqueConstraint(columns={"donated_at", "student_id", "donor_email"})})
 * @UniqueEntity(
 *     fields={"student", "donatedAt", "donorEmail"},
 *     errorPath="student",
 *     message="Already received a donation from this student on this day...."
 * )
 */
class Donation
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
     * @ORM\ManyToOne(targetEntity="Student", inversedBy="donations")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotNull()
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
     * @Assert\NotNull()
     */
    private $amount;

    /**
     * @var datetime
     *
     * @ORM\Column(name="donated_at", type="datetime", nullable=false)
     * @Assert\Date()
     * @Assert\NotNull()
     */
    private $donatedAt;

    /**
     * @var float
     *
     * @ORM\Column(name="tip", type="float", precision=10, scale=2, nullable=true)
     */
    private $tip;

    /**
     * @var float
     *
     * @ORM\Column(name="estimated_cc_fee", type="float", precision=10, scale=2, nullable=true)
     */
    private $estimatedCcFee;

    /**
     * @var float
     *
     * @ORM\Column(name="causevox_fee", type="float", precision=10, scale=2, nullable=true)
     */
    private $causevoxFee;

    /**
     * @var string
     *
     * @ORM\Column(name="donation_page", type="string", length=100, nullable=true)
     */
    private $donationPage;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=100, nullable=true)
     */
    private $type;

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
     * @return Donation
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
     * @return Donation
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
     * Set tip.
     *
     * @param float $tip
     *
     * @return Donation
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
     * @return Donation
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
     * @return Donation
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
     * Set donationPage.
     *
     * @param string $donationPage
     *
     * @return Donation
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

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return Donation
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
     * Set donorEmail.
     *
     * @param string $donorEmail
     *
     * @return Donation
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
     * @return Donation
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
     * @return Donation
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
     * @return Donation
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
     * Set student.
     *
     * @param \AppBundle\Entity\Student $student
     *
     * @return Donation
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
