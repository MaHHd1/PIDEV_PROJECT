<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\ParticipationEvenement;
use App\Form\ParticipationEvenementType;
use App\Repository\ParticipationEvenementRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participation-evenement')]
final class ParticipationEvenementController extends AbstractController
{
    #[Route(name: 'app_participation_evenement_index', methods: ['GET'])]
    public function index(ParticipationEvenementRepository $participationEvenementRepository): Response
    {
        return $this->render('participation_evenement/index.html.twig', [
            'participation_evenements' => $participationEvenementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_participation_evenement_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response
    {
        $participation = new ParticipationEvenement();

        // 1. Vérifier que l'utilisateur est connecté
        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour vous inscrire à un événement.');
            return $this->redirectToRoute('app_login');
        }

        $participation->setUtilisateur($user);

        // 2. Pré-remplir l'événement si passé en paramètre (?evenement=5)
        $evenement = null;
        if ($request->query->has('evenement')) {
            $evenementId = $request->query->getInt('evenement');
            $evenement = $entityManager->getRepository(Evenement::class)->find($evenementId);
            if ($evenement) {
                $participation->setEvenement($evenement);
            } else {
                $this->addFlash('warning', 'L\'événement demandé n\'existe pas.');
            }
        }

        // 3. Vérifier si l'utilisateur est DÉJÀ inscrit à cet événement
        if ($participation->getEvenement()) {
            $alreadyExists = $entityManager->getRepository(ParticipationEvenement::class)
                ->findOneBy([
                    'evenement' => $participation->getEvenement(),
                    'utilisateur' => $user,
                ]);

            if ($alreadyExists) {
                $this->addFlash('info', 'Vous êtes déjà inscrit à cet événement.');
                return $this->redirectToRoute('app_evenement_show', [
                    'id' => $participation->getEvenement()->getId()
                ]);
            }
        }

        // 4. Création du formulaire
        $form = $this->createForm(ParticipationEvenementType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($participation);
                $entityManager->flush();

                $this->addFlash('success', 'Votre participation a bien été enregistrée !');
                return $this->redirectToRoute('app_participation_evenement_index');
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'Vous êtes déjà inscrit à cet événement.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Une erreur est survenue : ' . $e->getMessage());
            }
        }

        return $this->render('participation_evenement/new.html.twig', [
            'participationEvenement' => $participation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_participation_evenement_show', methods: ['GET'])]
    public function show(ParticipationEvenement $participationEvenement): Response
    {
        return $this->render('participation_evenement/show.html.twig', [
            'participation_evenement' => $participationEvenement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_participation_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ParticipationEvenement $participationEvenement,
        EntityManagerInterface $entityManager
    ): Response
    {
        $form = $this->createForm(ParticipationEvenementType::class, $participationEvenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Participation modifiée avec succès.');
            return $this->redirectToRoute('app_participation_evenement_index');
        }

        return $this->render('participation_evenement/edit.html.twig', [
            'participation_evenement' => $participationEvenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_participation_evenement_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        ParticipationEvenement $participationEvenement,
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->isCsrfTokenValid('delete' . $participationEvenement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($participationEvenement);
            $entityManager->flush();

            $this->addFlash('success', 'Participation supprimée avec succès.');
        }

        return $this->redirectToRoute('app_participation_evenement_index');
    }
}