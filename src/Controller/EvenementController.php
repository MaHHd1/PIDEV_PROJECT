<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/evenement')]
final class EvenementController extends AbstractController
{
    #[Route(name: 'app_evenement_index', methods: ['GET'])]
    public function index(Request $request, EvenementRepository $evenementRepository): Response
    {
        // Récupérer les critères de recherche depuis la requête
        $criteria = [
            'search' => $request->query->get('search'),
            'type' => $request->query->get('type'),
            'statut' => $request->query->get('statut'),
            'visibilite' => $request->query->get('visibilite'),
            'date_debut' => $request->query->get('date_debut'),
            'sort' => $request->query->get('sort', 'e.dateDebut'),
            'direction' => $request->query->get('direction', 'DESC'),
        ];

        // Récupérer les événements filtrés
        $evenements = $evenementRepository->search($criteria);

        // Récupérer les compteurs par statut pour les filtres
        $statutCounts = $evenementRepository->countByStatus();

        return $this->render('evenement/index.html.twig', [
            'evenements' => $evenements,
            'criteria' => $criteria,
            'statut_counts' => $statutCounts,
        ]);
    }

    #[Route('/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response
    {
        $evenement = new Evenement();

        // Récupérer l'utilisateur connecté
        $user = $security->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour créer un événement.');
            return $this->redirectToRoute('app_login');
        }

        // Assigner le créateur
        $evenement->setCreateur($user);

        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($evenement);
                $entityManager->flush();

