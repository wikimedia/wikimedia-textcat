<?php
require_once 'TextCat.php';

if($argc != 3) {
	die("Use $argv[0] INPUTDIR OUTPUTDIR\n");
}
if(!file_exists($argv[2])) {
	mkdir($argv[2], 0755, true);
}
$cat = new TextCat($argv[2]);

foreach(new DirectoryIterator($argv[1]) as $file) {
	if(!$file->isFile()) {
		continue;
	}
	$ngrams = $cat->createLM(file_get_contents($file->getPathname()));
	$out = fopen( $argv[2] . "/" . $file->getBasename(".txt") . ".lm", "w" );
	foreach($ngrams as $word => $score) {
		fwrite($out, "$word\t $score\n");
	}
	fclose($out);
}
exit(0);