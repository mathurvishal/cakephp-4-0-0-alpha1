<?php
declare(strict_types=1);
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Bake\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Utility\Inflector;

/**
 * Mailer code generator.
 */
class MailerCommand extends SimpleBakeCommand
{
    /**
     * Task name used in path generation.
     *
     * @var string
     */
    public $pathFragment = 'Mailer/';

    /**
     * {@inheritDoc}
     */
    public function name(): string
    {
        return 'mailer';
    }

    /**
     * {@inheritDoc}
     */
    public function fileName(string $name): string
    {
        return $name . 'Mailer.php';
    }

    /**
     * {@inheritDoc}
     */
    public function template(): string
    {
        return 'Mailer/mailer';
    }

    /**
     * Bake the Mailer class and html/text layout files.
     *
     * @param string $name The name of the mailer to make.
     * @param \Cake\Console\Arguments $args The console arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    public function bake(string $name, Arguments $args, ConsoleIo $io): void
    {
        $this->bakeLayouts($name, $args, $io);

        parent::bake($name, $args, $io);
    }

    /**
     * Bake empty layout files for html/text emails.
     *
     * @param string $name The name of the mailer layouts are needed for.
     * @param \Cake\Console\Arguments $args The console arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    protected function bakeLayouts(string $name, Arguments $args, ConsoleIo $io): void
    {
        $restore = $this->pathFragment;
        $layoutsPath = implode(DS, ['..', 'templates', 'layout', 'email']);

        foreach (['html', 'text'] as $type) {
            $this->pathFragment = implode(DS, [$layoutsPath, $type, Inflector::underscore($name) . '.php']);
            $path = $this->getPath($args);
            $io->createFile($path, '');
        }

        $this->pathFragment = $restore;
    }
}
