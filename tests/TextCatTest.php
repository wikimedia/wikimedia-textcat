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
		// initialze testcat with a string, and multicats with arrays
		$this->testcat = new TextCat( __DIR__."/data/Models" );
		$this->multicat1 = new TextCat( array(__DIR__."/../LM", __DIR__."/../LM-query" ) );
		$this->multicat2 = new TextCat( array(__DIR__."/../LM-query", __DIR__."/../LM" ) );
	}

	public function testCreateLM()
	{
		$lm = $this->testcat->createLM( "test", 1000 );
		$result =
		array(
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
		$this->assertEquals( $result, $lm );
	}

	public function testCreateLMLimit()
	{
		$lm = $this->testcat->createLM( "test", 4 );
		$result =
		array(
		  '_' => 2,
		  't' => 2,
		  '_t' => 1,
		  '_te' => 1,
		);
		$this->assertEquals( $result, $lm );
	}

	public function getTexts()
	{
		$indir = __DIR__."/data/ShortTexts";
		$outdir = __DIR__."/data/Models";
		$data = array();
		foreach( new DirectoryIterator( $indir ) as $file ) {
			if ( !$file->isFile() || $file->getExtension() != "txt" ) {
				continue;
			}
			$data[] = array( $file->getPathname(), $outdir . "/" . $file->getBasename(".txt") . ".lm" );
		}
		return $data;
	}

	/**
	 * @dataProvider getTexts
	 * @param string $text
	 * @param string $lm
	 */
	public function testCreateFromTexts( $textFile, $lmFile )
	{
		include $lmFile;
		$this->assertEquals(
				$ngrams,
				$this->testcat->createLM( file_get_contents( $textFile ), 4000)
		);
	}

	/**
	 * @dataProvider getTexts
	 * @param string $text
	 * @param string $lm
	 */
	public function testFileLines( $textFile )
	{
		$lines = file( $textFile );
		$line = 5;
		do {
			$testLine = trim( $lines[$line] );
			$line++;
		} while( empty( $testLine ) );
		$detect = $this->testcat->classify( $testLine );
		reset( $detect );
		$this->assertEquals( basename( $textFile, ".txt" ), key( $detect ) );
	}

    public function multiCatData()
    {
        return array(
          array('this is english text français bisschen',
				array('sco', 'en', 'fr',  'de' ),
				array('fr',  'de', 'sco', 'en' ), ),
          array('الاسم العلمي: Felis catu',
				array('ar', 'la', 'fa', 'fr' ),
				array('ar', 'fr', 'la', 'fa' ), ),
          array('Кошка, или домашняя кошка A macska más néven házi macska',
				array('ru', 'uk', 'hu', 'fi' ),
				array('hu', 'ru', 'uk', 'fi' ), ),
          array('Il gatto domestico Kucing disebut juga kucing domestik',
				array('id', 'it', 'pt', 'es' ),
				array('it', 'id', 'es', 'pt' ), ),
          array('Domaća mačka Pisică de casă Hejma kato',
				array('hr', 'ro', 'eo', 'cs' ),
				array('hr', 'cs', 'ro', 'eo' ), ),
        );
    }

    /**
     * @dataProvider multiCatData
	 * @param string $testLine
	 * @param array $res1
	 * @param array $res2
     */
    public function testMultiCat( $testLine, $res1, $res2 )
    {
        $this->assertEquals( array_keys( $this->multicat1->classify( $testLine, $res1 ) ),
							 array_values( $res1 ) );
        $this->assertEquals( array_keys( $this->multicat2->classify( $testLine, $res2 ) ),
							 array_values( $res2 ) );
    }

    public function minInputLengthData()
    {
        return array(
          array( 'eso es español',
				array( 'spanish', 'catalan', 'portuguese' ), null, ),
          array( 'this is english',
				array( 'english', 'german' ), null, ),
          array( 'c\'est français',
				array( 'french', 'portuguese', 'romanian', 'catalan' ), null, ),
          // numbers and spaces get stripped, so result should be an empty array
          // regardless of min input length
          array( '56 8 49564     83 9',
				array( 'french', 'portuguese', 'romanian', 'catalan' ), array(), ),
        );
    }

    /**
     * @dataProvider minInputLengthData
	 * @param string $testLine
	 * @param array $lang
	 * @param array $res
     */
    public function testMinInputLength( $testLine, $lang, $res )
    {
		if ( !isset( $res ) ) {
			$res = $lang;
		}
		# should get results when min input len is 0
		$minLength = $this->testcat->setMinInputLength(0);
		$this->assertEquals( array_keys( $this->testcat->classify( $testLine, $res ) ),
							 array_values( $res ) );
        # should get no results when min input len is more than the length of the string
        $minLength = $this->testcat->setMinInputLength(mb_strlen($testLine) + 1);
        $this->assertEquals( array_keys( $this->testcat->classify( $testLine, $res ) ),
                             array() );
    }
}
