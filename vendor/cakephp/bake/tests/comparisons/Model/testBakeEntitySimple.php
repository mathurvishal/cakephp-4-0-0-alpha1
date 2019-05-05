<?php
namespace Bake\Test\App\Model\Entity;

use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property string|null $username
 * @property string|null $password
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $updated
 *
 * @property \Bake\Test\App\Model\Entity\Comment[] $comments
 * @property \Bake\Test\App\Model\Entity\TodoItem[] $todo_items
 */
class User extends Entity
{
}
