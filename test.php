<?php
include('diparser.class.php');
$dp =new DIParser();

// init load test
$start = microtime(true);
$dp->load('phonenum.database.txt'); // load($file = '', $cached=False, $setinfo=False)
$end = microtime(true);
$time=$end-$start;
echo "database load time:".$time."s<br/>";

// no hit test
$sec = $dp->getSection('beijingshi');
$start = microtime(true);
var_dump($sec->has('1386596')); // return true or false
$end = microtime(true);
$time=$end-$start;
echo "1386596 time:".$time."s<br/>";

//hit test
$start = microtime(true);
var_dump($sec->has('1800137')); // return true or false
$end = microtime(true);
$time=$end-$start;
echo "1800137 time:".$time."s<br/>";

//spend time test
$start = microtime(true);
for ($i=1380000; $i<1389999; $i++)
{
	// echo sprintf("%s:%d<br/>",$i,$sec->has($i));
}
$end = microtime(true);
$time=$end-$start;
echo "has 1380000-1389999 time:".$time."s<br/>";