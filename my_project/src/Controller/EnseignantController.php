<?php

namespace App\Controller;

use App\Entity\Enseignant;
use App\Form\EnseignantType;
use App\Repository\EnseignantRepository;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/enseignant')]
class EnseignantController extends AbstractController
{
    #[Route('/', name: 'app_enseignant_index', methods: ['GET'])]
    public function index(EnseignantRepository $enseignantRepository, AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('enseignant/index.html.twig', [
            'enseignants' => $enseignantRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_enseignant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $enseignant = new Enseignant();
        $form = $this->createForm(EnseignantType::class, $enseignant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash password
            $plainPassword = $form->get('motDePasse')->getData();
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
            $enseignant->setMotDePasse($hashedPassword);

            $entityManager->persist($enseignant);
            $entityManager->flush();

            $this->addFlash('success', 'Enseignant créé avec succès!');

            return $this->redirectToRoute('app_enseignant_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('enseignant/new.html.twig', [
            'enseignant' => $enseignant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_enseignant_show', methods: ['GET'])]
    public function show(Enseignant $enseignant, AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('enseignant/show.html.twig', [
            'enseignant' => $enseignant,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_enseignant_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Enseignant $enseignant, EntityManagerInterface $entityManager, AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(EnseignantType::class, $enseignant, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update password only if provided
            if ($form->has('motDePasse') && $form->get('motDePasse')->getData()) {
                $plainPassword = $form->get('motDePasse')->getData();
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                $enseignant->setMotDePasse($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Enseignant modifié avec succès!');

            return $this->redirectToRoute('app_enseignant_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('enseignant/edit.html.twig', [
            'enseignant' => $enseignant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_enseignant_delete', methods: ['POST'])]
    public function delete(Request $request, Enseignant $enseignant, EntityManagerInterface $entityManager, AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete'.$enseignant->getId(), $request->request->get('_token'))) {
            $entityManager->remove($enseignant);
            $entityManager->flush();

            $this->addFlash('success', 'Enseignant supprimé avec succès!');
        }

        return $this->redirectToRoute('app_enseignant_index', [], Response::HTTP_SEE_OTHER);
    }
}