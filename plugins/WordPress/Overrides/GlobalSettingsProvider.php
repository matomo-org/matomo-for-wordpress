<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides;

use Matomo\Ini\IniWriter;
use Piwik\Application\Kernel\GlobalSettingsProvider as DefaultGlobalSettingsProvider;
use Piwik\Common;
use Piwik\SettingsServer;
use WpMatomo\Installer;
use WpMatomo\Logger;
use WpMatomo\Settings;

/**
 * A GlobalSettingsProvider that keeps a backup copy of the Matomo config data in a WordPress option
 * (ie. the database).
 */
class GlobalSettingsProvider extends DefaultGlobalSettingsProvider
{
    const REDACTED_SECTIONS = ['database', 'database_reader', 'database_tests'];

    const DATABASE_KEYS_TO_BACKUP = [
        'charset',
        'collation',
        'enable_ssl',
        'ssl_ca',
        'ssl_ca_path',
        'ssl_cert',
        'ssl_cipher',
        'ssl_key',
        'ssl_no_verify',
    ];

    /**
     * Config keys that look like secrets (matched by this pattern) are left out of the DB backup.
     * A false positive only means the value is not restored, so the pattern errs on the side of
     * matching too much.
     */
    const SECRET_KEY_PATTERN = '/password|passwd|passphrase|secret|salt|token|bearer|credential|private_?key|api_?key|license_?key/i';

    /**
     * Name of the INI section that is always written as the very last section of config.ini.php
     * (enforced by the Config.beforeSave event handler in the WordPress plugin and by
     * writeLocalConfigFile()). If it is missing, a write to the file is in progress or was
     * interrupted (eg. disk full), so the file contents cannot be trusted. Added once to
     * existing config files during the plugin update (see Updater).
     */
    const END_OF_FILE_MARKER_SECTION = 'WpMatomoEndOfFileMarker';

    const END_OF_FILE_MARKER_KEY = 'marker';

    /**
     * How long a config.ini.php without the end-of-file marker is assumed to be a write in
     * progress. Once it is older than this, the write is considered interrupted for good and
     * the file is restored from the backup.
     */
    const INCOMPLETE_FILE_GRACE_PERIOD_SECONDS = 300;

    /**
     * @var \WpMatomo\Settings
     */
    private $settings;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct($pathGlobal = null, $pathLocal = null, $pathCommon = null, Settings $settings = null)
    {
        $this->settings = $settings;

        // before parent::__construct(), which calls reload() and can log via a restore
        $this->logger = new Logger();

        parent::__construct($pathGlobal, $pathLocal, $pathCommon);
    }

    public function reload($pathGlobal = null, $pathLocal = null, $pathCommon = null)
    {
        parent::reload($pathGlobal, $pathLocal, $pathCommon);
        $this->syncOrRestoreConfigBackup();
        $this->detectExtraPluginsToLoad();
    }

    public function persistConfigOption()
    {
        // only persist the values that differ from the INI default settings (ie, what would go
        // in config.ini.php), minus anything secret or blog-specific
        $diff = $this->removeValuesExcludedFromBackup($this->computeUserConfigDiff());

        $this->getWpMatomoSettings()->update_config_backup($diff);

        $this->updateEncryptedSaltIfNeeded();
    }

