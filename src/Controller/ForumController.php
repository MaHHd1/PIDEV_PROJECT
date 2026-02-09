<?php

namespace App\Controller;

use App\Entity\ForumDiscussion;
use App\Form\ForumDiscussionType;
use App\Repository\CommentaireForumRepository;
use App\Repository\ForumDiscussionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/forums')]
class ForumController extends AbstractController
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

    #[Route('', name: 'forum_list')]
    public function list(Request $request, ForumDiscussionRepository $repo, UserRepository $userRepo): Response
    {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $page = $request->query->getInt('page', 1);
        $q = $request->query->get('q', '');

        $data = $repo->paginate($q, $page, 6);

        return $this->render('forum/list.html.twig', [
            'activeUser' => $activeUser,
            'forums' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'pages' => $data['pages'],
            'q' => $data['q'],
        ]);
    }

    #[Route('/new', name: 'forum_new')]
    public function new(Request $request, EntityManagerInterface $em, UserRepository $userRepo): Response
    {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $forum = new ForumDiscussion();
        $forum->setCreateur($activeUser);

        $form = $this->createForm(ForumDiscussionType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forum->setDerniereActivite(new \DateTimeImmutable());

            $em->persist($forum);
            $em->flush();

            return $this->redirectToRoute('forum_list', ['u' => $activeUser->getId()]);
        }

        return $this->render('forum/new.html.twig', [
            'form' => $form->createView(),
            'activeUser' => $activeUser,
        ]);
    }

    #[Route('/{id}', name: 'forum_show', requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        Request $request,
        ForumDiscussionRepository $forumRepo,
        CommentaireForumRepository $commentRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $forum = $forumRepo->find($id);
        if (!$forum) {
            throw $this->createNotFoundException("Forum introuvable.");
        }

        $forum->incrementVues();
        $em->flush();

        $comments = $commentRepo->byForumVisible($forum);

        return $this->render('forum/show.html.twig', [
            'forum' => $forum,
            'comments' => $comments,
            'activeUser' => $activeUser,
        ]);
    }

    // ✅ Modifier forum
    #[Route('/{id}/edit', name: 'forum_edit', requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        Request $request,
        ForumDiscussionRepository $repo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $forum = $repo->find($id);
        if (!$forum) {
            throw $this->createNotFoundException("Forum introuvable.");
        }

        $form = $this->createForm(ForumDiscussionType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forum->setEstModifie(true);
            $forum->setDateModification(new \DateTimeImmutable());
            $forum->setDerniereActivite(new \DateTimeImmutable());
            $em->flush();

            return $this->redirectToRoute('forum_show', [
                'id' => $forum->getId(),
                'u' => $activeUser->getId()
            ]);
        }

        return $this->render('forum/edit.html.twig', [
            'form' => $form->createView(),
            'activeUser' => $activeUser,
            'forum' => $forum,
        ]);
    }

    // ✅ Like forum
    #[Route('/{id}/like', name: 'forum_like', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function like(
        int $id,
        Request $request,
        ForumDiscussionRepository $repo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $activeUser = $this->getActiveUser($request, $userRepo);
        $forum = $repo->find($id);

        if (!$forum) {
            throw $this->createNotFoundException("Forum introuvable.");
        }

        $forum->setLikes($forum->getLikes() + 1);
        $forum->setDerniereActivite(new \DateTimeImmutable());
        $em->flush();

        return $this->redirectToRoute('forum_show', ['id' => $id, 'u' => $activeUser->getId()]);
    }

    // ✅ Dislike forum
    #[Route('/{id}/dislike', name: 'forum_dislike', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function dislike(
        int $id,
        Request $request,
        ForumDiscussionRepository $repo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $activeUser = $this->getActiveUser($request, $userRepo);
        $forum = $repo->find($id);

        if (!$forum) {
            throw $this->createNotFoundException("Forum introuvable.");
        }

        $forum->setDislikes($forum->getDislikes() + 1);
        $forum->setDerniereActivite(new \DateTimeImmutable());
        $em->flush();

        return $this->redirectToRoute('forum_show', ['id' => $id, 'u' => $activeUser->getId()]);
    }

    // ✅ Report forum
    #[Route('/{id}/report', name: 'forum_report', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function report(
        int $id,
        Request $request,
        ForumDiscussionRepository $repo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $activeUser = $this->getActiveUser($request, $userRepo);
        $forum = $repo->find($id);

        if (!$forum) {
            throw $this->createNotFoundException("Forum introuvable.");
        }

        $forum->setSignalements($forum->getSignalements() + 1);

        // règle simple : si >= 3 reports -> statut = ferme
        if ($forum->getSignalements() >= 3 && $forum->getStatut() !== 'ferme') {
            $forum->setStatut('ferme');
        }

        $forum->setDerniereActivite(new \DateTimeImmutable());
        $em->flush();

        return $this->redirectToRoute('forum_show', ['id' => $id, 'u' => $activeUser->getId()]);
    }

    // ✅ Delete forum (suppression + suppression des commentaires liés)
    #[Route('/{id}/delete', name: 'forum_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        int $id,
        Request $request,
        ForumDiscussionRepository $repo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $activeUser = $this->getActiveUser($request, $userRepo);
        $forum = $repo->find($id);

        if (!$forum) {
            throw $this->createNotFoundException("Forum introuvable.");
        }

        $em->remove($forum);
        $em->flush();

        return $this->redirectToRoute('forum_list', ['u' => $activeUser->getId()]);
    }

}
