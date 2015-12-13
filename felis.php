<?php
require_once 'TextCat.php';

$options = getopt('a:d:f:t:u:l:h');
if(!empty($options['d'])) {
	$dir = $options['d'];
} else {
	$dir = getcwd();
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