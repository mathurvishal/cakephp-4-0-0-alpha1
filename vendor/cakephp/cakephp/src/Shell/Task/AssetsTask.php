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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Shell\Task;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Filesystem;
use Cake\Utility\Inflector;

/**
 * Task for symlinking / copying plugin assets to app's webroot.
 */
class AssetsTask extends Shell
{
    /**
     * Attempt to symlink plugin assets to app's webroot. If symlinking fails it
     * fallbacks to copying the assets. For vendor namespaced plugin, parent folder
     * for vendor name are created if required.
     *
     * @param string|null $name Name of plugin for which to symlink assets.
     *   If null all plugins will be processed.
     * @return void
     */
    public function symlink(?string $name = null): void
    {
        $this->_process($this->_list($name));
    }

    /**
     * Copying plugin assets to app's webroot. For vendor namespaced plugin,
     * parent folder for vendor name are created if required.
     *
     * @param string|null $name Name of plugin for which to symlink assets.
     *   If null all plugins will be processed.
     * @return void
     */
    public function copy(?string $name = null): void
    {
        $this->_process($this->_list($name), true, (bool)$this->param('overwrite'));
    }

    /**
     * Remove plugin assets from app's webroot.
     *
     * @param string|null $name Name of plugin for which to remove assets.
     *   If null all plugins will be processed.
     * @return void
     * @since 3.5.12
     */
    public function remove(?string $name = null): void
    {
        $plugins = $this->_list($name);

        foreach ($plugins as $plugin => $config) {
            $this->out();
            $this->out('For plugin: ' . $plugin);
            $this->hr();

            $this->_remove($config);
        }

        $this->out();
        $this->out('Done');
    }

    /**
     * Get list of plugins to process. Plugins without a webroot directory are skipped.
     *
     * @param string|null $name Name of plugin for which to symlink assets.
     *   If null all plugins will be processed.
     * @return array List of plugins with meta data.
     */
    protected function _list(?string $name = null): array
    {
        if ($name === null) {
            $pluginsList = Plugin::loaded();
        } else {
            if (!Plugin::isLoaded($name)) {
                $this->err(sprintf('Plugin %s is not loaded.', $name));

                return [];
            }
            $pluginsList = [$name];
        }

        $plugins = [];

        foreach ($pluginsList as $plugin) {
            $path = Plugin::path($plugin) . 'webroot';
            if (!is_dir($path)) {
                $this->verbose('', 1);
                $this->verbose(
                    sprintf('Skipping plugin %s. It does not have webroot folder.', $plugin),
                    2
                );
                continue;
            }

            $link = Inflector::underscore($plugin);
            $wwwRoot = Configure::read('App.wwwRoot');
            $dir = $wwwRoot;
            $namespaced = false;
            if (strpos($link, '/') !== false) {
                $namespaced = true;
                $parts = explode('/', $link);
                $link = array_pop($parts);
                $dir = $wwwRoot . implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR;
            }

            $plugins[$plugin] = [
                'srcPath' => Plugin::path($plugin) . 'webroot',
                'destDir' => $dir,
                'link' => $link,
                'namespaced' => $namespaced,
            ];
        }

        return $plugins;
    }

    /**
     * Process plugins
     *
     * @param array $plugins List of plugins to process
     * @param bool $copy Force copy mode. Default false.
     * @param bool $overwrite Overwrite existing files.
     * @return void
     */
    protected function _process(array $plugins, bool $copy = false, bool $overwrite = false): void
    {
        $overwrite = (bool)$this->param('overwrite');

        foreach ($plugins as $plugin => $config) {
            $this->out();
            $this->out('For plugin: ' . $plugin);
            $this->hr();

            if ($config['namespaced'] &&
                !is_dir($config['destDir']) &&
                !$this->_createDirectory($config['destDir'])
            ) {
                continue;
            }

            $dest = $config['destDir'] . $config['link'];

            if (file_exists($dest)) {
                if ($overwrite && !$this->_remove($config)) {
                    continue;
                } elseif (!$overwrite) {
                    $this->verbose(
                        $dest . ' already exists',
                        1
                    );

                    continue;
                }
            }

            if (!$copy) {
                $result = $this->_createSymlink(
                    $config['srcPath'],
                    $dest
                );
                if ($result) {
                    continue;
                }
            }

            $this->_copyDirectory(
                $config['srcPath'],
                $dest
            );
        }

        $this->out();
        $this->out('Done');
    }

    /**
     * Remove folder/symlink.
     *
     * @param array $config Plugin config.
     * @return bool
     */
    protected function _remove(array $config): bool
    {
        if ($config['namespaced'] && !is_dir($config['destDir'])) {
            $this->verbose(
                $config['destDir'] . $config['link'] . ' does not exist',
                1
            );

            return false;
        }

        $dest = $config['destDir'] . $config['link'];

        if (!file_exists($dest)) {
            $this->verbose(
                $dest . ' does not exist',
                1
            );

            return false;
        }

        if (is_link($dest)) {
            // phpcs:ignore
            if (@unlink($dest)) {
                $this->out('Unlinked ' . $dest);

                return true;
            } else {
                $this->err('Failed to unlink  ' . $dest);

                return false;
            }
        }

        $fs = new Filesystem();
        if ($fs->deleteDir($dest)) {
            $this->out('Deleted ' . $dest);

            return true;
        } else {
            $this->err('Failed to delete ' . $dest);

            return false;
        }
    }

    /**
     * Create directory
     *
     * @param string $dir Directory name
     * @return bool
     */
    protected function _createDirectory(string $dir): bool
    {
        $old = umask(0);
        // phpcs:disable
        $result = @mkdir($dir, 0755, true);
        // phpcs:enable
        umask($old);

        if ($result) {
            $this->out('Created directory ' . $dir);

            return true;
        }

        $this->err('Failed creating directory ' . $dir);

        return false;
    }

    /**
     * Create symlink
     *
     * @param string $target Target directory
     * @param string $link Link name
     * @return bool
     */
    protected function _createSymlink(string $target, string $link): bool
    {
        // phpcs:disable
        $result = @symlink($target, $link);
        // phpcs:enable

        if ($result) {
            $this->out('Created symlink ' . $link);

            return true;
        }

        return false;
    }

    /**
     * Copy directory
     *
     * @param string $source Source directory
     * @param string $destination Destination directory
     * @return bool
     */
    protected function _copyDirectory(string $source, string $destination): bool
    {
        $fs = new Filesystem();
        if ($fs->copyDir($source, $destination)) {
            $this->out('Copied assets to directory ' . $destination);

            return true;
        }

        $this->err('Error copying assets to directory ' . $destination);

        return false;
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser(): ConsoleOptionParser
    {
        $parser = parent::getOptionParser();

        $parser->addSubcommand('symlink', [
            'help' => 'Symlink (copy as fallback) plugin assets to app\'s webroot.',
        ])->addSubcommand('copy', [
            'help' => 'Copy plugin assets to app\'s webroot.',
        ])->addSubcommand('remove', [
            'help' => 'Remove plugin assets from app\'s webroot.',
        ])->addArgument('name', [
            'help' => 'A specific plugin you want to symlink assets for.',
            'optional' => true,
        ])->addOption('overwrite', [
            'help' => 'Overwrite existing symlink / folder / files.',
            'default' => false,
            'boolean' => true,
        ]);

        return $parser;
    }
}
