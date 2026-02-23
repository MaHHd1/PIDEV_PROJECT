<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GroqService
{
    public function __construct(
        private HttpClientInterface $client,
        #[Autowire(env: 'GROQ_API_KEY1')]
        private string $apiKey,
        #[Autowire(env: 'GROQ_MODEL')]
        private string $model
    ) {}

    // =========================================================
    //  MÉTHODE PRIVÉE — Appel API Groq générique
    // =========================================================

    private function callGroq(string $systemPrompt, string $userContent, float $temperature = 0.3): string
    {
        $response = $this->client->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model'    => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userContent],
                ],
                'temperature' => $temperature,
            ]
        ]);

        $data = $response->toArray();
        return $data['choices'][0]['message']['content'] ?? '';
    }

    // =========================================================
    //  1. 📚 Génération de Quiz
    // =========================================================

    public function generateQuiz(string $prompt): array
    {
        $content = $this->callGroq(
            'Tu es un assistant pédagogique sur une plateforme éducative universitaire.
Tu génères des quiz en JSON uniquement, sans texte autour. Format attendu :
{
  "title": "...",
  "questions": [
    {
      "question": "...",
      "options": ["A. ...", "B. ...", "C. ...", "D. ..."],
      "correct": "A",
      "explanation": "..."
    }
  ]
}',
            $prompt,
            0.7
        );

        return json_decode($content, true) ?? [];
    }

    // =========================================================
    //  2. 🚫 Modération — Spam & messages inappropriés
    // =========================================================

    /**
     * Analyse un message avant envoi.
     * Contexte : plateforme éducative entre étudiants et enseignants.
     * Retourne : ['action' => 'allow|warn|block', 'score' => 0.0-1.0, 'reason' => '...']
     */
    public function moderateMessage(string $objet, string $contenu): array
    {
        $content = $this->callGroq(
            'Tu es un modérateur sur une plateforme éducative universitaire.
Les utilisateurs sont uniquement des étudiants et des enseignants.
Analyse le message (objet + contenu) et réponds UNIQUEMENT en JSON sans texte autour :
{
  "is_spam": true/false,
  "is_inappropriate": true/false,
  "score": 0.0,
  "reason": "...",
  "action": "allow"
}
Règles :
- is_spam : publicité, liens suspects, arnaque, contenu sans rapport avec les études
- is_inappropriate : insultes, harcèlement, menaces, intimidation entre membres
- score : 0.0 (message académique normal) → 1.0 (très problématique)
- action : "allow" si score < 0.3 | "warn" si 0.3 ≤ score < 0.7 | "block" si score ≥ 0.7
- IMPORTANT : les messages sur les cours, examens, devoirs, absences,
  notes, rendez-vous avec enseignant, questions académiques → toujours "allow"',
            sprintf('Objet : "%s"\nContenu : "%s"', $objet, $contenu),
            0.1
        );

        $content = preg_replace('/```json|```/', '', trim($content));
        $result  = json_decode($content, true);

        return $result ?? [
            'is_spam'          => false,
            'is_inappropriate' => false,
            'score'            => 0.0,
            'reason'           => 'Analyse indisponible',
            'action'           => 'allow',
        ];
    }

    // =========================================================
    //  3. 💡 Suggestion de réponse
    // =========================================================

    /**
     * Suggère une réponse adaptée au contexte éducatif.
     * Exemples : réponse à une question de cours, demande de rendez-vous,
     * problème d'examen, question administrative.
     */
    public function suggestReply(string $objet, string $contenu): string
    {
        return $this->callGroq(
            'Tu es un assistant de communication sur une plateforme éducative universitaire.
Les échanges sont entre étudiants et enseignants (cours, examens, devoirs, notes, absences...).
Propose UNE seule réponse courte, polie et professionnelle adaptée au contexte scolaire.
- Si c\'est un étudiant qui pose une question → réponds comme un enseignant bienveillant
- Si c\'est un enseignant qui donne une instruction → réponds comme un étudiant respectueux
Réponds uniquement avec le texte de la réponse, sans introduction ni signature.',
            sprintf('Objet du message reçu : "%s"\nContenu : "%s"', $objet, $contenu),
            0.6
        );
    }

    // =========================================================
    //  4. 📝 Résumé de fil de messages (thread)
    // =========================================================

    /**
     * Résume un fil de messages en 3 points clés académiques.
     * Met en avant : demandes scolaires, décisions prises, actions à faire.
     */
    public function summarizeThread(array $thread): string
    {
        if (empty($thread)) {
            return 'Aucun message à résumer.';
        }

        $conversation = implode("\n\n", array_map(function ($msg) {
            $expediteur = $msg->getExpediteur()?->getNomComplet() ?? 'Inconnu';
            $date       = $msg->getDateEnvoi()->format('d/m/Y H:i');
            return sprintf('[%s — %s] : %s', $expediteur, $date, $msg->getContenu());
        }, $thread));

        return $this->callGroq(
            'Tu es un assistant sur une plateforme éducative universitaire.
Résume cette conversation entre étudiants et enseignants en exactement 3 points clés.
Mets en avant : les demandes académiques, les décisions prises, les actions à faire.
Réponds en français, sous forme de liste numérotée courte et claire.',
            "Voici la conversation :\n\n" . $conversation,
            0.3
        );
    }
}