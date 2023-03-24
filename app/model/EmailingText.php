<?php
 
namespace App\Model;

use Nette,
	Nette\Utils\Strings;

 
class EmailingTextManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_emailing_text';
	
	public function getEmailingText($use,$url,$data,$id = NULL, $cl_company_id = NULL)
	{
		$arrNumbers = ['invoice'	     => 'inv_number',
                        'invoice_advance'=> 'inv_number',
                        'invoice_arrived'=> 'inv_number',
                        'order'		     => 'od_number',
                        'commission'	 => 'cm_number',
                        'offer'	         => 'cm_number',
                        'sale'	         => 'sale_number',
                        'partners_event' => 'event_number',
                        'delivery_note'  => 'dn_number',
                        'cash'           => 'cash_number',
                        'b2b_order'      => 'cm_number',
                        'task'           => 'task_number',
                        'estate'         => 'est_number',
                        'complaint'      => 'co_number',
                        'rental'         => 'rnt_number'];

		$arrReplaces = ['reservation' => ['data' => ['[date_from]' => 'dtm_start', '[date_to]' => 'dtm_end', '[description]' => 'description',
                                                    '[staff_name]' => 'in_staff.name', '[staff_surname]' => 'in_staff.surname',
                                                    '[staff_number]' => 'in_staff.personal_number',
                                                    '[estate_label]' => 'in_estate.est_name', '[estate_number]' => 'in_estate.est_number'],
                                        'format' => ['[date_from]' => "date", '[date_to]' => "date"]
                                            ],
                        'ireminder' => ['data' => ['[invoices]' => 'invoices', '[total_sum]' => 'total_sum']],
                        'areminder' => ['data' => ['[invoices]' => 'invoices', '[total_sum]' => 'total_sum']]
        ];
	    //subject and body for email
		
	   if ($id != NULL) {
		   if ($cl_company_id !== NULL) {
			   $emailText = $this->findAllTotal()->where('id = ? AND cl_company_id = ? AND active = ?', $id, $cl_company_id, 1)->fetch();
		   }else {
			   $emailText = $this->find($id);
		   }
	   }elseif ($cl_company_id !== NULL) {
			   $emailText = $this->findAllTotal()->where(['cl_company_id' => $cl_company_id,
				   'email_use' => $use, 'active' => 1])->fetch();
       }else {
           $emailText = $this->findBy(['email_use' => $use, 'active' => 1])->fetch();
       }


	    if ($emailText) {
			//replace variables
			if ($url != '') {
                if (count(SERVER_ADR) > 0){
                    $url = str_replace(SERVER_ADR['local'], SERVER_ADR['remote'], $url);
                }
				$bodytext = str_replace('[link]', '<a href="' . $url . '">' . $url . '</a>', $emailText->email_body);
			}else {
				$bodytext = $emailText->email_body;
			}

            if ($use != '' && array_key_exists($use, $arrNumbers)){
                $title = str_replace('[doc_number]', $data[$arrNumbers[$use]], $emailText->email_subject);
            }else {
                $title = $emailText->email_subject;
            }

			if ($use != '' && array_key_exists($use, $arrNumbers)) {
				$bodytext = str_replace('[doc_number]', $data[$arrNumbers[$use]], $bodytext);
			}
            if (array_key_exists($use, $arrReplaces)){
                foreach($arrReplaces[$use]['data'] as $key => $one){
                    $relation = str_getcsv($one, '.');
                    if (count($relation) == 1){
                        $val = $data[$relation[0]];
                    }elseif (count($relation) == 2){
                        $val = $data[$relation[0]][$relation[1]];
                    }elseif (count($relation) == 3){
                        $val = $data[$relation[0]][$relation[1]][$relation[2]];
                    }
                    $strVal = $val;
                    if (array_key_exists($key, $arrReplaces[$use]['format'])){
                        $tmpFormat =  $arrReplaces[$use]['format'][$key];
                        if ($tmpFormat == 'date'){
                            $strVal = $val->format('d.m.Y');
                        }elseif ($tmpFormat == 'datetime'){
                            $strVal = $val->format('d.m.Y H:m');
                        }

                    }
                    $bodytext = str_replace($key, $strVal, $bodytext);
                    $title = str_replace($key, $strVal, $title);
                }

            }

			$emailBody = $bodytext;
			$emailTitle = $title;
		
	    }else{
			$emailBody = '';
			$emailTitle = '' ;
	    }	   
	    //dump($emailBody);
	    //sdie;
	    
	    return (['subject' => $emailTitle, 'body' => $emailBody, 'activeRow' => $emailText]);
	}


    public function getEmailingTexts($use)
    {
        $emailText = $this->findBy(['email_use' => $use, 'active' => 1])->order('email_name')->fetchPairs('id', 'email_name');
        return $emailText;
    }

}