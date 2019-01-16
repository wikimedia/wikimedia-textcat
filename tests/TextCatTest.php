<?php

class TextCatTest extends PHPUnit\Framework\TestCase {
	/**
	 * TextCat instance
	 * @var TextCat
	 */
	protected $cat;

	public function setUp() {
		// initialize testcat with a string
		$this->testcat = new TextCat( __DIR__ . "/data/Models" );

		// initialize multicats with multi-element arrays
		$this->multicat1 = new TextCat( [ __DIR__ . "/../LM", __DIR__ . "/../LM-query" ] );
		$this->multicat2 = new TextCat( [ __DIR__ . "/../LM-query", __DIR__ . "/../LM" ] );

		// effectively disable RR-based filtering for these cats
		$this->multicat1->setResultsRatio( 100 );
		$this->multicat2->setResultsRatio( 100 );

		// initialize ambiguouscat with a one-element array
		$this->ambiguouscat = new TextCat( [ __DIR__ . "/../LM-query" ] );

		// initialize wrongcat with LM models
		$this->wrongcat = new TextCat( [ __DIR__ . "/../LM" ] );
	}

	public function testCreateLM() {
		$lm = $this->testcat->createLM( "test", 1000 );
		$result =
		[
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
		];
		$this->assertEquals( $result, $lm );
	}

	public function testCreateLMLimit() {
		$lm = $this->testcat->createLM( "test", 4 );
		$result =
		[
		  '_' => 2,
		  't' => 2,
		  '_t' => 1,
		  '_te' => 1,
		];
		$this->assertEquals( $result, $lm );
	}

	public function getTexts() {
		$indir = __DIR__ . "/data/ShortTexts";
		$outdir = __DIR__ . "/data/Models";
		$data = [];
		foreach ( new DirectoryIterator( $indir ) as $file ) {
			if ( !$file->isFile() || $file->getExtension() != "txt" ) {
				continue;
			}
			$data[] = [ $file->getPathname(), $outdir . "/" . $file->getBasename( ".txt" ) . ".lm" ];
		}
		return $data;
	}

	/**
	 * @dataProvider getTexts
	 * @param string $textFile
	 * @param string $lmFile
	 */
	public function testCreateFromTexts( $textFile, $lmFile ) {
		include $lmFile;
		$this->assertEquals(
				$ngrams,
				$this->testcat->createLM( file_get_contents( $textFile ), 4000 )
		);
	}

	/**
	 * @dataProvider getTexts
	 * @param string $textFile
	 */
	public function testFileLines( $textFile ) {
		$lines = file( $textFile );
		$line = 5;
		do {
			$testLine = trim( $lines[$line] );
			$line++;
		} while ( empty( $testLine ) );
		$detect = $this->testcat->classify( $testLine );
		reset( $detect );
		$this->assertEquals( basename( $textFile, ".txt" ), key( $detect ) );
	}

	public function multiCatData() {
		return [
		  [ 'this is english text français bisschen',
				[ 'sco', 'en', 'fr',  'de' ],
				[ 'fr',  'de', 'sco', 'en' ], ],
		  [ 'الاسم العلمي: Felis catu',
				[ 'ar', 'la', 'fa', 'fr' ],
				[ 'ar', 'fr', 'la', 'fa' ], ],
		  [ 'Кошка, или домашняя кошка A macska más néven házi macska',
				[ 'ru', 'uk', 'hu', 'fi' ],
				[ 'hu', 'ru', 'uk', 'fi' ], ],
		  [ 'Il gatto domestico Kucing disebut juga kucing domestik',
				[ 'id', 'it', 'pt', 'es' ],
				[ 'it', 'id', 'es', 'pt' ], ],
		  [ 'Domaća mačka Pisică de casă Hejma kato',
				[ 'hr', 'ro', 'eo', 'cs' ],
				[ 'hr', 'cs', 'ro', 'eo' ], ],
		];
	}

	/**
	 * @dataProvider multiCatData
	 * @param string $testLine
	 * @param array $res1
	 * @param array $res2
	 */
	public function testMultiCat( $testLine, $res1, $res2 ) {
		$this->assertEquals( array_keys( $this->multicat1->classify( $testLine, $res1 ) ),
							 array_values( $res1 ) );
		$this->assertEquals( array_keys( $this->multicat2->classify( $testLine, $res2 ) ),
							 array_values( $res2 ) );
	}

	public function minInputLengthData() {
		return [
		  [ 'eso es español',
				[ 'spanish', 'catalan', 'portuguese' ], null, ],
		  [ 'this is english',
				[ 'english', 'german' ], null, ],
		  [ 'c\'est français',
				[ 'french', 'portuguese', 'romanian', 'catalan' ], null, ],
		  // numbers and spaces get stripped, so result should be an empty array
		  // regardless of min input length
		  [ '56 8 49564	 83 9',
				[ 'french', 'portuguese', 'romanian', 'catalan' ], [], ],
		];
	}

