<?php

namespace App\Form;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $activeUser */
        $activeUser = $options['active_user'];
        $replyMode = (bool) $options['reply_mode'];

        $builder
            ->add('destinataire', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'query_builder' => function (UserRepository $repo) use ($activeUser) {
                    return $repo->createQueryBuilder('u')
                        ->andWhere('u.id != :me')
                        ->setParameter('me', $activeUser->getId())
                        ->orderBy('u.username', 'ASC');
                },
                'disabled' => $replyMode, // en reply, destinataire est fixé
            ])
            ->add('objet', TextType::class, [
                'disabled' => $replyMode, // en reply, on garde "Re:"
            ])
            ->add('contenu', TextareaType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
            'active_user' => null,
            'reply_mode' => false,
        ]);
    }
}
