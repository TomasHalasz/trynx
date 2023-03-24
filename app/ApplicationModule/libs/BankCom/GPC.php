<?php
namespace App\BankCom;

use Nette;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;

/**
 * GPC Parser allows to parse *.gpc (bank export) file into JSON (system import)
 * @version 1.0.0
 * @author Petr Katerinak <katerinak@indeev.eu>
 */
class GPC
{
  use Nette\SmartObject;

  const GPC_POSITIONS = [
    '074' => [  1 =>  3,   4 => 16,  20 => 20,  40 =>  6,  46 => 14,
               60 =>  1,  61 => 14,  75 =>  1,  76 => 14,  90 =>  1,
               91 => 14, 105 =>  1, 106 =>  3, 109 =>  6, 115 => 13],
    '075' => [  1 =>  3,   4 => 16,  20 => 16,  36 => 13,  49 => 12,
               61 =>  1,  62 => 10,  72 =>  2,  74 =>  4,  78 =>  4,
               82 => 10,  92 =>  6,  98 => 20, 118 =>  5, 123 =>  6],
    '076' => [  1 =>  3,   4 => 26,  30 =>  6,  36 => 92],
    '078' => [  1 => 03,   4 => 35,  39 => 35,  74 => 54],
    '079' => [  1 => 03,   4 => 35,  39 => 35,  74 => 35, 110 => 18],
  ];

  /**
   * Parses gpc file into JSON array
   * @category ImportGpcToJSON
   *
   * @param string $file Path of the *.gpc to be imported
   * @return string JSON array escaped by 'success' or 'error'
   */
  public static function import(string $file): string
  {
    $gpc_string = '';
    try {                                                                       // Pokus o načtení souboru
      $gpc_string = FileSystem::read($file);
    } catch (Nette\IOException $ex) {
      return Json::encode(['error' => 'Soubor ' . $file . ' nelze přečíst.']);
    }

    if (!mb_strlen($gpc_string)) {                                              // Zjištění, zda soubor má nějaký obsah
      return Json::encode(['error' => 'Soubor ' . $file .
                                      ' neobsahuje žádná data.']);
    }

    $gpc_string = Functions::w1250_to_utf8( $gpc_string);

    $sentences = \explode("\r\n", $gpc_string);                                  // Převod obsahu souboru na jednotlivé "věty"

    $gpc_array = [];
    foreach ($sentences as $sentence) {                                         // Zpracování vět do pole záznamů
      if(!\mb_strlen($sentence)) break;                                         // Pokud je věta prázdná, cyklus je přerušen
      $lineType = \mb_substr($sentence, 0, 3);                                  // Zjištění typu věty dle prvních tří znaků (74,75,76,78,79)
      $sentenceArray = [];
      $j = 1;
      foreach(self::GPC_POSITIONS[$lineType] as $position => $length) {         // Rozdělení věty do pole prvků dle definice GPC
        $position--;                                                            // Popis formátu začíná pozicí 1, index se počítá od 0
        $record = \mb_substr($sentence, $position, $length);
        $sentenceArray[$j++] = $record;
      }
      $gpc_array[] = $sentenceArray;
    }
    return Json::encode(['success' => $gpc_array]);
  }




}