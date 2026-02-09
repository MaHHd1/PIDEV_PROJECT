<?php

namespace App\Controller;

use App\Entity\CommentaireForum;
use App\Form\CommentaireForumType;
use App\Repository\CommentaireForumRepository;
use App\Repository\ForumDiscussionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/comments')]
class CommentaireController extends AbstractController
{
    private function getActiveUser(Request $request, UserRepository $userRepo)
    {
        $uid = $request->query->getInt('u', 0);
        $user = $uid > 0 ? $userRepo->find($uid) : null;

        if (!$user) {
            $user = $userRepo->createQueryBuilder('u')
                ->orderBy('u.id', 'ASC')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
        }

        if (!$user) {
            throw $this->createNotFoundException("Aucun utilisateur dans la table user.");
        }

        return $user;
    }

    // ✅ nouveau commentaire (post)
    #[Route('/new/{forumId}', name: 'comment_new', requirements: ['forumId' => '\d+'])]
    public function new(
        int $forumId,
        Request $request,
        ForumDiscussionRepository $forumRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $forum = $forumRepo->find($forumId);
        if (!$forum) {
            throw $this->createNotFoundException("Forum introuvable.");
        }

        $comment = new CommentaireForum();
        $comment->setForum($forum);
        $comment->setUtilisateur($activeUser);

        $form = $this->createForm(CommentaireForumType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forum->setDerniereActivite(new \DateTimeImmutable());

            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('forum_show', [
                'id' => $forum->getId(),
                'u' => $activeUser->getId(),
            ]);
        }

        return $this->render('comment/new.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
            'activeUser' => $activeUser,
        ]);
    }

    // ✅ reply sur commentaire (thread parent_id)
    #[Route('/reply/{commentId}', name: 'comment_reply', requirements: ['commentId' => '\d+'])]
    public function reply(
        int $commentId,
        Request $request,
        CommentaireForumRepository $commentRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $parent = $commentRepo->find($commentId);
        if (!$parent) {
            throw $this->createNotFoundException("Commentaire introuvable.");
        }

        $reply = new CommentaireForum();
        $reply->setForum($parent->getForum());
        $reply->setUtilisateur($activeUser);
        $reply->setParent($parent);

        $form = $this->createForm(CommentaireForumType::class, $reply);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parent->setNbReponses($parent->getNbReponses() + 1);

            $forum = $parent->getForum();
            $forum->setDerniereActivite(new \DateTimeImmutable());

            $em->persist($reply);
            $em->flush();

            return $this->redirectToRoute('forum_show', [
                'id' => $forum->getId(),
                'u' => $activeUser->getId(),
            ]);
        }

        return $this->render('comment/reply.html.twig', [
            'form' => $form->createView(),
            'parent' => $parent,
            'activeUser' => $activeUser,
        ]);
    }

    // ✅ like
    #[Route('/{id}/like', name: 'comment_like', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function like(
        int $id,
        Request $request,
        CommentaireForumRepository $repo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $c = $repo->find($id);
        if (!$c) {
            throw $this->createNotFoundException("Commentaire introuvable.");
        }

        $c->setLikes($c->getLikes() + 1);
        $c->getForum()?->setDerniereActivite(new \DateTimeImmutable());
        $em->flush();

        return $this->redirectToRoute('forum_show', ['id' => $c->getForum()->getId(), 'u' => $activeUser->getId()]);
    }

    // ✅ dislike
    #[Route('/{id}/dislike', name: 'comment_dislike', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function dislike(
        int $id,
        Request $request,
        CommentaireForumRepository $repo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $c = $repo->find($id);
        if (!$c) {
            throw $this->createNotFoundException("Commentaire introuvable.");
        }

        $c->setDislikes($c->getDislikes() + 1);
        $c->getForum()?->setDerniereActivite(new \DateTimeImmutable());
        $em->flush();

        return $this->redirectToRoute('forum_show', ['id' => $c->getForum()->getId(), 'u' => $activeUser->getId()]);
    }

    // ✅ report (signalement)
    #[Route('/{id}/report', name: 'comment_report', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function report(
        int $id,
        Request $request,
        CommentaireForumRepository $repo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $c = $repo->find($id);
        if (!$c) {
            throw $this->createNotFoundException("Commentaire introuvable.");
        }

        $c->setSignalements($c->getSignalements() + 1);

        // règle simple: si >= 3 signalements => en_attente
        if ($c->getSignalements() >= 3 && $c->getStatut() !== 'supprime') {
            $c->setStatut('en_attente');
        }

        $c->getForum()?->setDerniereActivite(new \DateTimeImmutable());
        $em->flush();

        return $this->redirectToRoute('forum_show', ['id' => $c->getForum()->getId(), 'u' => $activeUser->getId()]);
    }

    // ✅ delete logique (statut = supprime)
    #[Route('/{id}/delete', name: 'comment_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        int $id,
        Request $request,
        CommentaireForumRepository $repo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $c = $repo->find($id);
        if (!$c) {
            throw $this->createNotFoundException("Commentaire introuvable.");
        }

        $c->setStatut('supprime');
        $c->getForum()?->setDerniereActivite(new \DateTimeImmutable());
        $em->flush();

        return $this->redirectToRoute('forum_show', ['id' => $c->getForum()->getId(), 'u' => $activeUser->getId()]);
    }
}
