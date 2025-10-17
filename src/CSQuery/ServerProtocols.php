<?php declare(strict_types=1);

/**
 * Clansuite Server Query
 *
 * SPDX-FileCopyrightText: 2003-2025 Jens A. Koch
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Clansuite\ServerQuery;

use function array_keys;
use Clansuite\ServerQuery\ServerProtocols\AgeOfTime;
use Clansuite\ServerQuery\ServerProtocols\ArkSurvivalEvolved;
use Clansuite\ServerQuery\ServerProtocols\Arma;
use Clansuite\ServerQuery\ServerProtocols\Arma3;
use Clansuite\ServerQuery\ServerProtocols\ArmaReforger;
use Clansuite\ServerQuery\ServerProtocols\Battlefield4;
use Clansuite\ServerQuery\ServerProtocols\Bc2;
use Clansuite\ServerQuery\ServerProtocols\Bf1942;
use Clansuite\ServerQuery\ServerProtocols\Bf2;
use Clansuite\ServerQuery\ServerProtocols\Bf3;
use Clansuite\ServerQuery\ServerProtocols\Blackops;
use Clansuite\ServerQuery\ServerProtocols\Blackopsmac;
use Clansuite\ServerQuery\ServerProtocols\Blockland;
use Clansuite\ServerQuery\ServerProtocols\Brink;
use Clansuite\ServerQuery\ServerProtocols\Bt;
use Clansuite\ServerQuery\ServerProtocols\Cod4;
use Clansuite\ServerQuery\ServerProtocols\Conan;
use Clansuite\ServerQuery\ServerProtocols\CounterStrike16;
use Clansuite\ServerQuery\ServerProtocols\CounterStrikeSource;
use Clansuite\ServerQuery\ServerProtocols\Cs2;
use Clansuite\ServerQuery\ServerProtocols\Csgo;
use Clansuite\ServerQuery\ServerProtocols\Cube;
use Clansuite\ServerQuery\ServerProtocols\DayOfDefeatSource;
use Clansuite\ServerQuery\ServerProtocols\Dayz;
use Clansuite\ServerQuery\ServerProtocols\Dayzmod;
use Clansuite\ServerQuery\ServerProtocols\Ddnet;
use Clansuite\ServerQuery\ServerProtocols\Deadside;
use Clansuite\ServerQuery\ServerProtocols\DontStarveTogether;
use Clansuite\ServerQuery\ServerProtocols\Doom3;
use Clansuite\ServerQuery\ServerProtocols\Eco;
use Clansuite\ServerQuery\ServerProtocols\Et;
use Clansuite\ServerQuery\ServerProtocols\Etqw;
use Clansuite\ServerQuery\ServerProtocols\Factorio;
use Clansuite\ServerQuery\ServerProtocols\FarmingSimulator;
use Clansuite\ServerQuery\ServerProtocols\Fear;
use Clansuite\ServerQuery\ServerProtocols\Ffow;
use Clansuite\ServerQuery\ServerProtocols\Gamespy2;
use Clansuite\ServerQuery\ServerProtocols\Gmod;
use Clansuite\ServerQuery\ServerProtocols\GtaMta;
use Clansuite\ServerQuery\ServerProtocols\Halflife;
use Clansuite\ServerQuery\ServerProtocols\Halflife2;
use Clansuite\ServerQuery\ServerProtocols\Halo;
use Clansuite\ServerQuery\ServerProtocols\Hl2zp;
use Clansuite\ServerQuery\ServerProtocols\Homefront;
use Clansuite\ServerQuery\ServerProtocols\Ins;
use Clansuite\ServerQuery\ServerProtocols\Jc2;
use Clansuite\ServerQuery\ServerProtocols\Kf2;
use Clansuite\ServerQuery\ServerProtocols\KillingFloor;
use Clansuite\ServerQuery\ServerProtocols\L4d;
use Clansuite\ServerQuery\ServerProtocols\L4d2;
use Clansuite\ServerQuery\ServerProtocols\Lifyo;
use Clansuite\ServerQuery\ServerProtocols\Minecraft;
use Clansuite\ServerQuery\ServerProtocols\Minecraftpe;
use Clansuite\ServerQuery\ServerProtocols\Miscreated;
use Clansuite\ServerQuery\ServerProtocols\Moh;
use Clansuite\ServerQuery\ServerProtocols\Mohw;
use Clansuite\ServerQuery\ServerProtocols\Ns2;
use Clansuite\ServerQuery\ServerProtocols\Pixark;
use Clansuite\ServerQuery\ServerProtocols\Quake;
use Clansuite\ServerQuery\ServerProtocols\Quake2;
use Clansuite\ServerQuery\ServerProtocols\Quake3Arena;
use Clansuite\ServerQuery\ServerProtocols\Quake4;
use Clansuite\ServerQuery\ServerProtocols\QuakeLive;
use Clansuite\ServerQuery\ServerProtocols\Ravaged;
use Clansuite\ServerQuery\ServerProtocols\Ro2;
use Clansuite\ServerQuery\ServerProtocols\Ror;
use Clansuite\ServerQuery\ServerProtocols\Rordh;
use Clansuite\ServerQuery\ServerProtocols\Rust;
use Clansuite\ServerQuery\ServerProtocols\Samp;
use Clansuite\ServerQuery\ServerProtocols\ScpSecretLaboratory;
use Clansuite\ServerQuery\ServerProtocols\Scum;
use Clansuite\ServerQuery\ServerProtocols\Skulltag;
use Clansuite\ServerQuery\ServerProtocols\SniperElite2;
use Clansuite\ServerQuery\ServerProtocols\Squad;
use Clansuite\ServerQuery\ServerProtocols\Starbound;
use Clansuite\ServerQuery\ServerProtocols\StarWarsJK;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use Clansuite\ServerQuery\ServerProtocols\Swat4;
use Clansuite\ServerQuery\ServerProtocols\Swbf2;
use Clansuite\ServerQuery\ServerProtocols\Teamspeak3;
use Clansuite\ServerQuery\ServerProtocols\Terraria;
use Clansuite\ServerQuery\ServerProtocols\Tf2;
use Clansuite\ServerQuery\ServerProtocols\Tibia;
use Clansuite\ServerQuery\ServerProtocols\Tribes2;
use Clansuite\ServerQuery\ServerProtocols\Unreal2;
use Clansuite\ServerQuery\ServerProtocols\UrbanTerror;
use Clansuite\ServerQuery\ServerProtocols\Ut;
use Clansuite\ServerQuery\ServerProtocols\Warhead;
use Clansuite\ServerQuery\ServerProtocols\Wolf;
use Clansuite\ServerQuery\ServerProtocols\Zandronum;

/**
 * Supported Server Protocols Registry.
 *
 * Contains the mapping of protocol names to their implementing classes.
 */
