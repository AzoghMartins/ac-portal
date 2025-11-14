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

    /** @var array<int,string> */
    private const MAP_NAMES = [
        0   => 'Eastern Kingdoms',
        1   => 'Kalimdor',
        530 => 'Outland',
        571 => 'Northrend',
        // Add more instance maps here if you want (e.g. 609 => 'Ebon Hold')
    ];

    /**
     * Note: this is a partial list for now, focused on major zones and cities.
     * You can extend it whenever you like with more `zoneId => 'Name'` pairs.
     *
     * Zone IDs are the same ones you see in the `characters.zone` column
     * (and in AreaTable.dbc). These are 3.3.5a IDs.
     *
     * Example: 1537 = Ironforge, 1519 = Stormwind City, etc.
     *
     * @var array<int,string>
     */
    private const ZONE_NAMES = [
        // Alliance starting / levelling
        12   => 'Elwynn Forest',
        1    => 'Dun Morogh',
        38   => 'Loch Modan',
        44   => 'Redridge Mountains',
        10   => 'Duskwood',
        1519 => 'Stormwind City',
        1537 => 'Ironforge',
        3483 => 'Hellfire Peninsula',
        3520 => 'Shadowmoon Valley',

        // Horde starting / levelling
        14   => 'Durotar',
        17   => 'The Barrens',
        85   => 'Tirisfal Glades',
        130  => 'Silverpine Forest',
        1637 => 'Orgrimmar',
        1638 => 'Thunder Bluff',
        3487 => 'Silvermoon City',

        // Neutral hubs / capitals
        3703 => 'Shattrath City',
        4395 => 'Dalaran',

        // Example you mentioned
        // 1537 => 'Ironforge',  // already there
    ];

    public static function mapName(int $mapId): ?string
    {
        return self::MAP_NAMES[$mapId] ?? null;
    }

    public static function zoneName(int $zoneId): ?string
    {
        return self::ZONE_NAMES[$zoneId] ?? null;
    }



}
