<?php

namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Votre commentaire',
                'required' => true,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Exprimez-vous...'
                ]
            ])
            ->add('note', ChoiceType::class, [
                'label' => 'Note',
                'choices' => [
                    '⭐️ 1' => 1,
                    '⭐️⭐️ 2' => 2,
                    '⭐️⭐️⭐️ 3' => 3,
                    '⭐️⭐️⭐️⭐️ 4' => 4,
                    '⭐️⭐️⭐️⭐️⭐️ 5' => 5,
                ],
                'expanded' => false,
                'multiple' => false,
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
        ]);
    }
} 