class ServerProtocols
{
    /**
     * Returns a map of supported protocols to their implementing class names.
     *
     * @return array<string, string> An array with names of the supported protocols
     */
    public static function getProtocolsMap(): array
    {
        return [
            'Steam'     => Steam::class,
            'Halflife'  => Halflife::class,
            'Halflife2' => Halflife2::class,
            'Halo'      => Halo::class,
            'Homefront' => Homefront::class,
            // Zombie Panic! Source
            'Hl2zp' => Hl2zp::class,
            'hl2zp' => Hl2zp::class,
            // Insurgency
            'Ins' => Ins::class,
            'ins' => Ins::class,
            // Just Cause 2
            'Jc2' => Jc2::class,
            // Doom
            'Doom3' => Doom3::class,
            'Etqw'  => Etqw::class,
            'Fear'  => Fear::class,
            'Ffow'  => Ffow::class,
            // Quake series
            'Quake'   => Quake::class,
            'Quake2'  => Quake2::class,
            'Quake3a' => Quake3Arena::class,
            'Quake4'  => Quake4::class,
            // Unreal Tournament 2004
            'ut2k4' => Unreal2::class,
            // Counter Strike 1.6
            'Cs16'                => CounterStrike16::class,
            'CounterStrike16'     => CounterStrike16::class,
            'CounterStrikeSource' => CounterStrikeSource::class,
            // Battlefield 4
            'Bf4' => Battlefield4::class,
            // Battlefield Hardline
            'bfhl' => Battlefield4::class,
            // Battlefield 1942
            'Bf1942' => Bf1942::class,
            // Battlefield Vietnam
            'bfv' => Bf1942::class,
            // Battlefield 2
            'Bf2' => Bf2::class,
            // Battlefield 3
            'Bf3'                => Bf3::class,
            'ArkSurvivalEvolved' => ArkSurvivalEvolved::class,
            // ARK: Survival Evolved
            'Arkse' => ArkSurvivalEvolved::class,
            // PixARK
            'Pixark' => Pixark::class,
            'pixark' => Pixark::class,
            // Counter-Strike: Global Offensive
            'Csgo' => Csgo::class,
            // Counter-Strike 2 (CS2) - Valve A2S (Steam)
            'CounterStrike2' => Cs2::class,
            'counterstrike2' => Cs2::class,
            'cs2'            => Cs2::class,
            'Arma3'          => Arma3::class,
            'ArmaReforger'   => ArmaReforger::class,
            // Counter-Strike: Condition Zero (GoldSource) - use CounterStrike16 implementation
            'Czero' => CounterStrike16::class,
            // Counter-Strike: Condition Zero (GoldSource) - use CounterStrike16 implementation
            'czero' => CounterStrike16::class,
            'Rust'  => Rust::class,
            'Gmod'  => Gmod::class,
            // Team Fortress 2
            'Tf2' => Tf2::class,
            // Left 4 Dead 2
            'L4d2' => L4d2::class,
            'L4d'  => L4d::class,
            // Killing Floor 2
            'Kf2'  => Kf2::class,
            'Dayz' => Dayz::class,
            // Call of Duty
            'Cod' => Quake3Arena::class,
            'cod' => Quake3Arena::class,
            // Call of Duty 2
            'Cod2' => Quake3Arena::class,
            'cod2' => Quake3Arena::class,
            // Call of Duty 4
            'Cod4' => Cod4::class,
            // Call of Duty: Black Ops
            'blackops' => Blackops::class,
            // Call of Duty: World at War
            'codww' => Quake3Arena::class,
            // Call of Duty United Offensive
            'uo' => Quake3Arena::class,
            // Medal of Honor Allied Assault
            'mohaa' => Quake3Arena::class,
            // Medal of Honor Spearhead
            'sh' => Quake3Arena::class,
            // Battlefield Bad Company 2
            'Bc2' => Bc2::class,
            // Cube 1
            'Cube'  => Cube::class,
            'cube'  => Cube::class,
            'Cube1' => Cube::class,
            // Assault Cube
            'AssaultCube' => Cube::class,
            // Cube 2: Sauerbraten
            'Sauerbraten' => Cube::class,
            // Blood Frontier
            'BloodFrontier'     => Cube::class,
            'Minecraft'         => Minecraft::class,
            'DayOfDefeatSource' => DayOfDefeatSource::class,
            // Day of Defeat: Source
            'dods' => DayOfDefeatSource::class,
            // 7 Days to Die
            '7daystodie' => Steam::class,
            // Alien Swarm
            'Alienswarm' => Steam::class,
            'alienswarm' => Steam::class,
            // Team Fortress Classic 1.6
            'Tfc' => Steam::class,
            'tfc' => Steam::class,
            // Day of Defeat
            'Dod' => Steam::class,
            'dod' => Steam::class,
            // Insurgency: Sandstorm
            'InsSandstorm'  => Steam::class,
            'ins_sandstorm' => Steam::class,
            // Contagion
            'Contagion' => Steam::class,
            'contagion' => Steam::class,
            // America's Army 3
            'Aa3' => Steam::class,
            'aa3' => Steam::class,
            // America's Army 2.0
            'aa' => Gamespy2::class,
            // ARMA 2
            'Arma2' => Steam::class,
            'arma2' => Steam::class,
            // Battalion 1944
            'Battalion1944' => Steam::class,
            'battalion1944' => Steam::class,
            // Fortress Forever
            'FortressForever' => Steam::class,
            'ff'              => Steam::class,
            // Insurgency (2014)
            'Insurgency2014' => Steam::class,
            'insurgency2014' => Steam::class,
            // Natural Selection
            'NaturalSelection' => Steam::class,
            'hlns'             => Steam::class,
            // Monday Night Combat
            'MondayNightCombat' => Steam::class,
            'mnc'               => Steam::class,
            // Hurtworld
            'Hurtworld' => Steam::class,
            'hurtworld' => Steam::class,
            // Space Engineers
            'SpaceEngineers' => Steam::class,
            'spaceengi'      => Steam::class,
            // Dota 2
            'Dota2' => Steam::class,
            'dota2' => Steam::class,
            // Avorion
            'Avorion' => Steam::class,
            'avorion' => Steam::class,
            // Black Mesa
            'BlackMesa' => Steam::class,
            'blackmesa' => Steam::class,
            // Blade Symphony
            'BladeSymphony' => Steam::class,
            'bladesymphony' => Steam::class,
            // Base Defense
            'BaseDefense' => Steam::class,
            'basedefense' => Steam::class,
            // Action Half-Life
            'ActionHalfLife' => Steam::class,
            'ahl'            => Steam::class,
            // Age of Chivalry
            'AgeOfChivalry' => Steam::class,
            'aoc'           => Steam::class,
            // Aliens vs. Predator (2010)
            'AvP2010' => Steam::class,
            'avp2010' => Steam::class,
            // The Ship
            'TheShip' => Steam::class,
            'theship' => Steam::class,
            // Tower Unite
            'TowerUnite' => Steam::class,
            'towerunite' => Steam::class,
            // Ballistic Overkill
            'BallisticOverkill' => Steam::class,
            'ballisticoverkill' => Steam::class,
            // Barotrauma
            'Barotrauma' => Steam::class,
            'barotrauma' => Steam::class,
            // Abiotic Factor
            'AbioticFactor' => Steam::class,
            'abioticfactor' => Steam::class,
            // Atlas
            'Atlas' => Steam::class,
            'atlas' => Steam::class,
            // BrainBread
            'BrainBread' => Steam::class,
            'brainbread' => Steam::class,
            // BrainBread 2
            'BrainBread2' => Steam::class,
            'brainbread2' => Steam::class,
            // Breach
            'Breach' => Steam::class,
            'breach' => Steam::class,
            // Chivalry: Medieval Warfare
            'Chivalry' => Steam::class,
            'cmw'      => Steam::class,
            // Colony Survival
            'ColonySurvival' => Steam::class,
            'colonysurvival' => Steam::class,
            // Core Keeper
            'CoreKeeper' => Steam::class,
            'corekeeper' => Steam::class,
            // Creativerse
            'Creativerse'  => Steam::class,
            'creativverse' => Steam::class,
            // The Forest
            'TheForest' => Steam::class,
            // The Forest
            'theforest'         => Steam::class,
            'Unturned'          => Steam::class,
            'unturned'          => Steam::class,
            'Valheim'           => Steam::class,
            'valheim'           => Steam::class,
            'VRising'           => Steam::class,
            'vrising'           => Steam::class,
            'ZombiePanicSource' => Steam::class,
            'zps'               => Steam::class,
            'Scum'              => Scum::class,
            'scum'              => Scum::class,
            'Terraria'          => Terraria::class,
            'terraria'          => Terraria::class,
            // Tibia
            'Tibia' => Tibia::class,
            'tibia' => Tibia::class,
            // Skulltag
            'Skulltag' => Skulltag::class,
            'skulltag' => Skulltag::class,
            // Multi Theft Auto (GTA: San Andreas - MTA)
            'mta' => GtaMta::class,
            // Zandronum
            'Zandronum' => Zandronum::class,
            'zandronum' => Zandronum::class,
            // DDnet
            'Ddnet' => Ddnet::class,
            'ddnet' => Ddnet::class,
            // Eco
            'Eco' => Eco::class,
            'eco' => Eco::class,
            // Factorio
            'Factorio' => Factorio::class,
            'factorio' => Factorio::class,
            // Farming Simulator
            'FarmingSimulator' => FarmingSimulator::class,
            'farmingsimulator' => FarmingSimulator::class,
            // Citadel: Forged with Fire
            'Citadel' => Steam::class,
            'citadel' => Steam::class,
            // Conan Exiles
            'Conan' => Conan::class,
            'conan' => Conan::class,
            // Conan Exiles (Source)
            'conanexiles' => Steam::class,
            // Miscreated
            'Miscreated' => Miscreated::class,
            'miscreated' => Miscreated::class,
            // Project Zomboid
            'Zomboid' => Steam::class,
            'zomboid' => Steam::class,
            // Wurm Online
            'Wurm'                => Steam::class,
            'wurm'                => Steam::class,
            'source'              => Steam::class,
            'quake3'              => Quake3Arena::class,
            'doom3'               => Doom3::class,
            'etqw'                => Etqw::class,
            'fear'                => Fear::class,
            'ffow'                => Ffow::class,
            'halo'                => Homefront::class,
            'homefront'           => Homefront::class,
            'bf1942'              => Bf1942::class,
            'bf2'                 => Bf2::class,
            'bf3'                 => Bf3::class,
            'bc2'                 => Bc2::class,
            'brink'               => Brink::class,
            'minecraft'           => Minecraft::class,
            'minecraftpe'         => Minecraftpe::class,
            'dayz'                => Dayz::class,
            'dayzmod'             => Dayzmod::class,
            'l4d'                 => L4d::class,
            'lifyo'               => Lifyo::class,
            'jc2'                 => Jc2::class,
            'killingfloor'        => KillingFloor::class,
            'mw3'                 => Steam::class,
            'bt'                  => Bt::class,
            'cod4'                => Cod4::class,
            'blackopsmac'         => Blackopsmac::class,
            'moh'                 => Moh::class,
            'mohw'                => Mohw::class,
            'unreal2'             => Unreal2::class,
            'arma'                => Arma::class,
            'starbound'           => Starbound::class,
            'swat4'               => Swat4::class,
            'ut'                  => Ut::class,
            'teamspeak3'          => Teamspeak3::class,
            'et'                  => Et::class,
            'urbanterror'         => UrbanTerror::class,
            'ql'                  => QuakeLive::class,
            'wolf'                => Wolf::class,
            'StarWarsJK'          => StarWarsJK::class,
            'ravaged'             => Ravaged::class,
            'ro2'                 => Ro2::class,
            'ror'                 => Ror::class,
            'rordh'               => Rordh::class,
            'sniperelite2'        => SniperElite2::class,
            'armareforger'        => ArmaReforger::class,
            'deadside'            => Deadside::class,
            'dontstarvetogether'  => DontStarveTogether::class,
            'samp'                => Samp::class,
            'scpsecretlaboratory' => ScpSecretLaboratory::class,
            'ns2'                 => Ns2::class,
            'warhead'             => Warhead::class,
            'swbf2'               => Swbf2::class,
            'swjk'                => StarWarsJK::class,
            'tribes2'             => Tribes2::class,
            'ageoftime'           => AgeOfTime::class,
            'blockland'           => Blockland::class,
            'squad'               => Squad::class,
        ];
    }

    /**
     * Returns the names of the supported server protocols.
     *
     * @return array<string> An array with names of the supported protocols
     */
    public static function getSupportedProtocols(): array
    {
        $protocolMap = self::getProtocolsMap();

        return array_keys($protocolMap);
    }

    /**
     * Return the class name for a given protocol.
     *
     * @param string $protocolClassname the protocol name
     *
     * @return string the class name
     */
    public static function getProtocolClass(string $protocolClassname): mixed
    {
        $protocolMap = self::getProtocolsMap();

        return $className = $protocolMap[$protocolClassname] ?? $protocolClassname;
    }
}
