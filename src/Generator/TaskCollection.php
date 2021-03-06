<?php
namespace IdeHelper\Generator;

use Cake\Core\Configure;
use IdeHelper\Generator\Task\BehaviorTask;
use IdeHelper\Generator\Task\ComponentTask;
use IdeHelper\Generator\Task\DatabaseTypeTask;
use IdeHelper\Generator\Task\ElementTask;
use IdeHelper\Generator\Task\HelperTask;
use IdeHelper\Generator\Task\ModelTask;
use IdeHelper\Generator\Task\PluginTask;
use IdeHelper\Generator\Task\TableAssociationTask;
use IdeHelper\Generator\Task\TableFinderTask;
use IdeHelper\Generator\Task\TaskInterface;
use InvalidArgumentException;

class TaskCollection {

	/**
	 * @var string[]
	 */
	protected $defaultTasks = [
		ModelTask::class => ModelTask::class,
		BehaviorTask::class => BehaviorTask::class,
		ComponentTask::class => ComponentTask::class,
		HelperTask::class => HelperTask::class,
		TableAssociationTask::class => TableAssociationTask::class,
		TableFinderTask::class => TableFinderTask::class,
		DatabaseTypeTask::class => DatabaseTypeTask::class,
		ElementTask::class => ElementTask::class,
		PluginTask::class => PluginTask::class,
	];

	/**
	 * @var \IdeHelper\Generator\Task\TaskInterface[]
	 */
	protected $tasks;

	/**
	 * @param array $tasks
	 */
	public function __construct(array $tasks = []) {
		$defaultTasks = $this->defaultTasks();
		$tasks += $defaultTasks;

		foreach ($tasks as $task) {
			if (!$task) {
				continue;
			}

			$this->add($task);
		}
	}

	/**
	 * @return string[]
	 */
	protected function defaultTasks() {
		$tasks = (array)Configure::read('IdeHelper.generatorTasks') + $this->defaultTasks;

		foreach ($tasks as $k => $v) {
			if (is_numeric($k)) {
				$tasks[$v] = $v;
				unset($tasks[$k]);
			}
		}

		return $tasks;
	}

	/**
	 * Adds a task to the collection.
	 *
	 * @param string|\IdeHelper\Generator\Task\TaskInterface $task The task to map.
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	protected function add($task) {
		if (is_string($task)) {
			$task = new $task();
		}

		$class = get_class($task);
		if (!$task instanceof TaskInterface) {
			throw new InvalidArgumentException(
				"Cannot use '$class' as task, it is not implementing " . TaskInterface::class . '.'
			);
		}

		$this->tasks[$class] = $task;

		return $this;
	}

	/**
	 * @return \IdeHelper\Generator\Task\TaskInterface[]
	 */
	public function tasks() {
		return $this->tasks;
	}

	/**
	 * @return array
	 */
	public function getMap() {
		$map = [];
		foreach ($this->tasks as $task) {
			$map += $task->collect();
		}

		ksort($map);

		return $map;
	}

}
