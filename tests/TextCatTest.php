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
		$this->testcat = new TextCat(__DIR__."/data/Models");
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

	public function getTexts()
	{
		$indir = __DIR__."/data/ShortTexts";
		$outdir = __DIR__."/data/Models";
		$data = array();
		foreach(new DirectoryIterator($indir) as $file) {
			if(!$file->isFile() || $file->getExtension() != "txt") {
				continue;
			}
			$data[] = array($file->getPathname(), $outdir . "/" . $file->getBasename(".txt") . ".lm");
		}
		return $data;
	}

	/**
	 * @dataProvider getTexts
	 * @param string $text
	 * @param string $lm
	 */
	public function testCreateFromTexts($textFile, $lmFile)
	{
		include $lmFile;
		$this->assertEquals(
				$ngrams,
				$this->cat->createLM(file_get_contents($textFile), 4000)
		);
	}

	/**
	 * @dataProvider getTexts
	 * @param string $text
	 * @param string $lm
	 */
	public function testFileLines($textFile)
	{
		$lines = file($textFile);
		$line = 5;
		do {
			$testLine = trim($lines[$line]);
			$line++;
		} while(empty($testLine));
		$detect = $this->testcat->classify($testLine);
		reset($detect);
		$this->assertEquals(basename($textFile, ".txt"), key($detect));
	}
}
