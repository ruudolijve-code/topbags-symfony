<?php

declare(strict_types=1);

namespace App\StyleGuide\Engine;

use App\StyleGuide\Entity\StyleGuideQuestion;
use App\StyleGuide\Repository\StyleGuideQuestionRepository;
use RuntimeException;

final class StyleGuideQuestionLoader
{
    public function __construct(
        private readonly StyleGuideQuestionRepository $questionRepository,
    ) {
    }

    public function getActiveQuestion(string $code): StyleGuideQuestion
    {
        $question = $this->questionRepository->createQueryBuilder('question')
            ->addSelect('answers')
            ->leftJoin(
                'question.answers',
                'answers',
                'WITH',
                'answers.isActive = true',
            )
            ->andWhere('question.code = :code')
            ->andWhere('question.isActive = true')
            ->setParameter('code', $code)
            ->orderBy('answers.position', 'ASC')
            ->addOrderBy('answers.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();

        if (!$question instanceof StyleGuideQuestion) {
            throw new RuntimeException(sprintf(
                'Actieve Stijlgids-vraag "%s" bestaat niet.',
                $code,
            ));
        }

        return $question;
    }

    /**
     * @return list<StyleGuideQuestion>
     */
    public function getActiveQuestions(): array
    {
        return $this->questionRepository->findActiveWithAnswers();
    }
}