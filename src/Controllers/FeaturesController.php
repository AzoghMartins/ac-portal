<?php
declare(strict_types=1);

namespace App\Controllers;

use App\View;

final class FeaturesController
{
    public function __invoke(): void
    {
        // Which feature is requested?
        $section = $_GET['section'] ?? 'overview';
        $section = (string)$section;

        // Canonical slugs used from the home page feature cards:
        // blizzlike, progression, playerbots, ahbot
        $features = [
            'overview' => [
                'slug'    => 'overview',
                'name'    => 'Overview',
                'pill'    => 'Core Concept',
                'summary' => 'A lore-aware AzerothCore realm tuned for long-form character stories, not just quick resets.',
                'body'    => '
                    <p>
                        Kardinal WoW is a Wrath of the Lich King realm focused on immersion, continuity,
                        and character-driven progression. The goal is not to race to the end and reroll,
                        but to build a world that feels alive even when you log in alone.
                    </p>
                    <p>
                        This portal gives you insight into the realm: population and realm status,
                        Armory views, and (over time) deeper tools for tracking your characters,
                        campaigns, and long-term goals.
                    </p>
                    <p>
                        On this page you can explore the core design pillars of the realm:
                        how rates are tuned, how progression is structured, and how bots fill out
                        the world and economy without replacing real players.
                    </p>
                ',
            ],

            'blizzlike' => [
                'slug'    => 'blizzlike',
                'name'    => 'Blizzlike Rates',
                'pill'    => 'Core',
                'summary' => '1x experience and loot with a few quality-of-life tweaks to keep the world alive and rewarding.',
                'body'    => '
                    <p>
                        Kardinal runs at <strong>blizzlike base rates</strong> for experience and loot.
                        Leveling is intended to feel like a journey, not a formality you sprint through in an evening.
                        When you gain a level here, it should feel earned.
                    </p>
                    <ul>
                        <li><strong>Experience:</strong> 1x baseline. Rested XP still applies as normal.</li>
                        <li><strong>Loot:</strong> Blizzlike, with private tuning only where it helps long-term stability.</li>
                        <li><strong>Gold &amp; economy:</strong> The AH bot and PlayerBots help keep the world supplied without flooding it.</li>
                    </ul>
                    <p>
                        The intent is to preserve the pacing of original Wrath-era gameplay while
                        still supporting modern realities: smaller populations, busy adult schedules,
                        and the need for bots to cover gaps when friends are offline.
                    </p>
                ',
            ],

            'progression' => [
                'slug'    => 'progression',
                'name'    => 'Individual Progression',
                'pill'    => 'Progression',
                'summary' => 'Characters move through Azeroth\'s history in ordered milestones instead of random content spam.',
                'body'    => '
                    <p>
                        Progression on Kardinal is designed with <strong>per-character history</strong> in mind.
                        Instead of instantly unlocking everything, your character advances through content in
                        <em>stages</em> that mirror the evolving story of Azeroth.
                    </p>
                    <p>
                        Over time, we aim to gate certain pieces of content behind clear milestones:
                    </p>
                    <ul>
                        <li>Completion of key questlines or dungeon achievements.</li>
                        <li>Story arcs that matter to your character&apos;s personal timeline.</li>
                        <li>Optional challenge routes for players who want a harder path.</li>
                    </ul>
                    <p>
                        The long-term vision is that you can look at a character on the Armory and see not just
                        their <em>gear</em>, but also what <em>era</em> of the world they belong to and which
                        story beats they&apos;ve actually lived through.
                    </p>
                    <p>
                        This system is being built gradually, with room for campaigns, seasonal challenges,
                        and custom content layered on top of the base Blizzard progression.
                    </p>
                ',
            ],

            'playerbots' => [
                'slug'    => 'playerbots',
                'name'    => 'PlayerBots',
                'pill'    => 'World Simulation',
                'summary' => 'Intelligent bots that fill parties, populate the world, and make off-peak hours feel alive.',
                'body'    => '
                    <p>
                        The realm uses <strong>AzerothCore PlayerBots</strong> to ensure that the world never feels empty.
                        Whether you log in during a quiet hour or just want to test a dungeon route, you won&apos;t be alone.
                    </p>
                    <p>
                        PlayerBots are:
                    </p>
                    <ul>
                        <li><strong>Configurable:</strong> You can adjust behaviours, roles, and group composition.</li>
                        <li><strong>Party-friendly:</strong> Use bots to fill missing roles for 5-mans and basic content.</li>
                        <li><strong>Respectful of players:</strong> They&apos;re there to support real players, not replace them.</li>
                    </ul>
                    <p>
                        Over time, the goal is to refine bot configurations so that the world feels populated
                        and responsive, while still making real player interaction the best way to experience
                        group content.
                    </p>
                ',
            ],

            'ahbot' => [
                'slug'    => 'ahbot',
                'name'    => 'Auction House Bot',
                'pill'    => 'Economy',
                'summary' => 'A dynamic auction house bot that keeps essential goods flowing without wrecking the economy.',
                'body'    => '
                    <p>
                        An <strong>AH Bot</strong> keeps the auction house stocked with useful items so that
                        crafting, gearing, and consumable use are always viable — even when the population dips.
                    </p>
                    <p>
                        The goals for the AH bot configuration are:
                    </p>
                    <ul>
                        <li>Provide <strong>baseline availability</strong> of key materials and consumables.</li>
                        <li>Avoid flooding the market or undercutting real players whenever possible.</li>
                        <li>React to supply and demand so that prices stay within a healthy band.</li>
                    </ul>
                    <p>
                        The AH bot is not meant to be a vending machine. It is there to complement real trading,
                        keep professions worth leveling, and ensure that dedicated players can always progress
                        their characters and goals without being blocked by an empty auction board.
                    </p>
                ',
            ],
        ];

        if (!isset($features[$section])) {
            $section = 'overview';
        }

        $active = $section;

        View::render('features', [
            'title'    => 'Realm Features',
            'features' => $features,
            'active'   => $active,
        ]);
    }
}
