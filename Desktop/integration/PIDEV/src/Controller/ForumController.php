<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\Enseignant;
use App\Entity\ForumDiscussion;
use App\Form\ForumDiscussionType;
use App\Repository\CommentaireForumRepository;
use App\Repository\ForumDiscussionRepository;
use App\Helper\AuthHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/forums')]
class ForumController extends AbstractController
{
    private AuthHelper $authHelper;

    public function __construct(AuthHelper $authHelper)
    {
        $this->authHelper = $authHelper;
    }

    private function getActiveUser()
    {
        if (!$this->authHelper->isUserLoggedIn()) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        return $this->authHelper->getCurrentUser();
    }

    private function buildUserVars(object $user): array
    {
        if ($user instanceof Etudiant) {
            return ['activeUser' => $user, 'student' => $user];
        }
        if ($user instanceof Enseignant) {
            return ['activeUser' => $user, 'enseignant' => $user];
        }
        return ['activeUser' => $user];
    }

    private function getTemplate(object $user, string $name): string
    {
        $type = $user instanceof Enseignant ? 'enseignant' : 'etudiant';
        return "forum/_{$name}_{$type}.html.twig";
    }

    #[Route('', name: 'forum_list')]
    public function list(Request $request, ForumDiscussionRepository $repo): Response
    {
        $user = $this->getActiveUser();

        $page = $request->query->getInt('page', 1);
        $q    = $request->query->get('q', '');

        $data = $repo->paginate($q, $page, 6);

        return $this->render($this->getTemplate($user, 'list'), array_merge(
            $this->buildUserVars($user),
            [
                'forums' => $data['items'],
                'total'  => $data['total'],
                'page'   => $data['page'],
                'pages'  => $data['pages'],
                'q'      => $data['q'],
            ]
        ));
    }

    #[Route('/new', name: 'forum_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getActiveUser();

        $forum = new ForumDiscussion();
        $forum->setCreateur($user);

        $form = $this->createForm(ForumDiscussionType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forum->setDerniereActivite(new \DateTimeImmutable());
            $em->persist($forum);
            $em->flush();

            $this->addFlash('success', 'Forum créé avec succès.');
            return $this->redirectToRoute('forum_list');
        }

        return $this->render($this->getTemplate($user, 'new'), array_merge(
            $this->buildUserVars($user),
            ['form' => $form->createView()]
        ));
    }

    #[Route('/{id}', name: 'forum_show')]
    public function show(
        ForumDiscussion $forum,
        CommentaireForumRepository $commentRepo,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getActiveUser();

        $forum->incrementVues();
        $em->flush();

        $comments = $commentRepo->byForumVisible($forum);

        return $this->render($this->getTemplate($user, 'show'), array_merge(
            $this->buildUserVars($user),
            [
                'forum'    => $forum,
                'comments' => $comments,
            ]
        ));
    }

    #[Route('/{id}/edit', name: 'forum_edit')]
    public function edit(
        Request $request,
        ForumDiscussion $forum,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getActiveUser();

        if ($forum->getCreateur() !== $user && !$this->authHelper->isAdmin()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier ce forum.');
            return $this->redirectToRoute('forum_list');
        }

        $form = $this->createForm(ForumDiscussionType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forum->setEstModifie(true);
            $forum->setDateModification(new \DateTimeImmutable());
            $forum->setDerniereActivite(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'Forum modifié avec succès.');
            return $this->redirectToRoute('forum_show', ['id' => $forum->getId()]);
        }

        return $this->render($this->getTemplate($user, 'edit'), array_merge(
            $this->buildUserVars($user),
            [
                'form'  => $form->createView(),
                'forum' => $forum,
            ]
        ));
    }

    #[Route('/{id}/delete', name: 'forum_delete', methods: ['POST'])]
    public function delete(
        ForumDiscussion $forum,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getActiveUser();

        if ($forum->getCreateur() !== $user ) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer ce forum.');
            return $this->redirectToRoute('forum_list');
        }

        $em->remove($forum);
        $em->flush();

        $this->addFlash('success', 'Forum supprimé avec succès.');
        return $this->redirectToRoute('forum_list');
    }

    #[Route('/{id}/like', name: 'forum_like', methods: ['POST'])]
    public function like(ForumDiscussion $forum, EntityManagerInterface $em): Response
    {
        $user = $this->getActiveUser();
        $forum->setLikes(($forum->getLikes() ?? 0) + 1);
        $em->flush();
        return $this->redirectToRoute('forum_show', ['id' => $forum->getId()]);
    }

    #[Route('/{id}/dislike', name: 'forum_dislike', methods: ['POST'])]
    public function dislike(ForumDiscussion $forum, EntityManagerInterface $em): Response
    {
        $user = $this->getActiveUser();
        $forum->setDislikes(($forum->getDislikes() ?? 0) + 1);
        $em->flush();
        return $this->redirectToRoute('forum_show', ['id' => $forum->getId()]);
    }

    #[Route('/{id}/report', name: 'forum_report', methods: ['POST'])]
    public function report(ForumDiscussion $forum, EntityManagerInterface $em): Response
    {
        $user = $this->getActiveUser();
        $forum->setSignalements(($forum->getSignalements() ?? 0) + 1);
        $em->flush();
        return $this->redirectToRoute('forum_show', ['id' => $forum->getId()]);
    }
}