<?php
namespace App\BankCom;

use Nette;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Nette\Utils\DateTime;

/**
 * ExportABO is used to parse JSON (system export)
 * into ABO format (bank import)
 * @version 1.0.0
 * @author Petr Katerinak <katerinak@indeev.eu>
 */
class ABO
{
  use Nette\SmartObject;

  /**
   * Parses JSON data into ABO format and saves it to file
   *
   * @param string $exportedFile Path of the *.gpc (abo) to be exported
   * @param string $jsonData JSON array to be parsed
   * @return string Success or Error information about export
   */
  public static function export(string $exportedFile, string $jsonData): string
  {
    $filename = Strings::after($exportedFile, '/', -1);                         // Validace vstupu
    $path = Strings::before($exportedFile, '/', -1);
    if(!mb_strlen($filename) || !mb_strlen($path))
      return Json::encode(['error' => 'Nesprávná cesta výstupního souboru']);
    if(!mb_strlen($jsonData))
      return Json::encode(['error' => 'Vstupní JSON je prázdný']);

    try {                                                                       // Vytvoření cesty a souboru
      FileSystem::createDir($path);
      FileSystem::write($path . '/' . $filename, '');
    } catch (Nette\IOException $ex) {
      return Json::encode(['error' => 'Nelze vytvořit výstupní soubor']);
    }

    $dataArray = Json::decode($jsonData, Json::FORCE_ARRAY);                    //Převedení JSON na pole

    if(!count($dataArray['header']))                                            //Chybové hlášení v případě chybějící hlavičky v json
      return Json::encode(['error' => 'Data neobsahují hlavičku']);
    if(!count($dataArray['payments']))                                          //Chybové hlášení v případě chybějících plateb v json
      return Json::encode(['error' => 'Data neobsahují platby']);

    $totalPaid = 0;
    foreach($dataArray['payments'] as $payment) {                               //výpočet částky všech převodů/inkas v haléřích
      $totalPaid += \intval($payment['amount_to_pay']) * 100;                   // * 100, protože vstupní JSON má hodnoty v korunách
    }

    $aboArray = [];                                                             //Generování ABO jako pole řádků
    if(!Functions::hasError($aboRecord = self::getAboUserLabel($dataArray['header']))) {
      $aboArray[] = \implode('', $aboRecord['success']);                        //Uživatelské návěští ABO souboru
    } else {
      return Json::encode(['error' => $aboRecord['error']]);
    }
    if(!Functions::hasError($aboRecord = self::getAboAccFileHeading($dataArray['header']))) {
      $aboArray[] = \implode('', $aboRecord['success']);                        //Hlavička účetního souboru
    } else {
      return Json::encode(['error' => $aboRecord['error']]);
    }
    if(!Functions::hasError($aboRecord = self::getAboGroupHeading($dataArray['header'], $totalPaid))) {
      $aboArray[] = \implode('', $aboRecord['success']);                        //Hlavička skupiny
    } else {
      return Json::encode(['error' => $aboRecord['error']]);
    }
    foreach($dataArray['payments'] as $payment) {
      if(!Functions::hasError($aboRecord = self::getAboItem($payment))) {
        $aboArray[] = \implode('', $aboRecord['success']);                      //Položky
      } else {
        return Json::encode(['error' => $aboRecord['error']]);
      }
    }
    $aboArray[] = "3 +\r\n";                                                    //Konec skupiny
    $aboArray[] = "5 +\r\n";                                                    //Konec souboru
    $aboString = \implode('', $aboArray);                                       //Převedení pole řádku na ABO řetězec
    $aboString = iconv('UTF-8', 'windows-1250', $aboString);                    //Převedení kódování na Windows-1250

    try {                                                                       //Export do souboru
       FileSystem::write($path . '/' . $filename, $aboString);                  //Uložení do výstupního souboru
    } catch (Nette\IOException $ex) {
      return Json::encode(['error' => 'Do výstupního souboru nelze zapisovat']);
    }
    return Json::encode(['success'  => 'Data byla úspěšně exportována']);
  }

