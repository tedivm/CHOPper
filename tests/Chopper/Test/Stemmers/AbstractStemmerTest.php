<?php

namespace Chopper\Test\Stemmers;

abstract class AbstractStemmerTest extends \PHPUnit_Framework_TestCase
{
    protected $class;
    protected $language;

    public function setUp()
    {
        $class = explode('\\', get_class($this));
        $classname = array_pop($class);
        $language = substr($classname, 0, strlen($classname) - 4);

        $testClass = 'Chopper\\Stemmers\\' . $language;

        if(!class_exists($testClass))
        {
            $this->markTestSkipped($language . ' is unavailable for testing');
        }

        $this->class = $testClass;
        $this->language = $language;

    }

    public function testDictionary()
    {
        $testClass = $this->class;
        $stemmer = new $testClass();
        $dataDir = __DIR__ . '/Data/';

        $input = $dataDir . $this->language . '_input.txt';
        $output = $dataDir . $this->language . '_output.txt';

        $fin = fopen($input, 'r');
        if($fin === false)
        {
            $this->markTestIncomplete('Unable to open input file ' . $input);
        }

        $fout = fopen($output, 'r');
        if($fout === false)
        {
            $this->markTestIncomplete('Unable to open output file ' . $output);
        }

        $line = 0;
        while(($test = fgets($fin)) && ($output = fgets($fout)))
        {
            $line++;
            $result = $stemmer->stem($test);

            $this->assertEquals($output, $result,
                "{$this->language} Stemmer does not give expected results. \n" .
                "Input: $test" .
                "Output: $output" .
                "Returned: $result");
        }

        fclose($fin);
        fclose($fout);

    }
}