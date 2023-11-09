<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Commands;

use Symfony\Component\Console\Input\ArrayInput;
use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildRelease extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('wordpress:build-release');
        $this->setDescription('Builds a release, either to a .zip or .tar.gz archive.');
        $this->addOption('zip', null, InputOption::VALUE_NONE, 'If supplied, a .zip archive will be created.');
        $this->addOption('tgz', null, InputOption::VALUE_NONE, 'If supplied, a .tgz archive will be created.');
        $this->addOption('version', null, InputOption::VALUE_REQUIRED, 'The version of this release. If not supplied, the latest value from CHANGELOG.md is used.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $version = $this->getReleaseVersion($input, $output);

        $zip = $input->getOption('zip');
        $tgz = $input->getOption('tgz');
        if (!$zip && !$tgz) {
            throw new \Exception('At least one output format is required, please supply --zip OR --tgz OR both (to build both archives).');
        }

        $this->generateCoreAssets($output);
        $this->generateLangFiles($output);

        $stashHash = $this->addGeneratedFilesToGit();
        if ($zip) {
            $this->generateArchive('zip', $version, $stashHash, $output);
        }

        if ($tgz) {
            $this->generateArchive('tgz', $version, $stashHash, $output);
        }

        return self::SUCCESS;
    }

    private function getReleaseVersion(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getOption('version');
        if (!empty($version)) {
            return $version;
        }

        $changelog = file_get_contents(__DIR__ . '/../../../CHANGELOG.md');
        $changelog = explode("\n", $changelog);
        foreach ($changelog as $line) {
            if (preg_match('/=+\s*(\d+\.\d+\.\d+)\s*=+/', $line, $matches)) {
                $output->writeln("<comment>Creating a release named '{$matches[1]}'</comment>");
                return $matches[1];
            }
        }

        throw new \Exception('Failed to extract version from CHANGELOG.md');
    }

    private function generateCoreAssets(OutputInterface $output)
    {
        $input = new ArrayInput([
            'command' => 'wordpress:generate-core-assets',
        ]);

        $returnCode = $this->getApplication()->doRun($input, $output);
        if ($returnCode !== self::SUCCESS) {
            throw new \Exception('wordpress:generate-core-assets failed!');
        }
    }

    private function generateLangFiles(OutputInterface $output)
    {
        $input = new ArrayInput([
            'command' => 'wordpress:generate-lang-files',
        ]);

        $returnCode = $this->getApplication()->doRun($input, $output);
        if ($returnCode !== self::SUCCESS) {
            throw new \Exception('wordpress:generate-lang-files failed!');
        }
    }

    // TODO: unit test that checks generated files are up to date
    private function generateArchive($format, $version, $stashHash, OutputInterface $output)
    {
        $output->writeln("Generating $format archive...");

        $command = "git archive --format=$format $stashHash > matomo-$version.$format";
        $this->executeShellCommand($command, "Failed to generate $format archive!");

        $output->writeln("<info>Created archive matomo-$version.$format.</info>");
    }

    private function addGeneratedFilesToGit()
    {
        $command = 'git add ' . PIWIK_INCLUDE_PATH . '/lang/*.json ' . plugin_dir_path(MATOMO_ANALYTICS_FILE) . 'assets/js/asset_manager_core_js.js';
        $this->executeShellCommand($command, "Could not git add generated files.");

        $command = 'git stash create';
        $output = $this->executeShellCommand($command, "Could not create git stash.");
        $output = array_filter($output);

        foreach ($output as $line) {
            if (preg_match('/^[a-fA-F0-9]+%/', $line)) {
                return $line;
            }
        }

        throw new \Exception('Could not get hash from output of git stash create command.');
    }

    private function executeShellCommand($command, $onFailureMessage)
    {
        exec($command, $output, $result);

        if ($result) {
            throw new \Exception($onFailureMessage . ' Output: ' . implode("\n", $output));
        }

        return $output;
    }
}
