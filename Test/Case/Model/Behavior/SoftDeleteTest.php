<?php
/**
 * Copyright 2009 - 2014, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009 - 2014, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Behavior', 'Utils.SoftDelete');
// App::uses('SoftDelete', 'Utils.Model/Behavior/SoftDelete');

/**
 * SoftDeleteTestBehavior
 *
 * @package default
 * @author Predominant
 */
// class SoftDeleteTestBehavior extends SoftDeleteBehavior {
// }

/**
 * SoftDeletedPost
 *
 * @package utils
 * @subpackage utils.tests.cases.behaviors
 */
class SoftDeletedPost extends CakeTestModel {

/**
 * Use Table
 *
 * @var string
 */
	public $useTable = 'posts';

/**
 * Behaviors
 *
 * @var array
 */
	public $actsAs = array('Utils.SoftDelete');

/**
 * Alias
 *
 * @var string
 */
	public $alias = 'Post';
}

/**
 * SoftDeletedPost
 *
 * @package utils
 * @subpackage utils.tests.cases.behaviors
 */
class SoftDeletedArticle extends CakeTestModel {

/**
 * Use Table
 *
 * @var string
 */
	public $useTable = 'articles';

/**
 * Behaviors
 *
 * @var array
 */
	public $actsAs = array('Utils.SoftDelete');

/**
 * Alias
 *
 * @var string
 */
	public $alias = 'Article';
}

/**
 * SoftDelete Test case
 */
class SoftDeleteTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.utils.post',
		'plugin.utils.article'
	);

/**
 * Creates the model instance
 *
 * @return void
 */
	public function setUp() {
		$this->Post = new SoftDeletedPost();
		$this->Article = new SoftDeletedArticle();
	}

/**
 * Destroy the model instance
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Post);
		ClassRegistry::flush();
	}

/**
 * Test saving a item
 *
 * @return void
 */
	public function testSoftDelete() {
		$data = $this->Post->read(null, 1);
		$this->assertEquals($data[$this->Post->alias][$this->Post->primaryKey], 1);
		$result = $this->Post->delete(1);
		$this->assertFalse($result);
		$data = $this->Post->read(null, 1);
		$this->assertTrue(empty($data));
		$this->Post->Behaviors->unload('SoftDelete');
		$data = $this->Post->read(null, 1);
		$this->assertEquals($data['Post']['deleted'], true);
		$this->assertTrue(!empty($data['Post']['deleted_date']));
	}

/**
 * testSoftDeleteWhenModelDataIsEmpty
 *
 * @return void
 */
	public function testSoftDeleteWhenModelDataIsEmpty() {
		$data = $this->Post->find('first', array('conditions' => array($this->Post->primaryKey => 1)));
		$this->assertEquals($data[$this->Post->alias][$this->Post->primaryKey], 1);
		$this->assertTrue(empty($this->Post->data));
		$result = $this->Post->delete(1);
		$this->assertFalse($result);
		$data = $this->Post->read(null, 1);
		$this->assertTrue(empty($data));
		$this->Post->Behaviors->unload('SoftDelete');
		$data = $this->Post->read(null, 1);
		$this->assertEquals($data['Post']['deleted'], true);
		$this->assertTrue(!empty($data['Post']['deleted_date']));
	}

/**
 * testSoftDeleteUsingIdThatDoesNotExist
 *
 * @return void
 */
	public function testSoftDeleteUsingIdThatDoesNotExist() {
		$data = $this->Post->read(null, 222);
		$this->assertTrue(empty($data));
		$result = $this->Post->delete(222);
		$this->assertFalse($result); // consistent with Model->delete()
		// previous implementation would try to create a new record
		$this->Post->Behaviors->unload('SoftDelete');
		$data = $this->Post->read(null, 222);
		$this->assertTrue(empty($data));
	}

/**
 * testUnDelete
 *
 * @return void
 */
	public function testUnDelete() {
		$data = $this->Post->read(null, 1);
		$result = $this->Post->delete(1);
		$result = $this->Post->undelete(1);
		$this->Post->Behaviors->unload('SoftDelete');
		$data = $this->Post->read(null, 1);
		$this->assertEquals($data['Post']['deleted'], false);
	}

/**
 * testSoftDeletePurge
 *
 * @return void
 */
	public function testSoftDeletePurge() {
		$this->Post->Behaviors->disable('SoftDelete');
		$data = $this->Post->read(null, 3);
		$this->assertTrue(!empty($data));
		$this->Post->Behaviors->enable('SoftDelete');
		$data = $this->Post->read(null, 3);
		$this->assertTrue(empty($data));
		$count = $this->Post->purgeDeletedCount();
		$this->assertEquals($count, 1);
		$this->Post->purgeDeleted();

		$data = $this->Post->read(null, 3);
		$this->assertTrue(empty($data));
		$this->Post->Behaviors->disable('SoftDelete');
		$data = $this->Post->read(null, 3);
		$this->assertTrue(empty($data));
	}

/**
 * testExistsAndNotDeleted
 *
 * @return void
 */
	public function testExistsAndNotDeleted() {
		$data = $this->Post->read(null, 1);
		$this->assertEquals($data[$this->Post->alias][$this->Post->primaryKey], 1);
		$result = $this->Post->delete(1);
		$this->assertFalse($result);
		$data = $this->Post->read(null, 1);
		$this->assertTrue(empty($data));
		$this->assertFalse($this->Post->existsAndNotDeleted(1));
		$this->Post->undelete(1);
		$this->assertTrue($this->Post->existsAndNotDeleted(1));
	}

/**
 * testSoftDeleteAll
 *
 * @return void
 */
	public function testSoftDeleteAll() {
		$this->Post->saveAll(array(
			array('title' => 'Test softDeleteAll'),
			array('title' => 'Test softDeleteAll'),
			array('title' => 'Test softDeleteAll')
		));
		$this->Post->softDeleteAll(array(
			'Post.title' => 'Test softDeleteAll'
		));
		$this->Post->Behaviors->unload('SoftDelete');
		$result = $this->Post->find('all', array(
			'conditions' => array(
				'Post.title' => 'Test softDeleteAll'
			),
			'fields' => array(
				'Post.title',
				'Post.deleted',
			)
		));
		$expected = array(
			0 => array(
				'Post' => array(
					'title' => 'Test softDeleteAll',
					'deleted' => true
				)
			),
			1 => array(
				'Post' => array(
					'title' => 'Test softDeleteAll',
					'deleted' => true
				)
			),
			2 => array(
				'Post' => array(
					'title' => 'Test softDeleteAll',
					'deleted' => true
				)
			)
		);
		$this->assertEquals($result, $expected);
	}

	public function testModelHasManyRelation() {
		$this->Post->bindModel(array(
			'belongsTo' => array(
				'Article' => array(
					'foreignKey' => 'article_id'
				)
			)
		));

		$this->Article->bindModel(array(
			'hasMany' => array(
				'Post' => array(
					'foreignKey' => 'article_id'
				)
			)
		));

		$result = $this->Post->delete(1);
		$this->Post->Behaviors->unload('SoftDelete');
		$this->assertFalse($result);
		$data = $this->Post->read(null, 1);
		$this->assertEquals($data['Post']['deleted'], true);
		$this->assertTrue(!empty($data['Post']['deleted_date']));
	}
}