<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $name
 * @property string $content
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property string|null $virtual_one
 * @property mixed $virtual_two
 * @property \App\Model\Entity\Wheel[] $wheels
 */
class Wheel extends Entity {

	const OTHER_NAME = 'unrelated';

	const FIELD_ID = 'id';
	const FIELD_NAME = 'name';
	const FIELD_CONTENT = 'content';
	const FIELD_CREATED = 'created';
	const FIELD_VIRTUAL_TWO = 'virtual_two';
	const FIELD_WHEELS = 'wheels';

	/**
	 * @var array
	 */
	protected $_virtual = [
		'virtual_one',
	];

	/**
	 * @return string|null
	 */
	protected function _getVirtualOne() {
		return 'Virtual One';
	}

	protected function _getVirtualTwo() {
		// Missing return type and docblock means mixed as result
		return 'Virtual Two';
	}

	/**
	 * @param \App\Model\Entity\Wheel[] $wheels
	 *
	 * @return \App\Model\Entity\Wheel[]
	 */
	protected function _getWheels($wheels = []) {
		return $wheels;
	}

}
