<?php

namespace App\Controller;

use App\Entity\Administrateur;
use App\Form\AdministrateurType;
use App\Repository\AdministrateurRepository;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/administrateur')]
class AdministrateurController extends AbstractController
{
    #[Route('/', name: 'app_administrateur_index', methods: ['GET'])]
    public function index(AdministrateurRepository $administrateurRepository, AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('administrateur/index.html.twig', [
            'administrateurs' => $administrateurRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_administrateur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $administrateur = new Administrateur();
        $form = $this->createForm(AdministrateurType::class, $administrateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash password
            $plainPassword = $form->get('motDePasse')->getData();
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
            $administrateur->setMotDePasse($hashedPassword);

            $entityManager->persist($administrateur);
            $entityManager->flush();

            $this->addFlash('success', 'Administrateur créé avec succès!');

            return $this->redirectToRoute('app_administrateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('administrateur/new.html.twig', [
            'administrateur' => $administrateur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_administrateur_show', methods: ['GET'])]
    public function show(Administrateur $administrateur, AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('administrateur/show.html.twig', [
            'administrateur' => $administrateur,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_administrateur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Administrateur $administrateur, EntityManagerInterface $entityManager, AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(AdministrateurType::class, $administrateur, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update password only if provided
            if ($form->has('motDePasse') && $form->get('motDePasse')->getData()) {
                $plainPassword = $form->get('motDePasse')->getData();
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                $administrateur->setMotDePasse($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Administrateur modifié avec succès!');

            return $this->redirectToRoute('app_administrateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('administrateur/edit.html.twig', [
            'administrateur' => $administrateur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_administrateur_delete', methods: ['POST'])]
    public function delete(Request $request, Administrateur $administrateur, EntityManagerInterface $entityManager, AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete'.$administrateur->getId(), $request->request->get('_token'))) {
            $entityManager->remove($administrateur);
            $entityManager->flush();

            $this->addFlash('success', 'Administrateur supprimé avec succès!');
        }

        return $this->redirectToRoute('app_administrateur_index', [], Response::HTTP_SEE_OTHER);
    }
}