    /**
     * Keeps an encrypted copy of the salt in a dedicated per-blog option so a restore can
     * bring the original salt back instead of generating a new one (a new salt would silently
     * invalidate visitors' signed tracking opt-out cookies — a privacy compliance violation — and
     * change config_id fingerprints).
     *
     * The salt is encrypted with a key derived from the WP auth key (wp_salt('auth'), ie.
     * AUTH_KEY . AUTH_SALT from wp-config.php, which survives the loss of config.ini.php), so
     * a wp_options dump alone does not reveal it. Cheap fingerprints of the auth key and of
     * the salt make this a no-op on the fast path: encryption only runs when the record does
     * not exist yet, the WP auth key was rotated, or the salt itself changed.
     */
    private function updateEncryptedSaltIfNeeded()
    {
        if (!$this->isSaltEncryptionSupported()) {
            return;
        }

        $general = $this->iniFileChain->get('General');
        $salt    = is_array($general) && !empty($general['salt']) ? $general['salt'] : '';
        if ('' === $salt || !is_string($salt)) {
            return;
        }

        $keyFingerprint  = $this->computeAuthKeyFingerprint();
        $saltFingerprint = $this->computeSaltFingerprint($salt);

        $record = $this->getWpMatomoSettings()->get_encrypted_salt_backup();
        if (!empty($record['ciphertext'])
            && isset($record['key_fingerprint'], $record['salt_fingerprint'])
            && $record['key_fingerprint'] === $keyFingerprint
            && $record['salt_fingerprint'] === $saltFingerprint
        ) {
            return; // up to date; only two cheap hashes were computed
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $this->getWpMatomoSettings()->update_encrypted_salt_backup([
            'ciphertext'       => base64_encode($nonce . sodium_crypto_secretbox($salt, $nonce, $this->deriveSaltEncryptionKey())),
            'key_fingerprint'  => $keyFingerprint,
            'salt_fingerprint' => $saltFingerprint,
        ]);
    }

    /**
     * @return string|null the decrypted salt, or null if there is nothing to decrypt, the WP
     *                     auth key was rotated in the meantime, or the record was tampered with
     */
    private function decryptSaltFromOption()
    {
        if (!$this->isSaltEncryptionSupported()) {
            return null;
        }

        $record = $this->getWpMatomoSettings()->get_encrypted_salt_backup();
        if (empty($record['ciphertext']) || !is_string($record['ciphertext'])) {
            return null;
        }

        $decoded = base64_decode($record['ciphertext'], true);
        if (false === $decoded || strlen($decoded) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }

        $nonce      = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        try {
            // the auth tag check fails (returns false) when the WP auth key was rotated or the
            // ciphertext was modified
            $salt = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->deriveSaltEncryptionKey());
        } catch (\Exception $ex) {
            return null;
        }

        return is_string($salt) && '' !== $salt ? $salt : null;
    }

    private function isSaltEncryptionSupported()
    {
        // sodium is native in PHP 7.2+ and polyfilled by WordPress 5.2+ (via sodium_compat)
        return function_exists('sodium_crypto_secretbox')
            && function_exists('sodium_crypto_secretbox_open')
            && function_exists('wp_salt');
    }

    private function deriveSaltEncryptionKey()
    {
        // derive a key from the WP auth key instead of re-using it directly. only a fixed-length
        // prefix of the auth key is used, so even a total compromise of the derived key can never
        // yield the complete WP auth secret.
        return hash('sha256', substr(wp_salt('auth'), 0, 64) . '|matomo-salt-encryption', true);
    }

    private function computeAuthKeyFingerprint()
    {
        // cheap (~1μs) and secure fingerprint to detect WP auth key rotation without decrypting/encrypting
        // on every request.
        return substr(hash('sha256', wp_salt('auth') . '|matomo-salt-key-fingerprint'), 0, 16);
    }

    private function computeSaltFingerprint($salt)
    {
        // detects a manually changed salt in config.ini.php, so the stored ciphertext is updated
        return substr(hash('sha256', $salt . '|matomo-salt-fingerprint'), 0, 16);
    }

    private function syncOrRestoreConfigBackup()
    {
        if (!$this->localConfigFileExists()) {
            // if local file does not exist (for example, deleted by hosting provider or another plugin),
            // restore the contents from the backup
            $this->restoreConfigFromBackup();
            return;
        }

        if ($this->isLocalConfigFileWrittenCompletely()) {
            // tracking requests need to be as fast as possible, so the backup is never refreshed
            // there; config changes are picked up by the next non-tracker request instead
            if (SettingsServer::isTrackerApiRequest()) {
                return;
            }

            // if local file exists and was written completely, backup its contents to the WP option
            // note: in WP, update_option() will not actually write to the database if the existing value
            // is the same as what's already there, so it's safe to do this on every request.
            $this->persistConfigOption();
            return;
        }

        // the file exists but the end-of-file marker was not read from it: so either a write is in
        // progress, or a previous write was interrupted.

        if ($this->wasLocalConfigFileModifiedRecently()) {
            // file was modified recently, assume a write is in progress; do not overwrite the
            // file with the backup either, the in progress write would be lost
            return;
        }

        // the last write to the file was interrupted: self-heal by restoring the
        // backup over it. this is safe for config files written by plugin versions that
        // predate the marker (they legitimately have no marker and an old mtime): their
        // backup option is still empty at that point, so nothing is restored over them
        // until the plugin update adds the marker (see Updater).
        $this->restoreConfigFromBackup();
    }

