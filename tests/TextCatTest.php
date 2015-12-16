<?php
require_once __DIR__.'/../TextCat.php';

class TextCatTest extends PHPUnit_Framework_TestCase
{
	/**
	 * TextCat instance
	 * @var TextCat
	 */
	protected $cat;

	public function setUp()
	{
		$this->cat = new TextCat(__DIR__."/../LM");
	}

	public function testCreateLM()
	{
		$lm = $this->cat->createLM("test", 1000);
		$result =
		array (
		  '_' => 2,
		  't' => 2,
		  '_t' => 1,
		  '_te' => 1,
		  '_tes' => 1,
		  '_test' => 1,
		  'e' => 1,
		  'es' => 1,
		  'est' => 1,
		  'est_' => 1,
		  's' => 1,
		  'st' => 1,
		  'st_' => 1,
		  't_' => 1,
		  'te' => 1,
		  'tes' => 1,
		  'test' => 1,
		  'test_' => 1,
		);
		$this->assertEquals($result, $lm);
	}

	public function testCreateLMLimit()
	{
		$lm = $this->cat->createLM("test", 4);
		$result =
		array (
		  '_' => 2,
		  't' => 2,
		  '_t' => 1,
		  '_te' => 1,
		);
		$this->assertEquals($result, $lm);
	}
}