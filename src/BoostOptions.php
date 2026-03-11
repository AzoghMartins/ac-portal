<?php
declare(strict_types=1);

namespace App;

final class BoostOptions
{
    /**
     * @var array<int,array<int,array{value:string,label:string}>>
     */
    private const CLASS_SPECS = [
        1 => [
            ['value' => 'arms', 'label' => 'Arms'],
            ['value' => 'fury', 'label' => 'Fury'],
            ['value' => 'protection', 'label' => 'Protection'],
        ],
        2 => [
            ['value' => 'holy', 'label' => 'Holy'],
            ['value' => 'protection', 'label' => 'Protection'],
            ['value' => 'retribution', 'label' => 'Retribution'],
        ],
        3 => [
            ['value' => 'beastmaster', 'label' => 'Beastmaster'],
            ['value' => 'marksman', 'label' => 'Marksman'],
            ['value' => 'survival', 'label' => 'Survival'],
        ],
        4 => [
            ['value' => 'assassination', 'label' => 'Assassination'],
            ['value' => 'combat', 'label' => 'Combat'],
            ['value' => 'subtlety', 'label' => 'Subtlety'],
        ],
        5 => [
            ['value' => 'discipline', 'label' => 'Discipline'],
            ['value' => 'holy', 'label' => 'Holy'],
            ['value' => 'shadow', 'label' => 'Shadow'],
        ],
        6 => [
            ['value' => 'blood_tank', 'label' => 'Blood Tank'],
            ['value' => 'blood_dps', 'label' => 'Blood DPS'],
            ['value' => 'frost', 'label' => 'Frost'],
            ['value' => 'unholy', 'label' => 'Unholy'],
        ],
        7 => [
            ['value' => 'elemental', 'label' => 'Elemental'],
            ['value' => 'enhancement', 'label' => 'Enhancement'],
            ['value' => 'restoration', 'label' => 'Restoration'],
        ],
        8 => [
            ['value' => 'arcane', 'label' => 'Arcane'],
            ['value' => 'fire', 'label' => 'Fire'],
            ['value' => 'frost', 'label' => 'Frost'],
        ],
        9 => [
            ['value' => 'affliction', 'label' => 'Affliction'],
            ['value' => 'demonology', 'label' => 'Demonology'],
            ['value' => 'destruction', 'label' => 'Destruction'],
        ],
        11 => [
            ['value' => 'balance', 'label' => 'Balance'],
            ['value' => 'feral_tank', 'label' => 'Feral Tank'],
            ['value' => 'feral_dps', 'label' => 'Feral DPS'],
            ['value' => 'restoration', 'label' => 'Restoration'],
        ],
    ];

    /**
     * Canonical profession tokens mapped to character skill IDs.
     *
     * @var array<string,int>
     */
    private const PROFESSIONS = [
        'alchemy' => 171,
        'blacksmithing' => 164,
        'enchanting' => 333,
        'engineering' => 202,
        'herbalism' => 182,
        'inscription' => 773,
        'jewelcrafting' => 755,
        'leatherworking' => 165,
        'mining' => 186,
        'skinning' => 393,
        'tailoring' => 197,
    ];

    /**
     * @return array<int,array{value:string,label:string}>
     */
    public static function specOptionsForClass(int $classId): array
    {
        return self::CLASS_SPECS[$classId] ?? [];
    }

    public static function isValidSpecForClass(int $classId, ?string $spec): bool
    {
        return self::normalizeSpecForClass($classId, $spec) !== null;
    }

    public static function normalizeSpecForClass(int $classId, ?string $spec): ?string
    {
        $spec = self::normalizeToken($spec);
        if ($spec === null) {
            return null;
        }

        foreach (self::specOptionsForClass($classId) as $row) {
            if ($row['value'] === $spec) {
                return $spec;
            }
        }

        return null;
    }

    /**
     * @return array<int,array{value:string,label:string}>
     */
    public static function professionOptions(): array
    {
        $options = [];
        foreach (self::PROFESSIONS as $token => $skillId) {
            $options[] = [
                'value' => $token,
                'label' => ucwords($token),
            ];
        }
        return $options;
    }

    public static function isValidProfession(?string $token): bool
    {
        return self::normalizeProfession($token) !== null;
    }

    public static function normalizeProfession(?string $token): ?string
    {
        $token = self::normalizeToken($token);
        if ($token === null) {
            return null;
        }

        return isset(self::PROFESSIONS[$token]) ? $token : null;
    }

    /**
     * @return int[]
     */
    public static function professionSkillIds(): array
    {
        return array_values(self::PROFESSIONS);
    }

    public static function professionTokenForSkill(int $skillId): ?string
    {
        $token = array_search($skillId, self::PROFESSIONS, true);
        return $token === false ? null : $token;
    }

    private static function normalizeToken(?string $token): ?string
    {
        if ($token === null) {
            return null;
        }

        $token = trim(strtolower($token));
        return $token === '' ? null : $token;
    }
}
