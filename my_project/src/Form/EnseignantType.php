<?php

namespace App\Form;

use App\Entity\Enseignant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnseignantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le nom'
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le prénom'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'exemple@email.com'
                ]
            ])
            ->add('motDePasse', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Minimum 8 caractères'
                ],
                'required' => $options['is_edit'] ? false : true,
                'mapped' => !$options['is_edit']
            ])
            ->add('matriculeEnseignant', TextType::class, [
                'label' => 'Matricule Enseignant',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: ENS-2024-001'
                ]
            ])
            ->add('diplome', ChoiceType::class, [
                'label' => 'Diplôme',
                'choices' => [
                    'Licence' => 'Licence',
                    'Master' => 'Master',
                    'Doctorat' => 'Doctorat',
                    'HDR' => 'HDR',
                    'Ingénieur' => 'Ingénieur'
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionnez un diplôme'
            ])
            ->add('specialite', TextType::class, [
                'label' => 'Spécialité',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Mathématiques'
                ]
            ])
            ->add('anneesExperience', IntegerType::class, [
                'label' => 'Années d\'expérience',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 50
                ]
            ])
            ->add('typeContrat', ChoiceType::class, [
                'label' => 'Type de contrat',
                'choices' => [
                    'CDI' => 'CDI',
                    'CDD' => 'CDD',
                    'Vacataire' => 'Vacataire',
                    'Contractuel' => 'Contractuel'
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionnez un type'
            ])
            ->add('tauxHoraire', NumberType::class, [
                'label' => 'Taux horaire (€)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'placeholder' => 'Ex: 45.50'
                ]
            ])
            ->add('disponibilites', TextareaType::class, [
                'label' => 'Disponibilités',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Lundi: 9h-12h, Mardi: 14h-17h...'
                ]
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'actif',
                    'Inactif' => 'inactif',
                    'Congé' => 'conge',
                    'Retraite' => 'retraite'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('submit', SubmitType::class, [
                'label' => $options['is_edit'] ? 'Modifier' : 'Créer',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Enseignant::class,
            'is_edit' => false
        ]);
    }
}