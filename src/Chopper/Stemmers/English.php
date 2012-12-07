<?php

namespace Chopper\Stemmers;

/**
 * This class takes in a word and returns a stem for that word.
 *
 * This code was based off of the English Porter2 stemming algorithm.
 * Test against http://snowball.tartarus.org/algorithms/english/diffs.txt
 *
 * @link http://snowball.tartarus.org/algorithms/english/stemmer.html
 */
class English
{
    static protected $invariantForms = array( 'sky', 'news', 'howe', 'atlas', 'cosmos', 'bias', 'andes');

    static protected $exceptions = array('skis' => 'ski',
        'skies'  => 'sky',
        'dying'  => 'die',
        'lying'  => 'lie',
        'tying'  => 'tie',
        'idly'   => 'idl',
        'gently' => 'gentl',
        'ugly'   => 'ugli',
        'early'  => 'earli',
        'only'   => 'onli',
        'singly' => 'singl');

    static protected $secondLevelExceptions = array('inning',
        'outing', 'canning', 'herring','earring', 'proceed', 'exceed', 'succeed');

    static protected $segmentExceptions = array('gener', 'commun', 'arsen');

    static private $segmentCache = array();

    static protected $step1Brules = array(
        'ingly' => 2,
        'eedly' => 1,
        'edly'  => 2,
        'eed'   => 1,
        'ing'   => 2,
        'ed'    => 2);

    static protected $step2rules = array(
        'ization' => 'ize',
        'ousness' => 'ous',
        'iveness' => 'ive',
        'ational' => 'ate',
        'fulness' => 'ful',
        'tional'  => 'tion',
        'lessli'  => 'less',
        'biliti'  => 'ble',
        'entli'   => 'ent',
        'ation'   => 'ate',
        'alism'   => 'al',
        'aliti'   => 'al',
        'ousli'   => 'ous',
        'iviti'   => 'ive',
        'fulli'   => 'ful',
        'enci'    => 'ence',
        'anci'    => 'ance',
        'abli'    => 'able',
        'izer'    => 'ize',
        'ator'    => 'ate',
        'alli'    => 'al',
        'bli'     => 'ble');

    static protected $step3rules = array(
        'ational' => 'ate',
        'tional'  => 'tion',
        'ative'   => true,
        'alize'   => 'al',
        'icate'   => 'ic',
        'iciti'   => 'ic',
        'ical'    => 'ic',
        'ness'    => false,
        'ful'     => false);

    static protected $step4Tests = array(
        'ement',
        'ance',
        'ence',
        'able',
        'ible',
        'ment',
        'ant',
        'ent',
        'ism',
        'ate',
        'ion',
        'iti',
        'ous',
        'ive',
        'ize',
        'er',
        'ic',
        'al');

    /**
     * This function takes in a word (in english) and returns its stem.
     *
     * @param string $word
     * @return string
     */
    static public function stem($word)
    {
        if(strlen($word) <= 2)
            return $word;

        $word = strtolower($word);

        if($value = self::firstException($word))
            return $value;

        self::$segmentCache = array();
        $word = self::markVowels($word);
        $word = self::step0($word);
        $word = self::step1a($word);

        if($value = self::secondException($word))
        {
            $word = $value;
        }else{

            $word = self::step1b($word);
            $word = self::step1c($word);
            $word = self::step2($word);
            $word = self::step3($word);
            $word = self::step4($word);
            $word = self::step5($word);
        }
        $word = str_replace('Y', 'y', $word);
        self::$segmentCache = array();
        return $word;
    }

    static protected function firstException($word)
    {
        if(in_array($word, self::$invariantForms))
            return $word;

        if(isset(self::$exceptions[$word]))
            return self::$exceptions[$word];

        return false;
    }

    static protected function secondException($word)
    {
        if(in_array($word, self::$secondLevelExceptions))
            return $word;

        return false;
    }

    static protected function step0($word)
    {
        if(strpos($word, '\'') === 0)
            $word = substr($word, 1);

        $wordLen = strlen($word);
        $endLength = ($wordLen < 3) ? $wordLen : 3;
        $lastChar = substr($word, -$endLength);

        if(strpos($lastChar, '\'') === false)
            return $word;

        if($lastThree == '\'s\'')
        {
            $word = substr($word, 0, strlen($word) - 3);
        }elseif($endLength >= 2 && substr($word, -2) == '\'s'){
            $word = substr($word, 0, strlen($word) - 2);
        }elseif($endLength >= 1 && substr($word, -1) == '\''){
            $word = substr($word, 0, strlen($word) - 1);
        }

        return $word;
    }

    static protected function step1a($word)
    {
        $suffix = substr($word, -4);
        if($suffix == 'sses')
        {
            $word = substr($word, 0, strlen($word) - 2);
            return $word;
        }

        $suffix = substr($word, -3);
        if($suffix == 'ied' || $suffix == 'ies')
        {
            $word = substr($word, 0, strlen($word) - 2);
            if(strlen($word) <= 2)
                $word .= 'e';

            return $word;
        }

        $suffix = substr($word, -2);
        if($suffix == 'us' || $suffix == 'ss')
            return $word;

        if(substr($word, -1) == 's')
        {
            $front = substr($word, 0, strlen($word) - 2);
            if(preg_match('#[aeiouy]#', $front) !== 0)
                $word = substr($word, 0, strlen($word) - 1);
        }
        return $word;
    }

