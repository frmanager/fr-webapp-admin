<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use AppBundle\Entity\Teacher;
use Doctrine\ORM\EntityRepository;

class StudentType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('teacher', EntityType::class, array(
              'class' => 'AppBundle:Teacher',
              'query_builder' => function (EntityRepository $er) {
                  return $er->createQueryBuilder('u')->orderBy('u.grade, u.teacherName', 'ASC');
              },
              'choice_label' => 'teacherAndGrade',
              ))
            ->add('name')
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Student',
        ));
    }
}
