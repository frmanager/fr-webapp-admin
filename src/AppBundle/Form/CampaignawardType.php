<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class CampaignawardType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('description', TextType::class, array('required' => false))
            ->add('award_type', ChoiceType::class, array(
                'choices' => array(
                    'Teacher/class' => 'teacher',
                    'Individual/Student' => 'student',
                ),
            ))
            ->add('award_style', ChoiceType::class, array(
                'choices' => array(
                    'Top' => 'top',
                    'Level' => 'level',
                ),
            ))
            ->add('amount', NumberType::class, array('required' => false))
            ->add('place', NumberType::class, array('required' => false))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Campaignaward',
        ));
    }
}