                $this->addFlash('success', 'Événement créé avec succès !');
                return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la création : ' . $e->getMessage());
            }
        }

        return $this->render('evenement/new.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement): Response
    {
        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Evenement $evenement,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response
    {
        // Optionnel : restreindre l'édition au créateur ou admin
        $user = $security->getUser();
        if (!$user || ($evenement->getCreateur() !== $user && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier cet événement.');
            return $this->redirectToRoute('app_evenement_index');
        }

        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();

                $this->addFlash('success', 'Événement modifié avec succès.');
                return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la modification : ' . $e->getMessage());
            }
        }

        return $this->render('evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Evenement $evenement,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response
    {
        $user = $security->getUser();

        // Optionnel : restreindre suppression au créateur ou admin
        if (!$user || ($evenement->getCreateur() !== $user && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer cet événement.');
            return $this->redirectToRoute('app_evenement_index');
        }

        if ($this->isCsrfTokenValid('delete' . $evenement->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($evenement);
                $entityManager->flush();

                $this->addFlash('success', 'Événement supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Action API pour la recherche rapide (AJAX)
     */
    #[Route('/api/search', name: 'app_evenement_api_search', methods: ['GET'])]
    public function apiSearch(Request $request, EvenementRepository $evenementRepository): JsonResponse
    {
        $criteria = [
            'search' => $request->query->get('q'),
            'type' => $request->query->get('type'),
            'statut' => $request->query->get('statut'),
            'limit' => 10
        ];

        $evenements = $evenementRepository->search($criteria);

        $data = array_map(function($evenement) {
            return [
                'id' => $evenement->getId(),
                'titre' => $evenement->getTitre(),
                'type' => $evenement->getTypeEvenement()->value,
                'dateDebut' => $evenement->getDateDebut()->format('d/m/Y H:i'),
                'statut' => $evenement->getStatut()->value,
                'url' => $this->generateUrl('app_evenement_show', ['id' => $evenement->getId()])
            ];
        }, $evenements);

        return $this->json($data);
    }

    /**
     * Export des événements en CSV
     */
    #[Route('/export/csv', name: 'app_evenement_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, EvenementRepository $evenementRepository): Response
    {
        $criteria = [
            'search' => $request->query->get('search'),
            'type' => $request->query->get('type'),
            'statut' => $request->query->get('statut'),
        ];

        $evenements = $evenementRepository->search($criteria);

        $csvData = "ID;Titre;Type;Date début;Date fin;Lieu;Capacité;Statut;Visibilité;Créateur\n";
        
        foreach ($evenements as $evenement) {
            $csvData .= sprintf(
                '%d;"%s";"%s";"%s";"%s";"%s";%s;"%s";"%s";"%s"' . "\n",
                $evenement->getId(),
                $evenement->getTitre(),
                $evenement->getTypeEvenement()->value,
                $evenement->getDateDebut()->format('d/m/Y H:i'),
                $evenement->getDateFin()->format('d/m/Y H:i'),
                $evenement->getLieu() ?? '',
                $evenement->getCapaciteMax() ?? '',
                $evenement->getStatut()->value,
                $evenement->getVisibilite()->value,
                $evenement->getCreateur() ? $evenement->getCreateur()->getEmail() : ''
            );
        }

        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="evenements_' . date('Y-m-d') . '.csv"');

        return $response;
    }

    /**
     * Export des événements en PDF (exemple avec Dompdf)
     */
    #[Route('/export/pdf', name: 'app_evenement_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, EvenementRepository $evenementRepository): Response
    {
        $criteria = [
            'search' => $request->query->get('search'),
            'type' => $request->query->get('type'),
            'statut' => $request->query->get('statut'),
        ];

        $evenements = $evenementRepository->search($criteria);

        // Rendu HTML pour le PDF
        $html = $this->renderView('evenement/export/pdf.html.twig', [
            'evenements' => $evenements,
            'date' => new \DateTime(),
        ]);

        // Note: Vous devez installer dompdf ou une autre bibliothèque PDF
        // Pour l'instant, retournons un HTML que vous pourrez convertir plus tard
        $response = new Response($html);
        $response->headers->set('Content-Type', 'text/html');
        $response->headers->set('Content-Disposition', 'attachment; filename="evenements_' . date('Y-m-d') . '.html"');

        return $response;
    }

    /**
     * Calendrier des événements
     */
    #[Route('/calendar', name: 'app_evenement_calendar', methods: ['GET'])]
    public function calendar(EvenementRepository $evenementRepository): Response
    {
        // Récupérer les événements des 3 prochains mois
        $startDate = new \DateTime();
        $endDate = (new \DateTime())->modify('+3 months');

        $criteria = [
            'date_debut' => $startDate,
            'direction' => 'ASC'
        ];

        $evenements = $evenementRepository->search($criteria);

        $calendarEvents = [];
        foreach ($evenements as $evenement) {
            if ($evenement->getDateDebut() <= $endDate) {
                $calendarEvents[] = [
                    'id' => $evenement->getId(),
                    'title' => $evenement->getTitre(),
                    'start' => $evenement->getDateDebut()->format('Y-m-d\TH:i:s'),
                    'end' => $evenement->getDateFin()->format('Y-m-d\TH:i:s'),
                    'color' => $this->getEventColor($evenement->getStatut()->value),
                    'url' => $this->generateUrl('app_evenement_show', ['id' => $evenement->getId()])
                ];
            }
        }

        return $this->render('evenement/calendar.html.twig', [
            'events' => json_encode($calendarEvents),
        ]);
    }

    /**
     * Dashboard statistiques
     */
    #[Route('/statistics', name: 'app_evenement_statistics', methods: ['GET'])]
    public function statistics(EvenementRepository $evenementRepository): Response
    {
        // Statistiques par type
        $typeStats = $this->calculateTypeStatistics($evenementRepository);
        
        // Statistiques par statut
        $statusStats = $evenementRepository->countByStatus();
        
        // Événements à venir
        $upcomingEvents = $evenementRepository->findUpcoming(10);
        
        // Événements récents
        $recentEvents = $evenementRepository->findRecent(10);

        return $this->render('evenement/statistics.html.twig', [
            'type_stats' => $typeStats,
            'status_stats' => $statusStats,
            'upcoming_events' => $upcomingEvents,
            'recent_events' => $recentEvents,
        ]);
    }

    /**
     * Recherche avancée avec plus de filtres
     */
    #[Route('/advanced-search', name: 'app_evenement_advanced_search', methods: ['GET'])]
    public function advancedSearch(Request $request, EvenementRepository $evenementRepository): Response
    {
        $criteria = [
            'search' => $request->query->get('search'),
            'type' => $request->query->get('type'),
            'statut' => $request->query->get('statut'),
            'visibilite' => $request->query->get('visibilite'),
            'date_debut_from' => $request->query->get('date_debut_from'),
            'date_debut_to' => $request->query->get('date_debut_to'),
            'capacite_min' => $request->query->get('capacite_min'),
            'capacite_max' => $request->query->get('capacite_max'),
            'sort' => $request->query->get('sort', 'e.dateDebut'),
            'direction' => $request->query->get('direction', 'DESC'),
        ];

        $evenements = $evenementRepository->search($criteria);

        return $this->render('evenement/advanced_search.html.twig', [
            'evenements' => $evenements,
            'criteria' => $criteria,
        ]);
    }

    /**
     * Dupliquer un événement
     */
    #[Route('/{id}/duplicate', name: 'app_evenement_duplicate', methods: ['GET', 'POST'])]
    public function duplicate(
        Request $request,
        Evenement $originalEvent,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response
    {
        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour dupliquer un événement.');
            return $this->redirectToRoute('app_login');
        }

        // Créer une copie de l'événement
        $newEvent = clone $originalEvent;
        $newEvent->setTitre($originalEvent->getTitre() . ' (Copie)');
        $newEvent->setCreateur($user);
        $newEvent->setDateDebut((new \DateTime())->modify('+7 days'));
        $newEvent->setDateFin((new \DateTime())->modify('+7 days 2 hours'));

        if ($request->isMethod('POST')) {
            try {
                $entityManager->persist($newEvent);
                $entityManager->flush();

                $this->addFlash('success', 'Événement dupliqué avec succès.');
                return $this->redirectToRoute('app_evenement_edit', ['id' => $newEvent->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la duplication : ' . $e->getMessage());
            }
        }

        return $this->render('evenement/duplicate.html.twig', [
            'original_event' => $originalEvent,
            'new_event' => $newEvent,
        ]);
    }

    /**
     * Changer le statut d'un événement (API)
     */
    #[Route('/{id}/change-status', name: 'app_evenement_change_status', methods: ['POST'])]
    public function changeStatus(
        Request $request,
        Evenement $evenement,
        EntityManagerInterface $entityManager,
        Security $security
    ): JsonResponse
    {
        $user = $security->getUser();
        if (!$user || ($evenement->getCreateur() !== $user && !$this->isGranted('ROLE_ADMIN'))) {
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $newStatus = $request->request->get('status');
        $validStatuses = ['planifie', 'en_cours', 'termine', 'annule'];

        if (!in_array($newStatus, $validStatuses)) {
            return $this->json([
                'success' => false,
                'message' => 'Statut invalide'
            ], 400);
        }

        try {
            $evenement->setStatut($newStatus);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Statut mis à jour',
                'new_status' => $newStatus
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper pour déterminer la couleur d'un événement dans le calendrier
     */
    private function getEventColor(string $statut): string
    {
        return match($statut) {
            'planifie' => '#17a2b8', // bleu info
            'en_cours' => '#ffc107', // jaune warning
            'termine' => '#28a745', // vert success
            'annule' => '#dc3545', // rouge danger
            default => '#6c757d', // gris
        };
    }

    /**
     * Calculer les statistiques par type
     */
    private function calculateTypeStatistics(EvenementRepository $repository): array
    {
        $events = $repository->findAll();
        $types = ['webinaire', 'examen', 'reunion', 'atelier'];
        $stats = [];

        foreach ($types as $type) {
            $count = count(array_filter($events, function($event) use ($type) {
                return $event->getTypeEvenement()->value === $type;
            }));
            
            $stats[$type] = [
                'count' => $count,
                'percentage' => count($events) > 0 ? round(($count / count($events)) * 100, 2) : 0
            ];
        }

        return $stats;
    }

    /**
     * Prévisualisation d'un événement
     */
    #[Route('/{id}/preview', name: 'app_evenement_preview', methods: ['GET'])]
    public function preview(Evenement $evenement): Response
    {
        return $this->render('evenement/preview.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    /**
     * Partager un événement
     */
    #[Route('/{id}/share', name: 'app_evenement_share', methods: ['GET'])]
    public function share(Evenement $evenement): Response
    {
        $shareUrl = $this->generateUrl('app_evenement_show', ['id' => $evenement->getId()], true);
        
        return $this->render('evenement/share.html.twig', [
            'evenement' => $evenement,
            'share_url' => $shareUrl,
        ]);
    }

    /**
     * Importer des événements depuis un fichier
     */
    #[Route('/import', name: 'app_evenement_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response
    {
        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour importer des événements.');
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST') && $request->files->has('csv_file')) {
            $file = $request->files->get('csv_file');
            
            if ($file->getClientOriginalExtension() !== 'csv') {
                $this->addFlash('error', 'Le fichier doit être au format CSV.');
                return $this->redirectToRoute('app_evenement_import');
            }

            try {
                $handle = fopen($file->getPathname(), 'r');
                $imported = 0;
                $skipped = 0;
                
                // Ignorer l'en-tête
                fgetcsv($handle, 1000, ';');
                
                while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                    if (count($data) < 5) {
                        $skipped++;
                        continue;
                    }
                    
                    $evenement = new Evenement();
                    $evenement->setTitre($data[0]);
                    $evenement->setDescription($data[1] ?? '');
                    $evenement->setTypeEvenement($data[2] ?? 'webinaire');
                    $evenement->setDateDebut(new \DateTime($data[3]));
                    $evenement->setDateFin(new \DateTime($data[4]));
                    $evenement->setLieu($data[5] ?? '');
                    $evenement->setCapaciteMax((int) ($data[6] ?? 0));
                    $evenement->setStatut($data[7] ?? 'planifie');
                    $evenement->setVisibilite($data[8] ?? 'public');
                    $evenement->setCreateur($user);
                    
                    $entityManager->persist($evenement);
                    $imported++;
                }
                
                fclose($handle);
                $entityManager->flush();
                
                $this->addFlash('success', sprintf('%d événements importés avec succès. %d lignes ignorées.', $imported, $skipped));
                return $this->redirectToRoute('app_evenement_index');
                
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de l\'importation : ' . $e->getMessage());
            }
        }

        return $this->render('evenement/import.html.twig');
    }
}