	/**
	 * @dataProvider minInputLengthData
	 * @param string $testLine
	 * @param array $lang
	 * @param array $res
	 */
	public function testMinInputLength( $testLine, $lang, $res ) {
		if ( !isset( $res ) ) {
			$res = $lang;
		}

		// disable RR-based filtering
		$this->testcat->setResultsRatio( 100 );

		// should get results when min input len is 0
		$this->testcat->setMinInputLength( 0 );
		$this->assertEquals( array_keys( $this->testcat->classify( $testLine, $res ) ),
							 array_values( $res ) );
		if ( !empty( $res ) ) {
			$this->assertEquals( $this->testcat->getResultStatus(), '' );
		}

		// should get no results when min input len is more than the length of the string
		$this->testcat->setMinInputLength( mb_strlen( $testLine ) + 1 );
		$this->assertEquals( array_keys( $this->testcat->classify( $testLine, $res ) ),
							 [] );
		$this->assertEquals( $this->testcat->getResultStatus(), TextCat::STATUSTOOSHORT );

		// reset to defaults
		$this->testcat->setMinInputLength( 0 );
		$this->testcat->setResultsRatio( 1.05 );
	}

	public function ambiguityData() {
		return [
		  // check effects of results ratio and max returned langs
		  [ 'espanol português', 1.05, 10, 3000, 1.00, [ 'pt' ], '' ],
		  [ 'espanol português', 1.20, 10, 3000, 1.00, [ 'pt', 'es' ], '' ],
		  [ 'espanol português', 1.20,  2, 3000, 1.00, [ 'pt', 'es' ], '' ],
		  [ 'espanol português', 1.20,  1, 3000, 1.00, [], TextCat::STATUSAMBIGUOUS ],
		  [ 'espanol português', 1.30, 10, 3000, 1.00,
				[ 'pt', 'es', 'fr', 'it', 'en', 'pl' ], '' ],
		  [ 'espanol português', 1.30,  6, 3000, 1.00,
				 [ 'pt', 'es', 'fr', 'it', 'en', 'pl' ], '' ],
		  [ 'espanol português', 1.30,  5, 3000, 1.00, [], TextCat::STATUSAMBIGUOUS ],

		  // check effects of model size
		  [ 'espanol português', 1.10, 20,  500, 1.00,
				 [ 'pt', 'es', 'it', 'fr', 'pl', 'cs', 'en', 'sv', 'de', 'id', 'nl' ], '' ],
		  [ 'espanol português', 1.10, 20,  700, 1.00,
				 [ 'pt', 'es', 'it', 'fr', 'en', 'de' ], '' ],
		  [ 'espanol português', 1.10, 20, 1000, 1.00, [ 'pt', 'es', 'it', 'fr' ], '' ],
		  [ 'espanol português', 1.10, 20, 2000, 1.00, [ 'pt', 'es' ], '' ],
		  [ 'espanol português', 1.10, 20, 3000, 1.00, [ 'pt' ], '' ],

		  // check effect of max proportion
		  [ 'espanol português', 1.50, 20, 3000, 1.00,
				 [ 'pt', 'es', 'fr', 'it', 'en', 'pl', 'de',
						'tr', 'sv', 'cs', 'nl', 'id', 'vi' ],
				 '' ],
		  [ 'espanol português', 1.50, 20, 3000, 0.56,
				 [ 'pt', 'es', 'fr', 'it', 'en', 'pl', 'de', 'tr', 'sv' ], '' ],
		  [ 'espanol português', 1.50, 20, 3000, 0.55,
				 [ 'pt', 'es', 'fr', 'it', 'en', 'pl' ], '' ],
		  [ 'espanol português', 1.50, 20, 3000, 0.54, [ 'pt', 'es', 'fr', 'it' ], '' ],
		  [ 'espanol português', 1.50, 20, 3000, 0.52, [ 'pt', 'es', 'fr' ], '' ],
		  [ 'espanol português', 1.50, 20, 3000, 0.51, [ 'pt', 'es' ], '' ],
		  [ 'espanol português', 1.50, 20, 3000, 0.45, [ 'pt' ], '' ],
		  [ 'espanol português', 1.50, 20, 3000, 0.40, [], TextCat::STATUSNOMATCH ],

		  // max proportion vs junk
		  [ 'qqqaaagggsggsggssssssshshshssss', 1.05, 10, 3000, 1.00,
			[ 'de', 'vi', 'sv', 'en', 'nl', 'it', 'id', 'fr' ], '' ],
		  [ 'qqqaaagggsggsggssssssshshshssss', 1.05, 10, 3000, 0.80,
			[], TextCat::STATUSNOMATCH ],

		  // test larger models
		  [ 'espanol português', 1.50, 20, 5000, 1.00,
				 [ 'pt', 'es', 'fr', 'it', 'tr', 'de', 'pl', 'en', 'sv' ], '' ],
		  [ 'espanol português', 1.50, 20, 10000, 1.00,
				 [ 'pt', 'es', 'fr' ], '' ],
		];
	}

