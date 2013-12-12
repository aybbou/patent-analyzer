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
        'international classes',
        'assignee'
    );

    public function __construct($filesPath = null) {
        $this->filesPath = $filesPath;
    }

    private function getLocation($locationText) {
        $result = '<location>';
        $location = explode(',', trim(str_replace(')', '', $locationText)));

        switch (count($location)) {
            case 0:
                $result.='<country></country>';
            case 1:
                $result.= '<country>' . $location[0] . '</country>';
                break;
            case 2:
                $result.= '<city>' . $location[0] . '</city>';
                $result.= '<country>' . $location[1] . '</country>';
                break;
            case 3:
                $result.='<city>' . $location[0] . '</city>';
                $result.='<state>' . trim($location[1]) . '</state>';
                $result.='<country>' . trim($location[2]) . '</country>';
                break;
        }

        $result .= '</location>';
        return $result;
    }

    private function getInventors($inventors) {
        $inventors = explode("\n", $inventors);
        $result = '';
        foreach ($inventors as $inventor) {
            $result.='<inventor>';
            $inventor = explode('(', $inventor);
            $result.='<name>' . trim($inventor[0]) . '</name>';
            if (count($inventor) > 1) {
                $result.= $this->getLocation($inventor[1]);
            }
            $result.='</inventor>';
        }
        return $result;
    }

    private function getAssignees($assignees) {
        $assignees = explode("\n", $assignees);
        $result = '';
        foreach ($assignees as $assignee) {
            $result.='<assignee>';
            $assignee = explode('(', $assignee);
            $result.='<name>' . htmlspecialchars(trim($assignee[0])) . '</name>';
            if (count($assignee) > 1) {
                $result.=$this->getLocation($assignee[count($assignee) - 1]);
            }
            $result.='</assignee>';
        }
        return $result;
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
                if (isset($fileContents[$field])) {
                    $tag = str_replace(' ', '_', $field);
                    if ($field == 'inventors') {
                        $fileContents[$field] = $this->getInventors($fileContents[$field]);
                    } else if ($field == 'assignee') {
                        $fileContents[$field] = $this->getAssignees($fileContents[$field]);
                    } else {
                        $fileContents[$field] = htmlspecialchars($fileContents[$field]);
                    }
                    if ($tag == 'assignee') {
                        $tag = 'assignees';
                    }
                    fputs($resultFile, '<' . $tag . '>');
                    fputs($resultFile, $fileContents[$field]);
                    fputs($resultFile, '</' . $tag . '>' . "\n");
                }
            }
            fputs($resultFile, '<id>' . $c . '</id>');
            fputs($resultFile, "</patent>\n");

            $fileName = $file->getRelativePathname();
            echo "Analizing $fileName...\n";
            $c++;
        }

        fputs($resultFile, "\n</patents>");
        fclose($resultFile);
    }

}
