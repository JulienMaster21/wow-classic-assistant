<?php

namespace App\Service;

class UnitConverter {

    /**
     * This method converts an integer into the equivalent money span string
     * Negative values will be converted to positive
     * @param $int 'the amount to be converted'
     * @return string
     */
    public function convertIntToMoneyAmount($int) {

        if (gettype($int) !== 'integer') {
            $result = 'The argument provided isn\'t an integer.';
        }
        else {
            $result = $this->determineMoneyString(abs($int));
        }

        return $result;
    }

    /**
     * Construct the money string from the given integer
     * @param $int
     * @return string
     */
    public function determineMoneyString(int $int) {

        $result = '';

        // Add gold coins to result
        if ($int >= 10000) {
            $gold = substr(strval($int), 0, -4);
            $result .= $gold . '<span class="money money-gold ml-1 mr-2"></span>';
        }

        // Add silver coins to result
        if ($int >= 100) {
            if (strlen(strval($int)) > 3) {
                $silver = substr(strval($int), -4, -2);
            }
            else {
                $silver = substr(strval($int), -3, -2);
            }
            $result .= $silver . '<span class="money money-silver ml-1 mr-2"></span>';
        }

        // Add copper coins to result
        if (strlen(strval($int)) > 1) {
            $copper = substr(strval($int), -2);
        }
        else {
            $copper = substr(strval($int), -1);
        }
        $result .= $copper . '<span class="money money-copper ml-1 mr-2"></span>';

        return $result;
    }

    /**
     * This method converts a money span string into the equivalent integer
     * The expected format is: <value as integer><span element>
     * For example '1<span class="money money-gold ml-1 mr-2"></span>' means 1 gold coin
     * Only known span tags can be used, these are all optional, but can only appear once
     * If a particular span tag appears more than once,
     * then all the values between the first and last occurrence is ignored
     * Strings where no value can be determined will return NAN
     * @param string $string
     * @return int|string
     */
    public function convertMoneyStringToInt($string) {

        if (gettype($string) !== 'string') {
            $result = 'The argument provided isn\'t a string.';
        }
        else {
            $result = $this->determineMoneyInt($string);
            if (is_nan($result)) {
                $result = 'The argument couldn\'t be converted to an integer.';
            }
        }

        return $result;
    }

    /**
     * Get the monetary value of the given string
     * @param $string
     * @return int
     */
    public function determineMoneyInt($string) {

        $result = NAN;
        $delimiters = [
            'gold' => '<span class="money money-gold ml-1 mr-2"></span>',
            'silver' => '<span class="money money-silver ml-1 mr-2"></span>',
            'copper' => '<span class="money money-copper ml-1 mr-2"></span>'
        ];

        $goldResult = explode($delimiters['gold'], $string);
        // Check if gold is included
        if (count($goldResult) >= 2) {
            // Add gold to result
            is_nan($result) ? $result = (intval($goldResult[0]) * 10000) : $result += (intval($goldResult[0]) * 10000);
            // Update string
            $string = end($goldResult);
        }
        // Check if only a number is left
        elseif (strval(intval($goldResult[0])) === $goldResult[0]) {
            // Add gold to result
            is_nan($result) ? $result = (intval($goldResult[0]) * 10000) : $result += (intval($goldResult[0]) * 10000);
            // Update string to an empty string
            $string = '';
        }
        else {
            // Update string
            $string = $goldResult[0];
        }

        $silverResult = explode($delimiters['silver'], $string);
        // Check if silver is included
        if (count($silverResult) >= 2) {
            // Add silver to result
            is_nan($result) ? $result = (intval($silverResult[0]) * 100) : $result += (intval($silverResult[0]) * 100);
            // Update string
            $string = end($silverResult);
        }
        // Check if only a number is left
        elseif (strval(intval($silverResult[0])) === $silverResult[0]) {
            // Add silver to result
            is_nan($result) ? $result = (intval($silverResult[0]) * 100) : $result += (intval($silverResult[0]) * 100);
            // Update string to an empty string
            $string = '';
        }
        else {
            // Update string
            $string = $silverResult[0];
        }

        $copperResult = explode($delimiters['copper'], $string);
        // Check if copper is included
        if (count($copperResult) >= 2) {
            // Add copper to result
            is_nan($result) ? $result = (intval($copperResult[0])) : $result += (intval($copperResult[0]));
            // Update string
            $string = end($copperResult);
        }
        // Check if only a number is left
        elseif (strval(intval($copperResult[0])) === $copperResult[0]) {
            // Add copper to result
            is_nan($result) ? $result = (intval($copperResult[0])) : $result += (intval($copperResult[0]));
            // Update string to an empty string
            $string = '';
        }
        else {
            // Update string
            $string = $copperResult[0];
        }

        return $result;
    }
}