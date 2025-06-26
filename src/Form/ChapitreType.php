<?php

namespace App\Form;

use App\Entity\Chapitre;
use App\Entity\Oeuvre;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChapitreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du chapitre',
                'attr' => [
                    'placeholder' => 'Titre du chapitre',
                    'class' => 'form-input'
                ]
            ])
            ->add('ordre', IntegerType::class, [
                'label' => 'Numéro d\'ordre',
                'attr' => [
                    'placeholder' => '1',
                    'class' => 'form-input',
                    'min' => 1
                ]
            ])
            ->add('resume', TextareaType::class, [
                'label' => 'Résumé',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Résumé du chapitre (optionnel)...',
                    'class' => 'form-textarea',
                    'rows' => 3
                ]
            ])
            ->add('pages', CollectionType::class, [
                'label' => 'Pages (URLs des images)',
                'entry_type' => UrlType::class,
                'entry_options' => [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'https://exemple.com/page.jpg',
                        'class' => 'form-input page-input'
                    ]
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'attr' => [
                    'class' => 'pages-collection'
                ]
            ]);

        // Si le chapitre n'a pas encore d'œuvre assignée, on peut permettre de la choisir
        if (!$builder->getData() || !$builder->getData()->getOeuvre()) {
            $builder->add('oeuvre', EntityType::class, [
                'class' => Oeuvre::class,
                'choice_label' => 'titre',
                'label' => 'Œuvre',
                'placeholder' => 'Sélectionner une œuvre',
                'attr' => ['class' => 'form-select']
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chapitre::class,
        ]);
    }
} 