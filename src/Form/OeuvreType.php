<?php

namespace App\Form;

use App\Entity\Oeuvre;
use App\Entity\Auteur;
use App\Entity\Tag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OeuvreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'placeholder' => 'Titre de l\'œuvre',
                    'class' => 'form-input'
                ]
            ])
            ->add('auteur', EntityType::class, [
                'class' => Auteur::class,
                'choice_label' => function(Auteur $auteur) {
                    return $auteur->getNom() . ($auteur->getPrenom() ? ' ' . $auteur->getPrenom() : '');
                },
                'label' => 'Auteur',
                'placeholder' => 'Sélectionner un auteur',
                'attr' => ['class' => 'form-select']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Manga' => 'Manga',
                    'Manhwa' => 'Manhwa',
                    'Manhua' => 'Manhua',
                    'Light Novel' => 'Light Novel',
                    'Web Novel' => 'Web Novel'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('couverture', UrlType::class, [
                'label' => 'URL de la couverture',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://exemple.com/image.jpg',
                    'class' => 'form-input'
                ]
            ])
            ->add('resume', TextareaType::class, [
                'label' => 'Résumé',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Résumé de l\'œuvre...',
                    'class' => 'form-textarea',
                    'rows' => 5
                ]
            ])
            ->add('datePublication', DateType::class, [
                'label' => 'Date de publication',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-input']
            ])
            ->add('isbn', TextType::class, [
                'label' => 'ISBN',
                'required' => false,
                'attr' => [
                    'placeholder' => 'ISBN (optionnel)',
                    'class' => 'form-input'
                ]
            ])
            ->add('mangadxId', TextType::class, [
                'label' => 'ID MangaDx',
                'required' => false,
                'attr' => [
                    'placeholder' => 'ID de l\'œuvre sur MangaDx (optionnel)',
                    'class' => 'form-input'
                ]
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Tags',
                'attr' => [
                    'class' => 'form-select-multiple'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Oeuvre::class,
        ]);
    }
} 