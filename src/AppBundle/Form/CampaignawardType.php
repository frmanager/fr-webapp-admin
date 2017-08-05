<?php

namespace AppBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

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
            ->add('campaignawardtype', EntityType::class, array(
              'class' => 'AppBundle:Campaignawardtype',
              'choice_label' => 'name',
              'label' => 'Type',
              'placeholder' => 'Choose an option',
              ))
              ->add('campaignawardstyle', EntityType::class, array(
                'class' => 'AppBundle:Campaignawardstyle',
                'choice_label' => 'name',
                'label' => 'Style',
                'placeholder' => 'Choose an option',
                ))
            ->add('amount', MoneyType::class, array('required' => false, 'currency' => 'USD'))
            ->add('place', IntegerType::class, array('required' => false, 'label' => 'Rank'))
            ->add('description', TextType::class, array('required' => false))
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
