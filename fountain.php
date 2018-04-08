<?php
/*
fountainToHTML v0.0

This is the core fountain-to-HTML function.

Based upon the Scrippet concept and design by John August (http://johnaugust.com),
and the WP-Fountain plugin by Nima Yousefi.
*/
?>
<?php

function transformType($matches) {

    // Create arrays for styling replacements
    $pattern = [];
    $replace = [];

    // Boneyard (deleted material); /s searches across lines
    $pattern[] = '/\/\*.*?\*\//s';
    $replace[] = '';

    // Punctuation
    // TODO: Option to enforce smart punctuation, typewriter style or neither
    $pattern[] = '/\.{3}|…/';
    $replace[] = '…';
    $pattern[] = '/(\s+-\s+|[ \t]*\-{2,3}[ \t]*|[ \t]*—[ \t]*|[ \t]*–[ \t]*)/';
    $replace[] = '—';

    // Emphasis
    // TODO: can this be smarter about badly nested tags?
    $pattern[] = '/(\*{2}|\[b\])([^\n]*?)(\*{2}|\[\/b\])/';
    $replace[] = '<strong>\2</strong>';

    $pattern[] = '/(\*{1}|\[i\])([^\n]*?)(\*{1}|\[\/i\])/';
    $replace[] = '<em>\2</em>';

    $pattern[] = '/(_|\[u\])([^\n]*?)(_|\[\/u\])/';
    $replace[] = '<u>\2</u>';

    $output = preg_replace($pattern, $replace, $matches);

    return $output;
}

