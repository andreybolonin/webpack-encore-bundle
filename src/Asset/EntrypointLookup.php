<?php

/*
 * This file is part of the Symfony WebpackEncoreBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\WebpackEncoreBundle\Asset;

/**
 * Returns the CSS or JavaScript files needed for a Webpack entry.
 *
 * This reads a JSON file with the format of Webpack Encore's entrypoints.json file.
 *
 * @final
 */
class EntrypointLookup
{
    private $entrypointJsonPath;

    private $entriesData;

    private $returnedFiles = [];

    public function __construct(string $entrypointJsonPath)
    {
        $this->entrypointJsonPath = $entrypointJsonPath;
    }

    public function getJavaScriptFiles(string $entryName): array
    {
        return $this->getEntryFiles($entryName, 'js');
    }

    public function getCssFiles(string $entryName): array
    {
        return $this->getEntryFiles($entryName, 'css');
    }

    /**
     * Resets the state of this service.
     */
    public function reset()
    {
        $this->returnedFiles = [];
    }

    private function getEntryFiles(string $entryName, string $key): array
    {
        $this->validateEntryName($entryName);
        $entriesData = $this->getEntriesData();
        $entryData = $entriesData[$entryName];

        if (!isset($entryData[$key])) {
            // If we don't find the file type then just send back nothing.
            return [];
        }

        // make sure to not return the same file multiple times
        $entryFiles = $entryData[$key];
        $newFiles = array_values(array_diff($entryFiles, $this->returnedFiles));
        $this->returnedFiles = array_merge($this->returnedFiles, $newFiles);

        return $newFiles;
    }

    private function validateEntryName(string $entryName)
    {
        $entriesData = $this->getEntriesData();
        if (!isset($entriesData[$entryName])) {
            $withoutExtension = substr($entryName, 0, strrpos($entryName, '.'));

            if (isset($entriesData[$withoutExtension])) {
                throw new \InvalidArgumentException(sprintf('Could not find the entry "%s". Try "%s" instead (without the extension).', $entryName, $withoutExtension));
            }

            throw new \InvalidArgumentException(sprintf('Could not find the entry "%s" in "%s". Found: %s.', $entryName, $this->entrypointJsonPath, implode(', ', array_keys($entriesData))));
        }
    }

    private function getEntriesData(): array
    {
        if (null === $this->entriesData) {
            if (!file_exists($this->entrypointJsonPath)) {
                throw new \InvalidArgumentException(sprintf('Could not find the entrypoints file from Webpack: the file "%s" does not exist.', $this->entrypointJsonPath));
            }

            $this->entriesData = json_decode(file_get_contents($this->entrypointJsonPath), true);

            if (null === $this->entriesData) {
                throw new \InvalidArgumentException(sprintf('There was a problem JSON decoding the "%s" file', $this->entrypointJsonPath));
            }
        }

        return $this->entriesData;
    }
}
