<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\ParticipationEvenement;
use App\Entity\Utilisateur;
use App\Form\ParticipationEvenementType;
use App\Repository\ParticipationEvenementRepository;
use App\Service\AuthChecker;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participation-evenement')]
final class ParticipationEvenementController extends AbstractController
{
    private AuthChecker $authChecker;

    public function __construct(AuthChecker $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    /**
     * Retourne le bon template de base selon le type d'utilisateur
     */
    private function getBaseTemplate(): string
    {
        if ($this->authChecker->isEnseignant()) {
            return 'enseignant/teacher_base.html.twig';
        } elseif ($this->authChecker->isEtudiant()) {
            return 'etudiant/student_base.html.twig';
        } elseif ($this->authChecker->isAdmin()) {
            return 'admin/admin_base.html.twig';
        }
        
        return 'base.html.twig';
    }

    /**
     * Retourne les variables nécessaires pour les templates de base
     */
    private function getTemplateVariables(EntityManagerInterface $entityManager): array
    {
        $currentUser = $this->authChecker->getCurrentUser();
        
        $variables = [
            'base_template' => $this->getBaseTemplate(),
        ];
        
        if ($this->authChecker->isEtudiant()) {
            $variables['student'] = $currentUser;
        } elseif ($this->authChecker->isEnseignant()) {
            $variables['teacher'] = $currentUser;
             $variables['enseignant'] = $currentUser; 
        } elseif ($this->authChecker->isAdmin()) {
            $variables['admin'] = $currentUser;
        }
        
        return $variables;
    }
    
    #[Route(name: 'app_participation_evenement_index', methods: ['GET'])]
    public function index(
        ParticipationEvenementRepository $participationEvenementRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Vérifier connexion
        if (!$this->authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        $currentUser = $this->authChecker->getCurrentUser();
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($currentUser->getId());

        // Admin voit tout, autres voient leurs propres participations
        if ($this->authChecker->isAdmin()) {
            $participations = $participationEvenementRepository->findAll();
        } else {
            $participations = $participationEvenementRepository->findBy(
                ['utilisateur' => $utilisateur],
                ['dateInscription' => 'DESC']
            );
        }

        return $this->render('participation_evenement/index.html.twig', array_merge(
            $this->getTemplateVariables($entityManager),
            [
                'participation_evenements' => $participations,
            ]
        ));
    }

    #[Route('/new', name: 'app_participation_evenement_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Vérifier connexion
        if (!$this->authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Vous devez être connecté pour vous inscrire à un événement.');
            return $this->redirectToRoute('app_login');
        }

        $participation = new ParticipationEvenement();
        $currentUser = $this->authChecker->getCurrentUser();

        // Récupérer l'entité Utilisateur parent
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($currentUser->getId());
        
        if (!$utilisateur) {
            $this->addFlash('error', 'Erreur lors de la récupération de votre profil.');
            return $this->redirectToRoute('app_login');
        }

        $participation->setUtilisateur($utilisateur);

        // Pré-remplir l'événement si passé en paramètre
        if ($request->query->has('evenement')) {
            $evenementId = $request->query->getInt('evenement');
            $evenement = $entityManager->getRepository(Evenement::class)->find($evenementId);
            if ($evenement) {
                $participation->setEvenement($evenement);
            } else {
                $this->addFlash('warning', 'L\'événement demandé n\'existe pas.');
            }
        }

        // Vérifier si déjà inscrit
        if ($participation->getEvenement()) {
            $alreadyExists = $entityManager->getRepository(ParticipationEvenement::class)
                ->findOneBy([
                    'evenement' => $participation->getEvenement(),
                    'utilisateur' => $utilisateur,
                ]);

            if ($alreadyExists) {
                $this->addFlash('info', 'Vous êtes déjà inscrit à cet événement.');
                return $this->redirectToRoute('app_evenement_show', [
                    'id' => $participation->getEvenement()->getId()
                ]);
            }
        }

        $form = $this->createForm(ParticipationEvenementType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $participation->setUtilisateur($utilisateur);
                
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

        return $this->render('participation_evenement/new.html.twig', array_merge(
            $this->getTemplateVariables($entityManager),
            [
                'participationEvenement' => $participation,
                'form' => $form,
            ]
        ));
    }

    #[Route('/{id}', name: 'app_participation_evenement_show', methods: ['GET'])]
    public function show(
        ParticipationEvenement $participationEvenement,
        EntityManagerInterface $entityManager
    ): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        $currentUser = $this->authChecker->getCurrentUser();
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($currentUser->getId());

        // Vérifier les droits d'accès
        if (!$this->authChecker->isAdmin() 
            && $participationEvenement->getUtilisateur()->getId() !== $utilisateur->getId()) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette participation.');
            return $this->redirectToRoute('app_participation_evenement_index');
        }

        return $this->render('participation_evenement/show.html.twig', array_merge(
            $this->getTemplateVariables($entityManager),
            [
                'participation_evenement' => $participationEvenement,
            ]
        ));
    }

    #[Route('/{id}/edit', name: 'app_participation_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ParticipationEvenement $participationEvenement,
        EntityManagerInterface $entityManager
    ): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        $currentUser = $this->authChecker->getCurrentUser();
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($currentUser->getId());

        // Seul l'utilisateur concerné ou admin peut modifier
        if (!$this->authChecker->isAdmin() 
            && $participationEvenement->getUtilisateur()->getId() !== $utilisateur->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier cette participation.');
            return $this->redirectToRoute('app_participation_evenement_index');
        }

        $form = $this->createForm(ParticipationEvenementType::class, $participationEvenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Participation modifiée avec succès.');
            return $this->redirectToRoute('app_participation_evenement_index');
        }

        return $this->render('participation_evenement/edit.html.twig', array_merge(
            $this->getTemplateVariables($entityManager),
            [
                'participation_evenement' => $participationEvenement,
                'form' => $form,
            ]
        ));
    }

    #[Route('/{id}', name: 'app_participation_evenement_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        ParticipationEvenement $participationEvenement,
        EntityManagerInterface $entityManager
    ): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        $currentUser = $this->authChecker->getCurrentUser();
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($currentUser->getId());

        // Seul l'utilisateur concerné ou admin peut supprimer
        if (!$this->authChecker->isAdmin() 
            && $participationEvenement->getUtilisateur()->getId() !== $utilisateur->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer cette participation.');
            return $this->redirectToRoute('app_participation_evenement_index');
        }

        if ($this->isCsrfTokenValid('delete' . $participationEvenement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($participationEvenement);
            $entityManager->flush();

            $this->addFlash('success', 'Participation supprimée avec succès.');
        }

        return $this->redirectToRoute('app_participation_evenement_index');
    }
}