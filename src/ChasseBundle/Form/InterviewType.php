<?php

namespace ChasseBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class InterviewType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('bonusWord')
                ->add('user')
                ->add('job')
                ->add('answers', EntityType::class, array(
                'class' => 'ChasseBundle:Answer',
                'choice_label' => 'word',
                'multiple' => true,
                'expanded' => true,
                'constraints' => array(
                    new Count(array(
                        'min' => 1,
                        'minMessage' => 'Choisis au moins 1 mot clé ! ',
                        )),
                    ),));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ChasseBundle\Entity\Interview'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'chassebundle_interview';
    }


}
