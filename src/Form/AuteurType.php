<?php

namespace App\Form;

use App\Entity\Auteur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuteurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de famille',
                'attr' => [
                    'placeholder' => 'Nom de famille de l\'auteur',
                    'class' => 'form-input'
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Prénom de l\'auteur (optionnel)',
                    'class' => 'form-input'
                ]
            ])
            ->add('nomPlume', TextType::class, [
                'label' => 'Nom de plume',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Nom de plume ou pseudonyme (optionnel)',
                    'class' => 'form-input'
                ]
            ])
            ->add('nationalite', TextType::class, [
                'label' => 'Nationalité',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Nationalité de l\'auteur (ex: Japonais, Coréen, Français)',
                    'class' => 'form-input'
                ]
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-input'
                ]
            ])
            ->add('biographie', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Biographie de l\'auteur...',
                    'class' => 'form-textarea',
                    'rows' => 6
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Auteur::class,
        ]);
    }
} 