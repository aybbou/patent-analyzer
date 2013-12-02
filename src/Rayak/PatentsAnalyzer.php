<?php

namespace Rayak;

use Symfony\Component\Finder\Finder;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Ayyoub
 */
class PatentsAnalyzer {

    private $filesPath;
    static private $fields = array(
        'title',
        'abstract',
        'inventors',
        'publication date',
        'filing date',
        'international classes'
    );

    public function __construct($filesPath = null) {
        $this->filesPath = $filesPath;
    }

    private function getFileContents($content) {
        $crawler = new Crawler($content);

        $fileContents = array('title' => 'test');

        $crawler->filter('.disp_doc2 .disp_elm_title')->each(function(Crawler $element) use (&$fileContents) {
            $content = trim($element->siblings()->filter('.disp_elm_text')->text());
            $field = strtolower(trim($element->text()));
            $field = str_replace(':', '', $field);

            if (in_array($field, self::$fields)) {
                $fileContents[$field] = $content;
            }
        });

        return $fileContents;
    }

    public function createResultFile() {

        $finder = new Finder();
        $finder->files()->in($this->filesPath);

        $resultFile = fopen('result.xml', 'w');
        fputs($resultFile, "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
        fputs($resultFile, "<patents>\n");

        $c = 1;

        foreach ($finder as $file) {
            $filePath = $file->getRealpath();

            $content = file_get_contents($filePath);

            $fileContents = $this->getFileContents($content);
            fputs($resultFile, '<patent>');
            foreach (self::$fields as $field) {
                $tag = str_replace(' ', '_', $field);
                fputs($resultFile, '<' . $tag . '>');
                fputs($resultFile, $fileContents[$field]);
                fputs($resultFile, '</' . $tag . '>' . "\n");
            }

            fputs($resultFile, "</patent>\n");

            $fileName = $file->getRelativePathname();
            echo "traitement du fichier $fileName\n";
            $c++;
            if ($c == 10) {
                break;
            }
        }

        fputs($resultFile, "\n</patents>");
        fclose($resultFile);
    }

}
