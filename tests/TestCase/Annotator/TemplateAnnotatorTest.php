<?php

namespace IdeHelper\Test\TestCase\Annotator;

use App\Model\Table\FooTable;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Database\Schema\TableSchema;
use Cake\ORM\TableRegistry;
use IdeHelper\Annotator\AbstractAnnotator;
use IdeHelper\Annotator\TemplateAnnotator;
use IdeHelper\Console\Io;
use Tools\TestSuite\ConsoleOutput;
use Tools\TestSuite\TestCase;
use Tools\TestSuite\ToolsTestTrait;

class TemplateAnnotatorTest extends TestCase {

	use DiffHelperTrait;
	use ToolsTestTrait;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	protected $out;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	protected $err;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$consoleIo = new ConsoleIo($this->out, $this->err);
		$this->io = new Io($consoleIo);

		$x = TableRegistry::get('IdeHelper.Foo', ['className' => FooTable::class]);
		$columns = [
			'id' => [
				'type' => 'integer',
				'length' => 11,
				'unsigned' => false,
				'null' => false,
				'default' => null,
				'comment' => '',
				'autoIncrement' => true,
				'baseType' => null,
				'precision' => null
			],
		];
		$schema = new TableSchema('Foo', $columns);
		$x->setSchema($schema);
		TableRegistry::set('Foo', $x);

		Configure::delete('IdeHelper');
		Configure::write('IdeHelper.preemptive', true);
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		Configure::delete('IdeHelper');

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testGetVariableAnnotations() {
		Configure::write('IdeHelper.autoCollect', function(array $variable) {
			if ($variable['name'] === 'date') {
				return 'Cake\I18n\FrozenTime';
			}

			return 'mixed';
		});

		$annotator = $this->_getAnnotatorMock([]);

		$variable = [
			'name' => 'date',
			'type' => 'object',
		];
		$result = $this->invokeMethod($annotator, '_getVariableAnnotation', [$variable]);
		$this->assertSame('@var Cake\I18n\FrozenTime $date', (string)$result);
	}

	/**
	 * @return void
	 */
	public function testNeedsViewAnnotation() {
		Configure::write('IdeHelper.preemptive', false);

		$annotator = $this->_getAnnotatorMock([]);

		$content = '';
		$result = $this->invokeMethod($annotator, '_needsViewAnnotation', [$content]);
		$this->assertFalse($result);

		$content = 'Foo Bar';
		$result = $this->invokeMethod($annotator, '_needsViewAnnotation', [$content]);
		$this->assertFalse($result);

		$content = 'Foo <?php echo $this->Foo->bar(); ?>';
		$result = $this->invokeMethod($annotator, '_needsViewAnnotation', [$content]);
		$this->assertTrue($result);

		$content = 'Foo <?= $x; ?>';
		$result = $this->invokeMethod($annotator, '_needsViewAnnotation', [$content]);
		$this->assertTrue($result);
	}

	/**
	 * Tests create() parsing part and creating a new PHP tag in first line.
	 *
	 * @return void
	 */
	public function testAnnotate() {
		$annotator = $this->_getAnnotatorMock([]);

		$expectedContent = str_replace("\r\n", "\n", file_get_contents(TEST_FILES . 'Template/edit.ctp'));
		$callback = function($value) use ($expectedContent) {
			$value = str_replace(["\r\n", "\r"], "\n", $value);
			if ($value !== $expectedContent) {
				$this->_displayDiff($expectedContent, $value);
			}
			return $value === $expectedContent;
		};
		$annotator->expects($this->once())->method('_storeFile')->with($this->anything(), $this->callback($callback));

		$path = APP . 'Template/Foos/edit.ctp';
		$annotator->annotate($path);

		$output = (string)$this->out->output();

		$this->assertTextContains('   -> 2 annotations added.', $output);
	}

	/**
	 * Tests loop and entity->field, as well as writing into an existing PHP tag.
	 *
	 * @return void
	 */
	public function testAnnotateLoop() {
		$annotator = $this->_getAnnotatorMock([]);

		$expectedContent = str_replace("\r\n", "\n", file_get_contents(TEST_FILES . 'Template/loop.ctp'));
		$callback = function($value) use ($expectedContent) {
			$value = str_replace(["\r\n", "\r"], "\n", $value);
			if ($value !== $expectedContent) {
				$this->_displayDiff($expectedContent, $value);
			}
			return $value === $expectedContent;
		};
		$annotator->expects($this->once())->method('_storeFile')->with($this->anything(), $this->callback($callback));

		$path = APP . 'Template/Foos/loop.ctp';
		$annotator->annotate($path);

		$output = (string)$this->out->output();

		$this->assertTextContains('   -> 3 annotations added.', $output);
	}

	/**
	 * Tests loop and entity->field, as well as writing into an existing PHP tag.
	 *
	 * @return void
	 */
	public function testAnnotatePhpLine() {
		$annotator = $this->_getAnnotatorMock([]);

		$expectedContent = str_replace("\r\n", "\n", file_get_contents(TEST_FILES . 'Template/phpline.ctp'));
		$callback = function($value) use ($expectedContent) {
			$value = str_replace(["\r\n", "\r"], "\n", $value);
			if ($value !== $expectedContent) {
				$this->_displayDiff($expectedContent, $value);
			}
			return $value === $expectedContent;
		};
		$annotator->expects($this->once())->method('_storeFile')->with($this->anything(), $this->callback($callback));

		$path = APP . 'Template/Foos/phpline.ctp';
		$annotator->annotate($path);

		$output = (string)$this->out->output();

		$this->assertTextContains('   -> 3 annotations added.', $output);
	}