function fountainParse($text) {

    //  Rules contain conditions (keys starting with 'cond').
    //  The first rule where all conditions are met determines how a given line is handled.
    //
    //  condStartsWith: starts with a given string
    //  condEndsWith: ends with a given string
    //  condFirst: first line in its block, i.e. follows a blank line
    //  condAlone: the only line in its block, i.e. blank lines before and after
    //  condUppercase: contains letters, all of which are uppercase
    //  condPattern: matches a given regex pattern
    //  condIn: true if the current block is, say, a speech
    //
    //  element: line will be rendered as the specified HTML element; defaults to <p>
    //  lineClass: class(es) to assign the line element
    //  keepMarkers: keep the condStartsWith and/or condEndsWith strings intact
    //      (e.g. 'INT.' or 'TO:'); defaults to deleting them (e.g. '@' or '^')
    //  begin: declares the surrounding block a given type; used for condIn checks
    //      (currently just 'speech' for dialogue)
    //  blockClass: the surrounding block is given this class
    //  retro: retroactively wrap this and the preceding block with a given class
    //      (used for dual dialogue)

    $Rules = [];

    //  Forced line types

    //  Forced scene heading (uses pattern to avoid matching lines starting with ...)
    $Rules[] = [
        'condPattern' => '/^\.\w/',
        'condStartsWith' => '.',
        'condAlone' => true,
        'element' => 'h2',
        'lineClass' => 'scene-heading',
    ];
    $Rules[] = [
        'condStartsWith' => '!',
        'lineClass' => 'action',
    ];
    $Rules[] = [
        'condStartsWith' => '>',
        'condEndsWith' => '<',
        'lineClass' => 'action center',
    ];
    $Rules[] = [
        'condStartsWith' => '>',
        'lineClass' => 'transition',
    ];




    //  In-character dialogue blocks (a non-standard Fountain extension)
    //  TODO: Make this optional

    //  Forced IC dialogue without '(AS CHARACTER NAME)'

    $Rules[] = [
        'condStartsWith' => '%',
        'condEndsWith' => '^',
        'element' => 'h3',
        'lineClass' => 'character role',
        'begin' => 'speech',
        'blockClass' => 'speech-ic',
        'retro' => 'dual',
    ];
    $Rules[] = [
        'condStartsWith' => '%',
        'element' => 'h3',
        'lineClass' => 'character role',
        'begin' => 'speech',
        'blockClass' => 'speech-ic',
    ];

    //  Forced character name; IC inferred from '@AnACTOR (AS CHARACTER NAME)'

    $Rules[] = [
        'condPattern' => '/^@[\w\s\'\.-]+\s\(AS\s[\w\s\'\.-]+\)\s*\^$/',
        'element' => 'h3',
        'lineClass' => 'character role',
        'begin' => 'speech',
        'blockClass' => 'speech-ic',
        'retro' => 'dual',
    ];
    $Rules[] = [
        'condPattern' => '/^@[\w\s\'\.-]+\s\(AS\s[\w\s\'\.-]+\)/',
        'element' => 'h3',
        'lineClass' => 'character role',
        'begin' => 'speech',
        'blockClass' => 'speech-ic',
    ];

    //  Inferred character name, inferred in-character

    $Rules[] = [
        'condPattern' => '/^[\w\s\'\.-]+\s\(AS\s[\w\s\'\.-]+\)\s*\^$/',
        'condUppercase' => true,
        'element' => 'h3',
        'lineClass' => 'character role',
        'begin' => 'speech',
        'blockClass' => 'speech-ic',
        'retro' => 'dual',
    ];
    $Rules[] = [
        'condPattern' => '/^[\w\s\'\.-]+\s\(AS\s[\w\s\'\.-]+\)/',
        'condUppercase' => true,
        'element' => 'h3',
        'lineClass' => 'character role',
        'begin' => 'speech',
        'blockClass' => 'speech-ic',
    ];


    $Rules[] = [
        'condStartsWith' => 'INT.',
        'condAlone' => true,
        'element' => 'h2',
        'lineClass' => 'scene-heading',
        'keepMarkers' => true,
    ];
    $Rules[] = [
        'condStartsWith' => 'EXT.',
        'condAlone' => true,
        'element' => 'h2',
        'lineClass' => 'scene-heading',
        'keepMarkers' => true,
    ];
    $Rules[] = [
        'condStartsWith' => 'I/E',
        'condAlone' => true,
        'element' => 'h2',
        'lineClass' => 'scene-heading',
        'keepMarkers' => true,
    ];

    $Rules[] = [
        'condUppercase' => true,
        'condEndsWith' => 'TO:',
        'lineClass' => 'transition',
        'keepMarkers' => true,
    ];


    //  Regular dialogue

    $Rules[] = [
        'condStartsWith' => '@',
        'condEndsWith' => '^',
        'element' => 'h3',
        'lineClass' => 'character dual-character',
        'makeClass' => true,
        'begin' => 'speech',
        'retro' => 'dual',
    ];
    $Rules[] = [
        'condStartsWith' => '@',
        'element' => 'h3',
        'lineClass' => 'character',
        'makeClass' => true,
        'begin' => 'speech',
    ];

    $Rules[] = [
        'condFirst' => true,
        'condUppercase' => true,
        'condEndsWith' => '^',
        'element' => 'h3',
        'lineClass' => 'character',
        'begin' => 'speech',
        'retro' => 'dual',
    ];
    $Rules[] = [
        'condFirst' => true,
        'condUppercase' => true,
        'element' => 'h3',
        'lineClass' => 'character',
        'begin' => 'speech',
    ];

    $Rules[] = [
        'condStartsWith' => '(',
        'condEndsWith' => ')',
        'condIn' => 'speech',
        'lineClass' => 'parenthetical',
        'keepMarkers' => true,
    ];
    $Rules[] = [
        'condIn' => 'speech',
        'lineClass' => 'dialogue',
    ];

    $Rules[] = [
        'lineClass' => 'action',
    ];


    $blocks = [];
    $lines = [];
    $isIn = [];
    

    $script = explode("\n", $text);

    //  First pass: Split the text into blocks according to blank lines

    foreach ($script as $line) {
        //  TODO: Have '  ' separate paragraphs; separate adjacent dialogue lines with <br>
        //  TODO: Also make this optional
        $line = trim($line);
        if ($line) {
            // We got content. Push it to our current block
            $lines[] = [
                'text' => $line,
            ];
        } else if ($lines) {
            // Blank line. If we've got content in our current block, push it
            $blocks[] = ['lines' => $lines];
            $lines = [];
        }
    }
    if ($lines) {
        $blocks[] = ['lines' => $lines];
    }

    //  Second pass: determine semantic elements/classes for blocks and lines

    foreach ($blocks as $i => &$block) {
        foreach ($block['lines'] as $j => &$line) {
            foreach ($Rules as $rule) {
                $match = true;
                $l = $line['text'];

                if ($rule['condAlone'])
                    $match = (count($block) === 1);
                
                if ($match && $rule['condFirst']) 
                    $match = ($j === 0);

                $c = $rule['condStartsWith'];
                if ($match && $c)
                    $match = (substr($l, 0, strlen($c)) === $c);
                
                $c = $rule['condEndsWith'];
                if ($match && $c)
                    $match = (substr($l, -strlen($c)) === $c);

                if ($match && $rule['condPattern'])
                    $match = preg_match($rule['condPattern'], $l);

                if ($match && $rule['condUppercase'])
                    $match = preg_match('/^[A-Z\s\(\),\.\'-\^]+$/', $l);

                if ($match && $rule['condIn'])
                    $match = $isIn[$rule['condIn']];
                
                if ($match) {
                    $line['element'] = $rule['element'] ? $rule['element'] : 'p';
                    $line['lineClass'] = $rule['lineClass'];

                    // If we're starting, for example, a dialogue block, remember that
                    $begin = $rule['begin'];
                    if ($begin) {
                        $isIn[$begin] = true;
                        $block['blockClass'] = $begin;
                        if ($rule['blockClass'])
                            $block['blockClass'] .= ' ' . $rule['blockClass'];
                    }

                    // Strip forcing markers at start and end of line
                    $c = $rule['condStartsWith'];
                    if ($c && !$rule['keepMarkers'])
                        $line['text'] = substr($line['text'], strlen($c));
                    
                    $c = $rule['condEndsWith'];
                    if ($c && !$rule['keepMarkers']) {
                        $line['text'] = substr($line['text'], 0, -strlen($c));
                    }

                    $retro = $rule['retro'];
                    if ($retro) {
                        if ($i) {
                            $blocks[$i-1]['beginWrap'] = $retro;
                            $block['endWrap'] = $retro;
                        }
                    }
                    break;
                }
            }
        }
        unset($line);
        $isIn = [];
    }
    unset($block);

    //  Third pass: transform array of blocks / lines into HTML tags

    $html = '';

    foreach ($blocks as $i => $block) {
        if ($block['beginWrap'])
            $html .= '<div class="' . $block['beginWrap'] . "\">\n";
        if ($block['blockClass'])
            $html .= '<div class="' . $block['blockClass'] . "\">\n";

        foreach ($block['lines'] as $line) {
            $html .= '<' . $line['element'] . ' class="' . $line['lineClass'] .'">';
            $html .= $line['text'];
            $html .= '</' . $line['element'] . ">\n";
        }

        if ($block['blockClass'])
            $html .= "</div>\n";
        if ($block['endWrap'])
            $html .= "</div>\n";
    }

    return $html;
}


function fountainToHTML($text) {

    // Find all the fountain blocks.
    // Only text between matched fountain blocks will be processed by the text replacement.
    $fountain_pattern = "/[\[<]fountain[\]>](.*?)[\[<]\/fountain[\]>]/si";
    preg_match_all($fountain_pattern, $text, $matches);

    $matches = $matches[1];             // we only need the matches of the (.*?) group

    $output = '';                       // initialize

    $num_matches = count($matches);
    if($num_matches > 0) {
        for($i=0; $i < $num_matches; $i++) {
            // Remove any HTML tags in the fountain block
            $matches[$i] = preg_replace('/<\/p>|<br(\/)?>/i', "\n", $matches[$i]);
            $matches[$i] = strip_tags($matches[$i]);

            $matches[$i] = $matches[$i] . "\n";   // this is a hack to eliminate some weirdness at the end of the fountain

            $matches = transformType($matches);

            // Regular Expression Magic!
            $output  = '<div class="fountain">' . fountainParse($matches[$i]) . '</div>';

            $text = preg_replace($fountain_pattern, $output, $text, 1);
        }
    }
    return $text;
}
