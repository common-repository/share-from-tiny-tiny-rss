<?php
/*
    Share from Tiny Tiny RSS - Automatically create a Wordpress post of items
    you marked as "published" in Tiny Tiny RSS

    Copyright (C) 2013 KJ Coop

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('DEBUG', true);

// Printing functions
// ------------------
/**
 * Print any variable
 *
 * @param var any the variable to print
 * @param string string to describe the string being printed; often the variable's name.
 * @param pre Boolean preformat everything, useful for large strings with newlines.
 * @param color string if you're printing many large objects in sequence, you can turn them different colors
 * @return void
*/
function print_object($var, $string='', $pre=false, $color='')
{
    if (DEBUG) {
        echo "\n<p>";

        $open  = '';
        $close = '';

        // If coloration has been specified, add that
        if ($color != '') {

            // If we forget the pound sign, tack it on
            if ($color{0} != '#')
                $color = '#'.$color;

            $open  = '<font color="'.$color.'">';
            $close = '</font>';
        }

        // No need to put pre tags around single-line data (strings, floats, etc) unless requested.
        if (($pre) || (is_array($var)) || (is_object($var)))
        {
            $open .=  '<pre>';
            $close = '</pre>'.$close."\n\n";        // For proper nesting.
        }

        echo $open;
        if (is_bool($var)) {
            print_bool($var, $string);
        } else {
            if ($string != '') {
                echo $string.': ';
            }

            print_r($var);
        }
        echo $close.'</p>';
    }
}

// Compare variables. Handy for debugging if statements
// Interlace variable and text.
function var_compare() {

    $args = func_get_args();

    echo "<table border=\"1\">\n";

    // Create 3 rows
    for ($i = 0; $i < 3; $i++) {
        // Names of things
        echo "\t<tr>\n";

        // Print each variable's name/value/type
        for ($j = 0; $j < (count($args)+1); $j += 2) {

            echo "\t\t<td>";
            switch ($i) {
                // Top row
                case 0:
                    // Print the title for the first box. For the next one, when $j = 2,
                    // we want to print the first variable, array[0], or array[$j-2]. Its
                    // title is the one after that, array[1] or array[$j-1]
                    phraseToPrint($j, '&nbsp;', $args[$j-1]);
                break;
                case 1:
                    phraseToPrint($j, 'Value', $args[$j-2]);
                break;
                case 2:
                    // Echoing var_dump is trouble.
                    if ($j == 0)
                        echo 'var_dump';
                    else {
                        var_dump($args[$j-2]);
                    }
                break;
            }
            echo "\t</td>\n";
        }
        echo "\t\t</tr>\n";
    }
    echo "</table>\n";
}

function print_everything($title = '') {
    if ($title != '')
        echo '<p>'.$title.'</p>';

    print_object($_COOKIE, 'Cookie information', NULL, '#0000ff');
    print_object($_SESSION, 'Session information', NULL, '#9933CC');
    print_object($_GET, 'Get information', NULL, '#ff0000');
    print_object($_POST, 'Post information', NULL, '#339933');
}

// Print the details of a boolean variable
function print_bool($var, $title='The variable', $echo = true, $color = '') {

    if ($var === true) {
        $str = '<p>'.$title.' is explicitly true';
    } elseif ($var === false) {
        $str = '<p>'.$title.' is explicitly false';
    } elseif ($var == true) {
        $str = '<p>'.$title.' is casually false';
    } elseif ($var == false) {
        $str = '<p>'.$title.' is casually true';
    } else {
        $str = '<p>'.$title.' is neither true nor false. How did you do that?</p>';
    }

    // Add color if requested
    if ($color != '')
        $str = '<font color="'.$color.'">'.$str.'</font>';

    if ($echo)
        echo '<p>'.$str.'</p>';
    else
        return $str;
}

// var_compare up there uses this to make the case statement
// slightly more compact
function phraseToPrint($count, $title, $value) {
    if ($count == 0)
        echo $title;
    else
        echo $value;
}

// Comparing an expected boolean value to other potential "boolean" values
function compare_bool ($var, $title = 'Title') {
    var_compare($var, $title, 0, 'zero', 1, 'one', true, 'true', false, 'false');
}

function print_timestamp($timestamp, $text='') {
    echo '<p>';
    echo $text.' as a timestamp: '.$timestamp.'<br>';
    echo $text.' as a date/time: '.date('Y-m-d h:i:s', $timestamp);
    echo '</p>';
}

// Shorthand for colorizing printed text
function print_color($color, $string) {
    echo '<font color="'.$color.'">'.$string.'</font><br />';

}
