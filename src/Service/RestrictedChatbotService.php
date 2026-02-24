<?php

namespace App\Service;

use App\Repository\EtudiantRepository;
use App\Repository\EnseignantRepository;
use App\Repository\AdministrateurRepository;
use App\Repository\QuizRepository;
use App\Repository\EvaluationRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class RestrictedChatbotService
{
    private $httpClient;
    private $etudiantRepo;
    private $enseignantRepo;
    private $adminRepo;
    private $quizRepo;
    private $evaluationRepo;
    private $logger;
    private $apiKey;
    private $model;

    public function __construct(
        HttpClientInterface $httpClient,
        EtudiantRepository $etudiantRepo,
        EnseignantRepository $enseignantRepo,
        AdministrateurRepository $adminRepo,
        QuizRepository $quizRepo,
        EvaluationRepository $evaluationRepo,
        LoggerInterface $logger,
        string $huggingfaceApiKey,
        string $huggingfaceModel = 'microsoft/phi-2'
    ) {
        $this->httpClient = $httpClient;
        $this->etudiantRepo = $etudiantRepo;
        $this->enseignantRepo = $enseignantRepo;
        $this->adminRepo = $adminRepo;
        $this->quizRepo = $quizRepo;
        $this->evaluationRepo = $evaluationRepo;
        $this->logger = $logger;
        $this->apiKey = $huggingfaceApiKey;
        $this->model = $huggingfaceModel;
    }

    public function ask(string $question): array
    {
        try {
            $intent = $this->analyzeIntent($question);
            $databaseData = $this->fetchDatabaseData($intent, $question);
            $context = $this->formatContext($databaseData);
            $systemPrompt = $this->createSystemPrompt($context);
            $answer = $this->queryHuggingFace($question, $systemPrompt);

            if ($this->containsExternalInfo($answer)) {
                return [
                    'success' => true,
                    'answer' => "Je ne peux répondre qu'avec les informations disponibles dans la base de données.",
                    'data' => $databaseData
                ];
            }

            return [
                'success' => true,
                'answer' => $answer,
                'data' => $databaseData
            ];

        } catch (\Exception $e) {
            $this->logger->error('Chatbot error: ' . $e->getMessage());
            return [
                'success' => false,
                'answer' => 'Désolé, une erreur technique est survenue. Veuillez réessayer.',
                'error' => $e->getMessage()
            ];
        }
    }

    private function analyzeIntent(string $question): array
    {
        $question = strtolower($question);
        $intent = [
            'type' => 'general',
            'entities' => []
        ];

        if (strpos($question, 'étudiant') !== false || strpos($question, 'etudiant') !== false) {
            $intent['entities'][] = 'etudiant';
        }
        if (strpos($question, 'enseignant') !== false) {
            $intent['entities'][] = 'enseignant';
        }
        if (strpos($question, 'admin') !== false || strpos($question, 'administrateur') !== false) {
            $intent['entities'][] = 'administrateur';
        }

        if (strpos($question, 'combien') !== false || strpos($question, 'nombre') !== false) {
            $intent['type'] = 'count';
        } elseif (strpos($question, 'liste') !== false || strpos($question, 'list') !== false) {
            $intent['type'] = 'list';
        } elseif (strpos($question, 'qui') !== false) {
            $intent['type'] = 'who';
        } elseif (strpos($question, 'quand') !== false || strpos($question, 'date') !== false) {
            $intent['type'] = 'when';
        } elseif (strpos($question, 'statistique') !== false || strpos($question, 'stat') !== false) {
            $intent['type'] = 'stats';
        }

        return $intent;
    }

    private function fetchDatabaseData(array $intent, string $question): array
    {
        $data = [];

        $data['stats'] = [
            'etudiants' => $this->etudiantRepo->count([]),
            'enseignants' => $this->enseignantRepo->count([]),
            'administrateurs' => $this->adminRepo->count([]),
            'quiz' => $this->quizRepo->count([]),
            'evaluations' => $this->evaluationRepo->count([])
        ];

        if (in_array('etudiant', $intent['entities']) || $intent['type'] === 'general') {
            $data['etudiants'] = [
                'total' => $this->etudiantRepo->count([]),
                'actifs' => $this->etudiantRepo->count(['statut' => 'actif']),
                'recents' => array_map(function($e) {
                    return [
                        'nom' => $e->getNom(),
                        'prenom' => $e->getPrenom(),
                        'email' => $e->getEmail(),
                        'date_creation' => $e->getDateCreation()?->format('d/m/Y')
                    ];
                }, $this->etudiantRepo->findBy([], ['dateCreation' => 'DESC'], 5))
            ];
        }

        if (in_array('enseignant', $intent['entities']) || $intent['type'] === 'general') {
            $data['enseignants'] = [
                'total' => $this->enseignantRepo->count([]),
                'actifs' => $this->enseignantRepo->count(['statut' => 'actif']),
                'recents' => array_map(function($e) {
                    return [
                        'nom' => $e->getNom(),
                        'prenom' => $e->getPrenom(),
                        'email' => $e->getEmail(),
                        'specialite' => $e->getSpecialite()
                    ];
                }, $this->enseignantRepo->findBy([], ['dateCreation' => 'DESC'], 5))
            ];
        }

        if (in_array('administrateur', $intent['entities']) || $intent['type'] === 'general') {
            $data['administrateurs'] = [
                'total' => $this->adminRepo->count([]),
                'actifs' => $this->adminRepo->count(['actif' => true]),
                'recents' => array_map(function($a) {
                    return [
                        'nom' => $a->getNom(),
                        'prenom' => $a->getPrenom(),
                        'email' => $a->getEmail(),
                        'fonction' => $a->getFonction()
                    ];
                }, $this->adminRepo->findBy([], ['dateCreation' => 'DESC'], 5))
            ];
        }

        if ($intent['type'] === 'list' && !empty($intent['entities'])) {
            $data['list'] = true;
        }

        return $data;
    }

    private function formatContext(array $data): string
    {
        $context = "=== INFORMATIONS DE LA BASE DE DONNÉES NOVALEARN ===\n\n";

        $context .= "STATISTIQUES GÉNÉRALES:\n";
        $context .= "- Total étudiants: {$data['stats']['etudiants']}\n";
        $context .= "- Total enseignants: {$data['stats']['enseignants']}\n";
        $context .= "- Total administrateurs: {$data['stats']['administrateurs']}\n";
        $context .= "- Total quiz: {$data['stats']['quiz']}\n";
        $context .= "- Total évaluations: {$data['stats']['evaluations']}\n\n";

        if (isset($data['etudiants'])) {
            $context .= "DÉTAILS ÉTUDIANTS:\n";
            $context .= "- Total: {$data['etudiants']['total']}\n";
            $context .= "- Actifs: {$data['etudiants']['actifs']}\n";
            $context .= "- 5 derniers inscrits:\n";
            foreach ($data['etudiants']['recents'] as $student) {
                $context .= "  • {$student['prenom']} {$student['nom']} ({$student['email']}) - Inscrit le {$student['date_creation']}\n";
            }
            $context .= "\n";
        }

        if (isset($data['enseignants'])) {
            $context .= "DÉTAILS ENSEIGNANTS:\n";
            $context .= "- Total: {$data['enseignants']['total']}\n";
            $context .= "- Actifs: {$data['enseignants']['actifs']}\n";
            $context .= "- 5 derniers inscrits:\n";
            foreach ($data['enseignants']['recents'] as $teacher) {
                $context .= "  • {$teacher['prenom']} {$teacher['nom']} ({$teacher['email']}) - Spécialité: {$teacher['specialite']}\n";
            }
            $context .= "\n";
        }

        if (isset($data['administrateurs'])) {
            $context .= "DÉTAILS ADMINISTRATEURS:\n";
            $context .= "- Total: {$data['administrateurs']['total']}\n";
            $context .= "- Actifs: {$data['administrateurs']['actifs']}\n";
            $context .= "- 5 derniers:\n";
            foreach ($data['administrateurs']['recents'] as $admin) {
                $context .= "  • {$admin['prenom']} {$admin['nom']} ({$admin['email']}) - Fonction: {$admin['fonction']}\n";
            }
            $context .= "\n";
        }

        return $context;
    }

    private function createSystemPrompt(string $context): string
    {
        return "Tu es un assistant virtuel pour l'application NovaLearn. Tu aides les administrateurs à gérer la plateforme.

RÈGLES IMPÉRATIVES:
1. Tu DOIS utiliser UNIQUEMENT les informations fournies ci-dessous
2. Si l'information n'est PAS dans les données, réponds: 'Je ne peux pas répondre à cette question'
3. Ne JAMAIS inventer ou utiliser des connaissances générales
4. Sois concis et professionnel
5. Réponds TOUJOURS en français

{$context}

Maintenant, réponds à la question de l'administrateur en utilisant UNIQUEMENT les données ci-dessus.";
    }

    private function queryHuggingFace(string $question, string $systemPrompt): string
    {
        try {
            // Pour les modèles comme microsoft/phi-2, le format est différent
            $prompt = "Question: " . $question . "\n\nContexte:\n" . $systemPrompt . "\n\nRéponse:";

            // URL CORRECTE : router.huggingface.co
            $response = $this->httpClient->request('POST', "https://router.huggingface.co/hf-inference/models/" . $this->model, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $prompt,
                    'parameters' => [
                        'max_new_tokens' => 300,
                        'temperature' => 0.3,
                        'top_p' => 0.9,
                    ],
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray();

            if ($statusCode === 200) {
                if (isset($data[0]['generated_text'])) {
                    return trim($data[0]['generated_text']);
                } elseif (isset($data['generated_text'])) {
                    return trim($data['generated_text']);
                }
            } elseif ($statusCode === 503) {
                return "Le modèle est en cours de chargement. Veuillez réessayer dans quelques secondes.";
            }

            return "Désolé, je n'ai pas pu générer une réponse.";

        } catch (\Exception $e) {
            $this->logger->error('Hugging Face error: ' . $e->getMessage());
            return "Désolé, une erreur technique est survenue.";
        }
    }

    private function containsExternalInfo(string $answer): bool
    {
        $externalKeywords = [
            'en général', 'habituellement', 'souvent', 'dans le monde',
            'selon des études', 'internet', 'google', 'wikipedia'
        ];

        foreach ($externalKeywords as $keyword) {
            if (strpos(strtolower($answer), $keyword) !== false) {
                return true;
            }
        }

        return false;
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

    // MÉTHODE DE TEST CORRIGÉE - utilise router, pas api-inference
    public function testApiConnection(): array
    {
        try {
            $testResponse = $this->httpClient->request('POST', "https://router.huggingface.co/hf-inference/models/" . $this->model, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => 'Bonjour, test',
                    'parameters' => [
                        'max_new_tokens' => 10,
                    ],
                ],
                'timeout' => 10,
            ]);

            $statusCode = $testResponse->getStatusCode();
            $content = $testResponse->getContent(false);

            return [
                'success' => true,
                'status_code' => $statusCode,
                'response' => $content,
                'model' => $this->model,
                'url' => "https://router.huggingface.co/hf-inference/models/" . $this->model,
                'api_key_prefix' => substr($this->apiKey, 0, 10) . '...',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'model' => $this->model,
                'url' => "https://router.huggingface.co/hf-inference/models/" . $this->model,
            ];
        }
    }
}