<?php
// src/Controller/SoumissionController.php
namespace App\Controller;

use App\Entity\Soumission;
use App\Form\SoumissionType;
use App\Repository\SoumissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/soumission')]
class SoumissionController extends AbstractController
{
    #[Route('/', name: 'app_soumission_index', methods: ['GET'])]
    public function index(Request $request, SoumissionRepository $soumissionRepository): Response
    {
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'dateSoumission');
        $order = $request->query->get('order', 'DESC');
        
        $soumissions = $soumissionRepository->findBySearchAndSort($search, $sortBy, $order);
        
        return $this->render('soumission/index.html.twig', [
            'soumissions' => $soumissions,
            'search' => $search,
            'sortBy' => $sortBy,
            'order' => $order,
        ]);
    }

#[Route('/new', name: 'app_soumission_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    $soumission = new Soumission();
    $form = $this->createForm(SoumissionType::class, $soumission);
    $form->handleRequest($request);

    // ⚠️ Après submit, l’évaluation est connue
    if ($form->isSubmitted()) {
        $evaluation = $soumission->getEvaluation();

        if ($evaluation && $evaluation->getStatut() === 'fermee') {
            $this->addFlash('danger', 'Cette évaluation est fermée. Vous ne pouvez plus soumettre.');
            return $this->redirectToRoute('app_soumission_index');
        }
    }

    if ($form->isSubmitted() && $form->isValid()) {

        if ($soumission->getEvaluation()->getDateLimite() < new \DateTime()) {
            $soumission->setStatut('en_retard');
        } else {
            $soumission->setStatut('soumise');
        }

        $entityManager->persist($soumission);
        $entityManager->flush();

        $this->addFlash('success', 'Soumission créée avec succès !');
        return $this->redirectToRoute('app_soumission_index');
    }

    return $this->render('soumission/new.html.twig', [
        'soumission' => $soumission,
        'form' => $form,
    ]);
}

    #[Route('/{id}', name: 'app_soumission_show', methods: ['GET'])]
    public function show(Soumission $soumission): Response
    {
        return $this->render('soumission/show.html.twig', [
            'soumission' => $soumission,
        ]);
    }

#[Route('/{id}/edit', name: 'app_soumission_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Soumission $soumission, EntityManagerInterface $entityManager): Response
{
    // 🔒 Vérification AVANT le formulaire
    if ($soumission->getEvaluation()->getStatut() === 'fermee') {
        $this->addFlash('danger', 'Cette évaluation est fermée. Modification interdite.');
        return $this->redirectToRoute('app_soumission_index');
    }

    $form = $this->createForm(SoumissionType::class, $soumission);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();

        $this->addFlash('success', 'Soumission modifiée avec succès !');
        return $this->redirectToRoute('app_soumission_index');
    }

    return $this->render('soumission/edit.html.twig', [
        'soumission' => $soumission,
        'form' => $form,
    ]);
}


    #[Route('/{id}', name: 'app_soumission_delete', methods: ['POST'])]
    public function delete(Request $request, Soumission $soumission, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$soumission->getId(), $request->request->get('_token'))) {
            $entityManager->remove($soumission);
            $entityManager->flush();
            
            $this->addFlash('success', 'Soumission supprimée avec succès !');
        }

        return $this->redirectToRoute('app_soumission_index');
    }
}