<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Form\EtudiantType;
use App\Repository\EtudiantRepository;
use App\Service\AuthChecker;  // CORRECTED: This use statement should be here
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/etudiant')]
class EtudiantController extends AbstractController
{
    #[Route('/', name: 'app_etudiant_index', methods: ['GET'])]
    public function index(
        EtudiantRepository $etudiantRepository,
        AuthChecker $authChecker
    ): Response
    {
        // ADD THIS CHECK
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('etudiant/index.html.twig', [
            'etudiants' => $etudiantRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_etudiant_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // ADD THIS CHECK
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $etudiant = new Etudiant();
        $form = $this->createForm(EtudiantType::class, $etudiant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('motDePasse')->getData();
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
            $etudiant->setMotDePasse($hashedPassword);

            $entityManager->persist($etudiant);
            $entityManager->flush();

            $this->addFlash('success', 'Étudiant créé avec succès!');

            return $this->redirectToRoute('app_etudiant_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('etudiant/new.html.twig', [
            'etudiant' => $etudiant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_etudiant_show', methods: ['GET'])]
    public function show(
        Etudiant $etudiant,
        AuthChecker $authChecker
    ): Response
    {
        // ADD THIS CHECK
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('etudiant/show.html.twig', [
            'etudiant' => $etudiant,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_etudiant_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Etudiant $etudiant,
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // ADD THIS CHECK
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(EtudiantType::class, $etudiant, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has('motDePasse') && $form->get('motDePasse')->getData()) {
                $plainPassword = $form->get('motDePasse')->getData();
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                $etudiant->setMotDePasse($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Étudiant modifié avec succès!');

            return $this->redirectToRoute('app_etudiant_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('etudiant/edit.html.twig', [
            'etudiant' => $etudiant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_etudiant_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Etudiant $etudiant,
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // ADD THIS CHECK
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete'.$etudiant->getId(), $request->request->get('_token'))) {
            $entityManager->remove($etudiant);
            $entityManager->flush();

            $this->addFlash('success', 'Étudiant supprimé avec succès!');
        }

        return $this->redirectToRoute('app_etudiant_index', [], Response::HTTP_SEE_OTHER);
    }
}