    private function wasLocalConfigFileModifiedRecently()
    {
        $mtime = filemtime($this->getPathLocal());
        if (false === $mtime) {
            // cannot tell (eg. the file just disappeared); err on the side of not touching it
            return true;
        }

        return (time() - $mtime) < self::INCOMPLETE_FILE_GRACE_PERIOD_SECONDS;
    }

    private function localConfigFileExists()
    {
        $path = $this->getPathLocal();
        return !empty($path) && is_file($path);
    }

    /**
     * The end-of-file marker is always the last section written to config.ini.php, so if it made
     * it into the parsed INI data, the whole file was read and the file was written completely.
     * The parsed data (and not the raw file) is checked on purpose: it is what gets persisted to
     * the backup, and re-reading the file here could race with a concurrent write to it.
     */
    private function isLocalConfigFileWrittenCompletely()
    {
        $marker = $this->iniFileChain->getFrom($this->getPathLocal(), self::END_OF_FILE_MARKER_SECTION);
        return !empty($marker[self::END_OF_FILE_MARKER_KEY]);
    }

    private function restoreConfigFromBackup()
    {
        // manually edited backup options may hold secrets or blog-specific values, so strip
        // them on the way in too
        $backup = $this->removeValuesExcludedFromBackup($this->getWpMatomoSettings()->get_config_backup());
        if (empty($backup)) {
            // nothing to restore, eg. a fresh install before config.ini.php has been created
            return;
        }

        $backup = $this->addUnbackedUpConfigValues($backup);

        $this->applyUserConfigDiff($backup);
        $this->writeLocalConfigFile($backup);
    }

    private function removeValuesExcludedFromBackup($config)
    {
        // save database keys that are not used to authenticate to the database
        // (like whether to use SSL) in the DB backup
        $portableDatabaseValues = [];
        if (isset($config['database']) && is_array($config['database'])) {
            $portableDatabaseValues = array_intersect_key(
                $config['database'],
                array_flip(self::DATABASE_KEYS_TO_BACKUP)
            );
        }

        $config = $this->redactSecrets($config);

        if (!empty($portableDatabaseValues)) {
            $config['database'] = $portableDatabaseValues;
        }

        // trusted_hosts is blog-specific (derived from the blog's home URL), so it must not
        // enter the network-shared backup; it is rebuilt on restore
        unset($config['General']['trusted_hosts']);
        if (empty($config['General'])) {
            unset($config['General']);
        }

        // the end-of-file marker is file bookkeeping, not user config; it is written fresh
        // whenever the file is (re)created
        unset($config[self::END_OF_FILE_MARKER_SECTION]);

        // in MWP the [Plugins] section reflects the runtime-computed plugin list (see
        // detectExtraPluginsToLoad()), it is built, indirectly, from WordPress' activated
        // plugins list.
        unset($config['Plugins']);

        return $config;
    }

    private function redactSecrets($config)
    {
        if (!is_array($config)) {
            return [];
        }

        foreach (self::REDACTED_SECTIONS as $sectionName) {
            unset($config[$sectionName]);
        }

        foreach ($config as $sectionName => $section) {
            if (!is_array($section)) {
                continue;
            }

            foreach ($section as $key => $value) {
                if (preg_match(self::SECRET_KEY_PATTERN, $key)) {
                    unset($config[$sectionName][$key]);
                }
            }

            if (empty($config[$sectionName])) {
                unset($config[$sectionName]);
            }
        }

        return $config;
    }

