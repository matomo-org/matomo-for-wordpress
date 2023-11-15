<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Commands;

use Piwik\Development;
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
        $this->addOption('name', null, InputOption::VALUE_REQUIRED,
            'The name of this release, should be set to a semantic version number. If not supplied, the latest value from CHANGELOG.md is used.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (Development::isEnabled()) {
            throw new \Exception('This command should not be run with development mode enabled.');
        }

        $version = $this->getReleaseVersion($input, $output);

        $zip = $input->getOption('zip');
        $tgz = $input->getOption('tgz');
        if (!$zip && !$tgz) {
            throw new \Exception('At least one output format is required, please supply --zip OR --tgz OR both (to build both archives).');
        }

        $this->generateCoreAssets($output);

        $stashHash = $this->addGeneratedFilesToGit();

        if ($zip) {
            $this->generateArchive('zip', $version, $stashHash, $output);
        }

        if ($tgz) {
            $this->generateArchive('tgz', $version, $stashHash, $output);
        }

        return 0;
    }

    private function getReleaseVersion(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getOption('name');
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
        if ($returnCode) {
            throw new \Exception('wordpress:generate-core-assets failed!');
        }
    }

    // TODO: unit test that checks generated files are up to date
    private function generateArchive($format, $version, $stashHash, OutputInterface $output)
    {
        $output->writeln("Generating $format archive...");

        $pathToRepo = $this->getPathToGitRepo();

        $command = "git -C " . $pathToRepo . " archive --format=$format $stashHash > " . $pathToRepo . "/matomo-$version.$format";
        $this->executeShellCommand($command, "Failed to generate $format archive!");

        $output->writeln("<info>Created archive matomo-$version.$format.</info>");
    }

    private function addGeneratedFilesToGit()
    {
        $pathToRepo = $this->getPathToGitRepo();

        $command = 'git -C ' . $pathToRepo . ' add ' . plugin_dir_path(MATOMO_ANALYTICS_FILE) . 'assets/js/asset_manager_core_js.js';
        $this->executeShellCommand($command, "Could not git add generated files.");

        $command = 'git -C ' . $pathToRepo . ' stash create';
        $output = $this->executeShellCommand($command, null); // ignore error
        if (empty($output)) { // nothing different in repo
            return 'HEAD';
        }

        $output = array_map('trim', $output);
        $output = array_filter($output);

        foreach ($output as $line) {
            if (preg_match('/^[a-fA-F0-9]+$/', $line)) {
                return $line;
            }
        }

        throw new \Exception('Could not get hash from output of git stash create command. Output: ' . implode("\n", $output));
    }

    private function executeShellCommand($command, $onFailureMessage)
    {
        exec($command, $output, $result);

        if ($onFailureMessage && $result) {
            throw new \Exception($onFailureMessage . ' Output: ' . implode("\n", $output));
        }

        return $output;
    }

    private function getPathToGitRepo()
    {
        return dirname( dirname( dirname( __DIR__ ) ) );
    }
}
