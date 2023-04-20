<?php

namespace App\Util;

use function Symfony\Component\String\u;

class SourceCleaner
{
    public static function cleanupPhpFile(string $contents, bool $removeClass = false): string
    {
        $contents = u($contents)
            ->replace("<?php\n", '')
            ->replaceMatches('/namespace[^\n]*/', '');

        if ($removeClass) {
            $contents = $contents->replaceMatches('/class[^\n]*\n{/', '')
                ->trim('{}')
                // remove use statements
                ->replaceMatches('/^use [^\n]*$/m', '');

            // unindent all lines by 4 spaces
            $lines = explode("\n", $contents);
            $lines = array_map(function (string $line) {
                return substr($line, 4);
            }, $lines);
            $contents = u(implode("\n", $lines));
        }

        return $contents->trim()->toString();
    }

    public static function processTerminalLines(string $content): string
    {
        $lines = explode("\n", $content);

        $lines = array_map(function (string $line) {
            $line = trim($line);

            if (!$line) {
                return '';
            }

            // comment lines
            if (str_starts_with($line, '//')) {
                return sprintf('<span class="hljs-comment">%s</span>', $line);
            }

            return '<span class="hljs-prompt">$ </span>'.$line;
        }, $lines);

        return trim(implode("\n", $lines));
    }

    public static function extractTwigBlock(string $content, string $targetTwigBlock, bool $showTwigExtends = true): string
    {
        $lines = explode("\n", $content);
        $startBlock = sprintf('{%% block %s %%}', $targetTwigBlock);
        $insideTargetBlock = false;
        $nestedBlockCount = 0;
        $blockLines = [];
        foreach ($lines as $line) {
            if (false === $insideTargetBlock && str_contains($line, $startBlock)) {
                $insideTargetBlock = true;
                $line = trim(str_replace($startBlock, '', $line));

                if ('' === $line) {
                    continue;
                }
            }

            if (!$insideTargetBlock) {
                continue;
            }

            // look for a nested block
            if (str_contains($line, '{% block ')) {
                ++$nestedBlockCount;
            }

            if (str_contains($line, '{% endblock %}')) {
                if (0 === $nestedBlockCount) {
                    $line = trim(str_replace('{% endblock %}', '', $line));
                    // if the entire last line was NOT just endblock, add it before breaking
                    if ('' !== $line) {
                        $blockLines[] = $line;
                    }

                    break;
                }

                --$nestedBlockCount;
            }

            $blockLines[] = $line;
        }

        $leastIndentedLineCount = null;
        foreach ($blockLines as $line) {
            if ('' === $line) {
                continue;
            }

            $indentation = strspn($line, ' ');
            if (null === $leastIndentedLineCount || $indentation < $leastIndentedLineCount) {
                $leastIndentedLineCount = $indentation;
            }
        }

        // remove the minimum indentation from each line
        $blockLines = array_map(function (string $line) use ($leastIndentedLineCount) {
            return substr($line, $leastIndentedLineCount);
        }, $blockLines);

        if (!$showTwigExtends) {
            return implode("\n", $blockLines);
        }

        $finalBlockContent = implode("\n    ", $blockLines);

        return <<<EOF
{% extends 'base.html.twig' %}

{% block body %}
    $finalBlockContent
{% endblock %}
EOF;
    }

    /**
     * An overly simplistic method that removes HTML attributes and empty
     * elements to convert some real HTML to one that is simpler and can
     * be demo'ed.
     */
    public static function removeExcessHtml(string $content): string
    {
        // remove all HTML attributes and values + whitespace around them
        $content = preg_replace('/\s+[a-z0-9-]+="[^"]*"/', '', $content);

        // Find all the <div> elements without attributes
        preg_match_all('/<div>\s*(.*?)\s*<\/div>/s', $content, $matches);

        if (isset($matches[1])) {
            // Loop through the found matches
            foreach ($matches[1] as $match) {
                // Replace the div tags without attributes with their content
                $content = preg_replace('/<div>\s*'.preg_quote($match, '/').'\s*<\/div>/s', $match, $content);
            }
        }

        $lines = explode("\n", $content);
        $elementCount = 0;
        foreach ($lines as $key => $line) {
            // i.e. the line starts with a closing tag
            if (str_starts_with(trim($line), '>') || str_starts_with(trim($line), '</')) {
                --$elementCount;
            }

            $lines[$key] = str_repeat(' ', 4 * $elementCount).trim($line);

            if (preg_match('/<[^>]+/', $line)) {
                ++$elementCount;
            }

            if (preg_match('/<\/[^>]+>/', $line)) {
                --$elementCount;
            }
        }

        return implode("\n", $lines);
    }
}
