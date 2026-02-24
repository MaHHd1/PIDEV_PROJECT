<?php

namespace App\Service;

class QuizGeneratorService
{
    public function __construct(private GroqService $groq) {}

    public function generateFromSubject(string $subject, int $nbQuestions, string $level): array
    {
        $prompt = "Génère un quiz de $nbQuestions questions sur le sujet : \"$subject\".
Niveau : $level.
Chaque question doit avoir 4 choix (A, B, C, D), une bonne réponse et une explication courte.";

        return $this->groq->generateQuiz($prompt);
    }

    public function generateFromDocument(string $content, int $nbQuestions): array
    {
        $content = mb_substr($content, 0, 3000);
        $prompt = "Voici un document de cours :\n\n$content\n\nGénère un quiz de $nbQuestions questions basées sur ce contenu. Chaque question doit avoir 4 choix (A, B, C, D), une bonne réponse et une explication.";

        return $this->groq->generateQuiz($prompt);
    }
}