	/**
	 * @dataProvider ambiguityData
	 * @param string $testLine
	 * @param float $resRatio
	 * @param int $maxRetLang
	 * @param int $modelSize
	 * @param float $maxProportion
	 * @param array $results
	 * @param string $errMsg
	 */
	public function testAmbiguity( $testLine, $resRatio, $maxRetLang, $modelSize, $maxProportion,
								   $results, $errMsg ) {
		$this->ambiguouscat->setMaxNgrams( $modelSize );
		$this->ambiguouscat->setResultsRatio( $resRatio );
		$this->ambiguouscat->setMaxReturnedLanguages( $maxRetLang );
		$this->ambiguouscat->setMaxProportion( $maxProportion );

		$this->assertEquals( array_keys( $this->ambiguouscat->classify( $testLine ) ),
							 array_values( $results ) );
		$this->assertEquals( $this->ambiguouscat->getResultStatus(), $errMsg );
	}

	public function boostedLangData() {
		return [
		  [ 'this is english text français bisschen',
				[ 'sco', 'en', 'fr', 'de' ],
				[ 'en', 'sco', 'fr', 'de' ],
				[ [ 'en' ], 0.01 ] ],

		  [ 'this is english text français bisschen',
				[ 'sco', 'en', 'fr', 'de' ],
				[ 'en', 'sco', 'de', 'fr' ],
				[ [ 'en', 'de' ], 0.01 ] ],

		  [ 'this is english text français bisschen',
				[ 'sco', 'en', 'fr',  'de' ],
				[ 'en',  'de', 'sco', 'fr' ],
				[ [ 'en', 'de' ], 0.02 ] ],

		  [ 'this is english text français bisschen',
				[ 'sco', 'en', 'fr',  'de' ],
				[ 'en',  'fr', 'sco', 'de' ],
				[ [ 'en', 'fr' ], 0.02 ] ],

		  [ 'this is english text français bisschen',
				[ 'sco', 'en', 'fr', 'de' ],
				[ 'fr', 'sco', 'en', 'de' ],
				[ [ 'fr' ], 0.10 ] ],

		];
	}

	/**
	 * @dataProvider boostedLangData
	 * @param string $testLine
	 * @param array $res1
	 * @param array $res2
	 * @param array $boost
	 */
	public function testBoostedLangs( $testLine, $res1, $res2, $boost ) {
		$this->multicat1->setBoostedLangs( [] );
		$this->multicat1->setLangBoostScore( 0.0 );
		$this->assertEquals( array_keys( $this->multicat1->classify( $testLine, $res1 ) ),
							 array_values( $res1 ) );

		$this->multicat1->setBoostedLangs( $boost[0] );
		$this->multicat1->setLangBoostScore( $boost[1] );
		$this->assertEquals( array_keys( $this->multicat1->classify( $testLine, $res1 ) ),
							 array_values( $res2 ) );

		$this->multicat1->setBoostedLangs( [] );
		$this->multicat1->setLangBoostScore( 0.0 );
	}

	public function testNoMatch() {
		# no xxx.lm model exists, so get no match
		$this->assertEquals( array_keys( $this->testcat->classify( "a string", [ "xxx" ] ) ),
							 [] );
		$this->assertEquals( $this->testcat->getResultStatus(), TextCat::STATUSNOMATCH );
	}

	public function testWordSep() {
		$this->testcat->setResultsRatio( 1.25 );
		$this->testcat->setMaxReturnedLanguages( 20 );
		$normalResults = $this->testcat->classify( "espanol português" );
		$weirdResults = $this->testcat->classify( "sp nol português" );

		// this is a non-sensical set of word separators, just for testing
		$this->testcat->setWordSeparator( 'a-e\s' );
		$this->assertNotEquals( $this->testcat->classify( "espanol português" ), $normalResults );
		$this->assertEquals( $this->testcat->classify( "espanol português" ), $weirdResults );
	}

	public function wrongData() {
		return [
		  // test wrong-keyboard input
		  [ 'пукьфт сгшышту', [ 'en_cyr' ], '' ],
		  [ '\'qatktdf ,fiyz', [ 'ru_lat' ], '' ],

		  // test wrong-encoding input
		  [ 'РњРѕСЃРєРІР°', [ 'ru_win1251' ], '' ],
		];
	}

	/**
	 * @dataProvider wrongData
	 * @param array $results
	 * @param string $errMsg
	 */
	public function testWrongThings( $testLine, $results, $errMsg ) {
		$this->wrongcat->setMaxNgrams( 6000 );
		$this->wrongcat->setResultsRatio( 1.02 );
		$this->wrongcat->setMaxReturnedLanguages( 5 );
		$this->wrongcat->setMaxProportion( 0.85 );

		$this->assertEquals( array_keys( $this->wrongcat->classify( $testLine ) ),
							 array_values( $results ) );
		$this->assertEquals( $this->wrongcat->getResultStatus(), $errMsg );
	}

}