    /**
     * Fills in the config values that are deliberately not part of the backup:
     * - the [database] section is rebuilt from the current WordPress credentials
     * - the salt is restored from the encrypted per-blog option, or regenerated when that is
     *   not possible
     * - trusted_hosts is derived from the current blog's WordPress home URL
     *
     * Restoring the original salt keeps visitors' signed tracking opt-out cookies valid (a
     * regenerated salt silently invalidates them, re-enabling tracking for visitors who opted
     * out — a privacy compliance issue) and keeps config_id fingerprints stable. Falling back to
     * a regenerated salt is otherwise acceptable in MWP since using the API with a Matomo
     * token_auth is not supported.
     *
     * @param array $backup
     * @return array
     */
    private function addUnbackedUpConfigValues($backup)
    {
        $portableDatabaseValues = isset($backup['database']) && is_array($backup['database'])
            ? $backup['database'] : [];
        $backup['database'] = array_merge(Installer::get_db_infos(), $portableDatabaseValues);

        if (!isset($backup['General']) || !is_array($backup['General'])) {
            $backup['General'] = [];
        }

        $salt = $this->decryptSaltFromOption();
        if (empty($salt)) {
            $salt = Common::generateUniqId();

            // record it, so super admins are informed about the regenerated salt and its
            // consequences in the system report (most importantly, visitors' signed tracking
            // opt-out cookies are no longer recognized)
            $this->getWpMatomoSettings()->set_time_salt_was_regenerated(time());
        }

        $backup['General']['salt'] = $salt;
        $backup['General']['trusted_hosts'] = [$this->getTrustedHost()];

        return $backup;
    }

    private function getTrustedHost()
    {
        $homeUrl = home_url();

        $domain = wp_parse_url($homeUrl, PHP_URL_HOST);
        if (!$domain) {
            return $homeUrl;
        }

        $port = wp_parse_url($homeUrl, PHP_URL_PORT);
        if ($port) {
            $domain .= ':' . $port;
        }
        return $domain;
    }

