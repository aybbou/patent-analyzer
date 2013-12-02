<?php

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/src/autoload.php';

use Rayak\PatentsAnalyzer;

$patentsPath = 'C:\wamp\www\patent-scraper\fporesults';

$fpoanalyzer = new PatentsAnalyzer($patentsPath);

$fpoanalyzer->createResultFile();
