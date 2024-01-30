<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

use Isolated\Symfony\Component\Finder\Finder;

/**
 * This file is for php-scoper, a tool used when prefixing dependencies.
 * TODO: link to docs here
 */

$dependenciesToPrefix = json_decode(getenv('MATOMO_DEPENDENCIES_TO_PREFIX'), true);
$namespacesToPrefix = json_decode(getenv('MATOMO_NAMESPACES_TO_PREFIX'), true);
$isRenamingReferences = getenv('MATOMO_RENAME_REFERENCES') == 1;

$namespacesToExclude = [];
$forceNoGlobalAlias = false;

if ($isRenamingReferences) {
    $finders = [
        Finder::create()
            ->files()
            ->in(__DIR__)
            ->exclude('build')
            ->exclude('vendor')
            ->exclude('node_modules')
            ->exclude('tmp')
            ->exclude('@types')
            ->exclude('js')
            ->exclude('lang')
            ->notName('*.ini.php')
            ->notPath('%^tests/PHPUnit/proxy/console$%')
            ->notPath('%^console$%')
            ->notPath('%^tests/javascript/index.php$%')
            ->notName('scoper.inc.php')

            // prefixing will change the line number of an exception and break the test, so we'll just skip it
            ->notPath('%^plugins/Monolog/tests/Unit/Processor/ExceptionToTextProcessorTest\\.php$%')
            ->notPath('%^tests/PHPUnit/System/ConsoleTest\\.php$%')
            ->notPath('%^tests/PHPUnit/System/FrontControllerTest\\.php$%')
            ->notPath('%^tests/resources/trigger-fatal\\.php$%')

            ->filter(function (\SplFileInfo $file) {
                return !($file->isLink() && $file->isDir());
            })
            ->filter(function (\SplFileInfo $file) {
                return !($file->isLink() && !$file->getRealPath());
            }),
    ];

    $namespacesToExclude = ['/^$/'];
    $forceNoGlobalAlias = true;
} else {
    $finders = array_map(function ($dependency) {
        return Finder::create()
            ->files()
            ->in($dependency);
    }, $dependenciesToPrefix);
}

$namespacesToIncludeRegexes = array_map(function ($n) {
    $n = rtrim($n, '\\');
    return '/^' . preg_quote($n) . '(?:\\\\|$)/';
}, $namespacesToPrefix);

return [
    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
    'force-no-global-alias' => $forceNoGlobalAlias,
    'prefix' => 'Matomo\\Dependencies',
    'finders' => $finders,
    'patchers' => [
        // patchers for twig
        static function (string $filePath, string $prefix, string $content) use ($isRenamingReferences): string {
            // correct use statements in generated templates
            if (preg_match('%twig/src/Node/ModuleNode\\.php$%', $filePath)) {
                return str_replace('"use Twig\\', '"use ' . str_replace('\\', '\\\\', $prefix) . '\\\\Twig\\', $content);
            }

            // correctly scope function calls to twig_... globals (which will not be globals anymore) in strings
            if (strpos($filePath, 'twig/twig') !== false
                || ($isRenamingReferences && preg_match('/\\.php$/', $filePath))
            ) {
                if ($isRenamingReferences) {
                    $content = preg_replace("/([^'\"])(_?twig_[a-z_0-9]+)\\(/", '${1}\\Matomo\\Dependencies\\\${2}(', $content);
                }

                $content = preg_replace("/'(_?twig_[a-z_0-9]+)([('])/", '\'\\Matomo\\Dependencies\\\${1}${2}', $content);
                $content = preg_replace("/\"(_?twig_[a-z_0-9]+)([(\"])/", '"\\\\\\Matomo\\\\\\Dependencies\\\\\\\${1}${2}', $content);

                $content = preg_replace("/([^\\\\])(_?twig_[a-z_0-9]+)\(\"/", '${1}\\\\\\Matomo\\\\\\Dependencies\\\\\\\${2}("', $content);
                $content = preg_replace("/([^\\\\])(_?twig_[a-z_0-9]+)\('/", '${1}\\Matomo\\Dependencies\\\${2}(\'', $content);
            }

            return $content;
        },

        // php-di has trouble w/ core\DI.php, since it has a class named DI and uses the DI namespace. replacing manually here.
        static function (string $filePath, string $prefix, string $content) use ($isRenamingReferences): string {
            if (!$isRenamingReferences || !preg_match('%core/DI\\.php%', $filePath)) {
                return $content;
            }

            $content = str_replace('use DI ', 'use Matomo\\Dependencies\\DI ', $content);
            $content = str_replace('\\DI\\', 'Matomo\\Dependencies\\DI\\', $content);

            return $content;
        },

        // the config/config.php file can sometimes be rendered empty (if, it just has return [], eg)
        static function (string $filePath, string $prefix, string $content) use ($isRenamingReferences): string {
            if (!$isRenamingReferences || !preg_match('%config/config\\.php%', $filePath)) {
                return $content;
            }

            if (preg_match('/^<\\?php\s+$/', $content)) {
                $content = '<?php return [];';
            }

            return $content;
        },

        // some control character sequences are not escaped properly by php-parser (and ReleaseChecklistTest complains)
        static function (string $filePath, string $prefix, string $content) use ($isRenamingReferences): string {
            if (preg_match('%symfony/string/AbstractString\\.php$%', $filePath)
                || preg_match('%symfony/string/AbstractUnicodeString\\.php$%', $filePath)
                || preg_match('%plugins/ImageGraph/StaticGraph\\.php$%', $filePath)
                || preg_match('%symfony/polyfill-intl-normalizer/Resources/unidata/compatibilityDecomposition\\.php$%', $filePath)
            ) {
                $content = str_replace(html_entity_decode('&nbsp;'), "\\xC2\\xA0", $content);
            }

            return $content;
        },

        // test related patchers
        static function (string $filePath, string $prefix, string $content) use ($isRenamingReferences): string {
            if (!$isRenamingReferences) {
                return $content;
            }

            if ($filePath === __DIR__ . '/index.php') { // for ReleaseCheckListTest.php
                $content = str_replace("\\define('PIWIK_PRINT_ERROR_BACKTRACE', \\false);", "define('PIWIK_PRINT_ERROR_BACKTRACE', false);", $content);
            }

            // disable the OneClickUpdate test since it's expected it can't work (as the downloaded Matomo will not be prefixed)
            if ($filePath === __DIR__ . '/tests/UI/specs/OneClickUpdate_spec.js'
                || $filePath === __DIR__ . '/tests/UI/specs/OneClickLastForcedUpdate_spec.js'
            ) {
                $content = str_replace('it(', 'it.skip(', $content);
            }

            return $content;
        },
    ],
    'include-namespaces' => $namespacesToIncludeRegexes,
    'exclude-namespaces' => $namespacesToExclude,
    'exclude-constants' => [
        '/^self::/', // work around php-scoper bug
    ],
];
