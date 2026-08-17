<?php

declare(strict_types=1);

namespace App\StyleGuide\Engine;

use App\StyleGuide\Entity\StyleGuideAnswer;
use App\StyleGuide\Entity\StyleGuideQuestion;
use App\StyleGuide\Enum\SelectionType;
use App\StyleGuide\Service\StyleGuideSession;

final class StyleGuideEngine
{
    public function __construct(
        private readonly StyleGuideQuestionLoader $questionLoader,
        private readonly StyleGuideSession $session,
    ) {
    }

    public function getQuestion(string $code): StyleGuideQuestion
    {
        return $this->questionLoader->getActiveQuestion($code);
    }

    /**
     * @return list<StyleGuideQuestion>
     */
    public function getQuestions(): array
    {
        return $this->questionLoader->getActiveQuestions();
    }

    /**
     * @return string|list<string>|null
     */
    public function getAnswer(string $questionCode): string|array|null
    {
        return $this->session->getAnswer($questionCode);
    }

    public function hasAnswer(string $questionCode): bool
    {
        return $this->session->hasAnswer($questionCode);
    }

    /**
     * @param mixed $submittedValue
     *
     * @return string|list<string>|null
     */
    public function normalizeSubmittedAnswer(
        StyleGuideQuestion $question,
        mixed $submittedValue,
    ): string|array|null {
        if ($question->getSelectionType() === SelectionType::MULTIPLE) {
            if (!is_array($submittedValue)) {
                return null;
            }

            $submittedCodes = array_values(array_unique(array_filter(
                $submittedValue,
                static fn (mixed $value): bool =>
                    is_string($value) && $value !== '',
            )));

            if ($submittedCodes === []) {
                return null;
            }

            $validCodes = $this->getValidAnswerCodes($question);

            $normalized = array_values(array_intersect(
                $submittedCodes,
                $validCodes,
            ));

            return $normalized !== []
                ? $normalized
                : null;
        }

        if (!is_string($submittedValue) || $submittedValue === '') {
            return null;
        }

        if (!in_array(
            $submittedValue,
            $this->getValidAnswerCodes($question),
            true,
        )) {
            return null;
        }

        return $submittedValue;
    }

    /**
     * @param string|list<string> $answer
     */
    public function saveAnswer(
        StyleGuideQuestion $question,
        string|array $answer,
    ): void {
        $this->session->setAnswer(
            $question->getCode(),
            $answer,
        );
    }

    /**
     * @return list<string>
     */
    private function getValidAnswerCodes(
        StyleGuideQuestion $question,
    ): array {
        $codes = [];

        foreach ($question->getAnswers() as $answer) {
            if (
                !$answer instanceof StyleGuideAnswer
                || !$answer->isActive()
            ) {
                continue;
            }

            $codes[] = $answer->getCode();
        }

        return $codes;
    }

    public function getSelectedAnswerEntity(
        string $questionCode,
    ): ?StyleGuideAnswer {
        $question = $this->getQuestion($questionCode);
        $selected = $this->getAnswer($questionCode);

        if (!is_string($selected)) {
            return null;
        }

        foreach ($question->getAnswers() as $answer) {
            if (
                $answer instanceof StyleGuideAnswer
                && $answer->getCode() === $selected
            ) {
                return $answer;
            }
        }

        return null;
    }

    /**
     * @return list<StyleGuideAnswer>
     */
    public function getSelectedAnswerEntities(
        string $questionCode,
    ): array {
        $question = $this->getQuestion($questionCode);
        $selected = $this->getAnswer($questionCode);

        if (!is_array($selected)) {
            return [];
        }

        $selectedLookup = array_fill_keys($selected, true);
        $result = [];

        foreach ($question->getAnswers() as $answer) {
            if (
                $answer instanceof StyleGuideAnswer
                && isset($selectedLookup[$answer->getCode()])
            ) {
                $result[] = $answer;
            }
        }

        return $result;
    }

    public function removeAnswer(string $questionCode): void
    {
        $this->session->removeAnswer($questionCode);
    }
}