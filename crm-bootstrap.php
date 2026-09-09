<?php

/**
 * X-SIEBEN CRM Bootstrap & Autoloader
 * Ensures full autonomy (Autarkie-Gebot) and automatic class resolution.
 */

if (!defined('CRM_VERSION')) {
    define('CRM_VERSION', '2.9.6');
}

// 1. Autoloader for CRM modular classes (DTOs, Repositories, Services, Renderer)
spl_autoload_register(function ($class) {
    static $classMap = null;

    if ($classMap === null) {
        $baseDir = __DIR__;
        $classMap = [
            // DTOs
            'ParticipantDTO'          => $baseDir . '/dto/ParticipantDTO.php',
            'CourseDTO'               => $baseDir . '/dto/CourseDTO.php',

            // Repositories
            'CrmStatusRepository'     => $baseDir . '/repositories/CrmStatusRepository.php',
            'CourseRepository'        => $baseDir . '/repositories/CourseRepository.php',
            'WPFormsRepository'       => $baseDir . '/repositories/WPFormsRepository.php',

            // Services
            'StatusTransitionService' => $baseDir . '/services/StatusTransitionService.php',
            'CoursePricingService'    => $baseDir . '/services/CoursePricingService.php',
        ];
    }

    if (isset($classMap[$class]) && file_exists($classMap[$class])) {
        require_once $classMap[$class];
    }
});

// 2. Core procedural helpers
require_once __DIR__ . '/helpers/normalize.php';
require_once __DIR__ . '/helpers/crm-status.php';
