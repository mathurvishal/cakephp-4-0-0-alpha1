<?php
declare(strict_types=1);
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Shell\Task;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Exception\MissingPluginException;
use Cake\Core\Plugin;
use Cake\Filesystem\Filesystem;
use Cake\Utility\Inflector;

/**
 * Language string extractor
 */
class ExtractTask extends Shell
{
    /**
     * Paths to use when looking for strings
     *
     * @var array
     */
    protected $_paths = [];

    /**
     * Files from where to extract
     *
     * @var array
     */
    protected $_files = [];

    /**
     * Merge all domain strings into the default.pot file
     *
     * @var bool
     */
    protected $_merge = false;

    /**
     * Use relative paths in the pot files rather than full path
     *
     * @var bool
     */
    protected $_relativePaths = false;

    /**
     * Current file being processed
     *
     * @var string|null
     */
    protected $_file;

    /**
     * Contains all content waiting to be write
     *
     * @var array
     */
    protected $_storage = [];

    /**
     * Extracted tokens
     *
     * @var array
     */
    protected $_tokens = [];

    /**
     * Extracted strings indexed by domain.
     *
     * @var array
     */
    protected $_translations = [];

    /**
     * Destination path
     *
     * @var string|null
     */
    protected $_output;

    /**
     * An array of directories to exclude.
     *
     * @var array
     */
    protected $_exclude = [];

    /**
     * Holds the validation string domain to use for validation messages when extracting
     *
     * @var string
     */
    protected $_validationDomain = 'default';

    /**
     * Holds whether this call should extract the CakePHP Lib messages
     *
     * @var bool
     */
    protected $_extractCore = false;

    /**
     * Displays marker error(s) if true
     *
     * @var bool
     */
    protected $_markerError = false;

    /**
     * Count number of marker errors found
     * @var int
     */
    protected $_countMarkerError = 0;

    /**
     * No welcome message.
     *
     * @return void
     */
    protected function _welcome()
    {
    }

    /**
     * Method to interact with the User and get path selections.
     *
     * @return void
     */
    protected function _getPaths(): void
    {
        $defaultPath = APP;
        while (true) {
            $currentPaths = count($this->_paths) > 0 ? $this->_paths : ['None'];
            $message = sprintf(
                "Current paths: %s\nWhat is the path you would like to extract?\n[Q]uit [D]one",
                implode(', ', $currentPaths)
            );
            $response = $this->in($message, null, $defaultPath);
            if (strtoupper($response) === 'Q') {
                $this->err('Extract Aborted');
                $this->_stop();

                return;
            }
            if (strtoupper($response) === 'D' && count($this->_paths)) {
                $this->out();

                return;
            }
            if (strtoupper($response) === 'D') {
                $this->warn('No directories selected. Please choose a directory.');
            } elseif (is_dir($response)) {
                $this->_paths[] = $response;
                $defaultPath = 'D';
            } else {
                $this->err('The directory path you supplied was not found. Please try again.');
            }
            $this->out();
        }
    }

