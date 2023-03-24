<?php

namespace App\Model;

use Nette,
    Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;
use Tracy\Debugger;
use function GuzzleHttp\Psr7\str;

/**
 * Commission management.
 */
class CommissionManager extends Base
{
    const COLUMN_ID = 'id';
    public $tableName = 'cl_commission';


    /** @var Nette\Database\Context */
    public $CommissionItemsManager;
    /** @var Nette\Database\Context */
    public $CommissionItemsSelManager;
    /** @var Nette\Database\Context */
    public $CommissionItemsProductionManager;
    /** @var Nette\Database\Context */
    public $CommissionWorkManager;
    /** @var Nette\Database\Context */
    public $StoreDocsManager;
    /** @var Nette\Database\Context */
    public $StoreManager;
    /** @var Nette\Database\Context */
    public $PairedDocsManager;
    /** @var App\Model\Base */
    public $CompaniesManager;
    /** @var App\Model\EshopsManager */
    public $EshopsManager;
    /** @var App\Model\CurrenciesManager */
    public $CurrenciesManager;
    /** @var App\Model\PartnersManager */
    public $PartnersManager;
    /** @var App\Model\DeliveryNoteManager */
    public $DeliveryNoteManager;
    /** @var App\Model\PaymentTypesManager */
    public $PaymentTypesManager;
    /** @var App\Model\TransportTypesManager */
    public $TransportTypesManager;
    /** @var App\Model\PartnersBranchManager */
    public $PartnersBranchManager;
    /** @var App\Model\PartnersBookWorkersManager */
    public $PartnersBookWorkersManager;
    /** @var App\Model\PriceListManager */
    public $PriceListManager;
    /** @var App\Model\StatusManager */
    public $StatusManager;
    /** @var App\Model\RatesVatManager */
    public $ratesVatManager;
    /** @var App\Model\InvoiceTypesManager */
    public $InvoiceTypesManager;
    /** @var App\Model\InvoiceManager */
    public $InvoiceManager;
    /** @var App\Model\InvoiceItemsManager */
    public $InvoiceItemsManager;
    /** @var App\Model\InvoiceAdvanceManager */
    public $InvoiceAdvanceManager;
    /** @var App\Model\InvoiceAdvanceItemsManager */
    public $InvoiceAdvanceItemsManager;
    /** @var App\Model\PricelistBondsManager */
    public $PricelistBondsManager;
    /** @var App\Model\NumberSeriesManager */
    public $NumberSeriesManager;
    /** @var App\Model\CountriesManager */
    public $CountriesManager;


