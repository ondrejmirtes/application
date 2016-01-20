<?php

/**
 * Test: Nette\Application\UI\Presenter::link()
 */

use Nette\Http;
use Nette\Application;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class TestControl extends Application\UI\Control
{
	/** @persistent array */
	public $order = array();

	/** @persistent int */
	public $round = 0;


	public function handleClick($x, $y)
	{
	}


	/**
	 * Loads params
	 * @param  array
	 * @return void
	 */
	public function loadState(array $params)
	{
		if (isset($params['order'])) {
			$params['order'] = explode('.', $params['order']);
		}
		parent::loadState($params);
	}


	/**
	 * Save params
	 * @param  array
	 * @return void
	 */
	public function saveState(array & $params)
	{
		parent::saveState($params);
		if (isset($params['order'])) {
			$params['order'] = implode('.', $params['order']);
		}
	}

}


class TestPresenter extends Application\UI\Presenter
{
	/** @persistent */
	public $var1 = 10;

	/** @persistent */
	public $ok = TRUE;

	/** @persistent */
	public $var2;

	/** @persistent */
	public $var3 = array();


	protected function createTemplate($class = NULL)
	{
	}


	protected function startup()
	{
		parent::startup();
		$this['mycontrol'] = new TestControl;
		$this->invalidLinkMode = self::INVALID_LINK_TEXTUAL;

		// Presenter & action link
		Assert::same('/index.php?action=product&presenter=Test', $this->link('product', array('var1' => $this->var1)));
		Assert::same('/index.php?var1=20&var3%5B0%5D=1&action=product&presenter=Test', $this->link('product', array('var1' => $this->var1 * 2, 'ok' => TRUE, 'var3' => array(1))));
		Assert::same('/index.php?var1=1&ok=0&action=product&presenter=Test', $this->link('product', array('var1' => TRUE, 'ok' => '0', 'var3' => array())));
		Assert::same('/index.php?var1=0&ok=0&action=product&presenter=Test', $this->link('product', array('var1' => FALSE, 'ok' => FALSE, 'var2' => FALSE, 'var3' => NULL)));
		Assert::same("#error: Value passed to persistent parameter 'ok' in presenter Test must be boolean, string given.", $this->link('product', array('var1' => NULL, 'ok' => 'a')));
		Assert::same("#error: Value passed to persistent parameter 'var1' in presenter Test must be integer, array given.", $this->link('product', array('var1' => array(1), 'ok' => FALSE)));
		Assert::same("#error: Unable to pass parameters to action 'Test:product', missing corresponding method.", $this->link('product', 1, 2));
		Assert::same('/index.php?x=1&y=2&action=product&presenter=Test', $this->link('product', array('x' => 1, 'y' => 2)));
		Assert::same('/index.php?action=product&presenter=Test', $this->link('product'));
		Assert::same('#error: Destination must be non-empty string.', $this->link(''));
		Assert::same('/index.php?x=1&y=2&action=product&presenter=Test', $this->link('product?x=1&y=2'));
		Assert::same('/index.php?x=1&y=2&action=product&presenter=Test#fragment', $this->link('product?x=1&y=2#fragment'));
		Assert::same('http://localhost/index.php?x=1&y=2&action=product&presenter=Test#fragment', $this->link('//product?x=1&y=2#fragment'));

		// Other presenter & action link
		Assert::same('/index.php?var1=10&action=product&presenter=Other', $this->link('Other:product', array('var1' => $this->var1)));
		Assert::same('/index.php?action=product&presenter=Other', $this->link('Other:product', array('var1' => $this->var1 * 2)));
		Assert::same('/index.php?var1=123&presenter=Nette%3AMicro', $this->link('Nette:Micro:', array('var1' => 123)));

		// Presenter & signal link
		Assert::same('/index.php?action=default&do=buy&presenter=Test', $this->link('buy!', array('var1' => $this->var1)));
		Assert::same('/index.php?var1=20&action=default&do=buy&presenter=Test', $this->link('buy!', array('var1' => $this->var1 * 2)));
		Assert::same('/index.php?y=2&action=default&do=buy&presenter=Test', $this->link('buy!', 1, 2));
		Assert::same('/index.php?y=2&bool=1&str=1&action=default&do=buy&presenter=Test', $this->link('buy!', '1', '2', TRUE, TRUE));
		Assert::same('/index.php?y=0&str=0&action=default&do=buy&presenter=Test', $this->link('buy!', '1', 0, FALSE, FALSE));
		Assert::same('/index.php?action=default&do=buy&presenter=Test', $this->link('buy!', array('str' => '', 'var2' => '')));
		Assert::same('/index.php?action=default&do=buy&presenter=Test', $this->link('buy!', array(1)));
		Assert::same('/index.php?action=default&do=buy&presenter=Test', $this->link('buy!', array(1), (object) array(1)));
		Assert::same('/index.php?y=2&action=default&do=buy&presenter=Test', $this->link('buy!', array(1, 'y' => 2)));
		Assert::same('/index.php?y=2&action=default&do=buy&presenter=Test', $this->link('buy!', array('x' => 1, 'y' => 2, 'var1' => $this->var1)));
		Assert::same('#error: Signal must be non-empty string.', $this->link('!'));
		Assert::same('/index.php?action=default&presenter=Test', $this->link('this', array('var1' => $this->var1)));
		Assert::same('/index.php?action=default&presenter=Test', $this->link('this!', array('var1' => $this->var1)));
		Assert::same('/index.php?sort%5By%5D%5Basc%5D=1&action=default&presenter=Test', $this->link('this', array('sort' => array('y' => array('asc' => TRUE)))));

		// Presenter & signal link type checking
		Assert::same('#error: Argument $x passed to TestPresenter::handleBuy() must be integer, string given.', $this->link('buy!', 'x'));
		Assert::same('#error: Argument $bool passed to TestPresenter::handleBuy() must be boolean, integer given.', $this->link('buy!', 1, 2, 3));
		Assert::same('#error: Argument $x passed to TestPresenter::handleBuy() must be integer, array given.', $this->link('buy!', array(array())));
		Assert::same('/index.php?action=default&do=buy&presenter=Test', $this->link('buy!'));
		Assert::same('/index.php?action=default&do=buy&presenter=Test', $this->link('buy!', array(new stdClass)));

		Assert::same('/index.php?a=x&action=default&do=obj&presenter=Test', $this->link('obj!', array('x')));
		Assert::same('/index.php?action=default&do=obj&presenter=Test', $this->link('obj!', array(new stdClass)));
		Assert::same('/index.php?action=default&do=obj&presenter=Test', $this->link('obj!', array(new Exception)));
		Assert::same('/index.php?action=default&do=obj&presenter=Test', $this->link('obj!', array(NULL)));
		Assert::same('/index.php?b=x&action=default&do=obj&presenter=Test', $this->link('obj!', array('b' => 'x')));
		Assert::same('/index.php?action=default&do=obj&presenter=Test', $this->link('obj!', array('b' => new stdClass)));
		Assert::same('/index.php?action=default&do=obj&presenter=Test', $this->link('obj!', array('b' => new Exception)));
		Assert::same('/index.php?action=default&do=obj&presenter=Test', $this->link('obj!', array('b' => NULL)));

		Assert::same('#error: Argument $arr1 passed to TestPresenter::handleArray() must be array, string given.', $this->link('array!', array('x')));
		Assert::same('/index.php?arr1%5B0%5D=1&arr2%5B0%5D=2&arr3%5B0%5D=3&action=default&do=array&presenter=Test', $this->link('array!', array(array(1), array(2), array(3))));
		Assert::same('/index.php?action=default&do=array&presenter=Test', $this->link('array!', array(array(), array(), array())));
		Assert::same('/index.php?action=default&do=array&presenter=Test', $this->link('array!', array(NULL, NULL, NULL)));

		// Component link
		Assert::same('#error: Signal must be non-empty string.', $this['mycontrol']->link('', 0, 1));
		Assert::same('/index.php?mycontrol-x=0&mycontrol-y=1&action=default&do=mycontrol-click&presenter=Test', $this['mycontrol']->link('click', 0, 1));
		Assert::same('/index.php?mycontrol-x=0a&mycontrol-y=1a&action=default&do=mycontrol-click&presenter=Test', $this['mycontrol']->link('click', '0a', '1a'));
		Assert::same('/index.php?mycontrol-x=1&action=default&do=mycontrol-click&presenter=Test', $this['mycontrol']->link('click', array(1)));
		Assert::same('/index.php?mycontrol-x=1&action=default&do=mycontrol-click&presenter=Test', $this['mycontrol']->link('click', array(1), (object) array(1)));
		Assert::same('/index.php?mycontrol-x=1&action=default&do=mycontrol-click&presenter=Test', $this['mycontrol']->link('click', TRUE, FALSE));
		Assert::same('/index.php?action=default&do=mycontrol-click&presenter=Test', $this['mycontrol']->link('click', NULL, ''));
		Assert::same('#error: Passed more parameters than method TestControl::handleClick() expects.', $this['mycontrol']->link('click', 1, 2, 3));
		Assert::same('/index.php?mycontrol-x=1&mycontrol-y=2&action=default&do=mycontrol-click&presenter=Test', $this['mycontrol']->link('click!', array('x' => 1, 'y' => 2, 'round' => 0)));
		Assert::same('/index.php?mycontrol-x=1&mycontrol-round=1&action=default&do=mycontrol-click&presenter=Test', $this['mycontrol']->link('click', array('x' => 1, 'round' => 1)));
		Assert::same('/index.php?mycontrol-x=1&mycontrol-round=1&action=default&presenter=Test', $this['mycontrol']->link('this', array('x' => 1, 'round' => 1)));
		Assert::same('/index.php?mycontrol-x=1&mycontrol-round=1&action=default&presenter=Test', $this['mycontrol']->link('this?x=1&round=1'));
		Assert::same('/index.php?mycontrol-x=1&mycontrol-round=1&action=default&presenter=Test#frag', $this['mycontrol']->link('this?x=1&round=1#frag'));
		Assert::same('http://localhost/index.php?mycontrol-x=1&mycontrol-round=1&action=default&presenter=Test#frag', $this['mycontrol']->link('//this?x=1&round=1#frag'));
		Assert::same('/index.php?mycontrol-x=1&mycontrol-y=2&action=default&do=mycontrol-click&presenter=Test', $this->link('mycontrol:click!', array('x' => 1, 'y' => 2, 'round' => 0)));

		// Component link type checking
		Assert::same("#error: Value passed to persistent parameter 'order' in component 'mycontrol' must be array, integer given.", $this['mycontrol']->link('click', array('order' => 1)));
		Assert::same("#error: Value passed to persistent parameter 'round' in component 'mycontrol' must be integer, array given.", $this['mycontrol']->link('click', array('round' => array())));
		$this['mycontrol']->order = 1;
		Assert::same("#error: Value passed to persistent parameter 'order' in component 'mycontrol' must be array, integer given.", $this['mycontrol']->link('click'));
		$this['mycontrol']->order = NULL;

		// silent invalid link mode
		$this->invalidLinkMode = self::INVALID_LINK_SILENT;
		Assert::same('#', $this->link('product', array('var1' => NULL, 'ok' => 'a')));

		// warning invalid link mode
		$this->invalidLinkMode = self::INVALID_LINK_WARNING;
		$me = $this;
		Assert::error(function () use ($me) {
			Assert::same('#', $me->link('product', array('var1' => NULL, 'ok' => 'a')));
		}, E_USER_WARNING, "Invalid link: Value passed to persistent parameter 'ok' in presenter Test must be boolean, string given.");

		// exception invalid link mode
		$this->invalidLinkMode = self::INVALID_LINK_EXCEPTION;
		Assert::exception(function () use ($me) {
			$me->link('product', array('var1' => NULL, 'ok' => 'a'));
		}, 'Nette\Application\UI\InvalidLinkException', "Value passed to persistent parameter 'ok' in presenter Test must be boolean, string given.");

		$this->var1 = NULL; // NULL in persistent parameter means default
		Assert::same('/index.php?action=product&presenter=Test', $this->link('product'));
	}


	public function handleBuy($x = 1, $y = 1, $bool = FALSE, $str = '')
	{
	}


	public function handleArray(array $arr1, array $arr2 = array(), array $arr3 = NULL)
	{
	}


	public function handleObj(stdClass $a, stdClass $b = NULL)
	{
	}

}


class OtherPresenter extends TestPresenter
{
	/** @persistent */
	public $var1 = 20;
}


class MockPresenterFactory extends Nette\Object implements Nette\Application\IPresenterFactory
{
	function getPresenterClass(& $name)
	{
		return str_replace(':', 'Module\\', $name) . 'Presenter';
	}

	function createPresenter($name)
	{}
}


$url = new Http\UrlScript('http://localhost/index.php');
$url->setScriptPath('/index.php');

$presenter = new TestPresenter;
$presenter->injectPrimary(
	NULL,
	new MockPresenterFactory,
	new Application\Routers\SimpleRouter,
	new Http\Request($url),
	new Http\Response
);

$presenter->invalidLinkMode = TestPresenter::INVALID_LINK_WARNING;
$presenter->autoCanonicalize = FALSE;

$request = new Application\Request('Test', Http\Request::GET, array());
$presenter->run($request);