    static protected function step1b($word)
    {
        $pieces = array();

        for($i = 5; $i > 1; $i--)
        {
            if(!isset($pieces[$i]))
                $pieces[$i] = substr($word, -$i);

            if(!$pieces[$i])
                continue;

            if(isset(self::$step1Brules[$pieces[$i]]))
            {
                $method = self::$step1Brules[$pieces[$i]];
                $checkStringSize = $i;

                if($method == 1)
                {
                    $segments = self::getSegments($word);
                    $r1 = (isset($segments['r1'])) ? $segments['r1'] : '';
                    if(strpos($r1, $pieces[$i]) !== false)
                    {
                        $newWord = substr($word, 0, -$checkStringSize);
                        $newWord .= 'ee';
                        $word = $newWord;
                    }

                }elseif($method == 2){
                    $newWord = substr($word, 0, -$checkStringSize);
                    if(preg_match('#[aeiouy]#', $newWord) != 0)
                    {
                        $end = substr($newWord, -2);

                        if($end == 'at' || $end == 'bl' || $end == 'iz')
                        {
                            $newWord .= 'e';
                            return $newWord;
                        }

                        $double = array('bb', 'dd', 'ff', 'gg', 'mm', 'nn', 'pp', 'rr', 'tt');
                        if(in_array($end, $double))
                        {
                            $newWord = substr($newWord, 0, strlen($newWord) - 1);
                            return $newWord;
                        }

                        $segments = self::getSegments($newWord);
                        $r1 = (isset($segments['r1'])) ? $segments['r1'] : '';

                        if(self::isShort($newWord) && $r1 == ''){
                            $newWord .= 'e';
                            return $newWord;
                        }

                        return $newWord;
                    }
                    return $word;


                }
                return $word;
            }
        }
        return $word;
    }

    static protected function step1c($word)
    {
        $strlen = strlen($word);

        if($strlen < 3)
            return $word;

        $suffix = substr($word, -1);

        if($suffix == 'y' || $suffix == 'Y' && !(preg_match('#[aeiouy]#', substr($word, 1, strlen($word) - 1)) != 0))
            $word = substr($word, 0, $strlen - 1) . 'i';

        return $word;
    }

    static protected function step2($word)
    {
        $pieces = array();
        for($i = 7; $i > 2; $i--)
        {
            if(!isset($pieces[$i]))
                $pieces[$i] = substr($word, -$i);

            if(!$pieces[$i])
                continue;

            if(isset(self::$step2rules[$pieces[$i]]))
            {
                $replacement = self::$step2rules[$pieces[$i]];
                $suffixSize = $i;

                $segments = self::getSegments($word);
                $r1 = (isset($segments['r1'])) ? $segments['r1'] : '';

                if(strpos($r1, $pieces[$i]) === false)
                    break;

                $word = substr($word, 0, strlen($word) - $suffixSize);
                $word .= $replacement;
                return $word;
            }
        }

        if(!isset($pieces[3]))
            $pieces[3] = substr($word, -3);

        if($pieces[3] == 'ogi')
        {
            $segments = self::getSegments($word);
            $r1 = (isset($segments['r1'])) ? $segments['r1'] : '';

            if(strpos($r1, 'ogi') === false)
                return $word;

            if(substr($word, -4, 1) == 'l')
            {
                $word = substr($word, 0, strlen($word) - 3);
                $word .= 'og';
            }
            return $word;
        }

        if(!isset($pieces[2]))
            $pieces[2] = substr($word, -2);

        if($pieces[2] == 'li')
        {
            $segments = self::getSegments($word);
            $r1 = (isset($segments['r1'])) ? $segments['r1'] : '';

            if(strpos($r1, 'li') === false)
                return $word;

            $char = substr($word, -3, 1);

            if(strpos('cdeghkmnrt', $char) !== false)
                return substr($word, 0, strlen($word) - 2);
        }

        return $word;
    }

    static protected function step3($word)
    {
        $pieces = array();
        for($i = 7; $i > 2; $i--)
        {
            if(!isset($pieces[$i]))
                $pieces[$i] = substr($word, -$i);

            if(!$pieces[$i])
                continue;

            if(isset(self::$step3rules[$pieces[$i]]))
            {
                $rule = self::$step3rules[$pieces[$i]];;
                $stringLen = $i;

                $segments = self::getSegments($word);
                $r1 = (isset($segments['r1'])) ? $segments['r1'] : '';
                $r2 = (isset($segments['r2'])) ? $segments['r2'] : '';

                if(strpos($r1, $pieces[$i]) === false)
                    return $word;

                if(is_string($rule))
                {
                    $newWord = substr($word, 0, strlen($word) - $stringLen);
                    $newWord .= $rule;
                    return $newWord;
                }elseif($i == 5 && $pieces[$i] == 'ative'){
                    if(strpos($r2, 'ative') !== false)
                    {
                        $newWord = substr($word, 0, strlen($word) - $stringLen);
                        return $newWord;
                    }
                    return $word;

                }elseif($rule === false){
                    $newWord = substr($word, 0, strlen($word) - $stringLen);
                    return $newWord;
                }
                return $word;

            }
        }
        return $word;
    }

