<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

class DmiMosqueParser
{
    /**
     * @return array<int, array{
     *     dmi_code: string,
     *     name: string,
     *     address: string|null,
     *     village: string|null,
     *     district_name: string|null,
     *     city: string|null,
     *     province: string|null,
     *     latitude: float|null,
     *     longitude: float|null,
     *     phone: string|null
     * }>
     */
    public static function parse(string $html): array
    {
        $previous = libxml_use_internal_errors(true);

        $document = new DOMDocument;
        $loaded = $document->loadHTML(
            '<?xml encoding="utf-8" ?>'.$html,
            LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return [];
        }

        $xpath = new DOMXPath($document);
        $rows = $xpath->query(
            '//table[contains(concat(" ", normalize-space(@class), " "), " masjid-dmi-table ")]//tbody/tr'
        );

        if ($rows === false) {
            return [];
        }

        $mosques = [];

        foreach ($rows as $row) {
            if (! $row instanceof DOMElement) {
                continue;
            }

            $cells = $xpath->query('./td', $row);

            if ($cells === false || $cells->length < 4) {
                continue;
            }

            $code = self::cleanText($cells->item(0));
            $name = self::cleanText($cells->item(1));

            if ($code === '' || $name === '') {
                continue;
            }

            $addressCell = $cells->item(2);
            $address = self::cleanText($addressCell);
            $address = trim((string) preg_replace('/\s*\(lokasi\)\s*$/u', '', $address));

            $region = self::splitRegion(self::cleanText($cells->item(3)));
            [$latitude, $longitude] = self::coordinates($xpath, $addressCell);

            $mosques[] = [
                'dmi_code' => $code,
                'name' => $name,
                'address' => $address !== '' ? $address : null,
                'village' => $region['village'],
                'district_name' => $region['district_name'],
                'city' => $region['city'],
                'province' => $region['province'],
                'latitude' => $latitude,
                'longitude' => $longitude,
                'phone' => self::nullableText($cells->item(4) ? self::cleanText($cells->item(4)) : ''),
            ];
        }

        return $mosques;
    }

    private static function cleanText(?object $node): string
    {
        if ($node === null) {
            return '';
        }

        $text = $node->textContent ?? '';

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private static function nullableText(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array{village: string|null, district_name: string|null, city: string|null, province: string|null}
     */
    private static function splitRegion(string $region): array
    {
        $parts = array_values(array_filter(
            array_map('trim', explode(',', $region)),
            fn (string $part): bool => $part !== ''
        ));

        return match (count($parts)) {
            0 => [
                'village' => null,
                'district_name' => null,
                'city' => null,
                'province' => null,
            ],
            1 => [
                'village' => null,
                'district_name' => null,
                'city' => null,
                'province' => $parts[0],
            ],
            2 => [
                'village' => null,
                'district_name' => null,
                'city' => $parts[0],
                'province' => $parts[1],
            ],
            3 => [
                'village' => null,
                'district_name' => $parts[0],
                'city' => $parts[1],
                'province' => $parts[2],
            ],
            default => [
                'village' => $parts[0],
                'district_name' => $parts[1],
                'city' => $parts[2],
                'province' => $parts[3],
            ],
        };
    }

    /**
     * @return array{0: float|null, 1: float|null}
     */
    private static function coordinates(DOMXPath $xpath, ?object $addressCell): array
    {
        if ($addressCell === null) {
            return [null, null];
        }

        $links = $xpath->query('.//a[contains(@href, "google.com/maps")]', $addressCell);

        if ($links === false || $links->length === 0) {
            return [null, null];
        }

        $href = $links->item(0)?->getAttribute('href') ?? '';

        if (! preg_match('/[?&]query=(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $href, $matches)) {
            return [null, null];
        }

        return [(float) $matches[1], (float) $matches[2]];
    }
}
