<?php
namespace App\BankCom;

/**
 * Support functions for BankCom
 * @version 1.0.0
 * @author Petr Katerinak <katerinak@indeev.eu>
 */

class Functions
{
  /**
   * Check if associative array contains an Error message
   *
   * @param array $data Usually array returned from some function
   * @return boolean
   * @example ['error' => 'This is an error message'] returns true
   * @example ['success' => ['data' => 'Some valid data to be processed']] return false
   */
  public static function hasError(array $data): bool
  {
    return (\array_key_exists('error', $data)) ? true : false;
  }

  /**
   * Disassembles account number into array of values
   *
   * @param string $account Bank account in format (PPPPPP-)NNNNNNNNNN/CCCC
   * @return array Splitted values into prefix, acc_number and bank_code
   */
  public static function splitBankAcc(string $account): array
  {
    if(!\preg_match('/^((?<prefix>\d{0,6})?-)?(?<acc_number>\d{2,10})\/(?<bank_code>\d{4})$/', $account, $parts)) {
        return ['error' => 'Nesprávný formát čísla účtu: ' . $account];
    }
    return ['success' => [
              'prefix' => \strlen($parts['prefix'])
                          ? $parts['prefix'] : null,
              'acc_number' => $parts['acc_number'],
              'bank_code' => $parts['bank_code']
    ]];
  }

  /**
   * This function returns the input string padded on the left, the right, or both sides to the specified padding length.
   * Till now, PHP 7.4 still doesn't have this unicode (multibite) version of this function in core.
   *
   * @param string $input The input string
   * @param int $input Required length of the output. If value <= $input length, $input is returned unchanged.
   * @param string $pad_string Padding character(s)
   * @param int $pad_type Side to add padding (STR_PAD_RIGHT, STR_PAD_LEFT or STR_PAD_BOTH)
   * @param string $encoding Character encoding. If omitted, internal character encoding is used.
   * @return string Padded string
   */
  public static function mb_str_pad(string $input, int $pad_length,
                              string $pad_string = ' ',
                              int $pad_type = STR_PAD_RIGHT,
                              string $encoding = null): string
  {
    if (!$encoding) {
        $diff = \strlen($input) - \mb_strlen($input);
    }
    else {
        $diff = \strlen($input) - \mb_strlen($input, $encoding);
    }
    return \str_pad($input, $pad_length + $diff, $pad_string, $pad_type);
  }

    public static function w1250_to_utf8($text) {
        // map based on:
        // http://konfiguracja.c0.pl/iso02vscp1250en.html
        // http://konfiguracja.c0.pl/webpl/index_en.html#examp
        // http://www.htmlentities.com/html/entities/
        $map = array(
            chr(0x8A) => chr(0xA9),
            chr(0x8C) => chr(0xA6),
            chr(0x8D) => chr(0xAB),
            chr(0x8E) => chr(0xAE),
            chr(0x8F) => chr(0xAC),
            chr(0x9C) => chr(0xB6),
            chr(0x9D) => chr(0xBB),
            chr(0xA1) => chr(0xB7),
            chr(0xA5) => chr(0xA1),
            chr(0xBC) => chr(0xA5),
            chr(0x9F) => chr(0xBC),
            chr(0xB9) => chr(0xB1),
            chr(0x9A) => chr(0xB9),
            chr(0xBE) => chr(0xB5),
            chr(0x9E) => chr(0xBE),
            chr(0x80) => '&euro;',
            chr(0x82) => '&sbquo;',
            chr(0x84) => '&bdquo;',
            chr(0x85) => '&hellip;',
            chr(0x86) => '&dagger;',
            chr(0x87) => '&Dagger;',
            chr(0x89) => '&permil;',
            chr(0x8B) => '&lsaquo;',
            chr(0x91) => '&lsquo;',
            chr(0x92) => '&rsquo;',
            chr(0x93) => '&ldquo;',
            chr(0x94) => '&rdquo;',
            chr(0x95) => '&bull;',
            chr(0x96) => '&ndash;',
            chr(0x97) => '&mdash;',
            chr(0x99) => '&trade;',
            chr(0x9B) => '&rsquo;',
            chr(0xA6) => '&brvbar;',
            chr(0xA9) => '&copy;',
            chr(0xAB) => '&laquo;',
            chr(0xAE) => '&reg;',
            chr(0xB1) => '&plusmn;',
            chr(0xB5) => '&micro;',
            chr(0xB6) => '&para;',
            chr(0xB7) => '&middot;',
            chr(0xBB) => '&raquo;',
        );
        return html_entity_decode(mb_convert_encoding(strtr($text, $map), 'UTF-8', 'ISO-8859-2'), ENT_QUOTES, 'UTF-8');
    }


}