    static protected function step4($word)
    {
        $pieces = array();
        for($i = 5; $i > 1; $i--)
        {
            if(!isset($pieces[$i]))
                $pieces[$i] = substr($word, -$i);

            if(!$pieces[$i])
                continue;

            if(in_array($pieces[$i], self::$step4Tests))
            {
                $segments = self::getSegments($word);
                $r1 = (isset($segments['r1'])) ? $segments['r1'] : '';
                $r2 = (isset($segments['r2'])) ? $segments['r2'] : '';

                if($r2 != '' && strpos($r2, $pieces[$i]) !== false)
                {
                    if($pieces[$i] == 'ion')
                    {
                        $char = substr($word, -4, 1);
                        if($char == 's' || $char == 't')
                        {
                            $newWord = substr($word, 0, strlen($word) - $i);
                            return $newWord;
                        }
                        return $word;

                    }else{
                        $newWord = substr($word, 0, strlen($word) - $i);
                        return $newWord;
                    }
                }
                return $word;
            }
        }
        return $word;
    }

    static protected function step5($word)
    {
        $lastChar = substr($word, -1);
        if($lastChar == 'e')
        {
            $segments = self::getSegments($word);
            $r1 = (isset($segments['r1'])) ? $segments['r1'] : '';
            $r2 = (isset($segments['r2'])) ? $segments['r2'] : '';

            if(strpos($r2, 'e') !== false)
            {
                return substr($word, 0, strlen($word) - 1);
            }else{

                if(strpos($r1, 'e') !== false)
                {
                    $subString = substr($word, 0, strlen($word) - 1);

                    if(!self::isShort($subString))
                        return $subString;
                }
                return $word;
            }
            return $word;

        }elseif($lastChar == 'l'){

            $segments = self::getSegments($word);
            $r2 = (isset($segments['r2'])) ? $segments['r2'] : '';

            if(strpos($r2, 'l') !== false && substr($word, -2, 1) == 'l')
                return substr($word, 0, strlen($word) - 1);
        }
        return $word;
    }

    static protected function markVowels($word)
    {
        $chars = str_split($word);

        if($chars[0] == 'y')
            $chars[0] = 'Y';

        $yChars = array_keys($chars, 'y');

        foreach($yChars as $index)
            if(preg_match('#[aeiouy]#', $chars[$index - 1]) != 0)
                $chars[$index] = 'Y';

        $word = implode('', $chars);
        return $word;
    }

    static protected function getSegments($word)
    {
        if(isset(self::$segmentCache[$word]))
            return self::$segmentCache[$word];

        $realWord = $word;
        $output = array();

        foreach(self::$segmentExceptions as $exception)
        {
            if(strpos($word, $exception) === 0)
            {
                if($word === $exception)
                {
                    self::$segmentCache[$word] = array();
                    return array();
                }

                $word = substr($word, strlen($exception));
                $output['r1'] = $word;
                break;
            }
        }

        $chars = str_split($word);
        $vowel = false;
        $const = false;

        if((preg_match('#[aeiouy]#', $word) != 0))
            foreach($chars as $index => $char)
            {
                if($vowel && $const)
                {
                    $vowel = false;
                    $const = false;
                    if(!isset($output['r1']))
                    {
                        $output['r1'] = substr($word, $index);
                        $vowel = false;
                    }else{
                        $output['r2'] = substr($word, $index);
                        break;
                    }
                }

                switch ($char)
                {
                    case 'a':
                    case 'e':
                    case 'i':
                    case 'o':
                    case 'u':
                    case 'y':
                        $vowel = true;
                        break;
                    default:
                        if($vowel)
                            $const = true;
                        break;
                }
            }

        self::$segmentCache[$realWord] = $output;
        return $output;
    }

    static protected function isShort($word)
    {
        $searchString = "#[aeiouy]#";
        $sortString = str_split($word);
        $sortString = array_reverse($sortString);

        // Remember we're testing in reverse! $sortArray[0] is the last charactor.
        if( !(preg_match('#[aeiouywxY]#', $sortString[0]) != 0)
            && (preg_match($searchString, $sortString[1]) != 0)
            && !(preg_match($searchString, $sortString[2]) != 0))
        {
            return true;

        }elseif( !isset($sortString[2])
            &&! (preg_match($searchString, $sortString[0]) != 0)
            && (preg_match($searchString, $sortString[1]) != 0))
        {
            return true;
        }

        return false;

    }
}

?>