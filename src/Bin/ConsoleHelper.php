<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Bin;

use DateTimeImmutable;
use DateTimeZone;
use ReflectionClass;

class ConsoleHelper
{
    public static function makeSpecial(string $text, string $color, string $font): string
    {
        $colors = [
            'black' => 30,
            'red' => 31,
            'green' => 32,
            'yellow' => 33,
            'blue' => 34,
            'magenta' => 35,
            'cyan' => 36,
            'white' => 37
        ];
        $fonts = [
            'reset' => 0,
            'bold' => 1,
            'underline' => 4,
            'reverse' => 7
        ];
        $codeColor = $colors[$color] ?? 37;
        $codeFont = $fonts[$font] ?? 0;
        $newString = "\033[{$codeFont};{$codeColor}m{$text}\033[0m";
        return $newString;
    }

    public static function getCommands(string $spaceName, string $directory): array
    {
        $commands = glob($directory . "/*.php");
        $cmdList = [];
        foreach ($commands as $command) {
            $className = basename($command, '.php');
            $fullName = $spaceName . $className;
            if (!class_exists($fullName)) {
                require_once $command;
            }
            if (!class_exists($fullName)) {
                continue;
            }
            $reflection = new ReflectionClass($fullName);
            if (!$reflection->isInstantiable()) {
                continue;
            }
            if (is_a($fullName, ConsoleInterface::class, true)) {
                $cmdList[] = $className;
            }
        }
        return $cmdList;
    }

    public static function saveToFile(string $path, string $filename, mixed $datas): bool
    {
        $fullPath = $path . DIRECTORY_SEPARATOR . $filename;
        //Check if path exists
        if (!is_dir($path)) {
            mkdir($path);
        }
        if (file_exists($fullPath)) {
            $name = basename($filename);
            rename($fullPath, $path . DIRECTORY_SEPARATOR . $name . '.old');
        }
        //put content in file
        if (false === file_put_contents($fullPath, $datas)) {
            return false;
        }
        return true;
    }

    public static function getDateTime(): DateTimeImmutable
    {
        $timezone = self::getWindowsTimezone();
        $nowDate = new DateTimeImmutable('now', new DateTimeZone($timezone));
        return $nowDate;
    }

    public static function ask(string $question): string
    {
        echo $question . ": ";
        return trim(fgets(STDIN));
    }

    private static function getWindowsTimezone(): string
    {
        // Try to get timezone from Windows registry
        if (PHP_OS_FAMILY === 'Windows') {
            // Method 1: Use tzutil command
            $output = shell_exec('tzutil /g');
            if ($output) {
                $windowsTimezone = trim($output);
                // Convert Windows timezone to PHP timezone
                $phpTimezone = self::convertWindowsTimezoneToPhp($windowsTimezone);
                if ($phpTimezone) {
                    return $phpTimezone;
                }
            }
            // Method 2: Try PowerShell
            $output = shell_exec('powershell -Command "Get-TimeZone | Select-Object -ExpandProperty Id"');
            if ($output) {
                $windowsTimezone = trim($output);
                $phpTimezone = self::convertWindowsTimezoneToPhp($windowsTimezone);
                if ($phpTimezone) {
                    return $phpTimezone;
                }
            }
        }
        // Fallback to UTC
        return 'UTC';
    }