    public $settings;

    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context   $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
                                CommissionItemsManager    $CommissionItemsManager, CommissionWorkManager $CommissionWorkManager,
                                CommissionItemsSelManager $CommissionItemsSelManager, StoreDocsManager $StoreDocsManager,
                                CommissionItemsProductionManager $CommissionItemsProductionManager,
                                StoreManager              $StoreManager, PairedDocsManager $PairedDocsManager, CompaniesManager $CompaniesManager, EshopsManager $eshopsManager,
                                CurrenciesManager         $currenciesManager, PartnersManager $partnersManager, PaymentTypesManager $paymentTypesManager, TransportTypesManager $transportTypesManager,
                                PartnersBranchManager     $partnersBranchManager, PartnersBookWorkersManager $partnersBookWorkersManager, PriceListManager $priceListManager, StatusManager $statusManager,
                                RatesVatManager           $ratesVatManager, InvoiceTypesManager $invoiceTypesManager, NumberSeriesManager $numberSeriesManager, InvoiceManager $invoiceManager,
                                PriceListBondsManager     $pricelistBondsManager, InvoiceItemsManager $invoiceItemsManager,
                                InvoiceAdvanceManager     $invoiceAdvanceManager,  InvoiceAdvanceItemsManager $invoiceAdvanceItemsManager,
                                DeliveryNoteManager       $deliveryNoteManager, CountriesManager $countriesManager)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);
        $this->CommissionItemsManager = $CommissionItemsManager;
        $this->CommissionItemsSelManager = $CommissionItemsSelManager;
        $this->CommissionItemsProductionManager = $CommissionItemsProductionManager;
        $this->CommissionWorkManager = $CommissionWorkManager;
        $this->StoreDocsManager = $StoreDocsManager;
        $this->StoreManager = $StoreManager;
        $this->PairedDocsManager = $PairedDocsManager;
        $this->EshopsManager = $eshopsManager;
        $this->settings = $CompaniesManager->getTable()->fetch();
        $this->CurrenciesManager = $currenciesManager;
        $this->PartnersManager = $partnersManager;
        $this->PaymentTypesManager = $paymentTypesManager;
        $this->TransportTypesManager = $transportTypesManager;
        $this->PartnersBranchManager = $partnersBranchManager;
        $this->PartnersBookWorkersManager = $partnersBookWorkersManager;
        $this->PriceListManager = $priceListManager;
        $this->StatusManager = $statusManager;
        $this->second_user = TRUE;
        $this->ratesVatManager = $ratesVatManager;
        $this->InvoiceTypesManager = $invoiceTypesManager;
        $this->InvoiceItemsManager = $invoiceItemsManager;
        $this->PricelistBondsManager = $pricelistBondsManager;
        $this->InvoiceManager = $invoiceManager;
        $this->InvoiceAdvanceManager = $invoiceAdvanceManager;
        $this->InvoiceAdvanceItemsManager = $invoiceAdvanceItemsManager;
        $this->NumberSeriesManager = $numberSeriesManager;
        $this->DeliveryNoteManager = $deliveryNoteManager;
        $this->CountriesManager = $countriesManager;
    }


    public function updateSum($id, $cl_company_id = NULL)
    {

        if (is_null($cl_company_id)) {
            $price_s = $this->CommissionItemsManager->findBy(array('cl_commission_id' => $id))->where('cl_commission_items_sel_id IS NULL')->sum('price_s*quantity');
            $price_s += $this->CommissionItemsSelManager->findBy(array('cl_commission_id' => $id))->sum('price_s*quantity');

            $price_e2 = $this->CommissionItemsSelManager->findBy(array('cl_commission_id' => $id))->sum('price_e2');
            $price_e2_vat = $this->CommissionItemsSelManager->findBy(array('cl_commission_id' => $id))->sum('price_e2_vat');

            $price_w = $this->CommissionWorkManager->findBy(array('cl_commission_id' => $id))->sum('work_rate*work_time');
            $price_w2 = $this->CommissionWorkManager->findBy(array('cl_commission_id' => $id))->sum('(work_rate*work_time)*(1+(profit/100))');
        } else {
            $price_s = $this->CommissionItemsManager->findAllTotal()->where(array('cl_company_id' => $cl_company_id, 'cl_commission_id' => $id))->where('cl_commission_items_sel_id IS NULL')->sum('price_s*quantity');
            $price_s += $this->CommissionItemsSelManager->findAllTotal()->where(array('cl_company_id' => $cl_company_id, 'cl_commission_id' => $id))->sum('price_s*quantity');

            $price_e2 = $this->CommissionItemsSelManager->findAllTotal()->where(array('cl_company_id' => $cl_company_id, 'cl_commission_id' => $id))->sum('price_e2');
            $price_e2_vat = $this->CommissionItemsSelManager->findAllTotal()->where(array('cl_company_id' => $cl_company_id, 'cl_commission_id' => $id))->sum('price_e2_vat');

            $price_w = $this->CommissionWorkManager->findAllTotal()->where(array('cl_company_id' => $cl_company_id, 'cl_commission_id' => $id))->sum('work_rate*work_time');
            $price_w2 = $this->CommissionWorkManager->findAllTotal()->where(array('cl_company_id' => $cl_company_id, 'cl_commission_id' => $id))->sum('(work_rate*work_time)*(1+(profit/100))');
        }


        $tmpCommissionItemsPackage = $this->CommissionItemsManager->findBy(array('cl_commission_id' => $id))->
        where('cl_pricelist.cl_pricelist_group.is_return_package = 1');
        $tmpCommissionItemsSelPackage = $this->CommissionItemsSelManager->findBy(array('cl_commission_id' => $id))->
        where('cl_pricelist.cl_pricelist_group.is_return_package = 1');

        $price_s_package = $tmpCommissionItemsSelPackage->sum('cl_commission_items_sel.price_s * cl_commission_items_sel.quantity');
        $price_e2_package = $tmpCommissionItemsSelPackage->sum('cl_commission_items_sel.price_e2');

        $price_s_package = $price_s_package + $tmpCommissionItemsPackage->sum('cl_commission_items.price_s * cl_commission_items.quantity');
        $price_e2_package = $price_e2_package + $tmpCommissionItemsPackage->sum('cl_commission_items.price_e2');

        //$price_e2_vat       = $price_e2_vat - $tmpCommissionItemsPackage->sum('cl_invoice_items.price_e2_vat');


        $parentData = new \Nette\Utils\ArrayHash;
        $parentData['id'] = $id;
        $parentData['price_s'] = $price_s; //costs for items
        $parentData['price_w'] = $price_w; //costs for work
        $parentData['price_s_package'] = $price_s_package; //costs for items package

        $parentData['price_w2'] = $price_w2; //sell for work
        $parentData['price_e2'] = $price_e2; //sell for items without vat
        $parentData['price_e'] = $price_s + $price_w; //total costs works+items
        $parentData['price_e2_base'] = $price_e2 + $price_w2; //total sell works+items
        $parentData['price_e2_package'] = $price_e2_package; //total sell for package

        bdump($parentData['price_e2'], 'price_e2');
        bdump($parentData['price_e2_package'], 'price_e2_package');
        bdump($parentData['price_s'], 'price_s');
        bdump($parentData['price_s_package'], 'price_s_package');

        $parentData['profit_abs'] = ($parentData['price_e2'] - $parentData['price_e2_package']) - ($parentData['price_s'] - $parentData['price_s_package']);
        bdump($parentData['profit_abs'], 'profit_abs');
        if ($parentData['price_s'] > 0) {
            //$parentData['profit'] = (($parentData['price_e2'] / $parentData['price_s']) - 1) * 100;
            $parentData['profit'] = 100 - ((($parentData['price_s'] - $parentData['price_s_package']) / ($parentData['price_e2'] - $parentData['price_e2_package'])) * 100);
        } else {
            $parentData['profit'] = 100;
        }


        if (is_null($cl_company_id)) {
            $vatRate = $this->find($id)->vat;
        } else {
            if ($tmpRate = $this->findAllTotal()->where('id = ?', $id)->fetch())
                $vatRate = $tmpRate->vat;
            else
                $vatRate = 0;
        }
        //dump($vatRate);
        $calcVat = round(($price_w2) * ($vatRate / 100), 2);
        $parentData['price_e2_vat'] = $price_e2_vat + $price_w2 + $calcVat;    //total sell with vat
        if (is_null($cl_company_id)) {
            $this->update($parentData);
        } else {
            $parentData['cl_company_id'] = $cl_company_id;
            $this->updateForeign($parentData);
        }

    }

    /** return all items from table cl_commission_items_sel
     * @param $id
     * @return Nette\Database\Table\Selection
     */
    public function getAllSelForOrder($id)
    {
        $data = $this->CommissionItemsSelManager->findAll()->where('cl_commission_items_sel.cl_commission_id = ? AND cl_commission_items_sel.cl_pricelist_id IS NOT NULL AND cl_commission_items_sel.cl_order_id IS NULL', $id);
        $data = $data->where('cl_pricelist.cl_partners_book_id IS NOT NULL AND '
            . '(cl_pricelist.cl_pricelist_group.is_product = 0 OR cl_pricelist.cl_pricelist_group_id IS NULL)')->
        select('cl_commission_items_sel.cl_commission_id, cl_commission_items_sel.id, cl_commission_items_sel.cl_order_id, SUM(cl_commission_items_sel.quantity) AS quantity, cl_pricelist.id AS cl_pricelist_id, cl_pricelist.identification, cl_pricelist.item_label,'
            . 'cl_pricelist.vat, cl_pricelist.price_s, cl_pricelist.unit AS units, cl_pricelist.cl_partners_book_id,cl_pricelist.cl_partners_book_id2,cl_pricelist.cl_partners_book_id3,cl_pricelist.cl_partners_book_id4, cl_pricelist.cl_storage_id AS cl_storage_id')->
        group('cl_pricelist.id, cl_pricelist.cl_storage_id')->
        order('cl_pricelist.cl_partners_book_id ASC, cl_pricelist.identification ASC');
        return $data;
    }

    public function getMissingSelForOrder($id)
    {
        $data = $this->CommissionItemsSelManager->findAll()->where('cl_commission_items_sel.cl_commission_id = ? AND cl_commission_items_sel.cl_pricelist_id IS NOT NULL AND cl_commission_items_sel.cl_order_id IS NULL', $id);
        $data = $data->where('cl_pricelist.cl_partners_book_id IS NOT NULL AND '
            . '(cl_pricelist.cl_pricelist_group.is_product = 0 OR cl_pricelist.cl_pricelist_group_id IS NULL)')->
        select('cl_commission_items_sel.cl_commission_id, cl_commission_items_sel.id, cl_commission_items_sel.cl_order_id, SUM(cl_commission_items_sel.quantity - cl_pricelist.quantity) AS quantity, cl_pricelist.id AS cl_pricelist_id, cl_pricelist.identification, cl_pricelist.item_label,'
            . 'cl_pricelist.vat, cl_pricelist.price_s, cl_pricelist.unit AS units, cl_pricelist.cl_partners_book_id,cl_pricelist.cl_partners_book_id2,cl_pricelist.cl_partners_book_id3,cl_pricelist.cl_partners_book_id4, cl_pricelist.cl_storage_id AS cl_storage_id')->
        group('cl_pricelist.id, cl_pricelist.cl_storage_id')->
        order('cl_pricelist.cl_partners_book_id ASC, cl_pricelist.identification ASC');
        //			    where('(cl_commission_items_sel.quantity - cl_pricelist.quantity) > 0')->
        return $data;
    }

    /** return all items from table cl_commission_items_sel without needing cl_partners_book_id
     * @param $id
     * @return Nette\Database\Table\Selection
     */
    public function getAll2SelForOrder($id)
    {
        $data = $this->CommissionItemsSelManager->findAll()->where('cl_commission_items_sel.cl_commission_id = ? AND cl_commission_items_sel.cl_order_id IS NULL', $id);
        $data = $data->select('cl_commission_items_sel.cl_commission_id, cl_commission_items_sel.id, cl_commission_items_sel.cl_order_id, (cl_commission_items_sel.quantity) AS quantity, cl_pricelist.id AS cl_pricelist_id,'
            . 'cl_pricelist.identification, cl_commission_items_sel.item_label,'
            . 'cl_commission_items_sel.vat, cl_commission_items_sel.price_s, cl_commission_items_sel.units, NULL AS cl_partners_book_id,NULL AS cl_partners_book_id2,NULL AS cl_partners_book_id3,NULL AS cl_partners_book_id4, cl_pricelist.cl_storage_id AS cl_storage_id')->
        order('cl_commission_items_sel.item_order ASC');
        return $data;
    }

    public function getAllForOrder($id)
    {
        $data = $this->CommissionItemsManager->findAll()->where('cl_commission_items.cl_commission_id = ? AND cl_commission_items.cl_pricelist_id IS NOT NULL AND cl_commission_items.cl_order_id IS NULL', $id);
        $data = $data->where('cl_pricelist.cl_partners_book_id IS NOT NULL AND '
            . '(cl_pricelist.cl_pricelist_group.is_product = 0 OR cl_pricelist.cl_pricelist_group_id IS NULL)')->
        select('cl_commission_items.cl_commission_items_sel_id, cl_commission_items.cl_commission_id, cl_commission_items.id, cl_commission_items.cl_order_id, SUM(cl_commission_items.quantity) AS quantity, cl_pricelist.id AS cl_pricelist_id, cl_pricelist.identification, cl_pricelist.item_label,'
            . 'cl_pricelist.vat, cl_pricelist.price_s, cl_pricelist.unit AS units, cl_pricelist.cl_partners_book_id,cl_pricelist.cl_partners_book_id2,cl_pricelist.cl_partners_book_id3,cl_pricelist.cl_partners_book_id4, cl_pricelist.cl_storage_id AS cl_storage_id')->
        group('cl_pricelist.id, cl_pricelist.cl_storage_id')->
        order('cl_pricelist.cl_partners_book_id ASC, cl_pricelist.identification ASC');
        return $data;
    }

    public function getMissingForOrder($id)
    {
        $data = $this->CommissionItemsManager->findAll()->where('cl_commission_items.cl_commission_id = ? AND cl_commission_items.cl_pricelist_id IS NOT NULL AND cl_commission_items.cl_order_id IS NULL', $id);
        $data = $data->where('cl_pricelist.cl_partners_book_id IS NOT NULL AND '
            . '(cl_pricelist.cl_pricelist_group.is_product = 0 OR cl_pricelist.cl_pricelist_group_id IS NULL)')->
        select('cl_commission_items.cl_commission_items_sel_id, cl_commission_items.cl_commission_id,cl_commission_items.id, cl_commission_items.cl_order_id, SUM(cl_commission_items.quantity - cl_pricelist.quantity) AS quantity, cl_pricelist.id AS cl_pricelist_id, cl_pricelist.identification, cl_pricelist.item_label,'
            . 'cl_pricelist.vat, cl_pricelist.price_s, cl_pricelist.unit AS units, cl_pricelist.cl_partners_book_id,cl_pricelist.cl_partners_book_id2,cl_pricelist.cl_partners_book_id3,cl_pricelist.cl_partners_book_id4, cl_pricelist.cl_storage_id AS cl_storage_id')->
        group('cl_pricelist.id, cl_pricelist.cl_storage_id')->
        order('cl_pricelist.cl_partners_book_id ASC, cl_pricelist.identification ASC');
        //			    where('(cl_commission_items.quantity - cl_pricelist.quantity) > 0')->
        return $data;
    }

    /** return all items from table cl_commission_items_sel without needing cl_partners_book_id
     * @param $id
     * @return Nette\Database\Table\Selection
     */
    public function getAll2ForOrder($id)
    {
        /*$data = $this->CommissionItemsManager->findAll()->where('cl_commission_items.cl_commission_id = ? ',$id);
        $data = $data->select('cl_commission_items.id, cl_commission_items.cl_order_id, (cl_commission_items.quantity) AS quantity, cl_pricelist.id AS cl_pricelist_id, cl_pricelist.identification, cl_pricelist.item_label,'
                            . 'cl_pricelist.vat, cl_pricelist.price_s, cl_pricelist.unit, NULL AS cl_partners_book_id, cl_pricelist.cl_storage_id AS cl_storage_id')->
                        group('cl_pricelist.id, cl_pricelist.cl_storage_id')->
                        order('cl_pricelist.identification ASC');*/

        $data = $this->CommissionItemsManager->findAll()->where('cl_commission_items.cl_commission_id = ? AND cl_commission_items.cl_order_id IS NULL', $id);
        $data = $data->select('cl_commission_items.cl_commission_id, cl_commission_items.id, cl_commission_items.cl_order_id, (cl_commission_items.quantity) AS quantity, cl_pricelist.id AS cl_pricelist_id,'
            . 'cl_pricelist.identification, cl_commission_items.item_label,'
            . 'cl_commission_items.vat, cl_commission_items.price_s, cl_commission_items.units AS units, NULL AS cl_partners_book_id,NULL AS cl_partners_book_id2,NULL AS cl_partners_book_id3,NULL AS cl_partners_book_id4, cl_pricelist.cl_storage_id AS cl_storage_id')->
        order('cl_commission_items.item_order ASC');

        return $data;
    }

    public function createOut($id, $arrDataItemsSel, $arrDataItems, $arrDataItemsProduction = [])
    {
        $docId = NULL;
        //$tmpStoreDoc = NULL;
        $counter = 0;
        foreach ($arrDataItemsSel as $key => $one) {
            $commissionItem = $this->CommissionItemsSelManager->find($one);
            if ($commissionItem) {
                if (!is_null($commissionItem->cl_pricelist_id) && !is_null($this->settings->cl_storage_id_commission)) {
                    $tmpData = $this->CommissionItemsSelManager->findAll()->where('cl_commission_id = ?', $id);
                    
                if (!is_null($commissionItem->cl_pricelist['cl_storage_id'])) //TH 24.03.2023 if storage is set in pricelist, use it
                    $commissionItem->update(['cl_storage_id' => $commissionItem->cl_pricelist['cl_storage_id']]);
                else
                    $commissionItem->update(['cl_storage_id' => $this->settings->cl_storage_id_commission]);

                    
                //$tmpData->update(['cl_storage_id' => $this->settings->cl_storage_id_commission]);

                    //commission items - give out
                    if ($counter == 0) {
                        $tmpNew = TRUE;
                    } else {
                        $tmpNew = FALSE;
                    }
                    //$docId = $this->StoreDocsManager->createStoreDoc(1, $id, $this->DataManager, $tmpNew);
                    $docId = $this->StoreDocsManager->createStoreDoc(1, $id, $this, $tmpNew);
                    $this->StoreDocsManager->update(['id' => $docId, 'doc_title' => 'prodejní položky zakázky', 'cl_storage_id' => $this->settings->cl_storage_id_commission]);

                    //update cl_commission.cl_store_docs_id with current cl_store_docs_id
                    $commissionItem->cl_commission->update(['cl_store_docs_id' => $docId]);

                    //store doc is created from commission, we need to update cl_invoice_id too
                    $this->StoreDocsManager->update(['id' => $docId, 'cl_commission_id' => $commissionItem->cl_commission_id]);
                    $commissionItem->update(['cl_store_docs_id' => $docId]);

                    //2. giveout current item
                    $dataId = $this->StoreManager->giveOutItem($docId, $commissionItem->id, $this->CommissionItemsSelManager);

                    //create pairedocs record with created cl_store_docs_id
                    $this->PairedDocsManager->insertOrUpdate(['cl_commission_id' => $id, 'cl_store_docs_id' => $docId]);
                    $counter++;
                }
            }
        }
        $counter = 0;
        foreach ($arrDataItems as $key => $one) {
            $commissionItem = $this->CommissionItemsManager->find($one);
            if ($commissionItem) {
                if (!is_null($commissionItem->cl_pricelist_id) && !is_null($this->settings->cl_storage_id_commission)) {
                    $tmpData = $this->CommissionItemsManager->findAll()->
                                                where('cl_commission_id = ?', $id);

                    if (!is_null($commissionItem->cl_pricelist['cl_storage_id'])) //TH 24.03.2023 if storage is set in pricelist, use it
                        $commissionItem->update(['cl_storage_id' => $commissionItem->cl_pricelist['cl_storage_id']]);
                    else
                        $commissionItem->update(['cl_storage_id' => $this->settings->cl_storage_id_commission]);

                            
                    //$tmpData->update(['cl_storage_id' => $this->settings->cl_storage_id_commission]);

                    //commission items - give out
                    if ($counter == 0) {
                        $tmpNew = TRUE;
                    } else {
                        $tmpNew = FALSE;
                    }
                    //$docId = $this->StoreDocsManager->createStoreDoc(1, $id, $this->DataManager, $tmpNew);
                    $docId = $this->StoreDocsManager->createStoreDoc(1, $id, $this, $tmpNew);
                    $this->StoreDocsManager->update(['id' => $docId, 'doc_title' => 'nákladové položky zakázky']);

                    //update cl_commission.cl_store_docs_id with current cl_store_docs_id
                    $commissionItem->cl_commission->update(['cl_store_docs_id' => $docId]);

                    //store doc is created from commission, we need to update cl_invoice_id too
                    $this->StoreDocsManager->update(['id' => $docId, 'cl_commission_id' => $commissionItem->cl_commission_id]);
                    $commissionItem->update(array('cl_store_docs_id' => $docId));

                    //2. giveout current item
                    $dataId = $this->StoreManager->giveOutItem($docId, $commissionItem->id, $this->CommissionItemsManager);

                    //create pairedocs record with created cl_store_docs_id
                    $this->PairedDocsManager->insertOrUpdate(['cl_commission_id' => $id, 'cl_store_docs_id' => $docId]);
                    $counter++;
                }
            }
        }
        $counter = 0;
        foreach ($arrDataItemsProduction as $key => $one) {
            $commissionItem = $this->CommissionItemsProductionManager->find($one);
            if ($commissionItem) {
                if (!is_null($commissionItem->cl_commission_items_sel->cl_pricelist_id) && !is_null($this->settings->cl_storage_id_commission)) {
                    $tmpData = $this->CommissionItemsProductionManager->findAll()->where('cl_commission_id = ?', $id);
                    
                    if (!is_null($commissionItem->cl_pricelist['cl_storage_id'])) //TH 24.03.2023 if storage is set in pricelist, use it
                        $commissionItem->update(['cl_storage_id' => $commissionItem->cl_pricelist['cl_storage_id']]);
                    else
                        $commissionItem->update(['cl_storage_id' => $this->settings->cl_storage_id_commission]);


                    //$tmpData->update(['cl_storage_id' => $this->settings->cl_storage_id_commission]);
                    
                    
                    //commission items - give out
                    $tmpNew = $counter == 0;
                    $docId = $this->StoreDocsManager->createStoreDoc(1, $id, $this, $tmpNew);
                    $this->StoreDocsManager->update(['id' => $docId, 'doc_title' => 'výrobní položky zakázky', 'cl_storage_id' => $this->settings->cl_storage_id_commission]);

                    //update cl_commission.cl_store_docs_id with current cl_store_docs_id
                    //$commissionItem->cl_commission->update(['cl_store_docs_id' => $docId]);

                    //store doc is created from commission, we need to update cl_invoice_id too
                    $this->StoreDocsManager->update(['id' => $docId, 'cl_commission_id' => $commissionItem->cl_commission_id]);
                    $commissionItem->update(['cl_store_docs_id' => $docId]);

                    //2. giveout current item
                    /*dump($docId);
                    dump($commissionItem->id);
                    dump($this->CommissionItemsProductionManager);
                    die;*/
                    $dataId = $this->StoreManager->giveOutItem($docId, $commissionItem->id, $this->CommissionItemsProductionManager);

                    //create pairedocs record with created cl_store_docs_id
                    $this->PairedDocsManager->insertOrUpdate(['cl_commission_id' => $id, 'cl_store_docs_id' => $docId]);
                    $counter++;
                }
            }
        }



        //update storedocs_id in commission
        $this->update(array('id' => $id, 'cl_store_docs_id' => $docId));

        return $docId;
    }

    /**sync all active eshops
     * @return array
     */
    public function syncEshops(): array
    {
        $tmpData = $this->EshopsManager->findAll()->where('sync_activ = 1');
        $i = 0;
        $errorMsg = '';
        $arrRet = [];
        foreach ($tmpData as $key => $one) {
            if ($one['local_file'] == 0 && $one['eshop_type'] != 2) {
                Debugger::log('url:' . $one['url'] . '\n\n Import script: ' . $one['import_script'] . '\n\n Token: ' . $one['token'], 'commission');
                $xml = $this->downloadXML($one['url'], $one['import_script'], $one['token']);
                Debugger::log('downloadXML: \n\n' . $xml, 'commission');
            } else {
                $xml = NULL;
            }

            // dump($xml);
            // die;
            if ($one['eshop_type'] == 0 && !is_null($xml)) {
                $arrRet = $this->import2HCS30($xml, $one);
                if ($one['confirm_disabled'] == 0) {
                    $this->confirm2HCS30($one, $arrRet);
                }
                $i += count($arrRet);
            } elseif ($one['eshop_type'] == 1 && !is_null($xml)) {
                $arrRet = $this->importPohoda20($xml, $one);
                $i += count($arrRet);
            } elseif ($one['eshop_type'] == 2) {
                $xml = $this->simpleStoreSync('', $one);
                $ret = $this->downloadXML($one['url'], $one['import_script'], $one['token'], [$xml]);
                if ($ret != '<OK>') {
                    $i = NULL;
                    $errorMsg = "Chyba_při_odesílání_stavů_skladu";
                }

                //dump($ret);
                //die;
                //$i = count($arrRet);
                //$xml = $this->downloadXML($one['url'], $one['import_script'], $one['token']);
            }
            if (!is_null($i)) {
                $arrRet['success'] = 'EshopSyncResult2';
                $arrRet['counter'] = $i;
            } else {
                $arrRet['error'] = $errorMsg;
                $arrRet['eshop_name'] = $one['name'];
            }
        }
        //$arrRet['success'] = 'Dodací_list_byl_vytvořen';
        //$arrRet['deliveryN_id'] = $deliveryId;
        //
        //$arrRet['error'] = 'Dodací_list_nebyl_vytvořen';

        return $arrRet;
    }


    private function simpleStoreSync($xml, $one)
    {
        //prepare store data
        $tmpPricelist = $this->PriceListManager->findAll()->where('not_active = 0');
        $xml .= '<?xml version = "1.0" encoding="windows-1250" standalone="yes"?>';
        $xml .= '<VFPDataSet xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
        foreach ($tmpPricelist as $key => $one) {
            $xml .= '<tbl_cenik>';
            $xml .= '<ident_ci>' . $one['identification'] . '</ident_ci>';
            $xml .= '<mj_astav>' . $one['quantity'] . '</mj_astav>';
            $xml .= '</mj_bstav>';
            foreach ($one->related('cl_store')->select('SUM(quantity) AS quantity, cl_storage.name AS name')->group('cl_store.cl_pricelist_id,cl_store.cl_storage_id') as $key2 => $one2) {
                $xml .= '<store>';
                $xml .= '<store_id>' . $one2['name'] . '</store_id>';
                $xml .= '<quantity>' . $one2['quantity'] . '</quantity>';
                $xml .= '</store>';
            }
            $xml .= '</tbl_cenik>';
        }
        $xml .= '</VFPDataSet>';
        Debugger::log('simpleStoreSync: \n\n' . $xml);
        return $xml;
    }

    /**
     * @param $eshop
     * @param $arrCom
     */
    public function confirm2HCS30($eshop, $arrCom)
    {
        //dump($arrCom);
        foreach ($arrCom as $one) {

            $confirm_script = $eshop['confirm_script'];
            //dump($one);
            $confirm_script = str_ireplace('id_val', $one, $confirm_script);
            //dump($confirm_script);
            //die;
            $xml = $this->downloadXML($eshop['url'], $confirm_script, $eshop['token']);
        }

    }

    /**import commissions from XML in 2HCS30 format
     * @param $xml
     * @return array
     */
    public function import2HCS30($xml, $cl_eshops): array
    {
        $count = 0;
        //bdump($xml);
        $ob = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        $arrRetCom = [];
        $maxCount = count($ob);
        foreach ($ob as $oneCom) {
            $this->userManager->setProgressBar($count, $maxCount, $this->user->getId(), 'Zakázky z eshopu');
            $arrComm = array();
            $arrComm['cl_eshops_id'] = $cl_eshops->id;
            $arrComm['cl_storage_id'] = $cl_eshops->cl_storage_id;
            $arrComm['cl_center_id'] = $cl_eshops->cl_center_id;
            $arrComm['cm_number'] = (string)((!isset($oneCom->cis_obj)) ? $oneCom->cis_zint : $oneCom->cis_obj);
            if (empty($arrComm['cm_number'])){
               //bdump(empty($oneCom->cis_zak));
                //bdump(empty($oneCom->cis_obj));
                $arrComm['cm_number'] = (string)((!empty($oneCom->cis_zak)) ? $oneCom->cis_zak : $oneCom->cis_obj);
            }

            $arrComm['cm_date'] = (string)$oneCom->dat_vyt;
            $arrComm['cm_time'] = (string)$oneCom->cas_vyt;
            $arrComm['price_e2'] = (float)$oneCom->z_pcena;
            $arrComm['price_e2_vat'] = (float)$oneCom->z_pcenas;
            $arrComm['vat'] = $this->settings->def_sazba;
            $arrComm['cm_title'] = (string)$oneCom->zakl_txt3;
            //bdump($oneCom);
            //bdump($arrComm);
            //die;
            //bdump($oneCom['mena']);

            //status
            $tmpStatus = $this->StatusManager->findAll()->where('s_eshop = 1')->fetch();
            if ($tmpStatus) {
                $arrComm['cl_status_id'] = $tmpStatus->id;
            }

            //currency
            $tmpCurrency_name = (string)$oneCom->mena;
            $tmpCurrency = $this->CurrenciesManager->findAll()->where('currency_name = ?', $tmpCurrency_name)->fetch();
            if (!$tmpCurrency) {
                $tmpCurrency_id = $this->settings->cl_currencies_id;
                $tmpCurrency_rate = 1;
            } else {
                $tmpCurrency_id = $tmpCurrency->id;
                $tmpCurrency_rate = $tmpCurrency['fix_rate'];
            }
            $arrComm['cl_currencies_id'] = $tmpCurrency_id;
            $arrComm['currency_rate'] = $tmpCurrency_rate;

            //payment
            $tmpPayment_name = (string)$oneCom->platba;
            $tmpPayment = $this->PaymentTypesManager->findAll()->where('name = ?', $tmpPayment_name)->fetch();
            if (!$tmpPayment) {
                $tmpPayment_id = $this->settings->cl_payment_types_id;
            } else {
                $tmpPayment_id = $tmpPayment->id;
            }
            $arrComm['cl_payment_types_id'] = $tmpPayment_id;

            //transport
            $tmpTransport_name = (string)$oneCom->preprava;


            //**12.11.2020 - LUMIKA úprava
            //IF LEFT(tbl_zakazky.preprava,23) = "ParcelShop - ParcelShop"
            //replace tbl_zakazky.u_dbranch	WITH STREXTRACT(tbl_zakazky.preprava,"(",")")
            //	replace tbl_zakazky.zakl_txt3	WITH tbl_zakazky.zakl_txt3+CHR(13)+"<preprava>" + ALLTRIM(tbl_zakazky.preprava) + "</preprava>"
            //	replace tbl_zakazky.preprava	WITH "ParcelShop"
            //ENDIF
            $arrComm['u_dbranch'] = '';
            //18.01.2022 - LUMIKA úprava
            if (substr($tmpTransport_name,0,23) == 'ParcelShop - ParcelShop') {
                $arrTransport = explode('(', $tmpTransport_name);
                //bdump($arrTransport);
                $strParcelShopId = end($arrTransport);
                //bdump($strParcelShopId);
                $arrComm['u_dbranch'] = str_replace(')', '', $strParcelShopId);
                $arrComm['description_txt'] = '<preprava>' . $tmpTransport_name . '</preprava>';
                $tmpTransport_name = 'ParcelShop';
                if (is_null($arrComm['u_dbranch'])){
                    $arrComm['u_dbranch'] = '';
                }
            }

            $tmpTransport = $this->TransportTypesManager->findAll()->where('name = ?', $tmpTransport_name)->fetch();
            if (!$tmpTransport) {
                //$tmpTransport_id = $this->settings->cl_payment_types_id;
                $tmpTransport = $this->TransportTypesManager->findAll()->limit(1)->fetch();
                $tmpTransport_id = $tmpTransport->id;
            } else {
                $tmpTransport_id = $tmpTransport->id;
            }
            $arrComm['cl_transport_types_id'] = $tmpTransport_id;

            //partner
            $tmpPartner_name = (string)$oneCom->nazev;
            //19.01.2022 - cl_countries
            $tmpCountry = $this->CountriesManager->findAllTotal()->
                                where('country_name = ? OR acronym = ? OR name = ?',
                                        (string)$oneCom->stat,  (string)$oneCom->stat, (string)$oneCom->stat)->
                                limit(1)->
                                fetch();
            if ($tmpCountry){
                $cl_countries_id = $tmpCountry['id'];
            }else{
                $cl_countries_id = $this->settings->cl_countries_id;
            }

            if (isset($oneCom->nazev2) && (string)$oneCom->nazev2 != '') {
                //company
                $strCompany = (string)$oneCom->nazev2;
                $strPerson  = (string)$oneCom->nazev;
            }else {
                //person
                $strCompany = (string)$oneCom->nazev;
                $strPerson = '';
            }

            $partnerData = ['company' => $strCompany, 'street' => (string)$oneCom->ulice,
                            'zip' => (string)$oneCom->psc, 'city' => (string)$oneCom->mesto,
                            'ico' => (string)$oneCom->ico, 'dic' => (string)$oneCom->dic,
                            'cl_countries_id' => $cl_countries_id,
                            'partner_code' => (string)$oneCom->ref_num,
                            'person' => $strPerson,
                            'email' => (string)$oneCom->email,
                            'phone' => (string)$oneCom->telefon];



            if ($oneCom->ref_num != "") {
                $tmpPartner = $this->PartnersManager->findAll()->where('partner_code = ?', (string)$oneCom->ref_num)->fetch();
            } elseif ($oneCom['ico'] != "") {
                $tmpPartner = $this->PartnersManager->findAll()->where('ico = ?', (string)$oneCom['ico'])->fetch();
            } else {
                $tmpPartner = $this->PartnersManager->findAll()->where('company = ?', $tmpPartner_name)->fetch();
            }

            //if (!$tmpPartner)
            //{
            if (!$tmpPartner) {
                //insert new
                $partnerData['supplier'] = 0;
                $newPartner = $this->PartnersManager->insert($partnerData);
                $tmpPartner_id = $newPartner->id;
            } else {
                $tmpPartner->update($partnerData);
                $tmpPartner_id = $tmpPartner->id;
            }
            // }else{
            //     $tmpPartner->update($partnerData);
            //     $tmpPartner_id = $tmpPartner->id;
            // }
            $arrComm['cl_partners_book_id'] = $tmpPartner_id;


            //branch
            if (isset($oneCom->k_nazev2) && (string)$oneCom->k_nazev2 != '') {
                //company
                $strCompany = (string)$oneCom->k_nazev2;
                $strPerson  = (string)$oneCom->k_nazev;
            }else {
                //person
                $strCompany = (string)$oneCom->k_nazev;
                $strPerson = '';
            }


            $tmpBranch_name = (string)$oneCom->k_nazev;
            $branchData = ['cl_partners_book_id' => $tmpPartner_id,
                            'b_name' => $strCompany,
                            'b_person' => $strPerson,
                            'b_street' => (string)$oneCom->k_ulice,
                            'b_zip' => (string)$oneCom->k_psc,
                            'b_city' => (string)$oneCom->k_mesto];

            if ($tmpBranch_name != ''){
                $tmpBranch = $this->PartnersBranchManager->findAll()->where('b_name = ?', $tmpBranch_name)->fetch();
                if (!$tmpBranch ) {
                    //insert new
                    $newBranch = $this->PartnersBranchManager->insert($branchData);
                    $tmpBranch_id = $newBranch->id;
                } else {
                    $tmpBranch->update($branchData);
                    $tmpBranch_id = $tmpBranch->id;
                }
                $arrComm['cl_partners_branch_id'] = $tmpBranch_id;
            }


            //person
            $tmpPerson_name = (string)$oneCom->osoba;
            if ($tmpPerson_name != '') {
                $personData = ['cl_partners_book_id' => $tmpPartner_id, 'worker_name' => (string)$oneCom->osoba, 'worker_phone' => (string)$oneCom->telefon,
                    'worker_email' => (string)$oneCom->email, 'cl_partners_branch_id' => $tmpBranch_id];
                $tmpWorker = $this->PartnersBookWorkersManager->findAll()->where('worker_name = ? AND cl_partners_book_id = ?', $tmpPerson_name, $tmpPartner_id)->fetch();
                if (!$tmpWorker) {
                    //insert new
                    $newWorker = $this->PartnersBookWorkersManager->insert($personData);
                    $tmpWorker_id = $newWorker->id;
                } else {
                    $tmpWorker->update($personData);
                    $tmpWorker_id = $tmpWorker->id;
                }
                $arrComm['cl_partners_book_workers_id'] = $tmpWorker_id;
            }




            $tmpComm_id = $this->insert($arrComm);

            $this->PartnersManager->useHeaderFooter($tmpComm_id, $arrComm['cl_partners_book_id'], $this);

            $count++;

            //items
            $order = 1;
            foreach ($oneCom->tbl_zakobs as $key => $oneItem) {
                $tmpItem = $this->PriceListManager->findAll()->where('identification = ?', (string)$oneItem->ident_ci)->fetch();
                $arrPricelist = ['identification' => (string)$oneItem->ident_ci, 'item_label' => (string)$oneItem->nazev, 'vat' => (float)$oneItem->sazba,
                                    'price' => (float)$oneItem->pcena_mj, 'price_vat' => (float)$oneItem->pscena_mj,
                                    'cl_currencies_id' => $tmpCurrency_id];
                if (!$tmpItem) {
                    $newItem = $this->PriceListManager->insert($arrPricelist);
                    $tmpItem_id = $newItem->id;
                    $tmpPriceS = 0;
                } else {
                    if ($cl_eshops['not_update_pricelist'] == 0) {
                        $arrPricelist['id'] = $tmpItem->id;
                        $this->PriceListManager->update($arrPricelist);
                    }
                    $tmpItem_id     = $tmpItem->id;
                    $tmpPriceS      = $tmpItem->price_s;
                }
                $arrItem = ['cl_commission_id' => $tmpComm_id, 'cl_pricelist_id' => $tmpItem_id, 'item_order' => $order++, 'quantity' => (float)$oneItem->pocet_mj, 'vat' => (float)$oneItem->sazba,
                                'item_label' => (string)$oneItem->nazev];

                if ($this->settings->price_e_type == 0) {
                    $arrItem['price_e'] = (float)$oneItem->pcena_mj;
                } else {
                    $arrItem['price_e'] = (float)$oneItem->pscena_mj;
                }
                $arrItem['price_e_type'] = $this->settings->price_e_type;
                $arrItem['price_e2'] = (float)$oneItem->pcena_mj * $arrItem['quantity'];
                $arrItem['price_e2_vat'] = (float)$oneItem->pscena_mj * $arrItem['quantity'];

                $arrItem['price_s'] = $tmpPriceS;
                if ($tmpPriceS > 0) {
                    $arrItem['profit'] = ($arrItem['price_e'] / $tmpPriceS - 1) * 100;
                } else {
                    $arrItem['profit'] = 100;
                }

                $this->CommissionItemsSelManager->insert($arrItem);
            }

            //transport as item   d_zak
            if ($tmpTransport && !is_null($tmpTransport->cl_pricelist_id)) {
                $arrItem = ['cl_commission_id' => $tmpComm_id, 'cl_pricelist_id' => $tmpTransport->cl_pricelist_id, 'item_order' => $order++, 'quantity' => 1, 'vat' => $tmpTransport->cl_pricelist['vat'],
                    'item_label' => $tmpTransport->cl_pricelist['item_label']];

                if ($this->settings->price_e_type == 1) {
                    $arrItem['price_e'] = (float)$oneCom->d_zak;
                    $arrItem['price_e2'] = (float)$arrItem['price_e'] / (1 + ($tmpTransport->cl_pricelist['vat'] / 100));
                    $arrItem['price_e2_vat'] = (float)$arrItem['price_e'];
                } else {
                    $arrItem['price_e'] = (float)$oneCom->d_zak / (1 + ($tmpTransport->cl_pricelist['vat'] / 100));
                    $arrItem['price_e2'] = (float)$arrItem['price_e'];
                    $arrItem['price_e2_vat'] = (float)$arrItem['price_e'] * (1 + ($tmpTransport->cl_pricelist['vat'] / 100));
                }
                $arrItem['price_e_type'] = $this->settings->price_e_type;
                $this->CommissionItemsSelManager->insert($arrItem);
            }

            //payment as item
            if ($tmpPayment && !is_null($tmpPayment->cl_pricelist_id)) {
                $arrItem = ['cl_commission_id' => $tmpComm_id, 'cl_pricelist_id' => $tmpPayment->cl_pricelist_id, 'item_order' => $order++, 'quantity' => 1, 'vat' => $tmpPayment->cl_pricelist['vat'],
                    'item_label' => $tmpPayment->cl_pricelist['item_label']];

                if ($this->settings->price_e_type == 1) {
                    $arrItem['price_e'] = (float)$oneCom->balne_zak;
                    $arrItem['price_e2'] = (float)$arrItem['price_e'] / (1 + ($tmpTransport->cl_pricelist['vat'] / 100));
                    $arrItem['price_e2_vat'] = (float)$arrItem['price_e'];
                } else {
                    $arrItem['price_e'] = (float)$oneCom->balne_zak / (1 + ($tmpTransport->cl_pricelist['vat'] / 100));
                    $arrItem['price_e2'] = (float)$arrItem['price_e'];
                    $arrItem['price_e2_vat'] = (float)$arrItem['price_e'] * (1 + ($tmpTransport->cl_pricelist['vat'] / 100));
                }
                $arrItem['price_e_type'] = $this->settings->price_e_type;
                $this->CommissionItemsSelManager->insert($arrItem);
            }

            //$arrRetCom[] = (string)!isset($oneCom->cis_obj) ? $oneCom->cis_zint : $oneCom->cis_obj;
            $arrRetCom[] = (string)(empty($oneCom->cis_obj) ? $oneCom->cis_zint : $oneCom->cis_obj);
        }
        $this->userManager->resetProgressBar($this->user->getId());
        //bdump($arrRetCom);
        //die;
        return $arrRetCom;
    }

    /** download file from given URL, credential is in token and values contain array of values to POST
     * @param $url
     * @param $token
     * @param array $values
     * @return string
     */
    private function downloadXML($url, $script, $token, $values = array()): string
    {
        if (substr($url, -1) != '/') {
            $url .= "/";
        }
        $url = $url . $token . $script;
        //dump($url);
        $params = http_build_query($values);
        // Initiate the curl session
        $ch = curl_init();
        //dump($ch);
        // Set the URL
        curl_setopt($ch, CURLOPT_URL, $url);
        if (count($values) > 0) {
            // Set post params if there are any
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        // Removes the headers from the output
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // Return the output instead of displaying it directly
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // Execute the curl session
        $output = curl_exec($ch);
        // Close the curl session
        curl_close($ch);
        // Return the output as a variable
        return $output;
    }


    /**import commissions from XML in Pohoda format
     * @param $xml
     * @return array
     */
    public function importPohoda20($xml, $cl_eshops = NULL): array
    {
        $count = 0;
        $ob = simplexml_load_string($xml);
        $arrRetCom = [];
        $orders = $ob->children('dat', true);
        $maxCount = count($orders);

        foreach ($orders as $key => $one) {
            $this->userManager->setProgressBar($count, $maxCount, $this->user->getId(), 'Zakázky z XML');
            $oneOrder = $one->children('ord', true);
            $orderHeader = ($oneOrder->order->orderHeader);
            $partnerIdentity = $orderHeader->partnerIdentity->children('typ', true);
            $paymentType = $orderHeader->paymentType->children('typ', true);
            $arrImport = ['orderHeader' => $orderHeader,
                'address' => $partnerIdentity->address,
                'shipToAddress' => $partnerIdentity->shipToAddress,
                'paymentType' => $paymentType,
                'cl_eshops' => $cl_eshops];

            $commId = $this->importHeader($arrImport);
            $tmpData = $this->find($commId);
            $orderDetails = ($oneOrder->order->orderDetail);
            $orderItems = $orderDetails->orderItem;
            $order = 1;
            foreach ($orderItems as $key2 => $one2) {
                $currency = $one2->homeCurrency->children('typ', true);
                $stock = $one2->stockItem->children('typ', true);
                $arrImportItem = ['order' => $order++,
                    'parent' => $tmpData,
                    'item' => $one2,
                    'currency' => $currency,
                    'stock' => $stock];
                $this->importItem($arrImportItem);
            }

            $arrRetCom[] = $commId;
            $this->updateSum($commId);
        }
        $this->userManager->resetProgressBar($this->user->getId());

        return $arrRetCom;
    }

    /**Partial method for process import from PohodaXML
     * @param $arrData
     */
    private function importItem($arrData)
    {
        //items
        //find in pricelist
        $tmpIdentification = (string)$arrData['stock']->stockItem->ids;
        $tmpItem = $arrData['item'];
        $tmpPrice = (float)$arrData['currency']->unitPrice;
        $tmpParent = $arrData['parent'];

        $arrVat = $this->ratesVatManager->findAllValid($tmpParent['cm_date'])->fetchPairs('code_name', 'rates');
        $tmpVat = (array_key_exists((string)$tmpItem->rateVAT, $arrVat)) ? $arrVat[(string)$tmpItem->rateVAT] : 0;
        $tmpPriceVat = $tmpPrice * (1 + $tmpVat / 100);

        if (!empty($tmpIdentification)) {
            $tmpPricelist = $this->PriceListManager->findAll()->where('identification = ?', $tmpIdentification)->fetch();
            if (!$tmpPricelist) {
                $arrPricelist = ['identification' => (string)$tmpIdentification, 'item_label' => (string)$tmpItem->text, 'vat' => $tmpVat,
                    'price' => $tmpPrice, 'price_vat' => (float)$tmpPriceVat,
                    'cl_currencies_id' => $tmpParent['cl_currencies_id']];
                $newItem = $this->PriceListManager->insert($arrPricelist);
                $tmpItem_id = $newItem->id;
                $tmpPriceS = 0;
            } else {
                $tmpItem_id = $tmpPricelist->id;
                $tmpPriceS = $tmpPricelist->price_s;
            }
        } else {
            $tmpItem_id = NULL;
            $tmpPriceS = 0;
        }
        $arrItem = ['cl_commission_id' => $tmpParent['id'],
            'cl_pricelist_id' => $tmpItem_id,
            'item_order' => $arrData['order'],
            'quantity' => (float)$tmpItem->quantity,
            'vat' => (float)$tmpVat,
            'item_label' => (string)$tmpItem->text];
        if ($this->settings->price_e_type == 0) {
            $arrItem['price_e'] = (float)$tmpPrice;
        } else {
            $arrItem['price_e'] = (float)$tmpPriceVat;
        }
        $arrItem['price_e2'] = (float)$tmpPrice * $arrItem['quantity'];
        $arrItem['price_e2_vat'] = (float)$tmpPriceVat * $arrItem['quantity'];

        $arrItem['price_s'] = $tmpPriceS;
        if ($tmpPriceS > 0) {
            $arrItem['profit'] = ($arrItem['price_e'] / $tmpPriceS - 1) * 100;
        } else {
            $arrItem['profit'] = 100;
        }

        $this->CommissionItemsSelManager->insert($arrItem);

    }


    /**Partial method for process import from PohodaXML
     * return inserted activerow
     * @param $arrData
     * @return Nette\Database\Table\ActiveRow
     */
    private function importHeader($arrData)
    {
        $arrComm = array();
        if (!is_null($arrData['cl_eshops'])) {
            $arrComm['cl_eshops_id'] = $arrData['cl_eshops']->id;
            $arrComm['cl_storage_id'] = $arrData['cl_eshops']->cl_storage_id;
            $arrComm['cl_center_id'] = $arrData['cl_eshops']->cl_center_id;
        }
        $tmpHeader = $arrData['orderHeader'];
        $tmpPayment = $arrData['paymentType'];
        $tmpAddress = $arrData['address'];
        $tmpShipToAddress = $arrData['shipToAddress'];
        $arrComm['cm_number'] = (string)$tmpHeader->numberOrder;
        $arrComm['cm_date'] = (string)$tmpHeader->date;
        $arrComm['vat'] = $this->settings->def_sazba;
        $arrComm['cm_title'] = (string)$tmpHeader->intNote;

        //status
        $tmpStatus = $this->StatusManager->findAll()->where('s_eshop = 1')->fetch();
        if ($tmpStatus) {
            $arrComm['cl_status_id'] = $tmpStatus->id;
        }

        //currency
        $tmpCurrency_id = $this->settings->cl_currencies_id;
        $tmpCurrency_rate = 1;
        $arrComm['cl_currencies_id'] = $tmpCurrency_id;
        $arrComm['currency_rate'] = $tmpCurrency_rate;

        //payment
        $tmpPayment_name = (string)$tmpPayment->ids;
        $tmpPayment = $this->PaymentTypesManager->findAll()->where('name = ?', $tmpPayment_name)->fetch();
        if (!$tmpPayment) {
            $tmpPayment_id = $this->settings->cl_payment_types_id;
        } else {
            $tmpPayment_id = $tmpPayment->id;
        }
        $arrComm['cl_payment_types_id'] = $tmpPayment_id;

        //transport
        //$tmpTransport_name = (string) $oneCom->preprava;
        //$tmpTransport = $this->TransportTypesManager->findAll()->where('name = ?', $tmpTransport_name)->fetch();
        //if (!$tmpTransport){
        //    //$tmpTransport_id = $this->settings->cl_payment_types_id;
        //    $tmpTransport = $this->TransportTypesManager->findAll()->limit(1)->fetch();
        //    $tmpTransport_id = $tmpTransport->id;
        //}else{
        //    $tmpTransport_id = $tmpTransport->id;
        //}
        //$arrComm['cl_transport_types_id'] = $tmpTransport_id;

        //partner
        if (empty($tmpAddress->company)) {
            $tmpPartner_name = (string)$tmpAddress->name;
        } else {
            $tmpPartner_name = (string)$tmpAddress->company;
        }

        $partnerData = ['company' => (string)$tmpPartner_name, 'street' => (string)$tmpAddress->street,
            'zip' => (string)$tmpAddress->zip, 'city' => (string)$tmpAddress->city,
            'ico' => (string)$tmpAddress->ico, 'dic' => (string)$tmpAddress->dic,
            'email' => (string)$tmpAddress->email,
            'phone' => (string)$tmpAddress->phone];

        if ($tmpAddress->ico != "") {
            $tmpPartner = $this->PartnersManager->findAll()->where('ico = ?', (string)$tmpAddress->ico)->fetch();
        } else {
            $tmpPartner = $this->PartnersManager->findAll()->where('company = ?', $tmpPartner_name)->fetch();
        }

        if (!$tmpPartner) {
            //insert new
            $partnerData['supplier'] = 0;
            $newPartner = $this->PartnersManager->insert($partnerData);
            $tmpPartner_id = $newPartner->id;
        } else {
            $tmpPartner->update($partnerData);
            $tmpPartner_id = $tmpPartner->id;
        }
        $arrComm['cl_partners_book_id'] = $tmpPartner_id;

        //branch
        if ($tmpAddress != $tmpShipToAddress) {
            if (!empty($tmpShipToAddress->company))
                $tmpBranch_name = (string)$tmpShipToAddress->company;
            else
                $tmpBranch_name = (string)$tmpShipToAddress->name;

            $branchData = ['cl_partners_book_id' => $tmpPartner_id, 'b_name' => (string)$tmpBranch_name, 'b_street' => (string)$tmpShipToAddress->street,
                'b_zip' => (string)$tmpShipToAddress->zip, 'b_city' => (string)$tmpShipToAddress->city];
            $tmpBranch = $this->PartnersBranchManager->findAll()->where('b_name = ?', $tmpBranch_name)->fetch();

            if (!$tmpBranch) {
                //insert new
                $newBranch = $this->PartnersBranchManager->insert($branchData);
                $tmpBranch_id = $newBranch->id;
            } else {
                $tmpBranch->update($branchData);
                $tmpBranch_id = $tmpBranch->id;
            }
            $arrComm['cl_partners_branch_id'] = $tmpBranch_id;
        }

        //person
        //$tmpPerson_name = (string) $oneCom->osoba;
        //$personData = [ 'cl_partners_book_id' => $tmpPartner_id, 'worker_name' => (string) $oneCom->osoba, 'worker_phone' => (string) $oneCom->telefon,
        //    'worker_email' => (string) $oneCom->email];
        //$tmpWorker = $this->PartnersBookWorkersManager->findAll()->where('worker_name = ?', $tmpPerson_name )->fetch();
        //if (!$tmpWorker)
        //{
        //insert new
        //    $newWorker = $this->PartnersBookWorkersManager->insert($personData);
        //    $tmpWorker_id = $newWorker->id;
        //}else{
        //    $tmpWorker->update($personData);
        //    $tmpWorker_id = $tmpWorker->id;
        //}
        //$arrComm['cl_partners_book_workers_id'] = $tmpWorker_id;
        $tmpComm_id = $this->insert($arrComm);

        $this->PartnersManager->useHeaderFooter($tmpComm_id, $arrComm['cl_partners_book_id'], $this);

        return $tmpComm_id;
    }


    public function importShop5()
    {

    }


    public function CreateInvoice2($dataItems, $dataWorks, $id, $newInvoice)
    {
        //bdump($dataItems, $dataWorks);
        $docId = null;
        $arrRet = [];
        try {
            if ($tmpData = $this->find($id)) {
                if ($tmpInvoiceType = $this->InvoiceTypesManager->findAll()->where('default_type = ?', 1)->fetch()) {
                    $tmpInvoiceType = $tmpInvoiceType->id;
                } else {
                    $tmpInvoiceType = NULL;
                }
                //default values for invoice
                $defDueDate = new \Nette\Utils\DateTime;
                $arrInvoice = [];
                $arrInvoice['cl_currencies_id'] = $this->settings->cl_currencies_id;
                $arrInvoice['currency_rate'] = $this->settings->cl_currencies->fix_rate;
                $arrInvoice['vat_active'] = $this->settings->platce_dph;

                $arrInvoice['cl_partners_book_id'] = $tmpData->cl_partners_book_id;
                $arrInvoice['cl_partners_book_workers_id'] = $tmpData->cl_partners_book_workers_id;
                $arrInvoice['cl_partners_branch_id'] = $tmpData->cl_partners_branch_id;
                $arrInvoice['cl_company_branch_id'] = $tmpData->cl_company_branch_id;
                $arrInvoice['cl_currencies_id'] = $tmpData->cl_currencies_id;
                $arrInvoice['currency_rate'] = $tmpData->currency_rate;
                $arrInvoice['cl_users_id'] = $tmpData->cl_users_id;
                $arrInvoice['inv_title'] = $tmpData->cm_title;
                $arrInvoice['cl_commission_id'] = $tmpData->id;
                $arrInvoice['price_e_type'] = $tmpData->price_e_type;
                $arrInvoice['inv_date'] = new \Nette\Utils\DateTime;
                if (!is_null($tmpData->delivery_date))
                    $arrInvoice['vat_date'] = $tmpData->delivery_date;
                else
                    $arrInvoice['vat_date'] = new \Nette\Utils\DateTime;

                $arrInvoice['od_number'] = $tmpData->cm_order;
                $arrInvoice['cm_number'] = $tmpData->cm_number;

                $arrInvoice['konst_symb'] = $this->settings->konst_symb;
                $arrInvoice['cl_invoice_types_id'] = $tmpInvoiceType;
                //$arrInvoice['cl_invoice_types_id'] = $tmpInvoiceType;

                $arrInvoice['header_show'] = $this->settings->header_show;
                $arrInvoice['footer_show'] = $this->settings->footer_show;
                $arrInvoice['header_txt'] = $this->settings->header_txt;
                $arrInvoice['footer_txt'] = $this->settings->footer_txt;

                //settings for specific partner
                if ($tmpData->cl_partners_book->due_date > 0)
                    $strModify = '+' . $tmpData->cl_partners_book->due_date . ' day';
                else
                    $strModify = '+' . $this->settings->due_date . ' day';

                $arrInvoice['due_date'] = $defDueDate->modify($strModify);

                //09.04.2021 -
                if (!is_null($tmpData['cl_payment_types_id'])) {
                    $clPayment = $tmpData['cl_payment_types_id'];
                } elseif (isset($tmpData->cl_partners_book->cl_payment_types_id)) {
                    $clPayment = $tmpData->cl_partners_book->cl_payment_types_id;
                } else {
                    $clPayment = $this->settings->cl_payment_types_id;
                }

                $spec_symb = $tmpData->cl_partners_book->spec_symb;
                $arrInvoice['cl_payment_types_id'] = $clPayment;
                $arrInvoice['spec_symb'] = $spec_symb;

                //create or update invoice
                if ($tmpData->cl_invoice_id == NULL || $newInvoice == '1') {
                    //new number
                    $nSeries = $this->NumberSeriesManager->getNewNumber('invoice');
                    $arrInvoice['inv_number'] = $nSeries['number'];
                    $arrInvoice['cl_number_series_id'] = $nSeries['id'];
                    if (!is_null($tmpData->cl_eshops_id) && $tmpData->cl_eshops['cm_number_to_invoice'] == 0) {
                        $arrInvoice['var_symb'] = $arrInvoice['inv_number'];
                    }else{
                        $arrInvoice['var_symb'] = $tmpData['cm_number'];
                    }
                    $arrInvoice['var_symb'] =  preg_replace('/\D/', '', $arrInvoice['var_symb']);
                    $tmpStatus = 'invoice';
                    if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?', $tmpStatus, 1)->fetch())
                        $arrInvoice['cl_status_id'] = $nStatus->id;

                    $row = $this->InvoiceManager->insert($arrInvoice);
                    $this->update(array('id' => $id, 'cl_invoice_id' => $row->id));
                    $invoiceId = $row->id;
                } else {
                    //update existing invoice
                    $arrInvoice['id'] = $tmpData->cl_invoice_id;
                    $row = $this->InvoiceManager->update($arrInvoice);
                    $invoiceId = $tmpData->cl_invoice_id;
                }
                $this->PartnersManager->useHeaderFooter($invoiceId, $arrInvoice['cl_partners_book_id'], $this->InvoiceManager);

                //now content of invoice
                //at first, delete old content
                //next insert new content
                /*$tmpItemsToDel = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $invoiceId));
                    foreach ($tmpItemsToDel as $key => $tmpLine) {
                        //10.03.2019 - before deleting have to delete paired cl_store_move items
                        if ($this->settings->invoice_to_store == 1) {
                            $this->StoreManager->deleteItemStoreMove($tmpLine);
                        }
                        $tmpLine->delete();
                    }*/
                $arrDataItems = json_decode($dataItems, true);
                $arrDataWorks = json_decode($dataWorks, true);

                //15.11.2020 - delete content which is not in $arrDataItems or in $arrDataWorks
                $tmpItemsToDel = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $invoiceId));
                foreach ($tmpItemsToDel as $key => $tmpLine) {
                    if (!array_key_exists($tmpLine['id'], $arrDataItems) && !array_key_exists($tmpLine['id'], $arrDataWorks) && !is_null($tmpLine['cl_commission_id'])) {
                        //10.03.2019 - before deleting have to delete paired cl_store_move items
                        if ($this->settings->invoice_to_store == 1) {
                            $this->StoreManager->deleteItemStoreMove($tmpLine);
                        }
                        $tmpLine->delete();
                    }
                }


                $lastOrder = 0;
                $parentBondId = NULL;
                foreach ($arrDataItems as $key => $one) {
                    $commissionItem = $this->CommissionItemsSelManager->find($one);
                    if ($commissionItem) {

                        $newItem = array();
                        $newItem['cl_invoice_id'] = $invoiceId;
                        $newItem['cl_commission_id'] = $commissionItem['cl_commission_id'];
                        $newItem['item_order'] = $commissionItem->item_order;
                        $newItem['cl_pricelist_id'] = $commissionItem->cl_pricelist_id;
                        $newItem['item_label'] = $commissionItem->item_label;
                        $newItem['quantity'] = $commissionItem->quantity;
                        $newItem['units'] = $commissionItem->units;
                        $newItem['price_s'] = $commissionItem->price_s;
                        $newItem['price_e'] = $commissionItem->price_e;
                        $newItem['discount'] = $commissionItem->discount;
                        $newItem['price_e2'] = $commissionItem->price_e2;
                        $newItem['vat'] = $commissionItem->vat;
                        $newItem['price_e2_vat'] = $commissionItem->price_e2_vat;
                        $newItem['price_e_type'] = $commissionItem->price_e_type;
                        $newItem['description1'] = $commissionItem->description1;
                        $newItem['description2'] = $commissionItem->description2;
                        $newItem['cl_store_move_id'] = $commissionItem->cl_store_move_id;

                        if ($this->settings->invoice_to_store == 1
                            && !is_null($commissionItem->cl_pricelist_id)
                            && !is_null($this->settings->cl_storage_id_commission)) {
                            $newItem['cl_storage_id'] = $this->settings->cl_storage_id_commission;
                        }


                        if (is_null($commissionItem['cl_parent_bond_id'])) {
                            $parentBondId = NULL;
                        }
                        $newItem['cl_parent_bond_id'] = $parentBondId;
                        if (is_null($commissionItem['cl_invoice_items_id'])) {
                            //is new for invoice
                            $tmpNew = $this->InvoiceItemsManager->insert($newItem);
                        } else {
                            //already exists at invoice update then
                            $newItem['id'] = $commissionItem['cl_invoice_items_id'];
                            $tmpUpdate = $this->InvoiceItemsManager->update($newItem);
                            $tmpNew = $this->InvoiceItemsManager->find($commissionItem['cl_invoice_items_id']);
                        }

                        $lastOrder = $commissionItem->item_order;

                        //07.03.2019 - now we have to solve outcome from storage in case of item from pricelist and
                        //active connection between invoice and store
                        if ($this->settings->invoice_to_store == 1
                            && !is_null($commissionItem->cl_pricelist_id)
                            && !is_null($this->settings->cl_storage_id_commission)) {
                            //give out is not possible when item is not from pricelist, isn't defined default storage or if there is already outgoing docs created on intem
                            //&& is_null($commissionItem->cl_commission->cl_store_docs_id)
                            //saled items - give out
                            //1. check if cl_store_docs exists if not, create new one
                            $docId = $this->StoreDocsManager->createStoreDoc(1, $id, $this);
                            //store doc is created from commission, we need to update cl_invoice_id too
                            $this->StoreDocsManager->update(['id' => $docId, 'cl_invoice_id' => $invoiceId]);
                            //update storedocs_id in invoice
                            $this->InvoiceManager->update(['id' => $invoiceId, 'cl_store_docs_id' => $docId]);
                            //update storedocs_id in commission
                            $this->update(['id' => $tmpData->id, 'cl_store_docs_id' => $docId]);

                            //2. giveout current item
                            $dataRet = $this->StoreManager->giveOutItem($docId, $tmpNew->id, $this->InvoiceItemsManager);
                            $dataId = $dataRet['id'];
                            //create pairedocs record with created cl_store_docs_id
                            $this->PairedDocsManager->insertOrUpdate(['cl_commission_id' => $id, 'cl_store_docs_id' => $docId]);
                            $this->PairedDocsManager->insertOrUpdate(['cl_invoice_id' => $invoiceId, 'cl_store_docs_id' => $docId]);
                            if (!is_null($commissionItem['cl_store_docs_id']) && !is_null($commissionItem->cl_store_docs['cl_delivery_note_id'])) {
                                $this->PairedDocsManager->insertOrUpdate(['cl_invoice_id' => $invoiceId, 'cl_delivery_note_id' => $commissionItem->cl_store_docs['cl_delivery_note_id']]);
                            }
                        } else {
                            $docId = NULL;
                            $dataId = NULL;
                        }
                        $commissionItem->update(['cl_invoice_id' => $invoiceId, 'cl_invoice_items_id' => $tmpNew->id, 'cl_store_move_id' => $dataId, 'cl_store_docs_id' => $docId]);

                        //14.03.2019 - bonded items solution
                        //$tmpBonds = $this->PricelistBondsManager->findBy(array('cl_pricelist_bonds_id' => $commissionItem->cl_pricelist_id));
                        if (!is_null($commissionItem->cl_pricelist_id)) {
                            $tmpBonds = $this->PricelistBondsManager->findAll()->where('cl_pricelist_bonds_id = ? AND limit_for_bond <= ?', $commissionItem->cl_pricelist_id, $commissionItem->quantity);
                            if ($tmpBonds) {
                                $parentBondId = $tmpNew->id;
                            }
                        }
                    }
                }


                //$arrDataWorks = json_decode($dataWorks, true);
                foreach ($arrDataWorks as $key => $one) {
                    $commissionWork = $this->CommissionWorkManager->find($one);
                    if ($commissionWork) {
                        $newItem = [];
                        $newItem['cl_invoice_id'] = $invoiceId;
                        $newItem['cl_commission_id'] = $commissionWork['cl_commission_id'];
                        $newItem['item_order'] = $lastOrder + $commissionWork->item_order;
                        $newItem['cl_pricelist_id'] = NULL;
                        if (isset($commissionWork->cl_users->name))
                            $tmpLabel = $commissionWork->work_label . ":" . $commissionWork->cl_users->name;
                        else
                            $tmpLabel = $commissionWork->work_label;

                        $newItem['item_label'] = $tmpLabel;
                        $newItem['quantity'] = $commissionWork->work_time;
                        $newItem['units'] = 'hod.';
                        $newItem['price_s'] = 0;
                        $newItem['discount'] = 0;
                        $profit = 1 + ($commissionWork->profit / 100);
                        if ($tmpData->price_e_type == 0 || $this->settings->platce_dph == 0) {
                            $newItem['price_e'] = $commissionWork->work_rate * $profit;
                        } else {
                            $calcVat = round(($commissionWork->work_rate * $profit) * ($tmpData->vat / 100), 2);
                            $newItem['price_e'] = ($commissionWork->work_rate * $profit) + $calcVat;
                        }

                        $newItem['price_e2'] = ($commissionWork->work_rate * $profit) * $commissionWork->work_time;
                        $newItem['vat'] = $tmpData->vat;
                        $calcVat = round(($commissionWork->work_rate * $profit * $commissionWork->work_time) * ($tmpData->vat / 100), 2);
                        $newItem['price_e2_vat'] = ($commissionWork->work_rate * $profit * $commissionWork->work_time) + $calcVat;
                        //$this->InvoiceItemsManager->insert($newItem);
                        //$commissionWork->update(array('cl_invoice_id' => $invoiceId));
                        if (is_null($commissionWork['cl_invoice_items_id'])) {
                            //is new for invoice
                            $tmpNew = $this->InvoiceItemsManager->insert($newItem);
                        } else {
                            //already exists at invoice update then
                            $newItem['id'] = $commissionWork['cl_invoice_items_id'];
                            $tmpUpdate = $this->InvoiceItemsManager->update($newItem);
                            $tmpNew = $this->InvoiceItemsManager->find($commissionWork['cl_invoice_items_id']);
                        }
                        $commissionWork->update(array('cl_invoice_id' => $invoiceId, 'cl_invoice_items_id' => $tmpNew->id));
                    }
                }

                $this->InvoiceManager->updateInvoiceSum($invoiceId);

                if (!is_null($docId) && $this->settings->dn_from_commission == 1){
                    //make delivery note
                    $arrRet = $this->DeliveryNoteManager->createDelivery($docId);
                    if (self::hasError($arrRet)) {
                        //$this->flashMessage($this->translator->translate('Dodací_list_nebyl_vytvořen'), 'warning');
                    }else{
                        $this->PairedDocsManager->insertOrUpdate(array('cl_commission_id' => $id, 'cl_delivery_note_id' => $arrRet['deliveryN_id']));
                        //$this->flashMessage($this->translator->translate('Dodací_list_byl_vytvořen'), 'success');
                        //$this->payload->id = $arrRet['deliveryN_id'];
                    }
                }

                //create pairedocs record
                $this->PairedDocsManager->insertOrUpdate(array('cl_commission_id' => $id, 'cl_invoice_id' => $invoiceId));

            }


            $arrRet['success'] = '';
            $arrRet['invoiceId'] = $invoiceId;
        } catch (\Exception $e) {
            $arrRet['error'] = $e->getMessage();
            Debugger::log('Invoice from Commission . ' . $e->getMessage());
        }

        return $arrRet;
    }


    public function CreateInvoiceAdvance2($dataItems, $dataWorks, $id, $newInvoice)
    {
        //bdump($dataItems, $dataWorks);
        $docId = null;
        $arrRet = [];
        try {
            if ($tmpData = $this->find($id)) {
                if ($tmpInvoiceType = $this->InvoiceTypesManager->findAll()->where('default_type = ? AND inv_type = 3', 1)->fetch()) {
                    $tmpInvoiceType = $tmpInvoiceType->id;
                } else {
                    $tmpInvoiceType = NULL;
                }
                //default values for invoice
                $defDueDate = new \Nette\Utils\DateTime;
                $arrInvoice = [];
                $arrInvoice['cl_currencies_id']             = $this->settings->cl_currencies_id;
                $arrInvoice['currency_rate']                = $this->settings->cl_currencies->fix_rate;
                $arrInvoice['vat_active']                   = $this->settings->platce_dph;

                $arrInvoice['cl_partners_book_id']          = $tmpData->cl_partners_book_id;
                $arrInvoice['cl_partners_book_workers_id']  = $tmpData->cl_partners_book_workers_id;
                $arrInvoice['cl_partners_branch_id']        = $tmpData->cl_partners_branch_id;
                $arrInvoice['cl_company_branch_id']         = $tmpData->cl_company_branch_id;
                $arrInvoice['cl_currencies_id']             = $tmpData->cl_currencies_id;
                $arrInvoice['currency_rate']                = $tmpData->currency_rate;
                $arrInvoice['cl_users_id']                  = $tmpData->cl_users_id;
                $arrInvoice['inv_title']                    = $tmpData->cm_title;
                $arrInvoice['cl_commission_id']             = $tmpData->id;
                $arrInvoice['price_e_type']                 = $tmpData->price_e_type;
                $arrInvoice['inv_date']                     = new \Nette\Utils\DateTime;
                if (!is_null($tmpData->delivery_date))
                    $arrInvoice['vat_date'] = $tmpData->delivery_date;
                else
                    $arrInvoice['vat_date'] = new \Nette\Utils\DateTime;

                $arrInvoice['od_number'] = $tmpData->cm_order;
                $arrInvoice['cm_number'] = $tmpData->cm_number;

                $arrInvoice['konst_symb']           = $this->settings->konst_symb;
                $arrInvoice['cl_invoice_types_id']  = $tmpInvoiceType;
                //$arrInvoice['cl_invoice_types_id'] = $tmpInvoiceType;

                $arrInvoice['header_show']  = $this->settings->header_show;
                $arrInvoice['footer_show']  = $this->settings->footer_show;
                $arrInvoice['header_txt']   = $this->settings->header_txt;
                $arrInvoice['footer_txt']   = $this->settings->footer_txt;

                //settings for specific partner
                if ($tmpData->cl_partners_book->due_date > 0)
                    $strModify = '+' . $tmpData->cl_partners_book->due_date . ' day';
                else
                    $strModify = '+' . $this->settings->due_date . ' day';

                $arrInvoice['due_date'] = $defDueDate->modify($strModify);

                //09.04.2021 -
                if (!is_null($tmpData['cl_payment_types_id'])) {
                    $clPayment = $tmpData['cl_payment_types_id'];
                } elseif (isset($tmpData->cl_partners_book->cl_payment_types_id)) {
                    $clPayment = $tmpData->cl_partners_book->cl_payment_types_id;
                } else {
                    $clPayment = $this->settings->cl_payment_types_id;
                }

                $spec_symb = $tmpData->cl_partners_book->spec_symb;
                $arrInvoice['cl_payment_types_id']  = $clPayment;
                $arrInvoice['spec_symb']            = $spec_symb;

                //create or update invoice
                if ($tmpData->cl_invoice_id == NULL || $newInvoice == '1') {
                    //new number
                    $nSeries = $this->NumberSeriesManager->getNewNumber('invoice_advance');
                    $arrInvoice['inv_number'] = $nSeries['number'];
                    $arrInvoice['cl_number_series_id'] = $nSeries['id'];
                    if (!is_null($tmpData->cl_eshops_id) && $tmpData->cl_eshops['cm_number_to_invoice'] == 0) {
                        $arrInvoice['var_symb'] = $arrInvoice['inv_number'];
                    }else{
                        $arrInvoice['var_symb'] = $tmpData['cm_number'];
                    }
                    $arrInvoice['var_symb'] =  preg_replace('/\D/', '', $arrInvoice['var_symb']);
                    $tmpStatus = 'invoice_advance';
                    if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?', $tmpStatus, 1)->fetch())
                        $arrInvoice['cl_status_id'] = $nStatus->id;

                    $row = $this->InvoiceAdvanceManager->insert($arrInvoice);
                    $this->update(['id' => $id, 'cl_invoice_advance_id' => $row->id]);
                    $invoiceId = $row->id;
                } else {
                    //update existing invoice
                    $arrInvoice['id'] = $tmpData->cl_invoice_id;
                    $row = $this->InvoiceAdvanceManager->update($arrInvoice);
                    $invoiceId = $tmpData->cl_invoice_id;
                }
                $this->PartnersManager->useHeaderFooter($invoiceId, $arrInvoice['cl_partners_book_id'], $this->InvoiceAdvanceManager);

                //now content of invoice
                //at first, delete old content
                //next insert new content
                /*$tmpItemsToDel = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $invoiceId));
                    foreach ($tmpItemsToDel as $key => $tmpLine) {
                        //10.03.2019 - before deleting have to delete paired cl_store_move items
                        if ($this->settings->invoice_to_store == 1) {
                            $this->StoreManager->deleteItemStoreMove($tmpLine);
                        }
                        $tmpLine->delete();
                    }*/
                $arrDataItems = json_decode($dataItems, true);
                $arrDataWorks = json_decode($dataWorks, true);

                //15.11.2020 - delete content which is not in $arrDataItems or in $arrDataWorks
                $tmpItemsToDel = $this->InvoiceAdvanceItemsManager->findBy(['cl_invoice_advance_id' => $invoiceId]);
                foreach ($tmpItemsToDel as $key => $tmpLine) {
                    if (!array_key_exists($tmpLine['id'], $arrDataItems) && !array_key_exists($tmpLine['id'], $arrDataWorks) && !is_null($tmpLine['cl_commission_id'])) {
                        //10.03.2019 - before deleting have to delete paired cl_store_move items
                        //if ($this->settings->invoice_to_store == 1) {
                        //    $this->StoreManager->deleteItemStoreMove($tmpLine);
                        //}
                        $tmpLine->delete();
                    }
                }


                $lastOrder = 0;
                $parentBondId = NULL;
                foreach ($arrDataItems as $key => $one) {
                    $commissionItem = $this->CommissionItemsSelManager->find($one);
                    if ($commissionItem) {

                        $newItem = [];
                        $newItem['cl_invoice_advance_id']       = $invoiceId;
                        $newItem['cl_commission_id']    = $commissionItem['cl_commission_id'];
                        $newItem['item_order']          = $commissionItem->item_order;
                        $newItem['cl_pricelist_id']     = $commissionItem->cl_pricelist_id;
                        $newItem['item_label']          = $commissionItem->item_label;
                        $newItem['quantity']            = $commissionItem->quantity;
                        $newItem['units']               = $commissionItem->units;
                        $newItem['price_s']             = $commissionItem->price_s;
                        $newItem['price_e']             = $commissionItem->price_e;
                        $newItem['discount']            = $commissionItem->discount;
                        $newItem['price_e2']            = $commissionItem->price_e2;
                        $newItem['vat']                 = $commissionItem->vat;
                        $newItem['price_e2_vat']        = $commissionItem->price_e2_vat;
                        $newItem['price_e_type']        = $commissionItem->price_e_type;
                        $newItem['cl_store_move_id']    = $commissionItem->cl_store_move_id;

                        if ($this->settings->invoice_to_store == 1
                            && !is_null($commissionItem->cl_pricelist_id)
                            && !is_null($this->settings->cl_storage_id_commission)) {
                            //$newItem['cl_storage_id'] = $this->settings->cl_storage_id_commission;
                        }


                        if (is_null($commissionItem['cl_parent_bond_id'])) {
                            $parentBondId = NULL;
                        }
                        $newItem['cl_parent_bond_id'] = $parentBondId;
                        if (is_null($commissionItem['cl_invoice_advance_items_id'])) {
                            //is new for invoice
                            $tmpNew = $this->InvoiceAdvanceItemsManager->insert($newItem);
                        } else {
                            //already exists at invoice update then
                            $newItem['id'] = $commissionItem['cl_invoice_advance_items_id'];
                            $tmpUpdate = $this->InvoiceAdvanceItemsManager->update($newItem);
                            $tmpNew = $this->InvoiceAdvanceItemsManager->find($commissionItem['cl_invoice_advance_items_id']);
                        }

                        $lastOrder = $commissionItem->item_order;

                        //07.03.2019 - now we have to solve outcome from storage in case of item from pricelist and
                        //active connection between invoice and store
                        /*if ($this->settings->invoice_to_store == 1
                            && !is_null($commissionItem->cl_pricelist_id)
                            && !is_null($this->settings->cl_storage_id_commission)) {
                            //give out is not possible when item is not from pricelist, isn't defined default storage or if there is already outgoing docs created on intem
                            //&& is_null($commissionItem->cl_commission->cl_store_docs_id)
                            //saled items - give out
                            //1. check if cl_store_docs exists if not, create new one
                            $docId = $this->StoreDocsManager->createStoreDoc(1, $id, $this);
                            //store doc is created from commission, we need to update cl_invoice_id too
                            $this->StoreDocsManager->update(['id' => $docId, 'cl_invoice_id' => $invoiceId]);
                            //update storedocs_id in invoice
                            $this->InvoiceManager->update(['id' => $invoiceId, 'cl_store_docs_id' => $docId]);
                            //update storedocs_id in commission
                            $this->update(['id' => $tmpData->id, 'cl_store_docs_id' => $docId]);

                            //2. giveout current item
                            $dataRet = $this->StoreManager->giveOutItem($docId, $tmpNew->id, $this->InvoiceItemsManager);
                            $dataId = $dataRet['id'];
                            //create pairedocs record with created cl_store_docs_id
                            $this->PairedDocsManager->insertOrUpdate(['cl_commission_id' => $id, 'cl_store_docs_id' => $docId]);
                            $this->PairedDocsManager->insertOrUpdate(['cl_invoice_id' => $invoiceId, 'cl_store_docs_id' => $docId]);
                            if (!is_null($commissionItem['cl_store_docs_id']) && !is_null($commissionItem->cl_store_docs['cl_delivery_note_id'])) {
                                $this->PairedDocsManager->insertOrUpdate(['cl_invoice_id' => $invoiceId, 'cl_delivery_note_id' => $commissionItem->cl_store_docs['cl_delivery_note_id']]);
                            }
                        } else {
                        */
                            $docId = NULL;
                            $dataId = NULL;
                       // }
                        $commissionItem->update(['cl_invoice_advance_id' => $invoiceId, 'cl_invoice_advance_items_id' => $tmpNew->id, 'cl_store_move_id' => $dataId, 'cl_store_docs_id' => $docId]);

                        //14.03.2019 - bonded items solution
                        //$tmpBonds = $this->PricelistBondsManager->findBy(array('cl_pricelist_bonds_id' => $commissionItem->cl_pricelist_id));
                        if (!is_null($commissionItem->cl_pricelist_id)) {
                            $tmpBonds = $this->PricelistBondsManager->findAll()->where('cl_pricelist_bonds_id = ? AND limit_for_bond <= ?', $commissionItem->cl_pricelist_id, $commissionItem->quantity);
                            if ($tmpBonds) {
                                $parentBondId = $tmpNew->id;
                            }
                        }
                    }
                }


                //$arrDataWorks = json_decode($dataWorks, true);
                foreach ($arrDataWorks as $key => $one) {
                    $commissionWork = $this->CommissionWorkManager->find($one);
                    if ($commissionWork) {
                        $newItem = [];
                        $newItem['cl_invoice_advance_id'] = $invoiceId;
                        $newItem['cl_commission_id'] = $commissionWork['cl_commission_id'];
                        $newItem['item_order'] = $lastOrder + $commissionWork->item_order;
                        $newItem['cl_pricelist_id'] = NULL;
                        if (isset($commissionWork->cl_users->name))
                            $tmpLabel = $commissionWork->work_label . ":" . $commissionWork->cl_users->name;
                        else
                            $tmpLabel = $commissionWork->work_label;

                        $newItem['item_label'] = $tmpLabel;
                        $newItem['quantity'] = $commissionWork->work_time;
                        $newItem['units'] = 'hod.';
                        $newItem['price_s'] = 0;
                        $newItem['discount'] = 0;
                        $profit = 1 + ($commissionWork->profit / 100);
                        if ($tmpData->price_e_type == 0 || $this->settings->platce_dph == 0) {
                            $newItem['price_e'] = $commissionWork->work_rate * $profit;
                        } else {
                            $calcVat = round(($commissionWork->work_rate * $profit) * ($tmpData->vat / 100), 2);
                            $newItem['price_e'] = ($commissionWork->work_rate * $profit) + $calcVat;
                        }

                        $newItem['price_e2'] = ($commissionWork->work_rate * $profit) * $commissionWork->work_time;
                        $newItem['vat'] = $tmpData->vat;
                        $calcVat = round(($commissionWork->work_rate * $profit * $commissionWork->work_time) * ($tmpData->vat / 100), 2);
                        $newItem['price_e2_vat'] = ($commissionWork->work_rate * $profit * $commissionWork->work_time) + $calcVat;
                        //$this->InvoiceItemsManager->insert($newItem);
                        //$commissionWork->update(array('cl_invoice_id' => $invoiceId));
                        if (is_null($commissionWork['cl_invoice_advance_items_id'])) {
                            //is new for invoice
                            $tmpNew = $this->InvoiceAdvanceItemsManager->insert($newItem);
                        } else {
                            //already exists at invoice update then
                            $newItem['id'] = $commissionWork['cl_invoice_advance_items_id'];
                            $tmpUpdate = $this->InvoiceAdvanceItemsManager->update($newItem);
                            $tmpNew = $this->InvoiceAdvanceItemsManager->find($commissionWork['cl_invoice_advance_items_id']);
                        }
                        $commissionWork->update(['cl_invoice_advance_id' => $invoiceId, 'cl_invoice_advance_items_id' => $tmpNew->id]);
                    }
                }

                $this->InvoiceAdvanceManager->updateInvoiceSum($invoiceId);

                /*if (!is_null($docId) && $this->settings->dn_from_commission == 1){
                    //make delivery note
                    $arrRet = $this->DeliveryNoteManager->createDelivery($docId);
                    if (self::hasError($arrRet)) {
                        //$this->flashMessage($this->translator->translate('Dodací_list_nebyl_vytvořen'), 'warning');
                    }else{
                        $this->PairedDocsManager->insertOrUpdate(array('cl_commission_id' => $id, 'cl_delivery_note_id' => $arrRet['deliveryN_id']));
                        //$this->flashMessage($this->translator->translate('Dodací_list_byl_vytvořen'), 'success');
                        //$this->payload->id = $arrRet['deliveryN_id'];
                    }
                }*/

                //create pairedocs record
                $this->PairedDocsManager->insertOrUpdate(['cl_commission_id' => $id, 'cl_invoice_advance_id' => $invoiceId]);

            }


            $arrRet['success'] = '';
            $arrRet['invoiceId'] = $invoiceId;
        } catch (\Exception $e) {
            $arrRet['error'] = $e->getMessage();
            Debugger::log('Invoice advance from Commission . ' . $e->getMessage());
        }

        return $arrRet;
    }

}

