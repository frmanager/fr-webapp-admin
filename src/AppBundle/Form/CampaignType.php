<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class CampaignType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, array('required' => true))
            ->add('description')
            ->add('url')
            ->add('onlineFlag', CheckboxType::class, array('label'    => 'Put campaign online', 'required' => false))
            ->add('teamsFlag', CheckboxType::class, array('label'    => 'Enable Teams', 'required' => false))
            ->add('email', TextType::class, array('required' => true))
            ->add('theme', ChoiceType::class, array(
                  'choices'  => array(
                      'Default' => 'cerulean',
                      'Cosmo' => 'cosmo',
                      'Cyborg' => 'cyborg',
                      'Darkly' => 'darkly',
                      'Flatly' => 'flatly',
                      'Journal' => 'journal',
                      'Lumen' => 'lumen',
                      'Paper' => 'paper',
                      'Readable' => 'readable',
                      'Sandstone' => 'sandstone',
                      'Cerulean' => 'cerulean',
                      'Slate' => 'slate',
                      'Solar' => 'solar',
                      'Space Lab' => 'spacelab',
                      'Superhero' => 'superhero',
                      'United' => 'united',
                      'Yeti' => 'yeti',
                  )))
            ->add('start_date', DateType::class, array('widget' => 'single_text' ))
            ->add('endDate', DateType::class, array('widget' => 'single_text'))
            ->add('fundingGoal', MoneyType::class, array('required' => true, 'currency' => 'USD'))
            ->add('donationFlag', CheckboxType::class, array('label'    => 'Enable Donations', 'required' => false))
            ->add('tippingFlag', CheckboxType::class, array('label'    => 'Enable Tipping', 'required' => false, 'disabled' => true))
            ->add('paypalEmail', EmailType::class, array('required' => false))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Campaign'
        ));
    }
}