    /**
     * Execution method always used for tasks
     *
     * @return void
     * @psalm-suppress InvalidReturnType
     */
    public function main(): void
    {
        if (!empty($this->params['exclude'])) {
            $this->_exclude = explode(',', $this->params['exclude']);
        }
        if (isset($this->params['files']) && !is_array($this->params['files'])) {
            $this->_files = explode(',', $this->params['files']);
        }
        if (isset($this->params['paths'])) {
            $this->_paths = explode(',', $this->params['paths']);
        } elseif (isset($this->params['plugin'])) {
            $plugin = Inflector::camelize($this->params['plugin']);
            if (!Plugin::isLoaded($plugin)) {
                throw new MissingPluginException(['plugin' => $plugin]);
            }
            $this->_paths = [Plugin::classPath($plugin), Plugin::templatePath($plugin)];
            $this->params['plugin'] = $plugin;
        } else {
            $this->_getPaths();
        }

        if (isset($this->params['extract-core'])) {
            $this->_extractCore = !(strtolower((string)$this->params['extract-core']) === 'no');
        } else {
            $response = $this->in('Would you like to extract the messages from the CakePHP core?', ['y', 'n'], 'n');
            $this->_extractCore = strtolower((string)$response) === 'y';
        }

        if (!empty($this->params['exclude-plugins']) && $this->_isExtractingApp()) {
            $this->_exclude = array_merge($this->_exclude, App::path('Plugin'));
        }

        if (!empty($this->params['validation-domain'])) {
            $this->_validationDomain = $this->params['validation-domain'];
        }

        if ($this->_extractCore) {
            $this->_paths[] = CAKE;
        }

        if (isset($this->params['output'])) {
            $this->_output = $this->params['output'];
        } elseif (isset($this->params['plugin'])) {
            $this->_output = $this->_paths[0] . 'Locale';
        } else {
            $message = "What is the path you would like to output?\n[Q]uit";
            while (true) {
                $response = $this->in(
                    $message,
                    null,
                    rtrim($this->_paths[0], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Locale'
                );
                if (strtoupper($response) === 'Q') {
                    $this->err('Extract Aborted');
                    $this->_stop();

                    return;
                }
                if ($this->_isPathUsable($response)) {
                    $this->_output = $response . DIRECTORY_SEPARATOR;
                    break;
                }

                $this->err('');
                $this->err(
                    '<error>The directory path you supplied was ' .
                    'not found. Please try again.</error>'
                );
                $this->out();
            }
        }

        if (isset($this->params['merge'])) {
            $this->_merge = !(strtolower((string)$this->params['merge']) === 'no');
        } else {
            $this->out();
            $response = $this->in(
                'Would you like to merge all domain strings into the default.pot file?',
                ['y', 'n'],
                'n'
            );
            $this->_merge = strtolower((string)$response) === 'y';
        }

        $this->_markerError = (bool)$this->param('marker-error');
        $this->_relativePaths = (bool)$this->param('relative-paths');

        if (empty($this->_files)) {
            $this->_searchFiles();
        }

        $this->_output = rtrim($this->_output, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!$this->_isPathUsable($this->_output)) {
            $this->err(sprintf('The output directory %s was not found or writable.', $this->_output));
            $this->_stop();

            return;
        }

        $this->_extract();
    }

    /**
     * Add a translation to the internal translations property
     *
     * Takes care of duplicate translations
     *
     * @param string $domain The domain
     * @param string $msgid The message string
     * @param array $details Context and plural form if any, file and line references
     * @return void
     */
    protected function _addTranslation(string $domain, string $msgid, array $details = []): void
    {
        $context = $details['msgctxt'] ?? '';

        if (empty($this->_translations[$domain][$msgid][$context])) {
            $this->_translations[$domain][$msgid][$context] = [
                'msgid_plural' => false,
            ];
        }

        if (isset($details['msgid_plural'])) {
            $this->_translations[$domain][$msgid][$context]['msgid_plural'] = $details['msgid_plural'];
        }

        if (isset($details['file'])) {
            $line = $details['line'] ?? 0;
            $this->_translations[$domain][$msgid][$context]['references'][$details['file']][] = $line;
        }
    }

    /**
     * Extract text
     *
     * @return void
     */
    protected function _extract(): void
    {
        $this->out();
        $this->out();
        $this->out('Extracting...');
        $this->hr();
        $this->out('Paths:');
        foreach ($this->_paths as $path) {
            $this->out('   ' . $path);
        }
        $this->out('Output Directory: ' . $this->_output);
        $this->hr();
        $this->_extractTokens();
        $this->_buildFiles();
        $this->_writeFiles();
        $this->_paths = $this->_files = $this->_storage = [];
        $this->_translations = $this->_tokens = [];
        $this->out();
        if ($this->_countMarkerError) {
            $this->err("{$this->_countMarkerError} marker error(s) detected.");
            $this->err(" => Use the --marker-error option to display errors.");
        }

        $this->out('Done.');
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser(): ConsoleOptionParser
    {
        $parser = parent::getOptionParser();
        $parser->setDescription(
            'CakePHP Language String Extraction:'
        )->addOption('app', [
            'help' => 'Directory where your application is located.',
        ])->addOption('paths', [
            'help' => 'Comma separated list of paths.',
        ])->addOption('merge', [
            'help' => 'Merge all domain strings into the default.po file.',
            'choices' => ['yes', 'no'],
        ])->addOption('relative-paths', [
            'help' => 'Use relative paths in the .pot file',
            'boolean' => true,
            'default' => false,
        ])->addOption('output', [
            'help' => 'Full path to output directory.',
        ])->addOption('files', [
            'help' => 'Comma separated list of files.',
        ])->addOption('exclude-plugins', [
            'boolean' => true,
            'default' => true,
            'help' => 'Ignores all files in plugins if this command is run inside from the same app directory.',
        ])->addOption('plugin', [
            'help' => 'Extracts tokens only from the plugin specified and '
                . 'puts the result in the plugin\'s Locale directory.',
        ])->addOption('ignore-model-validation', [
            'boolean' => true,
            'default' => false,
            'help' => 'Ignores validation messages in the $validate property.' .
                ' If this flag is not set and the command is run from the same app directory,' .
                ' all messages in model validation rules will be extracted as tokens.',
        ])->addOption('validation-domain', [
            'help' => 'If set to a value, the localization domain to be used for model validation messages.',
        ])->addOption('exclude', [
            'help' => 'Comma separated list of directories to exclude.' .
                ' Any path containing a path segment with the provided values will be skipped. E.g. test,vendors',
        ])->addOption('overwrite', [
            'boolean' => true,
            'default' => false,
            'help' => 'Always overwrite existing .pot files.',
        ])->addOption('extract-core', [
            'help' => 'Extract messages from the CakePHP core libs.',
            'choices' => ['yes', 'no'],
        ])->addOption('no-location', [
            'boolean' => true,
            'default' => false,
            'help' => 'Do not write file locations for each extracted message.',
        ])->addOption('marker-error', [
            'boolean' => true,
            'default' => false,
            'help' => 'Do not display marker error.',
        ]);

        return $parser;
    }

    /**
     * Extract tokens out of all files to be processed
     *
     * @return void
     */
    protected function _extractTokens(): void
    {
        /** @var \Cake\Shell\Helper\ProgressHelper $progress */
        $progress = $this->helper('progress');
        $progress->init(['total' => count($this->_files)]);
        $isVerbose = $this->param('verbose');

        $functions = [
            '__' => ['singular'],
            '__n' => ['singular', 'plural'],
            '__d' => ['domain', 'singular'],
            '__dn' => ['domain', 'singular', 'plural'],
            '__x' => ['context', 'singular'],
            '__xn' => ['context', 'singular', 'plural'],
            '__dx' => ['domain', 'context', 'singular'],
            '__dxn' => ['domain', 'context', 'singular', 'plural'],
        ];
        $pattern = '/(' . implode('|', array_keys($functions)) . ')\s*\(/';

        foreach ($this->_files as $file) {
            $this->_file = $file;
            if ($isVerbose) {
                $this->out(sprintf('Processing %s...', $file), 1, Shell::VERBOSE);
            }

            $code = file_get_contents($file);

            if (preg_match($pattern, $code) === 1) {
                $allTokens = token_get_all($code);

                $this->_tokens = [];
                foreach ($allTokens as $token) {
                    if (!is_array($token) || ($token[0] !== T_WHITESPACE && $token[0] !== T_INLINE_HTML)) {
                        $this->_tokens[] = $token;
                    }
                }
                unset($allTokens);

                foreach ($functions as $functionName => $map) {
                    $this->_parse($functionName, $map);
                }
            }

            if (!$isVerbose) {
                $progress->increment(1);
                $progress->draw();
            }
        }
    }

    /**
     * Parse tokens
     *
     * @param string $functionName Function name that indicates translatable string (e.g: '__')
     * @param array $map Array containing what variables it will find (e.g: domain, singular, plural)
     * @return void
     */
    protected function _parse(string $functionName, array $map): void
    {
        $count = 0;
        $tokenCount = count($this->_tokens);

        while (($tokenCount - $count) > 1) {
            $countToken = $this->_tokens[$count];
            $firstParenthesis = $this->_tokens[$count + 1];
            if (!is_array($countToken)) {
                $count++;
                continue;
            }

            [$type, $string, $line] = $countToken;
            if (($type === T_STRING) && ($string === $functionName) && ($firstParenthesis === '(')) {
                $position = $count;
                $depth = 0;

                while (!$depth) {
                    if ($this->_tokens[$position] === '(') {
                        $depth++;
                    } elseif ($this->_tokens[$position] === ')') {
                        $depth--;
                    }
                    $position++;
                }

                $mapCount = count($map);
                $strings = $this->_getStrings($position, $mapCount);

                if ($mapCount === count($strings)) {
                    $singular = $plural = $context = null;
                    /**
                     * @var string $singular
                     * @var string|null $plural
                     * @var string|null $context
                     */
                    extract(array_combine($map, $strings));
                    $domain = $domain ?? 'default';
                    $details = [
                        'file' => $this->_file,
                        'line' => $line,
                    ];
                    if ($this->_relativePaths) {
                        $details['file'] = '.' . str_replace(ROOT, '', $details['file']);
                    }
                    if ($plural !== null) {
                        $details['msgid_plural'] = $plural;
                    }
                    if ($context !== null) {
                        $details['msgctxt'] = $context;
                    }
                    $this->_addTranslation($domain, $singular, $details);
                } else {
                    $this->_markerError($this->_file, $line, $functionName, $count);
                }
            }
            $count++;
        }
    }

    /**
     * Build the translate template file contents out of obtained strings
     *
     * @return void
     */
    protected function _buildFiles(): void
    {
        $paths = $this->_paths;
        $paths[] = realpath(APP) . DIRECTORY_SEPARATOR;

        usort($paths, function ($a, $b) {
            return strlen($a) - strlen($b);
        });

        foreach ($this->_translations as $domain => $translations) {
            foreach ($translations as $msgid => $contexts) {
                foreach ($contexts as $context => $details) {
                    $plural = $details['msgid_plural'];
                    $files = $details['references'];
                    $header = '';

                    if (!$this->param('no-location')) {
                        $occurrences = [];
                        foreach ($files as $file => $lines) {
                            $lines = array_unique($lines);
                            foreach ($lines as $line) {
                                $occurrences[] = $file . ':' . $line;
                            }
                        }
                        $occurrences = implode("\n#: ", $occurrences);

                        $header = '#: '
                            . str_replace(DIRECTORY_SEPARATOR, '/', str_replace($paths, '', $occurrences))
                            . "\n";
                    }

                    $sentence = '';
                    if ($context !== '') {
                        $sentence .= "msgctxt \"{$context}\"\n";
                    }
                    if ($plural === false) {
                        $sentence .= "msgid \"{$msgid}\"\n";
                        $sentence .= "msgstr \"\"\n\n";
                    } else {
                        $sentence .= "msgid \"{$msgid}\"\n";
                        $sentence .= "msgid_plural \"{$plural}\"\n";
                        $sentence .= "msgstr[0] \"\"\n";
                        $sentence .= "msgstr[1] \"\"\n\n";
                    }

                    if ($domain !== 'default' && $this->_merge) {
                        $this->_store('default', $header, $sentence);
                    } else {
                        $this->_store($domain, $header, $sentence);
                    }
                }
            }
        }
    }

    /**
     * Prepare a file to be stored
     *
     * @param string $domain The domain
     * @param string $header The header content.
     * @param string $sentence The sentence to store.
     * @return void
     */
    protected function _store(string $domain, string $header, string $sentence): void
    {
        if (!isset($this->_storage[$domain])) {
            $this->_storage[$domain] = [];
        }
        if (!isset($this->_storage[$domain][$sentence])) {
            $this->_storage[$domain][$sentence] = $header;
        } else {
            $this->_storage[$domain][$sentence] .= $header;
        }
    }

    /**
     * Write the files that need to be stored
     *
     * @return void
     */
    protected function _writeFiles(): void
    {
        $overwriteAll = false;
        if (!empty($this->params['overwrite'])) {
            $overwriteAll = true;
        }
        foreach ($this->_storage as $domain => $sentences) {
            $output = $this->_writeHeader();
            foreach ($sentences as $sentence => $header) {
                $output .= $header . $sentence;
            }

            // Remove vendor prefix if present.
            $slashPosition = strpos($domain, '/');
            if ($slashPosition !== false) {
                $domain = substr($domain, $slashPosition + 1);
            }

            $filename = str_replace('/', '_', $domain) . '.pot';
            $response = '';
            while ($overwriteAll === false
                && file_exists($this->_output . $filename)
                && strtoupper($response) !== 'Y'
            ) {
                $this->out();
                $response = $this->in(
                    sprintf('Error: %s already exists in this location. Overwrite? [Y]es, [N]o, [A]ll', $filename),
                    ['y', 'n', 'a'],
                    'y'
                );
                if (strtoupper($response) === 'N') {
                    $response = '';
                    while (!$response) {
                        $response = $this->in('What would you like to name this file?', null, 'new_' . $filename);
                        $filename = $response;
                    }
                } elseif (strtoupper($response) === 'A') {
                    $overwriteAll = true;
                }
            }
            $fs = new Filesystem();
            $fs->dumpFile($this->_output . $filename, $output);
        }
    }

    /**
     * Build the translation template header
     *
     * @return string Translation template header
     */
    protected function _writeHeader(): string
    {
        $output = "# LANGUAGE translation of CakePHP Application\n";
        $output .= "# Copyright YEAR NAME <EMAIL@ADDRESS>\n";
        $output .= "#\n";
        $output .= "#, fuzzy\n";
        $output .= "msgid \"\"\n";
        $output .= "msgstr \"\"\n";
        $output .= "\"Project-Id-Version: PROJECT VERSION\\n\"\n";
        $output .= '"POT-Creation-Date: ' . date('Y-m-d H:iO') . "\\n\"\n";
        $output .= "\"PO-Revision-Date: YYYY-mm-DD HH:MM+ZZZZ\\n\"\n";
        $output .= "\"Last-Translator: NAME <EMAIL@ADDRESS>\\n\"\n";
        $output .= "\"Language-Team: LANGUAGE <EMAIL@ADDRESS>\\n\"\n";
        $output .= "\"MIME-Version: 1.0\\n\"\n";
        $output .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
        $output .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
        $output .= "\"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n\"\n\n";

        return $output;
    }

    /**
     * Get the strings from the position forward
     *
     * @param int $position Actual position on tokens array
     * @param int $target Number of strings to extract
     * @return array Strings extracted
     */
    protected function _getStrings(int &$position, int $target): array
    {
        $strings = [];
        $count = count($strings);
        while ($count < $target
            && ($this->_tokens[$position] === ','
                || $this->_tokens[$position][0] === T_CONSTANT_ENCAPSED_STRING
                || $this->_tokens[$position][0] === T_LNUMBER
            )
        ) {
            $count = count($strings);
            if ($this->_tokens[$position][0] === T_CONSTANT_ENCAPSED_STRING && $this->_tokens[$position + 1] === '.') {
                $string = '';
                while ($this->_tokens[$position][0] === T_CONSTANT_ENCAPSED_STRING
                    || $this->_tokens[$position] === '.'
                ) {
                    if ($this->_tokens[$position][0] === T_CONSTANT_ENCAPSED_STRING) {
                        $string .= $this->_formatString($this->_tokens[$position][1]);
                    }
                    $position++;
                }
                $strings[] = $string;
            } elseif ($this->_tokens[$position][0] === T_CONSTANT_ENCAPSED_STRING) {
                $strings[] = $this->_formatString($this->_tokens[$position][1]);
            } elseif ($this->_tokens[$position][0] === T_LNUMBER) {
                $strings[] = $this->_tokens[$position][1];
            }
            $position++;
        }

        return $strings;
    }

    /**
     * Format a string to be added as a translatable string
     *
     * @param string $string String to format
     * @return string Formatted string
     */
    protected function _formatString(string $string): string
    {
        $quote = substr($string, 0, 1);
        $string = substr($string, 1, -1);
        if ($quote === '"') {
            $string = stripcslashes($string);
        } else {
            $string = strtr($string, ["\\'" => "'", '\\\\' => '\\']);
        }
        $string = str_replace("\r\n", "\n", $string);

        return addcslashes($string, "\0..\37\\\"");
    }

    /**
     * Indicate an invalid marker on a processed file
     *
     * @param string $file File where invalid marker resides
     * @param int $line Line number
     * @param string $marker Marker found
     * @param int $count Count
     * @return void
     */
    protected function _markerError(string $file, int $line, string $marker, int $count): void
    {
        if (strpos($this->_file, CAKE_CORE_INCLUDE_PATH) === false) {
            $this->_countMarkerError++;
        }

        if (!$this->_markerError) {
            return;
        }

        $this->err(sprintf("Invalid marker content in %s:%s\n* %s(", $file, $line, $marker));
        $count += 2;
        $tokenCount = count($this->_tokens);
        $parenthesis = 1;

        while ((($tokenCount - $count) > 0) && $parenthesis) {
            if (is_array($this->_tokens[$count])) {
                $this->err($this->_tokens[$count][1], 0);
            } else {
                $this->err($this->_tokens[$count], 0);
                if ($this->_tokens[$count] === '(') {
                    $parenthesis++;
                }

                if ($this->_tokens[$count] === ')') {
                    $parenthesis--;
                }
            }
            $count++;
        }
        $this->err("\n");
    }

    /**
     * Search files that may contain translatable strings
     *
     * @return void
     */
    protected function _searchFiles(): void
    {
        $pattern = false;
        if (!empty($this->_exclude)) {
            $exclude = [];
            foreach ($this->_exclude as $e) {
                if (DIRECTORY_SEPARATOR !== '\\' && $e[0] !== DIRECTORY_SEPARATOR) {
                    $e = DIRECTORY_SEPARATOR . $e;
                }
                $exclude[] = preg_quote($e, '/');
            }
            $pattern = '/' . implode('|', $exclude) . '/';
        }
        foreach ($this->_paths as $path) {
            $path = realpath($path) . DIRECTORY_SEPARATOR;
            $fs = new Filesystem();
            $files = $fs->findRecursive($path, '/\.php$/');
            $files = array_keys(iterator_to_array($files));
            sort($files);
            if (!empty($pattern)) {
                $files = preg_grep($pattern, $files, PREG_GREP_INVERT);
                $files = array_values($files);
            }
            $this->_files = array_merge($this->_files, $files);
        }
        $this->_files = array_unique($this->_files);
    }

    /**
     * Returns whether this execution is meant to extract string only from directories in folder represented by the
     * APP constant, i.e. this task is extracting strings from same application.
     *
     * @return bool
     */
    protected function _isExtractingApp(): bool
    {
        return $this->_paths === [APP];
    }

    /**
     * Checks whether or not a given path is usable for writing.
     *
     * @param string $path Path to folder
     * @return bool true if it exists and is writable, false otherwise
     */
    protected function _isPathUsable($path): bool
    {
        if (!is_dir($path)) {
            mkdir($path, 0770, true);
        }

        return is_dir($path) && is_writable($path);
    }
}
