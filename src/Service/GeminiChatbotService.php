<?php

namespace App\Service;

use App\Repository\EtudiantRepository;
use App\Repository\EnseignantRepository;
use App\Repository\AdministrateurRepository;
use App\Repository\QuizRepository;
use App\Repository\EvaluationRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GeminiChatbotService
{
    private $httpClient;
    private $etudiantRepo;
    private $enseignantRepo;
    private $adminRepo;
    private $quizRepo;
    private $evaluationRepo;
    private $logger;
    private $apiKey;

    public function __construct(
        HttpClientInterface $httpClient,
        EtudiantRepository $etudiantRepo,
        EnseignantRepository $enseignantRepo,
        AdministrateurRepository $adminRepo,
        QuizRepository $quizRepo,
        EvaluationRepository $evaluationRepo,
        LoggerInterface $logger,
        string $geminiApiKey
    ) {
        $this->httpClient = $httpClient;
        $this->etudiantRepo = $etudiantRepo;
        $this->enseignantRepo = $enseignantRepo;
        $this->adminRepo = $adminRepo;
        $this->quizRepo = $quizRepo;
        $this->evaluationRepo = $evaluationRepo;
        $this->logger = $logger;
        $this->apiKey = $geminiApiKey;
    }

    public function ask(string $question): array
    {
        try {
            // 1. Récupérer les données de la base
            $databaseData = $this->fetchDatabaseData($question);

            // 2. Formater le contexte
            $context = $this->formatContext($databaseData);

            // 3. Créer le prompt pour Gemini
            $prompt = $this->createPrompt($question, $context);

            // 4. Appeler l'API Gemini
            $answer = $this->queryGemini($prompt);

            return [
                'success' => true,
                'answer' => $answer,
                'data' => $databaseData
            ];

        } catch (\Exception $e) {
            $this->logger->error('Gemini error: ' . $e->getMessage());
            return [
                'success' => false,
                'answer' => 'Désolé, une erreur technique est survenue. Veuillez réessayer.',
                'error' => $e->getMessage()
            ];
        }
    }

    private function fetchDatabaseData(string $question): array
    {
        $question = strtolower($question);
        $data = [];

        // Stats générales toujours disponibles
        $data['stats'] = [
            'etudiants' => $this->etudiantRepo->count([]),
            'enseignants' => $this->enseignantRepo->count([]),
            'administrateurs' => $this->adminRepo->count([]),
            'quiz' => $this->quizRepo->count([]),
            'evaluations' => $this->evaluationRepo->count([])
        ];

        // Données spécifiques selon la question
        if (strpos($question, 'étudiant') !== false || strpos($question, 'etudiant') !== false) {
            $data['etudiants_detail'] = [
                'total' => $this->etudiantRepo->count([]),
                'actifs' => $this->etudiantRepo->count(['statut' => 'actif']),
                'recents' => array_map(function($e) {
                    return "{$e->getPrenom()} {$e->getNom()} ({$e->getEmail()})";
                }, $this->etudiantRepo->findBy([], ['dateCreation' => 'DESC'], 5))
            ];
        }

        if (strpos($question, 'enseignant') !== false) {
            $data['enseignants_detail'] = [
                'total' => $this->enseignantRepo->count([]),
                'actifs' => $this->enseignantRepo->count(['statut' => 'actif']),
                'recents' => array_map(function($e) {
                    return "{$e->getPrenom()} {$e->getNom()} - {$e->getSpecialite()}";
                }, $this->enseignantRepo->findBy([], ['dateCreation' => 'DESC'], 5))
            ];
        }

        if (strpos($question, 'admin') !== false || strpos($question, 'administrateur') !== false) {
            $data['admins_detail'] = [
                'total' => $this->adminRepo->count([]),
                'actifs' => $this->adminRepo->count(['actif' => true]),
                'recents' => array_map(function($a) {
                    return "{$a->getPrenom()} {$a->getNom()} - {$a->getFonction()}";
                }, $this->adminRepo->findBy([], ['dateCreation' => 'DESC'], 5))
            ];
        }

        return $data;
    }

    private function formatContext(array $data): string
    {
        $context = "DONNÉES ACTUELLES DE L'APPLICATION NOVALEARN :\n\n";

        $context .= "STATISTIQUES GÉNÉRALES :\n";
        $context .= "- Étudiants : {$data['stats']['etudiants']}\n";
        $context .= "- Enseignants : {$data['stats']['enseignants']}\n";
        $context .= "- Administrateurs : {$data['stats']['administrateurs']}\n";
        $context .= "- Quiz : {$data['stats']['quiz']}\n";
        $context .= "- Évaluations : {$data['stats']['evaluations']}\n\n";

        if (isset($data['etudiants_detail'])) {
            $context .= "DÉTAILS ÉTUDIANTS :\n";
            $context .= "Total : {$data['etudiants_detail']['total']} (dont {$data['etudiants_detail']['actifs']} actifs)\n";
            $context .= "5 derniers inscrits :\n";
            foreach ($data['etudiants_detail']['recents'] as $recent) {
                $context .= "- $recent\n";
            }
            $context .= "\n";
        }

        if (isset($data['enseignants_detail'])) {
            $context .= "DÉTAILS ENSEIGNANTS :\n";
            $context .= "Total : {$data['enseignants_detail']['total']} (dont {$data['enseignants_detail']['actifs']} actifs)\n";
            $context .= "5 derniers inscrits :\n";
            foreach ($data['enseignants_detail']['recents'] as $recent) {
                $context .= "- $recent\n";
            }
            $context .= "\n";
        }

        if (isset($data['admins_detail'])) {
            $context .= "DÉTAILS ADMINISTRATEURS :\n";
            $context .= "Total : {$data['admins_detail']['total']} (dont {$data['admins_detail']['actifs']} actifs)\n";
            $context .= "5 derniers inscrits :\n";
            foreach ($data['admins_detail']['recents'] as $recent) {
                $context .= "- $recent\n";
            }
            $context .= "\n";
        }

        return $context;
    }

    private function createPrompt(string $question, string $context): string
    {
        return "Tu es un assistant virtuel pour l'application NovaLearn. Tu aides les administrateurs à gérer la plateforme.

RÈGLES IMPÉRATIVES :
1. Réponds UNIQUEMENT avec les informations fournies ci-dessous dans le CONTEXTE
2. Si la réponse n'est pas dans les données, dis : 'Je ne peux pas répondre à cette question car l'information n'est pas disponible.'
3. Ne JAMAIS inventer d'information
4. Sois concis et professionnel
5. Réponds TOUJOURS en français

CONTEXTE (données actuelles) :
{$context}

QUESTION DE L'ADMINISTRATEUR : {$question}

RÉPONSE (utilise UNIQUEMENT les données du CONTEXTE ci-dessus) :";
    }

    private function queryGemini(string $prompt): string
    {
        try {
            // Utilisation du modèle gemini-1.5-flash (gratuit)
            $response = $this->httpClient->request('POST', "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $this->apiKey, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 500,
                        'topP' => 0.8,
                        'topK' => 40
                    ]
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                $errorContent = $response->getContent(false);
                $this->logger->error("Gemini API error: " . $errorContent);
                return "Désolé, l'API Gemini est temporairement indisponible.";
            }

            $data = $response->toArray();

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($data['candidates'][0]['content']['parts'][0]['text']);
            }

            return "Désolé, je n'ai pas pu générer une réponse.";

        } catch (\Exception $e) {
            $this->logger->error('Gemini query error: ' . $e->getMessage());
            return "Désolé, une erreur technique est survenue.";
        }
    }

    public function testApiConnection(): array
    {
        try {
            $testResponse = $this->httpClient->request('POST', "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $this->apiKey, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => 'Dis "Bonjour, je fonctionne !" en français']
                            ]
                        ]
                    ]
                ],
                'timeout' => 10,
            ]);

            $statusCode = $testResponse->getStatusCode();
            $content = $testResponse->getContent(false);
            $data = json_decode($content, true);

            return [
                'success' => $statusCode === 200,
                'status_code' => $statusCode,
                'response' => $data,
                'api_working' => $statusCode === 200,
                'message' => $statusCode === 200 ? 'API Gemini fonctionne !' : 'Erreur API Gemini'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'api_working' => false
            ];
        }
    }

    public function getQuickStats(): array
    {
        return [
            'etudiants' => $this->etudiantRepo->count([]),
            'enseignants' => $this->enseignantRepo->count([]),
            'administrateurs' => $this->adminRepo->count([]),
            'quiz' => $this->quizRepo->count([]),
            'evaluations' => $this->evaluationRepo->count([]),
        ];
    }
}