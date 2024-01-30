<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Settings\Storage\Backend;

use Piwik\Common;
use Piwik\Db;
use Exception;
/**
 * Measurable settings backend. Stores all settings in a "site_setting" database table.
 *
 * If a value that needs to be stored is an array, will insert a new row for each value of this array.
 */
class MeasurableSettingsTable extends \Piwik\Settings\Storage\Backend\BaseSettingsTable
{
    /**
     * @var int
     */
    private $idSite;
    /**
     * @var string
     */
    private $pluginName;
    public function __construct($idSite, $pluginName)
    {
        parent::__construct();
        if (empty($pluginName)) {
            throw new Exception('No plugin name given for MeasurableSettingsTable backend');
        }
        if (empty($idSite)) {
            throw new Exception('No idSite given for MeasurableSettingsTable backend');
        }
        $this->idSite = (int) $idSite;
        $this->pluginName = $pluginName;
    }
    /**
     * Saves (persists) the current setting values in the database.
     * @param array $values Key/value pairs of setting values to be written
     */
    public function save($values)
    {
        $this->initDbIfNeeded();
        $valuesKeep = array();
        foreach ($values as $name => $value) {
            if (!isset($value)) {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                $jsonEncoded = 1;
                $value = json_encode($value);
            } else {
                $jsonEncoded = 0;
                if (is_bool($value)) {
                    // we are currently not storing booleans as json as it could result in trouble with the UI and regress
                    // preselecting the correct value
                    $value = (int) $value;
                }
            }
            $valuesKeep[] = array($this->idSite, $this->pluginName, $name, $value, $jsonEncoded);
        }
        $columns = array('idsite', 'plugin_name', 'setting_name', 'setting_value', 'json_encoded');
        $table = $this->getTableName();
        $lockKey = $this->getStorageId();
        $this->lock->execute($lockKey, function () use($valuesKeep, $table, $columns) {
            $this->delete();
            // No values = nothing to save
            if (!empty($valuesKeep)) {
                Db\BatchInsert::tableInsertBatchSql($table, $columns, $valuesKeep, true);
            }
        });
    }
    /**
     * @inheritdoc
     */
    public function getStorageId()
    {
        return 'MeasurableSettings_' . $this->idSite . '_' . $this->pluginName;
    }
    private function jsonEncodedMissingError(Exception $e)
    {
        return strpos($e->getMessage(), 'json_encoded') !== false;
    }
    public function load()
    {
        $this->initDbIfNeeded();
        $table = $this->getTableName();
        $sql = "SELECT `setting_name`, `setting_value`, `json_encoded` FROM " . $table . " WHERE idsite = ? and plugin_name = ?";
        $bind = array($this->idSite, $this->pluginName);
        try {
            $settings = $this->db->fetchAll($sql, $bind);
        } catch (\Exception $e) {
            // we catch an exception since json_encoded might not be present before matomo is updated to 3.5.0+ but the updater
            // may run this query
            if ($this->jsonEncodedMissingError($e)) {
                $sql = "SELECT `setting_name`, `setting_value` FROM " . $table . " WHERE idsite = ? and plugin_name = ?";
                $settings = $this->db->fetchAll($sql, $bind);
            } else {
                throw $e;
            }
        }
        $flat = array();
        foreach ($settings as $setting) {
            $name = $setting['setting_name'];
            if (!empty($setting['json_encoded'])) {
                $flat[$name] = json_decode($setting['setting_value'], true);
            } elseif (array_key_exists($name, $flat)) {
                if (!is_array($flat[$name])) {
                    $flat[$name] = array($flat[$name]);
                }
                $flat[$name][] = $setting['setting_value'];
            } else {
                $flat[$name] = $setting['setting_value'];
            }
        }
        return $flat;
    }
    protected function getTableName()
    {
        return Common::prefixTable('site_setting');
    }
    public function delete()
    {
        $this->initDbIfNeeded();
        $table = $this->getTableName();
        $sql = "DELETE FROM {$table} WHERE `idsite` = ? and plugin_name = ?";
        $bind = array($this->idSite, $this->pluginName);
        $this->db->query($sql, $bind);
    }
    /**
     * @internal
     * @param int $idSite
     * @throws \Exception
     */
    public static function removeAllSettingsForSite($idSite)
    {
        try {
            $query = sprintf('DELETE FROM %s WHERE idsite = ?', Common::prefixTable('site_setting'));
            Db::query($query, array($idSite));
        } catch (Exception $e) {
            if ($e->getCode() != 42) {
                // ignore table not found error, which might occur when updating from an older version of Piwik
                throw $e;
            }
        }
    }
    /**
     * @internal
     * @param string $pluginName
     * @throws \Exception
     */
    public static function removeAllSettingsForPlugin($pluginName)
    {
        try {
            $query = sprintf('DELETE FROM %s WHERE plugin_name = ?', Common::prefixTable('site_setting'));
            Db::query($query, array($pluginName));
        } catch (Exception $e) {
            if ($e->getCode() != 42) {
                // ignore table not found error, which might occur when updating from an older version of Piwik
                throw $e;
            }
        }
    }
}