    private function writeLocalConfigFile(array $userConfig)
    {
        $path = $this->getPathLocal();
        if (empty($path)) {
            return;
        }

        $header  = "; <?php exit; ?> DO NOT REMOVE THIS LINE\n";
        $header .= "; file automatically generated or modified by Matomo; you can manually override the default values in global.ini.php by redefining them in this file.\n";

        // the end-of-file marker must be the very last section of the file (see
        // isLocalConfigFileWrittenCompletely())
        unset($userConfig[self::END_OF_FILE_MARKER_SECTION]);
        $userConfig[self::END_OF_FILE_MARKER_SECTION] = self::getEndOfFileMarkerSection();

        // Config::forceSave()/IniFileChain::dumpChanges() cannot be used here: they post events,
        // which needs the DI container, and a restore runs while the environment is being created,
        // before the container exists. the install check (Installer::looks_like_it_is_installed())
        // needs the file back on disk as soon as the environment is created (otherwise it triggers
        // a full re-install), so the restored user config is dumped directly.
        //
        // note: we can do this safely since we only store the diff with global.ini.php/common.config.ini.php
        // in the db backup.

        try {
            $writer  = new IniWriter();
            $content = $writer->writeToString($this->encodeIniValues($userConfig), $header);
        } catch (\Exception $ex) {
            $this->logger->log('Failed to dump the restored Matomo config: ' . $ex->getMessage());
            return;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        // write to a temp file and rename so a concurrent request can never read a partially
        // written config.ini.php (it would back it up, overwriting the good backup)
        $tempPath     = $path . '.' . uniqid('tmp', true);
        $bytesWritten = @file_put_contents($tempPath, $content, LOCK_EX);
        if ($bytesWritten !== strlen($content) || !@rename($tempPath, $path)) {
            @unlink($tempPath);
            $this->logger->log('Failed to restore config.ini.php from the backup option.');
            return;
        }

        // use FS_CHMOD_FILE if a user has defined it (in wp-config.php for example)
        $mode = defined('FS_CHMOD_FILE') ? FS_CHMOD_FILE : 0664;
        @chmod($path, $mode);
    }

    /**
     * Same as IniFileChain::encodeValues() (protected, so not callable from here).
     */
    private function encodeIniValues($values)
    {
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $values[$key] = $this->encodeIniValues($value);
            }
            return $values;
        }
        if (is_float($values)) {
            return Common::forceDotAsSeparatorForDecimalPoint($values);
        }
        if (is_string($values)) {
            return str_replace('$', '&#36;', htmlentities($values, ENT_COMPAT, 'UTF-8'));
        }
        return $values;
    }

    private function computeUserConfigDiff()
    {
        $diff = [];
        foreach ($this->iniFileChain->getAll() as $sectionName => $section) {
            if (!is_array($section)) {
                continue;
            }

            $sectionDiff = $this->iniFileChain->arrayUnmerge($this->getDefaultSection($sectionName), $section);
            if (!empty($sectionDiff)) {
                $diff[$sectionName] = $sectionDiff;
            }
        }
        return $diff;
    }

    private function applyUserConfigDiff($diff)
    {
        foreach ($diff as $sectionName => $section) {
            if (!is_array($section)) {
                continue;
            }

            $existing = $this->iniFileChain->get($sectionName);
            $existing = is_array($existing) ? $existing : [];
            $this->iniFileChain->set($sectionName, array_merge($existing, $section));
        }
    }

    private function detectExtraPluginsToLoad()
    {
        $merged = $this->iniFileChain->getAll();
        if (empty($merged)) {
            return;
        }

        $plugins = isset($merged['Plugins']['Plugins']) ? $merged['Plugins']['Plugins'] : [];
        if (!is_array($plugins)) {
            $plugins = [];
        }

        $modified = $this->getActualPluginsToLoad($plugins);
        $this->iniFileChain->set('Plugins', [ 'Plugins' => $modified ]);
    }

    private function getActualPluginsToLoad( $plugins ) { // TODO: cache result of this?
        $pluginsToRemove = array('Marketplace', 'MultiSites', 'TwoFactorAuth', 'Widgetize', 'Feedback', 'ExamplePlugin', 'ExampleAPI', 'MobileAppMeasurable', 'CustomPiwikJs');
        foreach ($pluginsToRemove as $pluginToRemove) {
            // Marketplace => this is instead done in wordpress
            // MultiSites => doesn't really make sense since we have only one website per installation
            // TwoFactorAuth => not needed as login is being handled by WordPress
            // widgetize for now we don't want to allow widgetizing as it is based on the token_auth authentication
            // Monolog => we use our own logger
            // ProfessionalServices => we advertise in the WP plugin itself instead
            // feedback => we want to hide things like Need help in the admin etc
            // MobileAppMeasurable => for WP mobile apps are not a thing
            // custom variables we don't want to enable as we will deprecate them in Matomo 4 anyway => used to be disabled but we need to make sure the columns get installed otherwise matomo has issues... need to wait to matomo 4 to remove it
            $pos = array_search($pluginToRemove, $plugins);
            if ($pos !== false) {
                array_splice($plugins, $pos, 1);
            }
        }
        if (matomo_has_tag_manager()) {
            $plugins[] = 'TagManager';
        }
        $mustEnable = ['BulkTracking', 'CustomJsTracker'];
        foreach ($mustEnable as $enable) {
            if (!in_array($enable, $plugins)) {
                $plugins[] = $enable;
            }
        }
        if (!empty($GLOBALS['MATOMO_PLUGINS_ENABLED'])) {
            foreach ($GLOBALS['MATOMO_PLUGINS_ENABLED'] as $plugin) {
                if (!in_array($plugin, $plugins)) {
                    $plugins[] = $plugin;
                }
            }
        }
        if (!empty($GLOBALS['MATOMO_MARKETPLACE_PLUGINS'])) {
            matomo_filter_incompatible_plugins($plugins);
        }
        return $plugins;
    }

    private function getDefaultSection($sectionName)
    {
        $global = $this->iniFileChain->getFrom($this->getPathGlobal(), $sectionName);
        $common = $this->iniFileChain->getFrom($this->getPathCommon(), $sectionName);

        $global = is_array($global) ? $global : [];
        $common = is_array($common) ? $common : [];

        return array_merge($global, $common);
    }

    private function getWpMatomoSettings()
    {
        if ( empty( $this->settings ) ) {
            $this->settings = \WpMatomo::$settings ?: new Settings();
        }
        return $this->settings;
    }

    public static function addEndOfFileMarkerSectionTo(\Piwik\Config $config)
    {
        $marker_section            = self::END_OF_FILE_MARKER_SECTION;
        $config->{$marker_section} = self::getEndOfFileMarkerSection();
    }

    public static function isEndOfFileMarkerPresent(\Piwik\Config $config)
    {
        $markerSection = self::END_OF_FILE_MARKER_SECTION;
        $markerSection = $config->{$markerSection};
        return ! empty( $markerSection[self::END_OF_FILE_MARKER_KEY] )
            && $markerSection[self::END_OF_FILE_MARKER_KEY] == 1;
    }

    public static function getEndOfFileMarkerSection()
    {
        return [self::END_OF_FILE_MARKER_KEY => 1];
    }
}
