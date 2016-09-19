<?php

namespace AppBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use AppBundle\Entity\Student;
use AppBundle\Entity\Teacher;

class CausevoxdonationType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('student', EntityType::class, array(
              'class' => 'AppBundle:Student',
              'choice_label' => 'name',
              ))
            ->add('teacher', EntityType::class, array(
              'class' => 'AppBundle:Teacher',
              'choice_label' => 'teachername',
              ))
            ->add('amount', MoneyType::class, array('required' => true, 'currency' => 'USD'))
            ->add('donated_at', DateType::class)
            ->add('donor_email')
            ->add('type')
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Causevoxdonation',
        ));
    }
}
