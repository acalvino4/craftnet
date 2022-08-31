<?php

namespace craftnet;

use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use craft\helpers\DateTimeHelper;
use craft\helpers\HtmlPurifier;
use yii\helpers\Markdown;

class ChangelogParser
{
    public function parse(string $changelog, ?string $fromVersion = null, ?array $onlyVersions = null): array
    {
        // Move it to a temp file & parse it
        $file = tmpfile();
        fwrite($file, $changelog);
        fseek($file, 0);

        $releases = [];
        $currentVersion = null;
        $vp = new VersionParser();

        if ($onlyVersions !== null) {
            $onlyVersions = array_flip($onlyVersions);
        }

        while (($line = fgets($file)) !== false) {
            // Is this an H1 or H2?
            if (strncmp($line, '# ', 2) === 0 || strncmp($line, '## ', 3) === 0) {
                // If we're in the middle of getting a release's notes, finish it off
                $currentVersion = null;

                // Is it an H2 version heading?
                if (preg_match('/^## (?:.* )?\[?v?(\d+\.\d+\.\d+(?:\.\d+)?(?:-[0-9A-Za-z-\.]+)?)\]?(?:\(.*?\)|\[.*?\])? - (\d{4}[-\.]\d\d?[-\.]\d\d?)( \[critical\])?/i', $line, $match)) {
                    [, $version, $date] = $match;

                    // Make sure this is a version we care about
                    try {
                        $normalizedVersion = $vp->normalize($version);
                    } catch (\UnexpectedValueException $e) {
                        continue;
                    }

                    if ($fromVersion !== null && Comparator::lessThanOrEqualTo($normalizedVersion, $fromVersion)) {
                        // We've got everything we need
                        break;
                    }

                    if ($onlyVersions !== null && !isset($onlyVersions[$normalizedVersion])) {
                        // Not interested in this version
                        continue;
                    }

                    // Store the main release info
                    $currentVersion = $normalizedVersion;
                    $releases[$currentVersion] = [
                        'version' => $version,
                        'critical' => !empty($match[3]),
                        'date' => DateTimeHelper::toDateTime(str_replace('.', '-', $date), false, false),
                        'notes' => '',
                    ];
                }
            } else if ($currentVersion !== null) {
                // Append the line to the current release notes
                $releases[$currentVersion]['notes'] .= $line;
            }
        }

        // Close the temp file
        fclose($file);

        // Parse the release notes
        foreach ($releases as &$release) {
            $release['notes'] = trim($release['notes']);
            if ($release['notes'] !== '') {
                $release['notes'] = $this->_parseReleaseNotes($release['notes']);
            } else {
                $release['notes'] = null;
            }
        }
        unset($release);

        return $releases;
    }

    /**
     * Parses releases notes into HTML.
     *
     * @param string $notes
     * @return string
     */
    private function _parseReleaseNotes(string $notes): string
    {
        // Parse as Markdown
        $notes = Markdown::process($notes, 'gfm');

        // Purify HTML
        $notes = HtmlPurifier::process($notes);

        // Notes/tips/warnings
        $notes = preg_replace('/<blockquote><p>\{(note|tip|warning)\}\s*/i', '<blockquote class="note $1"><p>', $notes);

        // GitHub-style notes/tips/warnings
        // (see https://github.com/community/community/discussions/16925)
        $notes = preg_replace('/<blockquote><p><strong>(note|tip|warning)<\/strong>\s*/i', '<blockquote class="note $1"><p>', $notes); // GitHub style

        return $notes;
    }
}
