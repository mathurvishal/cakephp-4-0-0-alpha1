<?php
declare(strict_types=1);
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Exception;

/**
 * Used when a template file for a cell cannot be found.
 */
class MissingCellTemplateException extends MissingTemplateException
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $type = 'Cell template';

    /**
     * Constructor
     *
     * @param string $name The Cell name that is missing a view.
     * @param string $file The view filename.
     * @param array $paths The path list that template could not be found in.
     * @param int|null $code The code of the error.
     * @param \Exception|null $previous the previous exception.
     */
    public function __construct(string $name, string $file, array $paths = [], $code = null, $previous = null)
    {
        $this->name = $name;

        parent::__construct($file, $paths, $code, $previous);
    }

    /**
     * Get the passed in attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return [
            'name' => $this->name,
            'file' => $this->file,
            'paths' => $this->paths,
        ];
    }
}
