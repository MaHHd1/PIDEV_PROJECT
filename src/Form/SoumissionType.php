<?php
// src/Form/SoumissionType.php
namespace App\Form;

use App\Entity\Evaluation;
use App\Entity\Soumission;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SoumissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('evaluation', EntityType::class, [
                'class' => Evaluation::class,
                'choice_label' => 'titre',
                'label' => 'Évaluation',
                'attr' => ['class' => 'form-control']
            ])
            ->add('fichierSoumissionUrl', TextType::class, [
                'label' => 'URL du fichier',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('commentaireEtudiant', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Soumission::class,
        ]);
    }
}