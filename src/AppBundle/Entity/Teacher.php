<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="teacher",uniqueConstraints={@ORM\UniqueConstraint(columns={"teacher_name", "grade_id"})})
 */
class Teacher
{
    /**
     * @ORM\OneToMany(targetEntity="Student", mappedBy="teacher", cascade={"remove"})
     */
    private $students;

    /**
     * @ORM\OneToMany(targetEntity="Causevoxteam", mappedBy="teacher", cascade={"remove"})
     */
    private $causevoxteams;

    /**
     * @ORM\OneToMany(targetEntity="Causevoxfundraiser", mappedBy="teacher", cascade={"remove"})
     */
    private $causevoxfundraisers;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Grade
     *
     * @ORM\ManyToOne(targetEntity="Grade", inversedBy="teachers", cascade={"remove"})
     * @ORM\JoinColumn(name="grade_id", referencedColumnName="id")
     * @Assert\NotNull()
     */
    private $grade;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotNull()
     */
    private $teacherName;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email.",
     * )
     */
    private $email;

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
     * Set grade.
     *
     * @param string $grade
     *
     * @return Teacher
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
     * Set teacherName.
     *
     * @param string $teacherName
     *
     * @return Teacher
     */
    public function setTeacherName($teacherName)
    {
        $this->teacherName = $teacherName;

        return $this;
    }

    /**
     * Get teacherName.
     *
     * @return string
     */
    public function getTeacherName()
    {
        return $this->teacherName;
    }
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->students = new \Doctrine\Common\Collections\ArrayCollection();
        $this->causevoxteams = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add student.
     *
     * @param \AppBundle\Entity\Student $student
     *
     * @return Teacher
     */
    public function addStudent(\AppBundle\Entity\Student $student)
    {
        $this->students[] = $student;

        return $this;
    }

    /**
     * Remove student.
     *
     * @param \AppBundle\Entity\Student $student
     */
    public function removeStudent(\AppBundle\Entity\Student $student)
    {
        $this->students->removeElement($student);
    }

    /**
     * Get students.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStudents()
    {
        return $this->students;
    }

    /**
     * Add causevoxteam.
     *
     * @param \AppBundle\Entity\Causevoxteam $causevoxteams
     *
     * @return Teacher
     */
    public function addCausevoxteam(\AppBundle\Entity\Causevoxteam $causevoxteams)
    {
        $this->causevoxteams[] = $causevoxteams;

        return $this;
    }

    /**
     * Remove causevoxteams.
     *
     * @param \AppBundle\Entity\Causevoxteam $causevoxteams
     */
    public function removeCausevoxteam(\AppBundle\Entity\Causevoxteam $causevoxteams)
    {
        $this->causevoxteams->removeElement($causevoxteams);
    }

    /**
     * Get causevoxteams.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCausevoxteams()
    {
        return $this->causevoxteams;
    }

    public function getTeacherAndGrade()
    {
        return sprintf('%s - %s', $this->grade->getName(), $this->teacherName);
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Teacher
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
}
