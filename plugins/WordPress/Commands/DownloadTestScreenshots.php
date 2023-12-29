<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Commands;

use Piwik\Container\StaticContainer;
use Piwik\Development;
use Piwik\Http;
use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class DownloadTestScreenshots extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('wordpress:download-test-screenshots');
        $this->addOption('artifact', null, InputOption::VALUE_REQUIRED,
            'The ID of the screenshot artifacts to download.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $artifactId = $input->getOption('artifact');
        if (empty($artifactId)) {
            $artifactId = $this->pickArtifact($input, $output);
        }

        $artifactUrl = "https://api.github.com/repos/matomo-org/matomo-for-wordpress/actions/artifacts/$artifactId/zip";

        $output->writeln("Downloading...");
        $localArtifactsPath = $this->downloadArtifacts($artifactId, $artifactUrl);

        $output->writeln("Extracting...");
        $this->extractArtifacts($localArtifactsPath);

        if (is_file($localArtifactsPath)) {
            unlink($localArtifactsPath);
        }

        $output->writeln("Done. The artifacts were extracted to ./tests/e2e/actual.");
    }

    public function isEnabled()
    {
        return Development::isEnabled();
    }

    private function downloadArtifacts($artifactId, $artifactUrl)
    {
        $outputPath = StaticContainer::get('path.tmp') . '/' . $artifactId . '.zip';
        Http::sendHttpRequestBy(
            Http::getTransportMethod(),
            $artifactUrl,
            60,
            null,
            $outputPath,
            null,
            0,
            false,
            false,
            false,
            false,
            'GET',
            null,
            null,
            null,
            [
                'Accept: application/vnd.github+json',
                'Authorization: Bearer ' . $this->getGithubToken(),
                'X-GitHub-Api-Version: 2022-11-28',
            ]
        );
        return $outputPath;
    }

    private function extractArtifacts($archivePath)
    {
        $command = "unzip -o $archivePath -d " . PIWIK_INCLUDE_PATH . '/../tests/e2e/actual';
        exec($command, $output, $returnCode);
        if ($returnCode) {
            throw new \Exception('unzip failed: ' . implode("\n", $output));
        }
    }

    private function pickArtifact(InputInterface $input, OutputInterface $output)
    {
        $artifactsApiUrl = 'https://api.github.com/repos/matomo-org/matomo-for-wordpress/actions/artifacts?per_page=100';

        $response = Http::sendHttpRequest($artifactsApiUrl, 3000);
        $response = json_decode($response, true);

        // pick build
        $builds = [];
        foreach ($response['artifacts'] as $artifactInfo) {
            $buildId = $artifactInfo['workflow_run']['id'];
            $branchName = $artifactInfo['workflow_run']['head_branch'];

            $builds["$branchName (workflow run $buildId)"] = true;
        }
        $builds = array_keys($builds);
        $builds = array_slice($builds, 0, 10);

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion('Select a build:', $builds);
        $build = $helper->ask($input, $output, $question);

        preg_match('/workflow run ([^)]+)\)/', $build, $matches);
        $buildId = $matches[1];

        // pick artifact from build
        $artifacts = [];
        foreach ($response['artifacts'] as $artifactInfo) {
            if ($artifactInfo['workflow_run']['id'] != $buildId) {
                continue;
            }

            // downloading diffs not supported right now
            if (preg_match('/^diff/', $artifactInfo['name'])) {
                continue;
            }

            $artifacts[] = $artifactInfo;
        }
        $artifacts = array_slice($artifacts, 0, 10);

        $artifactNames = array_column($artifacts, 'name');

        $question = new ChoiceQuestion('Select an artifact:', $artifactNames);
        $artifactName = $helper->ask($input, $output, $question);

        foreach ($artifacts as $artifactInfo) {
            if ($artifactInfo['name'] == $artifactName) {
                $artifactId = $artifactInfo['id'];
                break;
            }
        }

        return $artifactId;
    }

    private function getGithubToken()
    {
        $token = getenv('GITHUB_TOKEN');
        if (!empty($token)) {
            return $token;
        }

        // quick hack to parse a .env file
        $envFileContents = parse_ini_file(PIWIK_INCLUDE_PATH . '/../.env');
        if (!empty($envFileContents['GITHUB_TOKEN'])) {
            return $envFileContents['GITHUB_TOKEN'];
        }

        throw new \Exception('No github token found. Create one that has the "actions" scope, and set it as the '
            . 'GITHUB_TOKEN environment variable either in your shell or in the root .env file.');
    }
}
