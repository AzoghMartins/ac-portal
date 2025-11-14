<?php
declare(strict_types=1);

namespace App;

/**
 * Helper for mapping WoW class/race IDs (3.3.5a) to readable
 * names and local icon paths.
 *
 * Expected icon structure:
 *
 *   public/assets/icons/class/
 *     1.gif  2.gif  ...  11.gif
 *
 *   public/assets/icons/race/
 *     1-0.gif 1-1.gif   (Human male/female)
 *     2-0.gif 2-1.gif   (Orc)
 *     ...
 *     11-0.gif 11-1.gif (Draenei)
 *
 * Where raceId is the numeric race from the characters table,
 * and gender is usually 0 = male, 1 = female.
 */
final class WowHelper
{
    // 3.3.5a class IDs
    public const CLASS_NAMES = [
         1 => 'Warrior',
         2 => 'Paladin',
         3 => 'Hunter',
         4 => 'Rogue',
         5 => 'Priest',
         6 => 'Death Knight',
         7 => 'Shaman',
         8 => 'Mage',
         9 => 'Warlock',
        11 => 'Druid',
    ];

    // 3.3.5a race IDs
    public const RACE_NAMES = [
         1 => 'Human',
         2 => 'Orc',
         3 => 'Dwarf',
         4 => 'Night Elf',
         5 => 'Undead',
         6 => 'Tauren',
         7 => 'Gnome',
         8 => 'Troll',
        10 => 'Blood Elf',
        11 => 'Draenei',
    ];

    /**
     * Returns the icon path for a class, using the numeric class ID.
     * Example: /assets/icons/class/1.gif for Warrior.
     */
    public static function classIcon(int $classId): string
    {
        return "/assets/icons/class/{$classId}.gif";
    }

    /**
     * Returns the icon path for a race.
     *
     * $gender is typically:
     *   0 = male
     *   1 = female
     *
     * If gender is null, we default to 0 (male) so the icon still resolves.
     */
    public static function raceIcon(int $raceId, ?int $gender = null): string
    {
        if ($gender === null) {
            $gender = 0;
        }
        return "/assets/icons/race/{$raceId}-{$gender}.gif";
    }

    public static function className(int $classId): string
    {
        return self::CLASS_NAMES[$classId] ?? "Class {$classId}";
    }

    public static function raceName(int $raceId): string
    {
        return self::RACE_NAMES[$raceId] ?? "Race {$raceId}";
    }
}
