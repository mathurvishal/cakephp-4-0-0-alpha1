<?php
namespace Bake\Test\App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Items Model
 *
 * @property \Bake\Test\App\Model\Table\UsersTable|\Cake\ORM\Association\BelongsTo $Users
 * @property \Bake\Test\App\Model\Table\TodoTasksTable|\Cake\ORM\Association\HasMany $TodoTasks
 * @property \Bake\Test\App\Model\Table\TodoLabelsTable|\Cake\ORM\Association\BelongsToMany $TodoLabels
 *
 * @method \Bake\Test\App\Model\Entity\Item get($primaryKey, $options = [])
 * @method \Bake\Test\App\Model\Entity\Item newEntity($data = null, array $options = [])
 * @method \Bake\Test\App\Model\Entity\Item[] newEntities(array $data, array $options = [])
 * @method \Bake\Test\App\Model\Entity\Item|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Bake\Test\App\Model\Entity\Item saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Bake\Test\App\Model\Entity\Item patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Bake\Test\App\Model\Entity\Item[] patchEntities($entities, array $data, array $options = [])
 * @method \Bake\Test\App\Model\Entity\Item findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ItemsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('todo_items');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('TodoTasks', [
            'foreignKey' => 'todo_item_id',
        ]);
        $this->belongsToMany('TodoLabels', [
            'foreignKey' => 'todo_item_id',
            'targetForeignKey' => 'todo_label_id',
            'joinTable' => 'todo_items_todo_labels',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', 'create');

        $validator
            ->scalar('title')
            ->maxLength('title', 50)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('body')
            ->allowEmptyString('body');

        $validator
            ->decimal('effort')
            ->requirePresence('effort', 'create')
            ->notEmptyString('effort');

        $validator
            ->boolean('completed')
            ->requirePresence('completed', 'create')
            ->notEmptyString('completed');

        $validator
            ->integer('todo_task_count')
            ->requirePresence('todo_task_count', 'create')
            ->notEmptyString('todo_task_count');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }

    /**
     * Returns the database connection name to use by default.
     *
     * @return string
     */
    public static function defaultConnectionName(): string
    {
        return 'test';
    }
}