  /**
   * Parses user label of the ABO export
   *
   * @param array $data Header part of the parsed JSON data
   * @return array Array of the formatted strings to be joined into ABO line
   */
  private static function getAboUserLabel(array $data): array
  {
    $userLabelArray[] = 'UHL1';                                                 //text označení uživatelského návěští
    $userLabelArray[] = date('dmy');                                            //aktuální datum ve tvaru DDMMRR
    $companyName = \mb_substr($data['company_name'], 0, 20);
    $userLabelArray[] = Functions::mb_str_pad($companyName, 20, ' ');               //20 alfanum.znaků zkráceného názvu organizace, doplněných případně mezerami zprava
    $userLabelArray[] = $data['unknown'] ?? '0000000000';                       //přidělené číslo komitenta s příp.vodícími nulami (Fio Banka nepřiděluje, vyplňte nulami)
    $userLabelArray[] = $data['unknown'] ?? '001';                              //počátek intervalu přidělených čísel účetních souborů (001)
    $userLabelArray[] = $data['unknown'] ?? '999';                              //konec intervalu přidělených čísel účetních souborů (999)
    $userLabelArray[] = "\r\n";                                                 //ukončovací znaky
    return ['success' => $userLabelArray];
  }

  /**
   * Parses File Heading of the ABO export
   *
   * @param array $data Header part of the parsed JSON data
   * @return array Array of the formatted strings to be joined into ABO line
   */
  private static function getAboAccFileHeading(array $data): array
  {
    $accFileHeadingArray[] = '1';                                               //neměnný typ vstupní zprávy pro hlavičku souboru
    $accFileHeadingArray[] = ' ';                                               //oddělovač
    $accFileHeadingArray[] = $data['unknown'] ?? '1501';                        //Druh dat: 1501 pro účetní soubor s příkazy k úhradě 1502 pro účetní soubor s příkazy k inkasu
    $accFileHeadingArray[] = ' ';                                               //oddělovač
    $accFileHeadingArray[] = $data['SSS'];                                      //SSS - číslo účetního souboru v rozsahu 001-999
    $accFileHeadingArray[] = '000';                                             //PPB - vyplňte nulami
    $accFileHeadingArray[] = ' ';                                               //oddělovač
    $ownBankAccArray = Functions::splitBankAcc($data['account_number_own']);        //Validace a rozložení vlastního účtu
    if(Functions::hasError($ownBankAccArray))
      return ['error' => 'Vlastní účet: ' . $ownBankAccArray['error']];
    $accFileHeadingArray[] = $ownBankAccArray['success']['bank_code'];          //Směrový kód banky (Pokud účet příjemce není validní, vloží 0000)
    $accFileHeadingArray[] = "\r\n";                                            //ukončovací znaky
    return ['success' => $accFileHeadingArray];
  }

