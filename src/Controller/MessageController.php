<?php

namespace App\Controller;

use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/messages')]
class MessageController extends AbstractController
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

    #[Route('/inbox', name: 'message_inbox')]
    public function inbox(Request $request, MessageRepository $repo, UserRepository $userRepo): Response
    {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $page = $request->query->getInt('page', 1);
        $q = $request->query->get('q', '');

        $data = $repo->paginateInbox($activeUser, $q, $page, 10);

        return $this->render('message/inbox.html.twig', [
            'activeUser' => $activeUser,
            'messages' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'pages' => $data['pages'],
            'q' => $data['q'],
        ]);
    }

    #[Route('/sent', name: 'message_sent')]
    public function sent(Request $request, MessageRepository $repo, UserRepository $userRepo): Response
    {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $page = $request->query->getInt('page', 1);
        $q = $request->query->get('q', '');

        $data = $repo->paginateSent($activeUser, $q, $page, 10);

        return $this->render('message/sent.html.twig', [
            'activeUser' => $activeUser,
            'messages' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'pages' => $data['pages'],
            'q' => $data['q'],
        ]);
    }

    #[Route('/archive', name: 'message_archive')]
    public function archive(Request $request, MessageRepository $repo, UserRepository $userRepo): Response
    {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $page = $request->query->getInt('page', 1);
        $q = $request->query->get('q', '');

        $data = $repo->paginateArchive($activeUser, $q, $page, 10);

        return $this->render('message/archive.html.twig', [
            'activeUser' => $activeUser,
            'messages' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'pages' => $data['pages'],
            'q' => $data['q'],
        ]);
    }

    #[Route('/new', name: 'message_new')]
    public function new(Request $request, EntityManagerInterface $em, UserRepository $userRepo): Response
    {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $message = new Message();
        $message->setExpediteur($activeUser);

        $form = $this->createForm(MessageType::class, $message, [
            'active_user' => $activeUser,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setDateEnvoi(new \DateTimeImmutable());
            $message->setStatut('envoye');

            $em->persist($message);
            $em->flush();

            return $this->redirectToRoute('message_inbox', ['u' => $activeUser->getId()]);
        }

        return $this->render('message/new.html.twig', [
            'form' => $form->createView(),
            'activeUser' => $activeUser,
        ]);
    }

    #[Route('/{id}', name: 'message_show', requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        Request $request,
        MessageRepository $repo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $msg = $repo->find($id);
        if (!$msg) {
            throw $this->createNotFoundException("Message introuvable.");
        }

        // Marquer comme lu si destinataire
        if ($msg->getDestinataire() && $msg->getDestinataire()->getId() === $activeUser->getId()) {
            if ($msg->getDateLecture() === null) {
                $msg->setDateLecture(new \DateTimeImmutable());
            }
            $msg->setStatut('lu');
            $em->flush();
        }

        [$root, $thread] = $repo->getThread($msg);

        return $this->render('message/show.html.twig', [
            'activeUser' => $activeUser,
            'root' => $root,
            'thread' => $thread,
            'message' => $msg,
        ]);
    }

    #[Route('/{id}/reply', name: 'message_reply', requirements: ['id' => '\d+'])]
    public function reply(
        int $id,
        Request $request,
        MessageRepository $repo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $parent = $repo->find($id);
        if (!$parent) {
            throw $this->createNotFoundException("Message introuvable.");
        }

        $reply = new Message();
        $reply->setExpediteur($activeUser);

        // destinataire = l'autre personne
        $dest = ($parent->getExpediteur() && $parent->getExpediteur()->getId() === $activeUser->getId())
            ? $parent->getDestinataire()
            : $parent->getExpediteur();

        $reply->setDestinataire($dest);
        $reply->setObjet('Re: ' . (string) $parent->getObjet());
        $reply->setParent($parent);

        $form = $this->createForm(MessageType::class, $reply, [
            'active_user' => $activeUser,
            'reply_mode' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reply->setDateEnvoi(new \DateTimeImmutable());
            $reply->setStatut('envoye');

            $em->persist($reply);
            $em->flush();

            return $this->redirectToRoute('message_show', ['id' => $parent->getId(), 'u' => $activeUser->getId()]);
        }

        return $this->render('message/reply.html.twig', [
            'form' => $form->createView(),
            'activeUser' => $activeUser,
            'parent' => $parent,
        ]);
    }

    #[Route('/{id}/toggle-archive', name: 'message_toggle_archive', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleArchive(
        int $id,
        Request $request,
        MessageRepository $repo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $activeUser = $this->getActiveUser($request, $userRepo);

        $msg = $repo->find($id);
        if (!$msg) {
            throw $this->createNotFoundException("Message introuvable.");
        }

        // si user est destinataire -> toggle estArchiveDestinataire
        if ($msg->getDestinataire() && $msg->getDestinataire()->getId() === $activeUser->getId()) {
            $msg->setEstArchiveDestinataire(! (bool) $msg->isEstArchiveDestinataire());
        }

        // si user est expediteur -> toggle estArchiveExpediteur
        if ($msg->getExpediteur() && $msg->getExpediteur()->getId() === $activeUser->getId()) {
            $msg->setEstArchiveExpediteur(! (bool) $msg->isEstArchiveExpediteur());
        }

        $em->flush();

        $from = $request->query->get('from', 'inbox');
        $route = $from === 'sent' ? 'message_sent' : ($from === 'archive' ? 'message_archive' : 'message_inbox');

        return $this->redirectToRoute($route, ['u' => $activeUser->getId()]);
    }

    #[Route('/{id}/delete', name: 'message_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
public function delete(
    int $id,
    Request $request,
    MessageRepository $repo,
    UserRepository $userRepo,
    EntityManagerInterface $em
): Response {
    $activeUser = $this->getActiveUser($request, $userRepo);

    $msg = $repo->find($id);
    if (!$msg) {
        throw $this->createNotFoundException("Message introuvable.");
    }

    // si user est destinataire -> delete destinataire
    if ($msg->getDestinataire() && $msg->getDestinataire()->getId() === $activeUser->getId()) {
        $msg->setEstSupprimeDestinataire(true);
    }

    // si user est expéditeur -> delete expéditeur
    if ($msg->getExpediteur() && $msg->getExpediteur()->getId() === $activeUser->getId()) {
        $msg->setEstSupprimeExpediteur(true);
    }

    // si les deux ont supprimé => suppression physique
    if ($msg->isEstSupprimeExpediteur() && $msg->isEstSupprimeDestinataire()) {
        $em->remove($msg);
    }

    $em->flush();

    $from = $request->query->get('from', 'inbox');
    $route = $from === 'sent' ? 'message_sent' : ($from === 'archive' ? 'message_archive' : 'message_inbox');

    return $this->redirectToRoute($route, ['u' => $activeUser->getId()]);
}

}
