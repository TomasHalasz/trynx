<?php
namespace App\EDI;

use Nette;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;

/**
 * GPC Parser allows to parse *.inh (edi invoice export) file into JSON (system import)
 * @version 1.0.0
 * @author Petr Katerinak <katerinak@indeev.eu>
 * @author Tomas Halasz <info@faktury.cz>
 */
class INH
{
    use Nette\SmartObject;

    const INH_POSITIONS = [
        'SYS' => [  1 => 3, 18 =>  14],
        'HDR' => [  1 => 3, 137 => [8, 'doc_date'], 145 => [9,'inv_number'], 224 => [10, 'ic_customer'], 239 => [14, 'dic_customer'],
                    323 => [10, 'ic_supplier'], 335 => [14, 'dic_supplier'], 416 => [3, 'currency_code'] ],
        'HDD' => [  1 => 3, 4 =>  [70, 'customer_name'], 74 => [105, 'customer_name2'], 179 => [140, 'customer_street'],
                    319 => [35, 'customer_city'], 354 => [6, 'customer_zip'],
                    360 => [175, 'supplier_name'], 535 => [140, 'supplier_street'], 675 => [35, 'supplier_city'], 710 => [6, 'supplier_zip'] ],
        'LIN' => [  1 => 3, 4 => [6, 'item_order'] /*order*/,   10 => [13, 'ean_code'] /*EAN*/,  61 => [14, 'quantity'] /*quantity*/,
                    106 => [12, 'price_in'] /*price_in*/,
                    118 => [12, 'price_in_total'] /*price_in total*/, 130 => [5, 'vat'] /*VAT*/, 135 => [3, 'units'] /*units*/,
                    223 => [8, 'rea_date'] /*recieve date*/,
                    231 => [10, 'order'] /*od_number*/, 358 => [70, 'item_label'] /*item_label*/],
        'TXT' => [  1 => 3, 4 => [207, 'txt']],
        'SUM' => [  1 => 3, 4 => [12, 'price_base3'] /*base 10*/,   16 => [12, 'price_base2'] /*base 15%*/,  28 => [12, 'price_base1'] /*base 21%*/ ,
                    40 => [12, 'price_e2'] /*total base*/, 62 => [12, 'price_vat2'] /*vat 15%*/, 86 => [12, 'price_vat1'] /*vat 21%*/,
                    86 => [12, 'total_vat'] /*total vat*/, 286 => [12, 'price_e2_vat'] /*price_e2_vat*/, 353 => [6, 'lines'] /*number of lines*/],
    ];

    /**
     * Parses inh file into JSON array
     * @category ImportInhToJSON
     *
     * @param string $file Path of the *.inh to be imported
     * @return string JSON array escaped by 'success' or 'error'
     */
    public static function import(string $file): string
    {
        $inh_string = '';
        try {                                                                       // Pokus o načtení souboru
            $inh_string = FileSystem::read($file);
        } catch (Nette\IOException $ex) {
            return Json::encode(['error' => 'Soubor ' . $file . ' nelze přečíst.']);
        }

        if (!mb_strlen($inh_string)) {                                              // Zjištění, zda soubor má nějaký obsah
            return Json::encode(['error' => 'Soubor ' . $file .
                ' neobsahuje žádná data.']);
        }

        if (substr($inh_string,0, 3) != "SYS"){                         // test EDI format
            return Json::encode(['error' => 'Soubor ' . $file . ' není ve formátu EDI']);
        }

        $inh_string = Functions::w1250_to_utf8( $inh_string);

        $sentences = \explode("\r\n", $inh_string);                                  // Převod obsahu souboru na jednotlivé "věty"

        $inh_array = [];
        foreach ($sentences as $sentence) {                                         // Zpracování vět do pole záznamů
            if(!\mb_strlen($sentence)) break;                                         // Pokud je věta prázdná, cyklus je přerušen
            $lineType = \mb_substr($sentence, 0, 3);                                  // Zjištění typu věty dle prvních tří znaků (74,75,76,78,79)
            $sentenceArray = [];
            $j = 1;
            foreach(self::INH_POSITIONS[$lineType] as $position => $length_name) {         // Rozdělení věty do pole prvků dle definice GPC
                $position--;                                                            // Popis formátu začíná pozicí 1, index se počítá od 0
                if (is_array($length_name)){
                    $length = $length_name[0];
                    $key = $length_name[1];
                }else{
                    $length = $length_name;
                    $key = $j++;
                }
                $record = \mb_substr($sentence, $position, $length);
                $sentenceArray[$key] = trim($record);
            }
            $inh_array[] = $sentenceArray;
        }
        return Json::encode(['success' => $inh_array]);
    }




}