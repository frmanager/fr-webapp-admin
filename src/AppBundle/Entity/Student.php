<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

// DON'T forget this use statement!!!
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="student",uniqueConstraints={@ORM\UniqueConstraint(columns={"name", "teacher_id"})})
 * @UniqueEntity(
 *     fields={"name", "teacher"},
 *     errorPath="name",
 *     message="Duplicate Student for Identified Teacher"
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
     * @var Teacher
     *
     * @ORM\ManyToOne(targetEntity="Teacher", inversedBy="students")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotNull()
     */
    private $teacher;

    /**
     * @ORM\OneToMany(targetEntity="Causevoxfundraiser", mappedBy="student", cascade={"remove"})
     */
    private $causevoxfundraisers;

    /**
     * @ORM\OneToMany(targetEntity="Causevoxdonation", mappedBy="student", cascade={"remove"})
     */
    private $causevoxdonations;

    /**
     * @ORM\OneToMany(targetEntity="Offlinedonation", mappedBy="student", cascade={"remove"})
     */
    private $offlinedonations;

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
        $this->causevoxfundraisers = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add causevoxfundraiser.
     *
     * @param \AppBundle\Entity\Causevoxfundraiser $causevoxfundraisers
     *
     * @return Student
     */
    public function addCausevoxfundraisers(\AppBundle\Entity\Causevoxfundraiser $causevoxfundraisers)
    {
        $this->causevoxfundraisers[] = $causevoxfundraisers;

        return $this;
    }

    /**
     * Remove causevoxfundraisers.
     *
     * @param \AppBundle\Entity\Causevoxfundraiser $causevoxfundraisers
     */
    public function removeCausevoxfundraisers(\AppBundle\Entity\Causevoxfundraiser $causevoxfundraisers)
    {
        $this->causevoxfundraisers->removeElement($causevoxfundraisers);
    }

    /**
     * Get causevoxfundraisers.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCausevoxfundraisers()
    {
        return $this->causevoxfundraisers;
    }

    public function getStudentAndTeacher()
    {
        return sprintf('%s - %s - %s', $this->teacher->getGrade()->getName(), $this->teacher->getTeacherName(), $this->name);
    }
    /**
     * Add causevoxdonations.
     *
     * @param \AppBundle\Entity\Causevoxdonation $causevoxdonations
     *
     * @return Student
     */
    public function addCausevoxdonations(\AppBundle\Entity\Causevoxdonation $causevoxdonations)
    {
        $this->causevoxdonations[] = $causevoxdonations;

        return $this;
    }

    /**
     * Remove causevoxdonation.
     *
     * @param \AppBundle\Entity\Causevoxdonation $causevoxdonations
     */
    public function removeCausevoxdonations(\AppBundle\Entity\Causevoxdonation $causevoxdonations)
    {
        $this->causevoxdonations->removeElement($causevoxdonations);
    }

    /**
     * Get causevoxdonations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCausevoxdonations()
    {
        return $this->causevoxdonations;
    }
}
