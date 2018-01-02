<?php

namespace Comcast\PhpLegalLicenses\Console;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('generate')
            ->setDescription('Generate Licenses file from project dependencies.');
    }

    /**
     * Execute the command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->verifyComposerLockFilePresent($output);
        $packages = $this->parseComposerLockFile();

        $output->writeln('<info>Generating Licenses file...</info>');
        $this->generateLicensesText($packages['packages']);

        $output->writeln('<info>Done!</info>');
    }

    /**
     * Verify that the composer.lock file exists.
     *
     * @return void
     */
    protected function verifyComposerLockFilePresent()
    {
        if (is_file(getcwd().'/composer.lock')) {
            return;
        }

        throw new RuntimeException('Composer Lock file missing! Please run composer install and try again.');
    }

    /**
     * Parses the composer.lock file to retrieve all installed packages.
     *
     * @return array
     */
    protected function parseComposerLockFile()
    {
        $path = getcwd().'/composer.lock';
        $contents = file_get_contents($path);

        return json_decode($contents, true);
    }

    /**
     * Generates Licenses Text using packages retrieved from composer.lock file.
     *
     * @param array $dependencies
     *
     * @return void
     */
    protected function generateLicensesText($dependencies)
    {
        $text = $this->getBoilerplate();

        foreach ($dependencies as $dependency) {
            $text .= $this->getTextForDependency($dependency);
        }

        file_put_contents('licenses.md', $text);
    }

    /**
     * Returns Boilerplate text for the Licences File.
     *
     * @return string
     */
    protected function getBoilerplate()
    {
        return '# Project Licenses
This file was generated by the [PHP Legal Licenses](https://github.com/Comcast/php-legal-licenses) utility. It contains the name, version and commit sha, description, homepage, and license information for every dependency in this project.

## Dependencies

';
    }

    /**
     * Retrieves text containing version, sha, and license information for the specified dependency.
     *
     * @param array $dependency
     *
     * @return string
     */
    protected function getTextForDependency($dependency)
    {
        $name = $dependency['name'];
        $description = isset($dependency['description']) ? $dependency['description'] : 'Not configured.';
        $version = $dependency['version'];
        $homepage = isset($dependency['homepage']) ? $dependency['homepage'] : 'Not configured.';
        $sha = str_split($dependency['source']['reference'], 7)[0];
        $licenseNames = isset($dependency['license']) ? implode(', ', $dependency['license']) : 'Not configured.';
        $license = $this->getFullLicenseText($name);

        return $this->generateDependencyText($name, $description, $version, $homepage, $sha, $licenseNames, $license);
    }

    /** Retrieves full license text for a dependency from the vendor directory.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getFullLicenseText($name)
    {
        $path = getcwd()."/vendor/$name/";
        $filenames = ['LICENSE.txt', 'LICENSE.md', 'LICENSE', 'license.txt', 'license.md', 'license', 'LICENSE-2.0.txt'];

        foreach ($filenames as $filename) {
            $text = @file_get_contents($path.$filename);
            if ($text) {
                return $text;
            }
        }

        return 'Full license text not found in dependency source.';
    }

    /**
     * Generates Dependency Text based on boilerplate.
     *
     * @param string $name
     * @param string $description
     * @param string $version
     * @param string $homepage
     * @param string $sha
     * @param string $licenceNames
     * @param string $license
     *
     * @return string
     */
    protected function generateDependencyText($name, $description, $version, $homepage, $sha, $licenseNames, $license)
    {
        return "### $name (Version $version | $sha)
$description
Homepage: $homepage
Licenses Used: $licenseNames
$license

";
    }
}
