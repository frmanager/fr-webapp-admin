<?php

namespace AppBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @UniqueEntity("teacherName")
 */
class Teacher
{
    /**
     * @ORM\OneToMany(targetEntity="Student", mappedBy="teacher")
     */
    private $students;

    /**
     * @ORM\OneToMany(targetEntity="Causevoxteam", mappedBy="teacher", cascade={"all"})
     */
    private $causevoxteams;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Grade",inversedBy="teachers", cascade={"all"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotNull()
     */
    private $grade;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotNull()
     */
    private $teacherName;

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
     * @param \AppBundle\Entity\Causevoxteam $causevoxteam
     *
     * @return Teacher
     */
    public function addCausevoxteam(\AppBundle\Entity\Causevoxteam $causevoxteam)
    {
        $this->causevoxteams[] = $causevoxteam;

        return $this;
    }

    /**
     * Remove causevoxteam.
     *
     * @param \AppBundle\Entity\Causevoxteam $causevoxteam
     */
    public function removeCausevoxteam(\AppBundle\Entity\Causevoxteam $causevoxteam)
    {
        $this->causevoxteams->removeElement($causevoxteam);
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
}