  /**
   * Parses Group Heading of the ABO export
   *
   * @param array $data Header part of the parsed JSON data
   * @param int $totalPay Sum of all payments in the group
   * @return array Array of the formatted strings to be joined into ABO line
   */
  private static function getAboGroupHeading(array $data, int $totalPay): array
  {
    $accFileHeadingArray[] = '2';                                               //neměnný typ vstupní zprávy pro hlavičku souboru
    $accFileHeadingArray[] = ' ';                                               //oddělovač
    $ownBankAccArray = Functions::splitBankAcc($data['account_number_own']);        //Validace a rozložení vlastního účtu
    if(Functions::hasError($ownBankAccArray))
      return ['error' => 'Vlastní účet: ' . $ownBankAccArray['error']];
    $prefix = $ownBankAccArray['success']['prefix'] ?? '000000';                //Pokud prefix neexistuje, je nahrazen 000000
    $accNumber = $ownBankAccArray['success']['acc_number'];
    $accFileHeadingArray[] = (\strlen($prefix))                                 //číslo účtu příkazce (PPPPPP-UUUUUUUUUU nepovinná položka),
                             ? $prefix . '-' . $accNumber                       // "PPPPPP" = předčíslí účtu - "UUUUUUUUUU" = číslo účtu
                             : $accNumber;                                      // Číslo účtu příkazce smí být ve skupině uvedeno pouze jednou,
                                                                                // je-li uvedeno v Hlavičce skupiny, neuvádí se v Položkách
    $accFileHeadingArray[] = ' ';                                               //oddělovač
    $accFileHeadingArray[] = $totalPay;                                         //celková částka převodů/inkas ve skupině v haléřích
    $accFileHeadingArray[] = ' ';                                               //oddělovač
    $accFileHeadingArray[] = DateTime::createFromFormat('d.m.Y', $data['due_date'])
                                     ->format('dmy');                           //Datum splatnosti ve tvaru DDMMRR
    $accFileHeadingArray[] = "\r\n";                                            //ukončovací znaky
    return ['success' => $accFileHeadingArray];
  }

  /**
   * Parses Item part of the ABO export
   *
   * @param array $data One payment item from the JSON
   * @return array Array of the formatted strings to be joined into ABO line
   */
  private static function getAboItem(array $data): array
  {
    // Číslo vlastního účtu a oddělovač jsou vynechány z důvodu uvedení v hlavičce skupiny
    $foreignBankAccArray = Functions::splitBankAcc($data['account_number_foreign']);//Validace a rozložení účtu přijemce
    if(Functions::hasError($foreignBankAccArray))
      return ['error' => 'Cizí účet: ' . $foreignBankAccArray['error']];
    $prefix = $foreignBankAccArray['success']['prefix'] ?? '000000';            //Pokud prefix neexistuje, je nahrazen 000000
    $accNumber = $foreignBankAccArray['success']['acc_number'];
    $itemArray[] = $prefix . '-' . $accNumber;                                  //číslo účtu příjemce/inkasovaného "PPPPPP" = předčíslí účtu - "UUUUUUUUUU" = číslo účtu
    $itemArray[] = ' ';                                                         //oddělovač
    $itemArray[] = \str_pad($data['amount_to_pay'] * 100, 15, '0',STR_PAD_LEFT);//částka převodu/inkasa v haléřích (+doplnění na 15 bytů)
                                                                                // * 100, protože vstupní JSON má hodnoty v korunách
    $itemArray[] = ' ';                                                         //oddělovač
    $itemArray[] = \str_pad($data['v_symbol'], 10, '0', STR_PAD_LEFT);          //variabilní symbol (+doplnění na 10 bytů)
    $itemArray[] = ' ';                                                         //oddělovač
    $itemArray[] = $foreignBankAccArray['success']['bank_code'];                //Směrový kód banky (Pokud účet příjemce není validní, vloží 0000)
    $itemArray[] = $data['k_symbol'];                                           //konstantní symbol
    $itemArray[] = ' ';                                                         //oddělovač
    $itemArray[] = \strlen($data['s_symbol'])
                    ? \str_pad($data['v_symbol'], 10, '0', STR_PAD_LEFT)
                    : ' ';                                                      //specifický symbol (nepovinná položka - v případě neuvedení nahraďte mezerou)
    $itemArray[] = ' ';                                                         //oddělovač
    $itemArray[] = \mb_strlen($data['description'])                             //„AV:“= povinný text uvozující AV–pole, je-li položka uvedena;
                    ? 'AV-' . $data['description']                              // vlastní text pro AV-pole ("zpráva pro příjemce"),
                    : ' ';                                                      //(nepovinná položka - v případě neuvedení nahraďte mezerou
    $itemArray[] = "\r\n";                                                      //ukončovací znaky
    return ['success' => $itemArray];
  }
}