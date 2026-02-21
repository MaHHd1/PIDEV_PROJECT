<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\Enseignant;
use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\MessageRepository;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/messages')]
class MessageController extends AbstractController
{
    private AuthChecker $authChecker;

    public function __construct(AuthChecker $authChecker)
    {
        $this->authChecker = $authChecker;
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

    #[Route('/inbox', name: 'message_inbox')]
    public function inbox(Request $request, MessageRepository $repo): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();

        $page = $request->query->getInt('page', 1);
        $q    = $request->query->get('q', '');

        $data = $repo->paginateInbox($user, $q, $page, 10);

        return $this->render('message/inbox.html.twig', array_merge(
            $this->buildUserVars($user),
            [
                'messages' => $data['items'],
                'total'    => $data['total'],
                'page'     => $data['page'],
                'pages'    => $data['pages'],
                'q'        => $data['q'],
            ]
        ));
    }

    #[Route('/sent', name: 'message_sent')]
    public function sent(Request $request, MessageRepository $repo): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();

        $page = $request->query->getInt('page', 1);
        $q    = $request->query->get('q', '');

        $data = $repo->paginateSent($user, $q, $page, 10);

        return $this->render('message/sent.html.twig', array_merge(
            $this->buildUserVars($user),
            [
                'messages' => $data['items'],
                'total'    => $data['total'],
                'page'     => $data['page'],
                'pages'    => $data['pages'],
                'q'        => $data['q'],
            ]
        ));
    }

    #[Route('/archive', name: 'message_archive')]
    public function archive(Request $request, MessageRepository $repo): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();

        $page = $request->query->getInt('page', 1);
        $q    = $request->query->get('q', '');

        $data = $repo->paginateArchive($user, $q, $page, 10);

        return $this->render('message/archive.html.twig', array_merge(
            $this->buildUserVars($user),
            [
                'messages' => $data['items'],
                'total'    => $data['total'],
                'page'     => $data['page'],
                'pages'    => $data['pages'],
                'q'        => $data['q'],
            ]
        ));
    }

    #[Route('/new', name: 'message_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();

        $message = new Message();
        $message->setExpediteur($user);

        $form = $this->createForm(MessageType::class, $message, [
            'active_user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setDateEnvoi(new \DateTimeImmutable());
            $message->setStatut('envoye');

            $em->persist($message);
            $em->flush();

            return $this->redirectToRoute('message_inbox');
        }

        return $this->render('message/new.html.twig', array_merge(
            $this->buildUserVars($user),
            ['form' => $form->createView()]
        ));
    }

    #[Route('/{id}', name: 'message_show', requirements: ['id' => '\d+'])]
    public function show(int $id, MessageRepository $repo, EntityManagerInterface $em): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();

        $msg = $repo->find($id);
        if (!$msg) {
            throw $this->createNotFoundException('Message introuvable.');
        }

        if ($msg->getDestinataire()?->getId() === $user->getId() && $msg->getDateLecture() === null) {
            $msg->setDateLecture(new \DateTimeImmutable());
            $msg->setStatut('lu');
            $em->flush();
        }

        [$root, $thread] = $repo->getThread($msg);

        return $this->render('message/show.html.twig', array_merge(
            $this->buildUserVars($user),
            [
                'root'    => $root,
                'thread'  => $thread,
                'message' => $msg,
            ]
        ));
    }

    #[Route('/{id}/reply', name: 'message_reply', requirements: ['id' => '\d+'])]
    public function reply(int $id, Request $request, MessageRepository $repo, EntityManagerInterface $em): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();

        $parent = $repo->find($id);
        if (!$parent) {
            throw $this->createNotFoundException('Message introuvable.');
        }

        $reply = new Message();
        $reply->setExpediteur($user);

        $dest = ($parent->getExpediteur()?->getId() === $user->getId())
            ? $parent->getDestinataire()
            : $parent->getExpediteur();
        $reply->setDestinataire($dest);
        $reply->setObjet('Re: ' . $parent->getObjet());
        $reply->setParent($parent);

        $form = $this->createForm(MessageType::class, $reply, [
            'active_user' => $user,
            'reply_mode'  => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reply->setDateEnvoi(new \DateTimeImmutable());
            $reply->setStatut('envoye');

            $em->persist($reply);
            $em->flush();

            return $this->redirectToRoute('message_show', ['id' => $parent->getId()]);
        }

        return $this->render('message/reply.html.twig', array_merge(
            $this->buildUserVars($user),
            [
                'form'   => $form->createView(),
                'parent' => $parent,
            ]
        ));
    }

    #[Route('/{id}/toggle-archive', name: 'message_toggle_archive', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleArchive(int $id, MessageRepository $repo, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();

        $msg = $repo->find($id);
        if (!$msg) {
            throw $this->createNotFoundException('Message introuvable.');
        }

        if ($msg->getDestinataire()?->getId() === $user->getId()) {
            $msg->setEstArchiveDestinataire(!$msg->isEstArchiveDestinataire());
        }
        if ($msg->getExpediteur()?->getId() === $user->getId()) {
            $msg->setEstArchiveExpediteur(!$msg->isEstArchiveExpediteur());
        }

        $em->flush();

        $from  = $request->request->get('from', 'inbox');
        $route = match ($from) {
            'sent'    => 'message_sent',
            'archive' => 'message_archive',
            default   => 'message_inbox',
        };

        return $this->redirectToRoute($route);
    }

    // =========================================================
    //  ROUTES ADMIN
    // =========================================================

    #[Route('/admin/messages', name: 'admin_message_list')]
    public function adminMessages(Request $request, MessageRepository $repo): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->authChecker->isAdmin()) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }

        $user = $this->authChecker->getCurrentUser();
        $page = $request->query->getInt('page', 1);
        $q    = $request->query->get('q', '');

        $data = $repo->paginateAll($q, $page, 15);

        return $this->render('admin/messages.html.twig', array_merge(
            $this->buildUserVars($user),
            [
                'messages' => $data['items'],
                'total'    => $data['total'],
                'page'     => $data['page'],
                'pages'    => $data['pages'],
                'q'        => $data['q'],
            ]
        ));
    }

    #[Route('/admin/messages/{id}/delete', name: 'admin_message_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function adminDeleteMessage(int $id, MessageRepository $repo, EntityManagerInterface $em): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->authChecker->isAdmin()) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }

        $msg = $repo->find($id);
        if (!$msg) {
            throw $this->createNotFoundException('Message introuvable.');
        }

        $em->remove($msg);
        $em->flush();

        $this->addFlash('success', 'Message supprimé par l\'administrateur.');
        return $this->redirectToRoute('admin_message_list');
    }

    #[Route('/{id}/delete', name: 'message_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, MessageRepository $repo, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();

        $msg = $repo->find($id);
        if (!$msg) {
            throw $this->createNotFoundException('Message introuvable.');
        }

        if ($msg->getDestinataire()?->getId() === $user->getId()) {
            $msg->setEstSupprimeDestinataire(true);
        }
        if ($msg->getExpediteur()?->getId() === $user->getId()) {
            $msg->setEstSupprimeExpediteur(true);
        }

        if ($msg->isEstSupprimeExpediteur() && $msg->isEstSupprimeDestinataire()) {
            $em->remove($msg);
        }

        $em->flush();

        $from  = $request->request->get('from', 'inbox');
        $route = match ($from) {
            'sent'    => 'message_sent',
            'archive' => 'message_archive',
            default   => 'message_inbox',
        };

        return $this->redirectToRoute($route);
    }
}