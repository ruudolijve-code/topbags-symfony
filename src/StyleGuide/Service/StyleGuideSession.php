<?php

declare(strict_types=1);

namespace App\StyleGuide\Service;

use App\StyleGuide\Enum\StyleWorld;
use App\StyleGuide\Enum\UseMoment;
use App\StyleGuide\Enum\BagSizePreference;
use App\StyleGuide\Enum\CarryItem;
use App\StyleGuide\Enum\CarryMethod;
use App\StyleGuide\Enum\MaterialPreference;
use App\StyleGuide\Enum\BudgetPreference;
use App\StyleGuide\Enum\OutfitPreference;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class StyleGuideSession
{
    private const SESSION_KEY = 'style_guide';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function setStyleWorld(StyleWorld $styleWorld): void
    {
        $data = $this->all();
        $data['style_world'] = $styleWorld->value;

        $this->session()->set(self::SESSION_KEY, $data);
    }

    public function getStyleWorld(): ?StyleWorld
    {
        $value = $this->all()['style_world'] ?? null;

        if (!is_string($value)) {
            return null;
        }

        return StyleWorld::tryFrom($value);
    }

    public function setUseMoment(UseMoment $useMoment): void
    {
        $data = $this->all();
        $data['use_moment'] = $useMoment->value;

        $this->session()->set(self::SESSION_KEY, $data);
    }

    public function getUseMoment(): ?UseMoment
    {
        $value = $this->all()['use_moment'] ?? null;

        if (!is_string($value)) {
            return null;
        }

        return UseMoment::tryFrom($value);
    }

    /**
     * @param list<CarryItem> $items
     */
    public function setCarryItems(array $items): void
    {
        $data = $this->all();

        $data['carry_items'] = array_map(
            static fn (CarryItem $item): string => $item->value,
            $items
        );

        $this->session()->set(self::SESSION_KEY, $data);
    }

    /**
     * @return list<CarryItem>
     */
    public function getCarryItems(): array
    {
        $values = $this->all()['carry_items'] ?? [];

        if (!is_array($values)) {
            return [];
        }

        return CarryItem::fromRequestValues($values);
    }

    public function setBagSizePreference(
        BagSizePreference $bagSizePreference,
    ): void {
        $data = $this->all();
        $data['bag_size_preference'] = $bagSizePreference->value;

        $this->session()->set(self::SESSION_KEY, $data);
    }

    public function getBagSizePreference(): ?BagSizePreference
    {
        $value = $this->all()['bag_size_preference'] ?? null;

        if (!is_string($value)) {
            return null;
        }

        return BagSizePreference::tryFrom($value);
    }

    public function setCarryMethod(CarryMethod $carryMethod): void
    {
        $data = $this->all();
        $data['carry_method'] = $carryMethod->value;

        $this->session()->set(self::SESSION_KEY, $data);
    }

    public function getCarryMethod(): ?CarryMethod
    {
        $value = $this->all()['carry_method'] ?? null;

        if (!is_string($value)) {
            return null;
        }

        return CarryMethod::tryFrom($value);
    }

    public function setMaterialPreference(
        MaterialPreference $materialPreference,
    ): void {
        $data = $this->all();
        $data['material_preference'] = $materialPreference->value;

        $this->session()->set(self::SESSION_KEY, $data);
    }

    public function getMaterialPreference(): ?MaterialPreference
    {
        $value = $this->all()['material_preference'] ?? null;

        if (!is_string($value)) {
            return null;
        }

        return MaterialPreference::tryFrom($value);
    }

    public function setBudgetPreference(
        BudgetPreference $budgetPreference,
    ): void {
        $data = $this->all();
        $data['budget_preference'] = $budgetPreference->value;

        $this->session()->set(self::SESSION_KEY, $data);
    }

    public function getBudgetPreference(): ?BudgetPreference
    {
        $value = $this->all()['budget_preference'] ?? null;

        if (!is_string($value)) {
            return null;
        }

        return BudgetPreference::tryFrom($value);
    }

    public function setOutfitPreference(
        OutfitPreference $outfitPreference,
    ): void {
        $data = $this->all();
        $data['outfit_preference'] = $outfitPreference->value;

        $this->session()->set(self::SESSION_KEY, $data);
    }

    public function getOutfitPreference(): ?OutfitPreference
    {
        $value = $this->all()['outfit_preference'] ?? null;

        if (!is_string($value)) {
            return null;
        }

        return OutfitPreference::tryFrom($value);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $data = $this->session()->get(self::SESSION_KEY, []);

        return is_array($data) ? $data : [];
    }

    public function clear(): void
    {
        $this->session()->remove(self::SESSION_KEY);
    }

    public function setPersonalWish(?string $personalWish): void
    {
        $data = $this->all();
        $value = $personalWish !== null ? trim($personalWish) : '';

        if ($value === '') {
            unset($data['personal_wish']);
        } else {
            $data['personal_wish'] = mb_substr($value, 0, 1000);
        }

        $this->session()->set(self::SESSION_KEY, $data);
    }

    public function getPersonalWish(): ?string
    {
        $value = $this->all()['personal_wish'] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    private function session(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    /**
     * @param string|list<string> $value
     */
    public function setAnswer(
        string $questionCode,
        string|array $value,
    ): void {
        $data = $this->all();

        $data['answers'] ??= [];

        if (!is_array($data['answers'])) {
            $data['answers'] = [];
        }

        $data['answers'][$questionCode] = $value;

        $this->session()->set(self::SESSION_KEY, $data);
    }

    public function getAnswer(string $questionCode): string|array|null
    {
        $answers = $this->all()['answers'] ?? [];

        if (!is_array($answers)) {
            return null;
        }

        $value = $answers[$questionCode] ?? null;

        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            return array_values(array_filter(
                $value,
                static fn (mixed $item): bool => is_string($item),
            ));
        }

        return null;
    }

    /**
     * @return array<string, string|list<string>>
     */
    public function getAnswers(): array
    {
        $answers = $this->all()['answers'] ?? [];

        if (!is_array($answers)) {
            return [];
        }

        $result = [];

        foreach ($answers as $questionCode => $value) {
            if (!is_string($questionCode)) {
                continue;
            }

            if (is_string($value)) {
                $result[$questionCode] = $value;

                continue;
            }

            if (is_array($value)) {
                $result[$questionCode] = array_values(array_filter(
                    $value,
                    static fn (mixed $item): bool => is_string($item),
                ));
            }
        }

        return $result;
    }

    public function hasAnswer(string $questionCode): bool
    {
        $answer = $this->getAnswer($questionCode);

        if (is_string($answer)) {
            return $answer !== '';
        }

        return is_array($answer) && $answer !== [];
    }

    public function removeAnswer(string $questionCode): void
    {
        $data = $this->all();
        $answers = $data['answers'] ?? [];

        if (!is_array($answers)) {
            return;
        }

        unset($answers[$questionCode]);

        $data['answers'] = $answers;

        $this->session()->set(self::SESSION_KEY, $data);
    }
    }
