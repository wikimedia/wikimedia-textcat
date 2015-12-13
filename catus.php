<?php
require_once 'TextCat.php';

$options = getopt('a:d:f:t:u:l:h');

if(isset($options['h'])) {
	$help = <<<HELP
{$argv[0]} [-d Dir] [-a Int] [-f Int] [-l Text] [-t Int] [-u Float]

    -a NUM  the program returns the best-scoring language together
            with all languages which are N times worse (see option -u).
            If the number of languages to be printed is larger than the value
            of this option then no language is returned, but
            instead a message that the input is of an unknown language is
            printed. Default: 10.
    -d DIR  indicates in which directory the language models are
            located (files ending in .lm). Currently only a single
            directory is supported. Default: current directory.
    -f NUM  Before sorting is performed the Ngrams which occur this number
            of times or less are removed. This can be used to speed up
            the program for longer inputs. For short inputs you should use
            the default or -f 0. Default: 0.
    -l TEXT indicates that input is given as an argument on the command line,
            e.g. {$argv[0]} -l "this is english text"
            If this option is not given, the input is stdin.
    -t NUM  indicates the topmost number of ngrams that should be used.
            If used in combination with -n this determines the size of the
            output. If used with categorization this determines
            the number of ngrams that are compared with each of the language
            models (but each of those models is used completely).
    -u NUM  determines how much worse result must be in order not to be
            mentioned as an alternative. Typical value: 1.05 or 1.1.
            Default: 1.05.

HELP;
	echo $help;
	exit(0);
}

if(!empty($options['d'])) {
	$dir = $options['d'];
} else {
	$dir = dirname(__FILE__)."/LM";
}

$cat = new TextCat($dir);

if(!empty($options['t'])) {
	$cat->setMaxNgrams(intval($options['t']));
}
if(!empty($options['f'])) {
	$cat->setMinFreq(intval($options['f']));
}

$input = isset($options['l']) ? $options['l'] : file_get_contents("php://stdin");
$result = $cat->classify($input);

if(empty($result)) {
	echo "No match found.\n";
	exit(1);
}

if(!empty($options['u'])) {
	$max = reset($result) * $options['u'];
} else {
	$max = reset($result) * 1.05;
}

if(!empty($options['a'])) {
	$top = $options['a'];
} else {
	$top = 10;
}
$result = array_filter($result, function ($res) use($max) { return $res < $max; });
if($result && count($result) <= $top) {
	echo join(" or ", array_keys($result)) . "\n";
	exit(0);
} else {
	echo "Can not determine language.\n";
	exit(1);
}