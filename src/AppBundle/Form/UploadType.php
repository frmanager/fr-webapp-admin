<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
class UploadType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data = $builder->getData();
        $builder
        ->add('file_type', ChoiceType::class, array(
          'choices' => array(
              'Students' => 'Student',
              'Teachers' => 'Teacher',
              'Grades' => 'Grade',
              'CauseVox Teams' => 'Causevoxteam',
              'CauseVox Fundraisers' => 'Causevoxfundraiser',
              'CauseVox Donations' => 'Causevoxdonation',
              'Fun Run Ledger' => 'Offlinedonation',
            ),
            'disabled' => true,
          )
        )
        ->add('attachment', FileType::class)
        ->add('upload_mode', ChoiceType::class, array(
              'expanded' => true,
              'choices' => array(
                      'Insert/Update (Default)' => 'update',
                      'Truncate (Delete All First)' => 'truncate',
                      'Validate Only (No Database Changes)' => 'validate',
                  ),
              'data' => 'update',
               ));

             // dump($options['roles']);
          if (in_array('ROLE_SUPER_ADMIN', $data['role'])) {
              // do as you want if admin
              $builder
              ->add('upload_mode', ChoiceType::class, array(
                    'expanded' => true,
                    'choices' => array(
                            'Insert/Update (Default)' => 'update',
                            'Truncate (Delete All First)' => 'truncate',
                            'Validate Only (No Database Changes)' => 'validate',
                        ),
                    'data' => 'update',
                     ));
          } else {
              $builder
               ->add('upload_mode', ChoiceType::class, array(
                    'expanded' => true,
                    'choices' => array(
                            'Insert/Update (Default)' => 'update',
                            'Validate Only (No Database Changes)' => 'validate',
                        ),
                    'data' => 'update',
                     ));
          }
    }

}