    private static function convertWindowsTimezoneToPhp(string $windowsTimezone): ?string
    {
        // Windows timezone to PHP timezone mapping
        $timezoneMap = [
            'Dateline Standard Time' => 'Etc/GMT+12',
            'UTC-11' => 'Etc/GMT+11',
            'Aleutian Standard Time' => 'America/Adak',
            'Hawaiian Standard Time' => 'Pacific/Honolulu',
            'Marquesas Standard Time' => 'Pacific/Marquesas',
            'Alaskan Standard Time' => 'America/Anchorage',
            'UTC-09' => 'Etc/GMT+9',
            'Pacific Standard Time (Mexico)' => 'America/Tijuana',
            'UTC-08' => 'Etc/GMT+8',
            'Pacific Standard Time' => 'America/Los_Angeles',
            'US Mountain Standard Time' => 'America/Phoenix',
            'Mountain Standard Time (Mexico)' => 'America/Chihuahua',
            'Mountain Standard Time' => 'America/Denver',
            'Central America Standard Time' => 'America/Guatemala',
            'Central Standard Time' => 'America/Chicago',
            'Easter Island Standard Time' => 'Pacific/Easter',
            'Central Standard Time (Mexico)' => 'America/Mexico_City',
            'Canada Central Standard Time' => 'America/Regina',
            'SA Pacific Standard Time' => 'America/Bogota',
            'Eastern Standard Time (Mexico)' => 'America/Cancun',
            'Eastern Standard Time' => 'America/New_York',
            'Haiti Standard Time' => 'America/Port-au-Prince',
            'Cuba Standard Time' => 'America/Havana',
            'US Eastern Standard Time' => 'America/Indianapolis',
            'Turks And Caicos Standard Time' => 'America/Grand_Turk',
            'Paraguay Standard Time' => 'America/Asuncion',
            'Atlantic Standard Time' => 'America/Halifax',
            'Venezuela Standard Time' => 'America/Caracas',
            'Central Brazilian Standard Time' => 'America/Cuiaba',
            'SA Western Standard Time' => 'America/La_Paz',
            'Pacific SA Standard Time' => 'America/Santiago',
            'Newfoundland Standard Time' => 'America/St_Johns',
            'Tocantins Standard Time' => 'America/Araguaina',
            'E. South America Standard Time' => 'America/Sao_Paulo',
            'SA Eastern Standard Time' => 'America/Cayenne',
            'Argentina Standard Time' => 'America/Buenos_Aires',
            'Greenland Standard Time' => 'America/Godthab',
            'Montevideo Standard Time' => 'America/Montevideo',
            'Magallanes Standard Time' => 'America/Punta_Arenas',
            'Saint Pierre Standard Time' => 'America/Miquelon',
            'Bahia Standard Time' => 'America/Bahia',
            'UTC-02' => 'Etc/GMT+2',
            'Mid-Atlantic Standard Time' => 'Atlantic/South_Georgia',
            'Azores Standard Time' => 'Atlantic/Azores',
            'Cape Verde Standard Time' => 'Atlantic/Cape_Verde',
            'UTC' => 'Etc/UTC',
            'GMT Standard Time' => 'Europe/London',
            'Greenwich Standard Time' => 'Atlantic/Reykjavik',
            'Sao Tome Standard Time' => 'Africa/Sao_Tome',
            'Morocco Standard Time' => 'Africa/Casablanca',
            'W. Europe Standard Time' => 'Europe/Berlin',
            'Central Europe Standard Time' => 'Europe/Budapest',
            'Romance Standard Time' => 'Europe/Paris',
            'Central European Standard Time' => 'Europe/Warsaw',
            'W. Central Africa Standard Time' => 'Africa/Lagos',
            'Jordan Standard Time' => 'Asia/Amman',
            'GTB Standard Time' => 'Europe/Bucharest',
            'Middle East Standard Time' => 'Asia/Beirut',
            'Egypt Standard Time' => 'Africa/Cairo',
            'E. Europe Standard Time' => 'Europe/Chisinau',
            'Syria Standard Time' => 'Asia/Damascus',
            'West Bank Standard Time' => 'Asia/Hebron',
            'South Africa Standard Time' => 'Africa/Johannesburg',
            'FLE Standard Time' => 'Europe/Kiev',
            'Israel Standard Time' => 'Asia/Jerusalem',
            'Kaliningrad Standard Time' => 'Europe/Kaliningrad',
            'Sudan Standard Time' => 'Africa/Khartoum',
            'Libya Standard Time' => 'Africa/Tripoli',
            'Namibia Standard Time' => 'Africa/Windhoek',
            'Arabic Standard Time' => 'Asia/Baghdad',
            'Turkey Standard Time' => 'Europe/Istanbul',
            'Arab Standard Time' => 'Asia/Riyadh',
            'Belarus Standard Time' => 'Europe/Minsk',
            'Russian Standard Time' => 'Europe/Moscow',
            'E. Africa Standard Time' => 'Africa/Nairobi',
            'Iran Standard Time' => 'Asia/Tehran',
            'Arabian Standard Time' => 'Asia/Dubai',
            'Astrakhan Standard Time' => 'Europe/Astrakhan',
            'Azerbaijan Standard Time' => 'Asia/Baku',
            'Russia Time Zone 3' => 'Europe/Samara',
            'Mauritius Standard Time' => 'Indian/Mauritius',
            'Saratov Standard Time' => 'Europe/Saratov',
            'Georgian Standard Time' => 'Asia/Tbilisi',
            'Volgograd Standard Time' => 'Europe/Volgograd',
            'Caucasus Standard Time' => 'Asia/Yerevan',
            'Afghanistan Standard Time' => 'Asia/Kabul',
            'West Asia Standard Time' => 'Asia/Tashkent',
            'Ekaterinburg Standard Time' => 'Asia/Yekaterinburg',
            'Pakistan Standard Time' => 'Asia/Karachi',
            'Qyzylorda Standard Time' => 'Asia/Qyzylorda',
            'India Standard Time' => 'Asia/Calcutta',
            'Sri Lanka Standard Time' => 'Asia/Colombo',
            'Nepal Standard Time' => 'Asia/Katmandu',
            'Central Asia Standard Time' => 'Asia/Almaty',
            'Bangladesh Standard Time' => 'Asia/Dhaka',
            'Omsk Standard Time' => 'Asia/Omsk',
            'Myanmar Standard Time' => 'Asia/Rangoon',
            'SE Asia Standard Time' => 'Asia/Bangkok',
            'Altai Standard Time' => 'Asia/Barnaul',
            'W. Mongolia Standard Time' => 'Asia/Hovd',
            'North Asia Standard Time' => 'Asia/Krasnoyarsk',
            'N. Central Asia Standard Time' => 'Asia/Novosibirsk',
            'Tomsk Standard Time' => 'Asia/Tomsk',
            'China Standard Time' => 'Asia/Shanghai',
            'North Asia East Standard Time' => 'Asia/Irkutsk',
            'Singapore Standard Time' => 'Asia/Singapore',
            'W. Australia Standard Time' => 'Australia/Perth',
            'Taipei Standard Time' => 'Asia/Taipei',
            'Ulaanbaatar Standard Time' => 'Asia/Ulaanbaatar',
            'Aus Central W. Standard Time' => 'Australia/Eucla',
            'Transbaikal Standard Time' => 'Asia/Chita',
            'Tokyo Standard Time' => 'Asia/Tokyo',
            'North Korea Standard Time' => 'Asia/Pyongyang',
            'Korea Standard Time' => 'Asia/Seoul',
            'Yakutsk Standard Time' => 'Asia/Yakutsk',
            'Cen. Australia Standard Time' => 'Australia/Adelaide',
            'AUS Central Standard Time' => 'Australia/Darwin',
            'E. Australia Standard Time' => 'Australia/Brisbane',
            'AUS Eastern Standard Time' => 'Australia/Sydney',
            'West Pacific Standard Time' => 'Pacific/Port_Moresby',
            'Tasmania Standard Time' => 'Australia/Hobart',
            'Vladivostok Standard Time' => 'Asia/Vladivostok',
            'Lord Howe Standard Time' => 'Australia/Lord_Howe',
            'Bougainville Standard Time' => 'Pacific/Bougainville',
            'Russia Time Zone 10' => 'Asia/Srednekolymsk',
            'Magadan Standard Time' => 'Asia/Magadan',
            'Norfolk Standard Time' => 'Pacific/Norfolk',
            'Sakhalin Standard Time' => 'Asia/Sakhalin',
            'Central Pacific Standard Time' => 'Pacific/Guadalcanal',
            'Russia Time Zone 11' => 'Asia/Kamchatka',
            'New Zealand Standard Time' => 'Pacific/Auckland',
            'UTC+12' => 'Etc/GMT-12',
            'Fiji Standard Time' => 'Pacific/Fiji',
            'Chatham Islands Standard Time' => 'Pacific/Chatham',
            'UTC+13' => 'Etc/GMT-13',
            'Tonga Standard Time' => 'Pacific/Tongatapu',
            'Samoa Standard Time' => 'Pacific/Apia',
            'Line Islands Standard Time' => 'Pacific/Kiritimati'
        ];
        return $timezoneMap[$windowsTimezone] ?? null;
    }
}
