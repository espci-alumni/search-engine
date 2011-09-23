<?php

class highlighter
{
    static function highlight($extrait, $highlight, $maxlen)
    {
        $maxlen > 0 || $maxlen = PHP_INT_MAX;

        ob_start();

        $len = 0;
        $glue = '';
        $space = false;
        $prev = array();

        foreach ($extrait as $item)
        {
            if (is_array($item))
            {
                list($field, $extrait) = $item;

                $space && $glue = ' ';

                $a = trim($extrait);
                if (-1 == $len || '' === $a)
                {
                    return ob_get_clean();
                }

                $a = preg_split("'([«»“”″‘’′ʿ◊[:punct:][:cntrl:][:space:]]+)'u", $a, -1, PREG_SPLIT_DELIM_CAPTURE);
                array_unshift($a, $glue);

                $rx = isset($highlight['']) ? $highlight[''] : '';
                isset($highlight[$field]) && $rx .= $highlight[$field];
                $rx = substr($rx, 1);

                $extrait = '';

                $aLen = count($a);
                for ($i = 1; $i < $aLen; $i+=2)
                {
                    $b = '' !== (string) $rx ? preg_replace("/^({$rx})/ui", '<b class="highlight">$1</b>', $a[$i]) : $a[$i];

                    if ($len || PHP_INT_MAX === $maxlen)
                    {
                        $len += strlen($a[$i-1]) + strlen($a[$i]);

                        $extrait .= htmlspecialchars($a[$i-1]) . $b;

                        if ($maxlen < $len)
                        {
                            if ($i+2 != $aLen) $extrait .= '…';

                            $len = -1;
                            break;
                        }
                    }
                    else if ($b != $a[$i])
                    {
                        if (count($prev) > 6)
                        {
                            $extrait = array_splice($prev, -6);
                            $extrait[0] = '…';
                        }
                        else
                        {
                            $extrait = $prev;
                            $prev = array();
                        }

                        $extrait = implode('', $extrait) . htmlspecialchars($a[$i-1]);

                        $len = strlen($extrait) + strlen($a[$i]);

                        $extrait .= $b;
                    }
                    else
                    {
                        $prev[] = $a[$i-1];
                        $prev[] = $b;
                    }
                }

                echo $extrait;

                $space = true;
            }
            else
            {
                $glue = $item;
                $space = false;
            }
        }

        if (0 <= $len && $len < $maxlen)
        {
            $dotOk = false;

            if (0 != $len)
            {
                $prev[0] = ' - ';
                $dotOk = true;
            }

            $prevLen = count($prev);
            for ($i = 1; $i < $prevLen; $i+=2)
            {
                $len += strlen($prev[$i-1]) + strlen($prev[$i]);

                echo htmlspecialchars($prev[$i-1]) . $prev[$i];

                if ($maxlen < $len) break;
            }

            if ($dotOk && $i > 1) echo '…';
        }

        return ob_get_clean();
    }
}
