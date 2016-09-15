<?php

namespace AppBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @UniqueEntity(fields={"name", "teacher"},
 *     errorPath="name",
 *     message="This teacher already has a student by this name."
 * )
 */
class Student
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Assert\NotNull()
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Teacher",inversedBy="students", cascade={"remove"})
     * @ORM\JoinColumn(name="teacher_id", referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotNull()
     */
    private $teacher;

    /**
     * @ORM\OneToMany(targetEntity="Causevoxfundraiser", mappedBy="student", cascade={"all"})
     */
    private $causevoxfundraisers;

    /**
     * @ORM\OneToMany(targetEntity="Causevoxdonation", mappedBy="student", cascade={"all"})
     */
    private $causevoxdonations;

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
     * Get id.
     *
     * @return array
     */
    public function getGrades()
    {
        return $this;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Student
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
     * Set teacher.
     *
     * @param string $teacher
     *
     * @return Student
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

    /**
     * Set grade.
     *
     * @param string $grade
     *
     * @return Student
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;

        return $this;
    }

    /**
     * Get grade.
     *
     * @return string
     */
    public function getGrade()
    {
        return $this->grade;
    }
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->causevoxfundraiser = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add causevoxfundraiser.
     *
     * @param \AppBundle\Entity\Causevoxfundraiser $causevoxfundraiser
     *
     * @return Student
     */
    public function addCausevoxfundraiser(\AppBundle\Entity\Causevoxfundraiser $causevoxfundraiser)
    {
        $this->causevoxfundraiser[] = $causevoxfundraiser;

        return $this;
    }

    /**
     * Remove causevoxfundraiser.
     *
     * @param \AppBundle\Entity\Causevoxfundraiser $causevoxfundraiser
     */
    public function removeCausevoxfundraiser(\AppBundle\Entity\Causevoxfundraiser $causevoxfundraiser)
    {
        $this->causevoxfundraiser->removeElement($causevoxfundraiser);
    }

    /**
     * Get causevoxfundraiser.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCausevoxfundraiser()
    {
        return $this->causevoxfundraiser;
    }

    /**
     * Add causevoxdonation.
     *
     * @param \AppBundle\Entity\Causevoxdonation $causevoxdonation
     *
     * @return Student
     */
    public function addCausevoxdonation(\AppBundle\Entity\Causevoxdonation $causevoxdonation)
    {
        $this->causevoxdonation[] = $causevoxdonation;

        return $this;
    }

    /**
     * Remove causevoxdonation.
     *
     * @param \AppBundle\Entity\Causevoxdonation $causevoxdonation
     */
    public function removeCausevoxdonation(\AppBundle\Entity\Causevoxdonation $causevoxdonation)
    {
        $this->causevoxdonation->removeElement($causevoxdonation);
    }

    /**
     * Get causevoxdonation.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCausevoxdonation()
    {
        return $this->causevoxdonation;
    }
}