	/**
	 * Tests merging with existing PHP tag and doc block.
	 *
	 * @return void
	 */
	public function testAnnotateExisting() {
		$annotator = $this->_getAnnotatorMock([]);

		$expectedContent = str_replace("\r\n", "\n", file_get_contents(TEST_FILES . 'Template/existing.ctp'));
		$callback = function($value) use ($expectedContent) {
			$value = str_replace(["\r\n", "\r"], "\n", $value);
			if ($value !== $expectedContent) {
				$this->_displayDiff($expectedContent, $value);
			}
			return $value === $expectedContent;
		};
		$annotator->expects($this->once())->method('_storeFile')->with($this->anything(), $this->callback($callback));

		$path = APP . 'Template/Foos/existing.ctp';
		$annotator->annotate($path);

		$output = (string)$this->out->output();

		$this->assertTextContains('   -> 2 annotations added.', $output);
	}

	/**
	 * Tests merging with existing PHP tag and doc block and replacing outdated annotations.
	 *
	 * @return void
	 */
	public function testAnnotateExistingOutdated() {
		$annotator = $this->_getAnnotatorMock([]);

		$expectedContent = str_replace("\r\n", "\n", file_get_contents(TEST_FILES . 'Template/outdated.ctp'));
		$callback = function($value) use ($expectedContent) {
			$value = str_replace(["\r\n", "\r"], "\n", $value);
			if ($value !== $expectedContent) {
				$this->_displayDiff($expectedContent, $value);
			}
			return $value === $expectedContent;
		};
		$annotator->expects($this->once())->method('_storeFile')->with($this->anything(), $this->callback($callback));

		$path = APP . 'Template/Foos/outdated.ctp';
		$annotator->annotate($path);

		$output = (string)$this->out->output();

		$this->assertTextContains('   -> 2 annotations updated, 1 annotation removed, 1 annotation skipped.', $output);
	}

	/**
	 * Tests with empty template
	 *
	 * @return void
	 */
	public function testAnnotateEmptyPreemptive() {
		$annotator = $this->_getAnnotatorMock([]);

		$expectedContent = str_replace("\r\n", "\n", file_get_contents(TEST_FILES . 'Template/empty.ctp'));
		$callback = function($value) use ($expectedContent) {
			$value = str_replace(["\r\n", "\r"], "\n", $value);
			if ($value !== $expectedContent) {
				$this->_displayDiff($expectedContent, $value);
			}
			return $value === $expectedContent;
		};
		$annotator->expects($this->once())->method('_storeFile')->with($this->anything(), $this->callback($callback));

		$path = APP . 'Template/Foos/empty.ctp';
		$annotator->annotate($path);

		$output = (string)$this->out->output();

		$this->assertTextContains('   -> 1 annotation added.', $output);
	}

	/**
	 * Tests with template variables.
	 *
	 * @return void
	 */
	public function testAnnotateVars() {
		$annotator = $this->_getAnnotatorMock([]);

		$expectedContent = str_replace("\r\n", "\n", file_get_contents(TEST_FILES . 'Template/vars.ctp'));
		$callback = function($value) use ($expectedContent) {
			$value = str_replace(["\r\n", "\r"], "\n", $value);
			if ($value !== $expectedContent) {
				$this->_displayDiff($expectedContent, $value);
			}
			return $value === $expectedContent;
		};
		$annotator->expects($this->once())->method('_storeFile')->with($this->anything(), $this->callback($callback));

		$path = APP . 'Template/Foos/vars.ctp';
		$annotator->annotate($path);

		$output = (string)$this->out->output();

		$this->assertTextContains('   -> 6 annotations added.', $output);
	}

	/**
	 * Tests with empty template
	 *
	 * @return void
	 */
	public function testAnnotateEmpty() {
		Configure::write('IdeHelper.preemptive', false);

		$annotator = $this->_getAnnotatorMock([]);

		$callback = function($value) {
		};
		$annotator->expects($this->never())->method('_storeFile')->with($this->anything(), $this->callback($callback));

		$path = APP . 'Template/Foos/empty.ctp';
		$annotator->annotate($path);

		$output = (string)$this->out->output();

		$this->assertTextEquals('', $output);
	}

	/**
	 * Tests merging with existing inline doc block.
	 *
	 * @return void
	 */
	public function testAnnotateInline() {
		$annotator = $this->_getAnnotatorMock([]);

		$expectedContent = str_replace("\r\n", "\n", file_get_contents(TEST_FILES . 'Template/inline.ctp'));
		$callback = function($value) use ($expectedContent) {
			$value = str_replace(["\r\n", "\r"], "\n", $value);
			if ($value !== $expectedContent) {
				$this->_displayDiff($expectedContent, $value);
			}
			return $value === $expectedContent;
		};
		$annotator->expects($this->once())->method('_storeFile')->with($this->anything(), $this->callback($callback));

		$path = APP . 'Template/Foos/inline.ctp';
		$annotator->annotate($path);

		$output = (string)$this->out->output();

		$this->assertTextContains('   -> 1 annotation added.', $output);
	}

	/**
	 * @param array $params
	 * @return \IdeHelper\Annotator\TemplateAnnotator|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected function _getAnnotatorMock(array $params) {
		$params += [
			AbstractAnnotator::CONFIG_REMOVE => true,
			AbstractAnnotator::CONFIG_DRY_RUN => true
		];
		return $this->getMockBuilder(TemplateAnnotator::class)->setMethods(['_storeFile'])->setConstructorArgs([$this->io, $params])->getMock();
	}

}
