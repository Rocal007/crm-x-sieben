<?php

/**
 * Service to calculate status transitions and labels in X-SIEBEN CRM.
 * Acts as a State Machine for entry lifecycles.
 */
class StatusTransitionService
{
    /**
     * Resolve the next status and human-readable label based on current state and triggered action context.
     *
     * @param string $currentStatusKey The current status of the entry.
     * @param string $context          The action context ('xsieben_angebot', 'kurszeitenbestaetigung', etc.)
     * @return array{key: string, label: string}
     */
    public function resolveNextStatus(string $currentStatusKey, string $context): array
    {
        $normalizedContext = strtolower(trim($context));

        switch ($normalizedContext) {
            case 'kurszeitenbestaetigung':
            case 'xsieben_kurszeitenbestaetigung':
                if ($currentStatusKey === 'angebot_gesendet') {
                    return [
                        'key'   => 'angebot_und_kurszeiten_gesendet',
                        'label' => 'Angebot & KB gesendet',
                    ];
                }
                return [
                    'key'   => 'kurszeitenbestaetigung_gesendet',
                    'label' => 'Kurszeitenbestätigung gesendet',
                ];

            case 'xsieben_angebot_kurszeiten':
            case 'xsieben_angebot_und_kurszeiten':
                return [
                    'key'   => 'angebot_und_kurszeiten_gesendet',
                    'label' => 'Angebot & KB gesendet',
                ];

            case 'teilnahmebestaetigung':
            case 'xsieben_teilnahmebestaetigung':
                return [
                    'key'   => 'teilnahmebestaetigung_gesendet',
                    'label' => 'Teilnahmebestätigung gesendet',
                ];

            case 'xsieben_diplom':
            case 'diplom':
                return [
                    'key'   => 'diplom_gesendet',
                    'label' => 'Diplom gesendet',
                ];

            case 'anmeldung':
            case 'xsieben_anmeldung':
                return [
                    'key'   => 'angemeldet',
                    'label' => 'Anmeldung gesendet',
                ];

            default:
                // Default: Angebot versendet
                if ($currentStatusKey === 'kurszeitenbestaetigung_gesendet') {
                    return [
                        'key'   => 'angebot_und_kurszeiten_gesendet',
                        'label' => 'Angebot & KB gesendet',
                    ];
                }
                return [
                    'key'   => 'angebot_gesendet',
                    'label' => 'Angebot gesendet',
                ];
        }
    }
}
