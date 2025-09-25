<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Commands;

use Piwik\Config;
use Piwik\Db;
use Piwik\Development;
use Piwik\Plugin\ConsoleCommand;

class ExportWpStatisticsData extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('wordpress:export-wp-statistics-data');
        $this->addRequiredValueOption(
            'output',
            'o',
            'Output file name of database dump.',
            dirname( PIWIK_INCLUDE_PATH ) . '/tests/phpunit/wpmatomo/wpstatistics/dump.sql'
        );
    }

    public function isEnabled()
    {
        return Development::isEnabled();
    }

    protected function doExecute(): int
    {
        $this->checkMysqlDumpIsAvailable();

        $output = $this->getInput()->getOption('output');

        return $this->executeMysqlDumpCommand($output);
    }

    private function executeMysqlDumpCommand(string $output)
    {
        $config = Config::getInstance()->database;

        $username = $config['username'];
        $password = $config['password'];
        $dbName = $config['dbname'];
        $host = $config['host'];
        $port = $config['port'];

        $tables = Db::fetchAll("SHOW TABLES LIKE 'wp_statistics_%';");
        if (empty($tables)) {
            throw new \Exception('No wp-statistics tables found.');
        }

        $tables = array_column($tables, key($tables[0]));

        $credentialsFilePath = PIWIK_INCLUDE_PATH . '/.mysql.options';

        $params = [
            '--defaults-extra-file=' . $credentialsFilePath,
            '--host=' . $host,
            '--port=' . $port,
            $dbName,
        ];
        $params = array_merge($params, $tables);
        $params = array_map('escapeshellarg', $params);
        $params = implode(' ', $params);

        try {
            $credentialsFileContents = <<<EOF
[client]
user=$username
password=$password
EOF;
            file_put_contents($credentialsFilePath, $credentialsFileContents);

            $this->getOutput()->writeln('Executing mysqldump...');

            $command = "mysqldump $params > " . escapeshellarg($output);
            passthru($command, $returnCode);

            $this->getOutput()->writeln('Done.');

            return $returnCode;
        } finally {
            unlink($credentialsFilePath);
        }
    }

    private function checkMysqlDumpIsAvailable()
    {
        exec('which mysqldump', $output, $returnCode);

        if ($returnCode) {
            throw new \Exception('mysqldump must be available for this command.');
        }
    }
}
