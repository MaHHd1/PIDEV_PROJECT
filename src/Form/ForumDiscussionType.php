<?php

namespace App\Form;

use App\Entity\ForumDiscussion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Url;

class ForumDiscussionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class)
            ->add('description', TextareaType::class)
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Public' => 'public',
                    'Privé' => 'prive',
                    'Par classe' => 'par_classe',
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Ouvert' => 'ouvert',
                    'Fermé' => 'ferme',
                    'Épinglé' => 'epingle',
                ],
            ])
            ->add('imageCouvertureUrl', TextType::class, [
                'required' => false,
                'label' => 'Image (URL)',
                'help' => 'Ex: https://site.com/image.jpg',
                'constraints' => [
                    new Url(['message' => 'URL invalide. Exemple: https://...'])
                    ],
            ])
            ->add('reglesModeration', TextareaType::class, [
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ForumDiscussion::class,
        ]);
    }
}
