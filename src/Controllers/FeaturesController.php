<?php
declare(strict_types=1);

namespace App\Controllers;

use App\View;

/**
 * Serves the /features page with static realm configuration details.
 */
final class FeaturesController
{
    /**
     * Renders the features list.
     */
    public function __invoke(): void
    {
        // Feature definitions used by the Features page and the home page cards.
        $features = [
            'overview' => [
                'slug' => 'overview',
                'name' => 'Overview',
                'pill' => 'Core Concept',
                'summary' => 'A lore-aware AzerothCore realm tuned for long-form character stories, not just quick resets.',
                'body' => '
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
                ',
            ],

            'blizzlike' => [
                'slug' => 'blizzlike',
                'name' => 'Blizzlike Rates',
                'pill' => 'Core',
                'summary' => 'Experience, loot, and item tables shift with the era you inhabit, binding you to the pace of history.',
                'body' => '
                    <p>
                        Kardinal WoW keeps <strong>blizzlike rates</strong> but bends them to the expansion your character currently walks through.
                        Experience gains, available gear, and drop chances all mirror what Blizzard shipped in that era,
                        so the world feels like the moment it was forged, not a sprint past it.
                    </p>
                    <ul>
                        <li><strong>Levels 1&ndash;60:</strong> Classic pacing reigns. XP flows slowly, loot tables stay lean, and every green item feels like a relic won from the dark.</li>
                        <li><strong>Levels 60&ndash;70:</strong> The Burning Crusade settings take over, restoring that razor balance between Outland danger and reward.</li>
                        <li><strong>Levels 70&ndash;80:</strong> Standard Wrath-era rates return, steady enough for endgame campaigns but never generous enough to dull the edge.</li>
                    </ul>
                    <p>
                        By matching your timeline, leveling remains slower than modern Wrath realms and respectful of the long night between milestones.
                        Rested XP still whispers its aid, but there are no hidden boosts, no easy skips&mdash;only the deliberate march of Azeroth&apos;s wars.
                    </p>
                    <p>
                        The result is an epic, slightly haunted journey where progress carries weight and every upgrade feels earned beneath the northern lights.
                    </p>
                ',
            ],

            'progression' => [
                'slug' => 'progression',
                'name' => 'Individual Progression',
                'pill' => 'Progression',
                'summary' => 'Each hero is locked to a specific patch of WoW history until they master its level, quest, and raid trials.',
                'body' => '
                    <p>
                        Kardinal WoW uses the <strong>Individual Progression</strong> system: your character is bound to a specific
                        patch until they have conquered its requirements. Level caps, dungeon keys, and raid attunements all
                        anchor you in the correct era, and only by defeating that patch&apos;s final boss (or completing its closing quest)
                        does the next slice of history unlock.
                    </p>
                    <p><strong>VANILLA ERA</strong></p>
                    <ul>
                        <li><strong>Tier 0:</strong> Reach level 60.</li>
                        <li><strong>Tier 1:</strong> Defeat Ragnaros and Onyxia.</li>
                        <li><strong>Tier 2:</strong> Defeat Nefarian.</li>
                        <li><strong>Tier 3:</strong> Complete <em>Might of Kalimdor</em> or <em>Bang a Gong!</em>.</li>
                        <li><strong>Tier 4:</strong> Complete <em>Chaos and Destruction</em>.</li>
                        <li><strong>Tier 5:</strong> Defeat C&apos;thun.</li>
                        <li><strong>Tier 6:</strong> Defeat Kel&apos;thuzad.</li>
                        <li><strong>Tier 7:</strong> Complete <em>Into the Breach</em>.</li>
                    </ul>
                    <p><strong>THE BURNING CRUSADE ERA</strong></p>
                    <ul>
                        <li><strong>Tier 8:</strong> Defeat Prince Malchezaar.</li>
                        <li><strong>Tier 9:</strong> Defeat Kael&apos;thas.</li>
                        <li><strong>Tier 10:</strong> Defeat Illidan.</li>
                        <li><strong>Tier 11:</strong> Defeat Zul&apos;jin.</li>
                        <li><strong>Tier 12:</strong> Defeat Kil&apos;jaeden.</li>
                    </ul>
                    <p><strong>WRATH OF THE LICH KING ERA</strong></p>
                    <ul>
                        <li><strong>Tier 13:</strong> Defeat Kel&apos;thuzad (level 80).</li>
                        <li><strong>Tier 14:</strong> Defeat Yogg-Saron.</li>
                        <li><strong>Tier 15:</strong> Defeat Anub&apos;arak.</li>
                        <li><strong>Tier 16:</strong> Defeat the Lich King.</li>
                        <li><strong>Tier 17:</strong> Defeat Halion.</li>
                    </ul>
                    <p>
                        Because progression is tied to <em>defeating the era&apos;s end boss or sealing its questline</em>, your Armory entry
                        tells a true story: which wars you have survived, which gates you have opened, and whether the next age of Azeroth
                        is yet willing to let you pass.
                    </p>
                ',
            ],

            'playerbots' => [
                'slug' => 'playerbots',
                'name' => 'PlayerBots',
                'pill' => 'World Simulation',
                'summary' => 'Intelligent bots that fill parties, populate the world, and make off-peak hours feel alive.',
                'body' => '
                    <p>
                        <strong>PlayerBots</strong> are AI-controlled characters that can join your party, run dungeons,
                        and help keep the world active when real players are scarce. Kardinal supports the
                        <a href="https://github.com/Wishmaster117/MultiBot" target="_blank" rel="noopener">MultiBot addon</a>
                        for richer control, and you can study the full command set on the
                        <a href="https://github.com/mod-playerbots/mod-playerbots/wiki/Playerbot-Commands" target="_blank" rel="noopener">PlayerBot wiki</a>.
                    </p>
                    <p>
                        The goal is not to replace human groups, but to:
                    </p>
                    <ul>
                        <li>Let you run dungeons on your own schedule.</li>
                        <li>Fill gaps in key roles (tank/healer) when the population is uneven.</li>
                        <li>Make questing routes and smaller hubs feel less empty.</li>
                    </ul>
                    <p>
                        Over time, the goal is to refine bot configurations so that the world feels populated
                        and responsive, while still making real player interaction the best way to experience
                        group content.
                    </p>
                ',
            ],

            'ahbot' => [
                'slug' => 'ahbot',
                'name' => 'Auction House Bot',
                'pill' => 'Economy',
                'summary' => 'A dynamic auction house bot that keeps essential goods flowing without wrecking the economy.',
                'body' => '
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

            'challenges' => [
                'slug' => 'challenges',
                'name' => 'Challenge Modes',
                'pill' => 'Rules',
                'summary' => 'Optional difficulty modifiers you can pledge to at the Shrine of Challenge to reshape your leveling journey.',
                'body' => '
                    <p>
                        For players who like their adventures with a sharper edge, <strong>Challenge Modes</strong> let you twist the
                        rules of Azeroth to your liking. At the <em>Shrine of Challenge</em>, found near every starting zone graveyard,
                        fresh characters can pledge themselves to one or more special difficulty modifiers. Each challenge reshapes
                        the leveling journey into something a bit more ruthless, a bit more tactical — and a lot more memorable.
                    </p>

                    <p><strong>Available Challenges</strong></p>
                    <ul>
                        <li><strong>Hardcore:</strong> Death is final. A fallen hero becomes a ghost forever, unable to return to the world of the living.</li>
                        <li><strong>Self-Crafted:</strong> Only equipment crafted by your own hands may be worn. If you didn’t make it, you can’t use it.</li>
                        <li><strong>Normal Gear Only:</strong> You may only equip items of Normal or Poor quality. No fancy stuff.</li>
                        <li><strong>Slow XP Gain:</strong> Experience earned is reduced to 0.5×, making each level a longer climb.</li>
                        <li><strong>Very Slow XP Gain:</strong> For the truly patient: XP is reduced to 0.25×, turning leveling into a marathon of grit.</li>
                        <li><strong>Quest XP Only:</strong> The only way to gain experience is through quests. No grinding mobs, no dungeon spam — pure questing.</li>
                    </ul>

                    <p><strong>Activation &amp; Restrictions</strong><br>
                        Challenges can only be enabled on level 1 characters (or level 55 for Death Knights), and once chosen, they define that character’s entire journey.
                    </p>

                    <p><strong>Combining Challenges</strong><br>
                        Multiple challenges can be active at the same time, as long as they don’t directly contradict each other.
                        For example, XP-based modes (Slow XP, Very Slow XP, and Quest XP Only) cannot be combined with one another,
                        while modes like Hardcore or Self-Crafted can be stacked freely.
                    </p>

                    <p>
                        Forge your path, test your limits, and see how far you can push a character under the harshest conditions.
                        After all, glory tastes better when you’ve had to crawl for it.
                    </p>
                ',
            ],
        ];

        View::render('features', [
            'title' => 'Realm Features',
            'features' => $features,
        ]);
    }
}
