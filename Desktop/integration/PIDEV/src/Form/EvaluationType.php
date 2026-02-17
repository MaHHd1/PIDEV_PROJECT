<?php
// src/Form/EvaluationType.php
namespace App\Form;

use App\Entity\Evaluation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvaluationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l\'évaluation',
                'attr' => ['class' => 'form-control']
            ])
            ->add('typeEvaluation', ChoiceType::class, [
                'label' => 'Type d\'évaluation',
                'choices' => [
                    'Projet' => 'projet',
                    'Examen' => 'examen',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('idCours', TextType::class, [
                'label' => 'ID Cours',
                'attr' => ['class' => 'form-control']
            ])
            ->add('idEnseignant', TextType::class, [
                'label' => 'ID Enseignant',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateLimite', DateTimeType::class, [
                'label' => 'Date limite',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('noteMax', NumberType::class, [
                'label' => 'Note maximale',
                'attr' => ['class' => 'form-control', 'step' => '0.01']
            ])
            ->add('modeRemise', ChoiceType::class, [
                'label' => 'Mode de remise',
                'choices' => [
                    'En ligne' => 'en_ligne',
                    'Présentiel' => 'presentiel',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Ouverte' => 'ouverte',
                    'Fermée' => 'fermee',
                ],
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evaluation::class,
        ]);
    }
}