
--13.07.2016
ALTER TABLE `cl_commission` ADD `description_txt` MEDIUMTEXT NOT NULL AFTER `footer_show`, ADD `description_show` TINYINT NOT NULL DEFAULT '0' COMMENT '0 - netisknout, 1 - tisknout' AFTER `description_txt`;
ALTER TABLE `cl_files` ADD `cl_commission_id` INT NULL DEFAULT NULL AFTER `cl_kdb_id`;
ALTER TABLE `cl_files` ADD INDEX(`cl_commission_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`cl_commission_id`) REFERENCES `klienticz`.`cl_commission`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `cl_company` ADD `cl_users_license_id` INT NULL DEFAULT NULL AFTER `price_e_type`;
ALTER TABLE `cl_company` ADD INDEX(`cl_users_license_id`);
ALTER TABLE `cl_company` ADD FOREIGN KEY (`cl_users_license_id`) REFERENCES `klienticz`.`cl_users_license`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--25.07.2016
ALTER TABLE `cl_company` ADD `sync_token` CHAR(128) NULL DEFAULT NULL COMMENT 'token pro synchronizaci s 2HCS Fakturace' AFTER `cl_users_license_id`;

---17.08.2016
ALTER TABLE `cl_users` ADD `user_image` VARCHAR(68) NULL DEFAULT NULL AFTER `cl_users_license_id`;

--21.08.2016

---ALTER TABLE `cl_documents` CHANGE `html_document` `html_document` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL;


---08.10.2016

ALTER TABLE `cl_users_license` CHANGE `status` `status` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_czech_ci NULL DEFAULT NULL;


---31.12.2016
ALTER TABLE `cl_order_items` CHANGE `cl_pricelist_id` `cl_pricelist_id` INT(11) NULL DEFAULT NULL;


---15.01.2017
ALTER TABLE `cl_pricelist` ADD `ean_code` VARCHAR(128) NOT NULL AFTER `identification`;
ALTER TABLE `cl_pricelist` ADD INDEX(`identification`);
ALTER TABLE `cl_pricelist` ADD INDEX(`ean_code`);
ALTER TABLE `cl_pricelist` ADD INDEX(`item_label`);

---05.03.2017
new table cl_center

ALTER TABLE `cl_partners_book` ADD `cl_center_id` INT NULL DEFAULT NULL AFTER `master_cl_company_id`;
ALTER TABLE `cl_partners_book` ADD INDEX(`cl_center_id`);
ALTER TABLE `cl_partners_book` ADD FOREIGN KEY (`cl_center_id`) REFERENCES `cl_center`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;


new table cl_calendar_plane

---beta
---06.04.2017
ALTER TABLE `cl_company` ADD `hd_ending` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '1-při ukončení všech odpovědí požadavku se ukončí i požadavek, 0-neukončuje se nic' AFTER `hd7_emailing_text_id`;
---07.04.2017
ALTER TABLE `cl_center` ADD `email` VARCHAR(60) NOT NULL AFTER `name`;

ALTER TABLE `cl_files` ADD `cl_center_id` INT NULL DEFAULT NULL AFTER `cl_commission_id`;
ALTER TABLE `cl_files` ADD INDEX(`cl_center_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`cl_center_id`) REFERENCES `cl_center`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--09.04.2017
ALTER TABLE `cl_files` ADD `file_session` VARCHAR(64) NULL DEFAULT NULL AFTER `cl_center_id`;
--ALTER TABLE `cl_files` CHANGE `file_session` `file_session` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_czech_ci NULL DEFAULT NULL;

--11.04.2017
--new tables

--cl_sale
--cl_sale_items

ALTER TABLE `cl_users` ADD `after_login` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '0-uvodni,1-faktury,2-zakazky,3-sklad,4-prodejna,5-objednavky,6-helpdesk,7-vseznalek' AFTER `user_image`;

---19.04.2017
ALTER TABLE `cl_sale` ADD FOREIGN KEY (`cl_payment_types_id`) REFERENCES `cl_payment_types`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_company` ADD `cl_partners_book_id_sale` INT NULL DEFAULT NULL AFTER `sync_token`;

---19.04.2017 II
ALTER TABLE `cl_company` ADD `cl_payment_types_id_sale` INT NULL DEFAULT NULL AFTER `cl_partners_book_id_sale`;

---04.05.2017
ALTER TABLE `cl_partners_event` ADD `cl_invoice_id` INT NULL DEFAULT NULL AFTER `email_rcv`;
ALTER TABLE `cl_partners_event` ADD INDEX(`cl_commission_id`);
ALTER TABLE `cl_partners_event` ADD INDEX(`cl_invoice_id`);
ALTER TABLE `cl_partners_event` ADD FOREIGN KEY (`cl_commission_id`) REFERENCES `cl_commission`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_partners_event` ADD FOREIGN KEY (`cl_invoice_id`) REFERENCES `cl_invoice`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_partners_event` ADD `selected` TINYINT(1) NOT NULL DEFAULT '0' AFTER `cl_invoice_id`;
ALTER TABLE `cl_partners_event` CHANGE `selected` `selected` INT NULL DEFAULT NULL;
ALTER TABLE `cl_company` ADD `hd_vat` DOUBLE NOT NULL DEFAULT '0' COMMENT 'VAT rate for events going to invoice or commission' AFTER `hd_ending`;
ALTER TABLE `cl_invoice_items` ADD `cl_partners_event_id` INT NULL DEFAULT NULL AFTER `price_e2_vat`;
ALTER TABLE `cl_commission_items` ADD `cl_partners_event_id` INT NULL DEFAULT NULL AFTER `price_e2_vat`;


---29.05.2017
ALTER TABLE `cl_storage` ADD `price_method` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0-FiFo,1-VAP' AFTER `email_notification`;

---30.05.2017
ALTER TABLE `cl_store` ADD `price_s` DECIMAL(15,4) NOT NULL DEFAULT '0' COMMENT 'průměrná skladová cena zásoby VAP' AFTER `quantity_req`;
ALTER TABLE `cl_store_move` ADD `price_vap` DECIMAL(15,4) NOT NULL DEFAULT '0' AFTER `price_s`;
ALTER TABLE `cl_store_move` ADD `s_total` DECIMAL(15,4) NOT NULL DEFAULT '0' COMMENT 'celkový stav v okamžiku naskladnění, použitý pro výpočet VAP' AFTER `s_end`;
ALTER TABLE `cl_store_out`  ADD `s_total` DECIMAL(15,4) NOT NULL DEFAULT '0' COMMENT 'celkový stav zásoby, z které se vydává. Stav je po provedeném výdeji'  AFTER `price_s`;
ALTER TABLE `cl_store`  ADD `st_total` DECIMAL(15,4) NOT NULL DEFAULT '0' COMMENT 'vypočtený stav pro inventuru'  AFTER `price_s`;
ALTER TABLE `cl_store`  ADD `st_price` DECIMAL(15,4) NOT NULL DEFAULT '0' COMMENT 'cena pro inventuru'  AFTER `st_total`;
ALTER TABLE `cl_store` ADD `st_date` DATE NULL DEFAULT NULL COMMENT 'datum inventury' AFTER `st_price`;

new table invoice_arrived a invoice_arrived_payments

ALTER TABLE `cl_files` ADD `cl_invoice_arrived_id` INT NULL DEFAULT NULL AFTER `cl_center_id`;

---03.09.2017
new table cl_prices_groups, cl_prices

ALTER TABLE `cl_partners_book` ADD `cl_prices_groups_id` INT NULL DEFAULT NULL AFTER `hd_eml`;
ALTER TABLE `cl_partners_book` ADD INDEX(`cl_prices_groups_id`);
ALTER TABLE `cl_partners_book` ADD FOREIGN KEY (`cl_prices_groups_id`) REFERENCES `cl_prices_groups`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_prices_groups` ADD `price_surcharge` DECIMAL(10,2) NOT NULL DEFAULT '0' AFTER `name`;
ALTER TABLE `cl_company` ADD `cl_storage_id_sale` INT NULL DEFAULT NULL AFTER `cl_storage_id`;
ALTER TABLE `cl_company` ADD `cl_storage_id_commission` INT NULL DEFAULT NULL AFTER `cl_storage_id_sale`;


---08.09.2017
ALTER TABLE `cl_prices_groups` ADD `price_change` DECIMAL(10,2) NOT NULL DEFAULT '0' AFTER `price_surcharge`;

---13.09.2017
new table cl_erased_sync

ALTER TABLE `cl_company` ADD `sync_last` DATETIME NULL DEFAULT NULL AFTER `sync_token`;

---18.09.2017
ALTER TABLE `cl_order` ADD `rea_date` DATE NULL DEFAULT NULL AFTER `req_date`;
ALTER TABLE `cl_order` ADD `memo_txt` LONGTEXT NOT NULL AFTER `footer_show`;
ALTER TABLE `cl_order` ADD `inv_numbers` VARCHAR(64) NOT NULL AFTER `memo_txt`, ADD `dln_numbers` VARCHAR(64) NOT NULL AFTER `inv_numbers`;
ALTER TABLE `cl_order` ADD `com_numbers` VARCHAR(64) NOT NULL COMMENT 'commisions numbers' AFTER `dln_numbers`, ADD `delivery_place` VARCHAR(128) NOT NULL AFTER `com_numbers`, ADD `delivery_method` VARCHAR(128) NOT NULL AFTER `delivery_place`;
ALTER TABLE `cl_order_items` ADD `quantity_rcv` DECIMAL(15,4) NOT NULL DEFAULT '0' AFTER `quantity`;
ALTER TABLE `cl_order_items` ADD `price_e2_rcv` DECIMAL(15,4) NOT NULL DEFAULT '0' AFTER `price_e2_vat`;
ALTER TABLE `cl_order_items` ADD `note_txt` VARCHAR(64) NOT NULL AFTER `price_e2_rcv`;
ALTER TABLE `cl_order_items` ADD `rea_date` DATE NULL DEFAULT NULL AFTER `quantity_rcv`;
ALTER TABLE `cl_order_items` CHANGE `price_e2_rcv` `price_e_rcv` DECIMAL(15,4) NOT NULL DEFAULT '0.0000';


---29.12.2017
ALTER TABLE `cl_commission` ADD `cl_store_docs_id` INT NULL DEFAULT NULL AFTER `cl_invoice_id`;
ALTER TABLE `cl_commission` ADD INDEX(`cl_store_docs_id`);
ALTER TABLE `cl_commission` ADD FOREIGN KEY (`cl_store_docs_id`) REFERENCES `cl_store_docs`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--29.12.2017
ALTER TABLE `cl_invoice_items` ADD `description1` CHAR(64) NOT NULL AFTER `cl_partners_event_id`, ADD `description2` CHAR(64) NOT NULL AFTER `description1`;
ALTER TABLE `cl_commission_items` ADD `description1` CHAR(64) NOT NULL AFTER `cl_partners_event_id`, ADD `description2` CHAR(64) NOT NULL AFTER `description1`;

--29.12.2017
ALTER TABLE `cl_company` ADD `own_names` TEXT NOT NULL AFTER `cl_payment_types_id_sale`;


--26.1.2018
ALTER TABLE `cl_company` ADD `email_income_exclude` TEXT NOT NULL AFTER `email_income`;

--11.02.2018
ALTER TABLE `cl_company` ADD `sms_manager` TEXT NOT NULL AFTER `own_names`;
ALTER TABLE `cl_users` ADD `phone` VARCHAR(40) NOT NULL AFTER `email`;

// new table cl_sms, cl_sms_response


--09.03.2018
//new tables cl_offer, cl_offer_work, cl_offer_items
ALTER TABLE `cl_files` ADD `cl_offer_id` INT NULL DEFAULT NULL AFTER `cl_commission_id`;
ALTER TABLE `cl_files` ADD INDEX(`cl_offer_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`cl_offer_id`) REFERENCES `cl_offer`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;


//na betě
--07.07.2018
ALTER TABLE `cl_users` ADD `tables_settings` TEXT NOT NULL AFTER `event_settings`;

ALTER TABLE `cl_users` ADD `not_active` TINYINT(1) NOT NULL DEFAULT '0' AFTER `active`;

ALTER TABLE `cl_commission` ADD `price_pe2_base` DECIMAL(15) NOT NULL DEFAULT '0' AFTER `price_e2_vat`, ADD `price_pe2_vat` DECIMAL(15) NOT NULL DEFAULT '0' AFTER `price_pe2_base`;
ALTER TABLE `cl_commission` CHANGE `price_pe2_base` `price_pe2_base` DECIMAL(15,4) NOT NULL DEFAULT '0', CHANGE `price_pe2_vat` `price_pe2_vat` DECIMAL(15,4) NOT NULL DEFAULT '0' ;

ALTER TABLE `cl_commission` ADD `profit_items` DECIMAL(5) NULL DEFAULT '0' COMMENT 'požadovaný zisk na položkách' AFTER `price_e_type`, ADD `profit_works` DECIMAL(5) NOT NULL DEFAULT '0' COMMENT 'požadovaný zisk na práci' AFTER `profit_items`;
ALTER TABLE `cl_commission` CHANGE `profit_items` `profit_items` DECIMAL(5,2) NULL DEFAULT '0' COMMENT 'požadovaný zisk na položkách', CHANGE `profit_works` `profit_works` DECIMAL(5,2) NOT NULL DEFAULT '0' COMMENT 'požadovaný zisk na práci' ;
ALTER TABLE `cl_commission` ADD `price_w` DECIMAL(15) NOT NULL DEFAULT '0' AFTER `price_w2`;
ALTER TABLE `cl_commission` CHANGE `price_w` `price_w` DECIMAL(15) NOT NULL DEFAULT '0';
ALTER TABLE `cl_commission` ADD `price_e` DECIMAL(15,4) NOT NULL DEFAULT '0' COMMENT 'total costs' AFTER `price_w`;

ALTER TABLE `cl_emailing` ADD `cl_commission_id` INT NOT NULL AFTER `sendTo`;
ALTER TABLE `cl_emailing` CHANGE `cl_commission_id` `cl_commission_id` INT(11) NULL DEFAULT NULL;
ALTER TABLE `cl_emailing` ADD INDEX(`cl_commission_id`);
UPDATE `cl_emailing` SET `cl_commission_id`=NULL
ALTER TABLE `cl_emailing` ADD FOREIGN KEY (`cl_commission_id`) REFERENCES `cl_commission`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_emailing` DROP FOREIGN KEY `cl_emailing_ibfk_1`; ALTER TABLE `cl_emailing` ADD CONSTRAINT `cl_emailing_ibfk_1` FOREIGN KEY (`cl_commission_id`) REFERENCES `cl_commission`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

ALTER TABLE `cl_commission_work` CHANGE `work_date_s` `work_date_s` DATETIME NULL DEFAULT NULL;
ALTER TABLE `cl_commission_work` CHANGE `work_date_e` `work_date_e` DATETIME NULL DEFAULT NULL;
ALTER TABLE `cl_commission_work` ADD `profit` DECIMAL(15,2) NOT NULL DEFAULT '0' AFTER `price_e_type`;

ALTER TABLE `cl_commission` CHANGE `cm_title` `cm_title` TEXT CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL;
ALTER TABLE `cl_commission_work` ADD `note` TEXT NOT NULL AFTER `profit`;
ALTER TABLE `cl_commission_task` ADD `note` TEXT NOT NULL AFTER `profit`;
ALTER TABLE `cl_commission_work` ADD `cl_commission_task_id` INT NULL DEFAULT NULL AFTER `note`;
ALTER TABLE `cl_commission_work` ADD INDEX(`cl_commission_task_id`);
ALTER TABLE `cl_commission_work` ADD FOREIGN KEY (`cl_commission_task_id`) REFERENCES `cl_commission_task`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_commission_work` DROP FOREIGN KEY `cl_commission_work_ibfk_4`; ALTER TABLE `cl_commission_work` ADD CONSTRAINT `cl_commission_work_ibfk_4` FOREIGN KEY (`cl_commission_task_id`) REFERENCES `cl_commission_task`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `cl_commission` CHANGE `price_w` `price_w` DECIMAL(15,4) NOT NULL DEFAULT '0'
//new table cl_invoice_arrived_commission

//POZOR !! Před tímto musíme zálohovat původní obsah a pak zkopírovat do upravaného pole
//ALTER TABLE `cl_commission` CHANGE `cm_number` `cm_number` VARCHAR(20) CHARACTER SET utf32 COLLATE utf32_czech_ci NOT NULL;
//takže raději takto
ALTER TABLE `cl_commission` ADD `cm_number2` VARCHAR(20) CHARACTER SET utf32 COLLATE utf32_czech_ci NOT NULL AFTER `change_by`;
UPDATE `cl_commission` SET `cm_number2`=`cm_number`;
ALTER TABLE `cl_commission` DROP `cm_number`;
ALTER TABLE `cl_commission` CHANGE `cm_number2` `cm_number` VARCHAR(20) CHARACTER SET utf32 COLLATE utf32_czech_ci NOT NULL;
ALTER TABLE `cl_invoice_arrived` ADD `price_on_commission` DECIMAL(15) NOT NULL DEFAULT '0' AFTER `cl_commission_id`;
ALTER TABLE `cl_invoice_arrived` CHANGE `price_on_commission` `price_on_commission` DECIMAL(15,2) NOT NULL DEFAULT '0';

ALTER TABLE `cl_commission` ADD `cl_center_id` INT NULL DEFAULT NULL AFTER `cm_order`;
ALTER TABLE `cl_commission` ADD INDEX(`cl_center_id`);
ALTER TABLE `cl_commission` ADD FOREIGN KEY (`cl_center_id`) REFERENCES `cl_center`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_users` ADD `picture_stamp` VARCHAR(68) CHARACTER SET utf32 COLLATE utf32_czech_ci NOT NULL AFTER `after_login`;
ALTER TABLE `cl_files` ADD `cl_store_docs_id` INT NULL DEFAULT NULL AFTER `cl_invoice_arrived_id`;
ALTER TABLE `cl_files` ADD INDEX(`cl_invoice_arrived_id`);
ALTER TABLE `cl_files` ADD INDEX(`cl_store_docs_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`cl_store_docs_id`) REFERENCES `cl_store_docs`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_store_docs` ADD `description_txt` MEDIUMTEXT CHARACTER SET utf32 COLLATE utf32_czech_ci NOT NULL AFTER `cl_invoice_id`;

ALTER TABLE `cl_commission_task` DROP FOREIGN KEY `cl_commission_task_ibfk_2`; ALTER TABLE `cl_commission_task` ADD CONSTRAINT `cl_commission_task_ibfk_2` FOREIGN KEY (`cl_commission_id`) REFERENCES `cl_commission`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice_arrived_commission` DROP FOREIGN KEY `cl_invoice_arrived_commission_ibfk_4`; ALTER TABLE `cl_invoice_arrived_commission` ADD CONSTRAINT `cl_invoice_arrived_commission_ibfk_4` FOREIGN KEY (`cl_commission_id`) REFERENCES `cl_commission`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice_arrived_commission` DROP FOREIGN KEY `cl_invoice_arrived_commission_ibfk_3`; ALTER TABLE `cl_invoice_arrived_commission` ADD CONSTRAINT `cl_invoice_arrived_commission_ibfk_3` FOREIGN KEY (`cl_invoice_arrived_id`) REFERENCES `cl_invoice_arrived`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

//only on local
//new table cl_offer_task
ALTER TABLE `cl_offer_items` ADD `note` TEXT NOT NULL AFTER `cl_partners_event_id`;
ALTER TABLE `cl_offer` CHANGE `cm_title` `cm_title` TEXT CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL;
ALTER TABLE `cl_offer` CHANGE `cm_date` `offer_date` DATE NULL DEFAULT NULL;
ALTER TABLE `cl_offer` CHANGE `delivery_date` `validity_date` DATE NULL DEFAULT NULL;
ALTER TABLE `cl_offer` ADD `validity_days` TINYINT(3) NOT NULL AFTER `validity_date`;
ALTER TABLE `cl_offer` CHANGE `validity_days` `validity_days` TINYINT(3) NOT NULL DEFAULT '0';
ALTER TABLE `cl_offer` ADD `mark1` VARCHAR(32) NOT NULL AFTER `price_e_type`, ADD `mark2` VARCHAR(32) NOT NULL AFTER `mark1`;
ALTER TABLE `cl_offer` ADD `delivery_period` TINYINT(3) NOT NULL DEFAULT '0' AFTER `mark2`, ADD `delivery_period_type` VARCHAR(20) NOT NULL AFTER `delivery_period`;
ALTER TABLE `cl_offer` CHANGE `delivery_period_type` `delivery_period_type` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `cl_offer` ADD `terms_delivery` VARCHAR(64) NOT NULL AFTER `delivery_period_type`, ADD `terms_payment` VARCHAR(64) NOT NULL AFTER `terms_delivery`;
ALTER TABLE `cl_offer` ADD `delivery_price` DECIMAL(15) NOT NULL DEFAULT '0' AFTER `terms_payment`;
ALTER TABLE `cl_offer` CHANGE `delivery_price` `delivery_price` DECIMAL(15,4) NOT NULL DEFAULT '0';
ALTER TABLE `cl_offer` ADD `price_w` DECIMAL(15) NOT NULL DEFAULT '0' AFTER `price_e2`;
ALTER TABLE `cl_offer` CHANGE `price_w` `price_w` DECIMAL(15,4) NOT NULL DEFAULT '0';
ALTER TABLE `cl_offer` ADD `price_e` DECIMAL(0) NOT NULL DEFAULT '0' COMMENT 'total costs' AFTER `price_w2`;
ALTER TABLE `cl_offer` CHANGE `price_e` `price_e` DECIMAL(15,4) NOT NULL DEFAULT '0' COMMENT 'total costs';
ALTER TABLE `cl_emailing` ADD `cl_offer_id` INT NULL DEFAULT NULL AFTER `cl_commission_id`;
ALTER TABLE `cl_emailing` ADD INDEX(`cl_offer_id`);
ALTER TABLE `cl_emailing` ADD FOREIGN KEY (`cl_offer_id`) REFERENCES `cl_offer`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_offer` ADD `total_sum_off` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0 - celkove soucty zapnuty, 1 - celkové součty vypnuty' AFTER `delivery_price`;
ALTER TABLE `cl_offer` ADD `cl_center_id` INT NULL DEFAULT NULL AFTER `cl_partners_book_id`;
ALTER TABLE `cl_offer` ADD INDEX(`cl_center_id`);
ALTER TABLE `cl_offer` ADD FOREIGN KEY (`cl_center_id`) REFERENCES `cl_center`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_commission_items` ADD `note` TEXT NOT NULL AFTER `description2`;

ALTER TABLE `cl_offer_items` ADD `cl_commission_id` INT NULL DEFAULT NULL AFTER `cl_partners_event_id`, ADD `cl_invoice_id` INT NULL DEFAULT NULL AFTER `cl_commission_id`;
ALTER TABLE `cl_offer_items` ADD INDEX(`cl_commission_id`);
ALTER TABLE `cl_offer_items` ADD INDEX(`cl_invoice_id`);
ALTER TABLE `cl_offer_items` ADD FOREIGN KEY (`cl_commission_id`) REFERENCES `cl_commission`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_offer_items` ADD FOREIGN KEY (`cl_invoice_id`) REFERENCES `cl_invoice`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

//ALTER TABLE `cl_offer_task` ADD `cl_commission_id` INT NULL DEFAULT NULL AFTER `note`, ADD `cl_invoice_id` INT NULL DEFAULT NULL AFTER `cl_commission_id`;
//ALTER TABLE `cl_offer_task` ADD INDEX(`cl_commission_id`);
//ALTER TABLE `cl_offer_task` ADD INDEX(`cl_invoice_id`);
//ALTER TABLE `cl_offer_task` ADD FOREIGN KEY (`cl_commission_id`) REFERENCES `cl_commission`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
//ALTER TABLE `cl_offer_task` ADD FOREIGN KEY (`cl_invoice_id`) REFERENCES `cl_invoice`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

//new table cl_paired_docs
ALTER TABLE `cl_invoice` ADD `description_txt` MEDIUMTEXT NOT NULL AFTER `cl_commission_id`;
ALTER TABLE `cl_invoice` ADD `cl_center_id` INT NULL DEFAULT NULL AFTER `cl_currencies_id`;
ALTER TABLE `cl_invoice` ADD INDEX(`cl_center_id`);
ALTER TABLE `cl_invoice` ADD FOREIGN KEY (`cl_center_id`) REFERENCES `cl_center`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_files` ADD `cl_invoice_id` INT NULL DEFAULT NULL AFTER `cl_store_docs_id`;
ALTER TABLE `cl_files` ADD INDEX(`cl_invoice_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`cl_invoice_id`) REFERENCES `cl_invoice`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice` ADD `cl_partners_book_workers_id` INT NULL DEFAULT NULL AFTER `cl_users_id`;
ALTER TABLE `cl_invoice` ADD INDEX(`cl_partners_book_workers_id`);
ALTER TABLE `cl_invoice` ADD FOREIGN KEY (`cl_partners_book_workers_id`) REFERENCES `cl_partners_book_workers`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_partners_book_workers` ADD `use_invoice` TINYINT(1) NOT NULL DEFAULT '0' AFTER `worker_other`, ADD `use_invoice_arrived` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_invoice`, ADD `use_offer` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_invoice_arrived`, ADD `use_commission` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_offer`, ADD `use_order` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_commission`, ADD `use_delivery` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_order`;
ALTER TABLE `cl_partners_book_workers` CHANGE `use_invoice` `use_cl_invoice` TINYINT(1) NOT NULL DEFAULT '0', CHANGE `use_invoice_arrived` `use_cl_invoice_arrived` TINYINT(1) NOT NULL DEFAULT '0', CHANGE `use_offer` `use_cl_offer` TINYINT(1) NOT NULL DEFAULT '0', CHANGE `use_commission` `use_cl_commission` TINYINT(1) NOT NULL DEFAULT '0', CHANGE `use_order` `use_cl_order` TINYINT(1) NOT NULL DEFAULT '0', CHANGE `use_delivery` `use_cl_delivery` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `cl_invoice_arrived` ADD `cl_partners_book_workers_id` INT NULL DEFAULT NULL AFTER `cl_partners_book_id`;
ALTER TABLE `cl_invoice_arrived` ADD INDEX(`cl_partners_book_workers_id`);
ALTER TABLE `cl_invoice_arrived` ADD FOREIGN KEY (`cl_partners_book_workers_id`) REFERENCES `cl_partners_book_workers`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_commission` ADD `cl_partners_book_workers_id` INT NULL DEFAULT NULL AFTER `cl_partners_book_id`;
ALTER TABLE `cl_commission` ADD INDEX(`cl_partners_book_workers_id`);
ALTER TABLE `cl_commission` ADD FOREIGN KEY (`cl_partners_book_workers_id`) REFERENCES `cl_partners_book_workers`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_offer` ADD `cl_partners_book_workers_id` INT NULL DEFAULT NULL AFTER `cl_partners_book_id`;
ALTER TABLE `cl_offer` ADD INDEX(`cl_partners_book_workers_id`);
ALTER TABLE `cl_offer` ADD FOREIGN KEY (`cl_partners_book_workers_id`) REFERENCES `cl_partners_book_workers`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_emailing` ADD `cl_invoice_id` INT NULL DEFAULT NULL AFTER `cl_offer_id`;
ALTER TABLE `cl_emailing` ADD INDEX(`cl_invoice_id`);
ALTER TABLE `cl_emailing` ADD FOREIGN KEY (`cl_invoice_id`) REFERENCES `cl_invoice`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
//new table commision_items_sel



//new table cl_sale_shorts

ALTER TABLE `cl_sale` ADD `discount` DECIMAL(14) NOT NULL DEFAULT '0' AFTER `cl_commission_id`, ADD `discount_abs` DECIMAL(14) NOT NULL DEFAULT '0' AFTER `discount`;
ALTER TABLE `cl_sale` CHANGE `discount` `discount` DECIMAL(14,2) NOT NULL DEFAULT '0', CHANGE `discount_abs` `discount_abs` DECIMAL(14,2) NOT NULL DEFAULT '0';
ALTER TABLE `cl_order` ADD `cl_partners_book_workers_id` INT NULL DEFAULT NULL AFTER `cl_partners_book_id`;
ALTER TABLE `cl_order` ADD INDEX(`cl_partners_book_workers_id`);
ALTER TABLE `cl_order` ADD FOREIGN KEY (`cl_partners_book_workers_id`) REFERENCES `cl_partners_book_workers`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_order` ADD `description_txt` MEDIUMTEXT NOT NULL AFTER `delivery_method`;
ALTER TABLE `cl_files` ADD `cl_order_id` INT NULL DEFAULT NULL AFTER `cl_invoice_id`;
ALTER TABLE `cl_files` ADD INDEX(`cl_order_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`cl_order_id`) REFERENCES `cl_order`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_paired_docs` ADD `cl_order_id` INT NULL DEFAULT NULL AFTER `cl_store_docs_id`;
ALTER TABLE `cl_paired_docs` ADD INDEX(`cl_order_id`);
ALTER TABLE `cl_paired_docs` ADD FOREIGN KEY (`cl_order_id`) REFERENCES `cl_order`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_store_docs` ADD `cl_partners_book_workers_id` INT NULL DEFAULT NULL AFTER `cl_partners_book_id`;
ALTER TABLE `cl_store_docs` ADD INDEX(`cl_partners_book_workers_id`);
ALTER TABLE `cl_store_docs` ADD FOREIGN KEY (`cl_partners_book_workers_id`) REFERENCES `cl_partners_book_workers`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;



//new table cl_invoice_items_back

ALTER TABLE `cl_invoice_items` ADD `cl_store_move_id` INT NULL DEFAULT NULL AFTER `description2`;
ALTER TABLE `cl_invoice_items` ADD INDEX(`cl_store_move_id`);
ALTER TABLE `cl_invoice_items` ADD FOREIGN KEY (`cl_store_move_id`) REFERENCES `cl_store_move`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice_items` ADD `cl_storage_id` INT NULL DEFAULT NULL AFTER `cl_store_move_id`;
ALTER TABLE `cl_invoice_items` ADD INDEX(`cl_storage_id`);
ALTER TABLE `cl_invoice_items` ADD FOREIGN KEY (`cl_storage_id`) REFERENCES `cl_storage`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice_items_back` ADD `cl_storage_id` INT NULL DEFAULT NULL AFTER `cl_store_move_id`;
ALTER TABLE `cl_invoice_items_back` ADD INDEX(`cl_storage_id`);
ALTER TABLE `cl_invoice_items_back` ADD FOREIGN KEY (`cl_storage_id`) REFERENCES `cl_storage`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `cl_users_license` ADD `addons` TEXT NOT NULL AFTER `cl_company_id`;

ALTER TABLE `cl_invoice` ADD `cl_store_docs_id_in` INT NULL DEFAULT NULL AFTER `cl_store_docs_id`;
ALTER TABLE `cl_store_move` ADD `cl_invoice_items_id` INT NULL DEFAULT NULL AFTER `cl_storage_id`, ADD `cl_invoice_items_back_id` INT NULL DEFAULT NULL AFTER `cl_invoice_items_id`;


ALTER TABLE `cl_invoice_arrived` ADD `cl_center_id` INT NULL DEFAULT NULL AFTER `cl_currencies_id`;
ALTER TABLE `cl_invoice_arrived` ADD INDEX(`cl_center_id`);
ALTER TABLE `cl_invoice_arrived` ADD FOREIGN KEY (`cl_center_id`) REFERENCES `cl_center`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

//new table cl_headers_footers

ALTER TABLE `cl_partners_book` ADD `header_txt` TEXT NOT NULL AFTER `cl_prices_groups_id`, ADD `header_app` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0-overwrite default header, 1-appen to default header' AFTER `header_txt`, ADD `footer_txt` TEXT NOT NULL AFTER `header_app`, ADD `footer_app` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0-overwrite default header, 1-appen to default header' AFTER `footer_txt`;

//new table cl_texts


/new table cl_partners_branch

ALTER TABLE `cl_texts` ADD `text_use` VARCHAR(20) NOT NULL AFTER `cl_users_id`;

ALTER TABLE `cl_invoice` ADD `cl_partners_branch_id` INT NULL DEFAULT NULL AFTER `cl_partners_book_workers_id`;
ALTER TABLE `cl_invoice` ADD INDEX(`cl_partners_branch_id`);
ALTER TABLE `cl_invoice` ADD FOREIGN KEY (`cl_partners_branch_id`) REFERENCES `cl_partners_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `cl_partners_branch` CHANGE `invoice_on` `use_cl_invoice` TINYINT(1) NOT NULL DEFAULT '1', CHANGE `delivery_on` `use_cl_delivery` TINYINT(1) NOT NULL DEFAULT '1', CHANGE `commission_on` `use_cl_commission` TINYINT(1) NOT NULL DEFAULT '1', CHANGE `offer_on` `use_cl_offer` TINYINT(1) NOT NULL DEFAULT '1';

ALTER TABLE `cl_commission` ADD `cl_partners_branch_id` INT NULL DEFAULT NULL AFTER `cl_partners_book_workers_id`;
ALTER TABLE `cl_commission` ADD INDEX(`cl_partners_branch_id`);
ALTER TABLE `cl_commission` ADD FOREIGN KEY (`cl_partners_branch_id`) REFERENCES `cl_partners_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `cl_offer` ADD `cl_partners_branch_id` INT NULL DEFAULT NULL AFTER `cl_partners_book_workers_id`;
ALTER TABLE `cl_offer` ADD INDEX(`cl_partners_branch_id`);
ALTER TABLE `cl_offer` ADD FOREIGN KEY (`cl_partners_branch_id`) REFERENCES `cl_partners_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

//ALTER TABLE `cl_partners_branch` CHANGE `b_name` `b_name` VARCHAR(60) CHARACTER SET utf32 COLLATE utf32_czech_ci NOT NULL, CHANGE `b_street` `b_street` VARCHAR(60) CHARACTER SET utf32 COLLATE utf32_czech_ci NOT NULL, CHANGE `b_city` `b_city` VARCHAR(60) CHARACTER SET utf32 COLLATE utf32_czech_ci NOT NULL, CHANGE `b_phone` `b_phone` VARCHAR(60) CHARACTER SET utf32 COLLATE utf32_czech_ci NOT NULL, CHANGE `b_email` `b_email` VARCHAR(60) CHARACTER SET utf32 COLLATE utf32_czech_ci NOT NULL, CHANGE `b_person` `b_person` VARCHAR(60) CHARACTER SET utf32 COLLATE utf32_czech_ci NOT NULL, CHANGE `create_by` `create_by` VARCHAR(20) CHARACTER SET utf32 COLLATE utf32_czech_ci NOT NULL, CHANGE `change_by` `change_by` VARCHAR(20) CHARACTER SET utf32 COLLATE utf32_czech_ci NOT NULL;

//uploaded on production 03.01.2019

//new from 03.01.2019
ALTER TABLE `cl_partners_branch` ADD `use_cl_invoice_arrived` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_cl_offer`;
ALTER TABLE `cl_files` ADD `cl_offer_id` INT NULL DEFAULT NULL AFTER `cl_commission_id`;
ALTER TABLE `cl_files` ADD INDEX(`cl_offer_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`cl_offer_id`) REFERENCES `cl_offer`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;


//new table cl_pricelist_macro
ALTER TABLE `cl_files` ADD `cl_pricelist_id` INT NULL DEFAULT NULL AFTER `cl_order_id`;
ALTER TABLE `cl_files` ADD INDEX(`cl_pricelist_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`cl_pricelist_id`) REFERENCES `cl_pricelist`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_pricelist` ADD `order_code` VARCHAR(64) NOT NULL AFTER `ean_code`;
ALTER TABLE `cl_pricelist` ADD `cl_storage_id` INT NULL DEFAULT NULL AFTER `cl_users_id`;
ALTER TABLE `cl_pricelist` ADD INDEX(`cl_storage_id`);
ALTER TABLE `cl_pricelist` ADD FOREIGN KEY (`cl_storage_id`) REFERENCES `cl_storage`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_pricelist` ADD `order_label` VARCHAR(200) NOT NULL COMMENT 'objednaci nazev' AFTER `item_label`;
ALTER TABLE `cl_pricelist` ADD `image` VARCHAR(68) NOT NULL AFTER `cl_storage_id`;

ALTER TABLE `cl_files` ADD `cl_pricelist_image_id` INT NULL DEFAULT NULL AFTER `cl_pricelist_id`;
ALTER TABLE `cl_files` ADD INDEX(`cl_pricelist_image_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`cl_pricelist_image_id`) REFERENCES `cl_pricelist`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `cl_pricelist` ADD `length` DECIMAL(15) NOT NULL DEFAULT '0' AFTER `image`, ADD `width` DECIMAL(15) NOT NULL DEFAULT '0' AFTER `length`, ADD `height` DECIMAL(15) NOT NULL DEFAULT '0' AFTER `width`, ADD `weight` DECIMAL(15) NOT NULL DEFAULT '0' AFTER `height`, ADD `in_package` DECIMAL(15) NOT NULL DEFAULT '0' COMMENT 'počet v balení' AFTER `weight`, ADD `excise_duty` DECIMAL(15) NOT NULL COMMENT 'spotřební daň' AFTER `in_package`;
ALTER TABLE `cl_pricelist` CHANGE `length` `length` DECIMAL(15,2) NOT NULL DEFAULT '0', CHANGE `width` `width` DECIMAL(15,2) NOT NULL DEFAULT '0', CHANGE `height` `height` DECIMAL(15,2) NOT NULL DEFAULT '0', CHANGE `weight` `weight` DECIMAL(15,2) NOT NULL DEFAULT '0', CHANGE `in_package` `in_package` DECIMAL(15,2) NOT NULL DEFAULT '0' COMMENT 'počet v balení', CHANGE `excise_duty` `excise_duty` DECIMAL(15,2) NOT NULL COMMENT 'spotřební daň';

ALTER TABLE `cl_pricelist` ADD `length_unit` TINYINT(1) NOT NULL DEFAULT '0' AFTER `length`;
ALTER TABLE `cl_pricelist` ADD `width_unit` TINYINT(1) NOT NULL DEFAULT '0' AFTER `width`;
ALTER TABLE `cl_pricelist` ADD `height_unit` TINYINT(1) NOT NULL DEFAULT '0' AFTER `height`;
ALTER TABLE `cl_pricelist` ADD `weight_unit` TINYINT(1) NOT NULL DEFAULT '0' AFTER `weight`;
ALTER TABLE `cl_pricelist` ADD `description_txt` MEDIUMTEXT NOT NULL AFTER `excise_duty`;

//uploaded on production and beta 20.01.2019

//new

ALTER TABLE `cl_commission_items` ADD `cl_commission_items_sel_id` INT NULL DEFAULT NULL AFTER `cl_users_id`;
ALTER TABLE `cl_commission_items` ADD INDEX(`cl_commission_items_sel_id`);
ALTER TABLE `cl_commission_items` ADD FOREIGN KEY (`cl_commission_items_sel_id`) REFERENCES `cl_commission_items_sel`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_commission_items` DROP FOREIGN KEY `cl_commission_items_ibfk_4`; ALTER TABLE `cl_commission_items` ADD CONSTRAINT `cl_commission_items_ibfk_4` FOREIGN KEY (`cl_commission_items_sel_id`) REFERENCES `cl_commission_items_sel`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `cl_commission_items` ADD `cl_pricelist_macro_id` INT NULL DEFAULT NULL AFTER `cl_commission_items_sel_id`;
ALTER TABLE `cl_commission_items` ADD INDEX(`cl_pricelist_macro_id`);
ALTER TABLE `cl_commission_items` ADD FOREIGN KEY (`cl_pricelist_macro_id`) REFERENCES `cl_pricelist_macro`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `cl_company` ADD `cl_storage_id_macro` INT NULL DEFAULT NULL AFTER `cl_storage_id_commission`;

ALTER TABLE `cl_users` ADD `quick_sums` TINYINT(1) NOT NULL DEFAULT '1' AFTER `picture_stamp`;

ALTER TABLE `cl_company` ADD `invoice_to_store` TINYINT(1) NOT NULL DEFAULT '1' AFTER `sms_manager`;
ALTER TABLE `cl_store_move` ADD `cl_store_docs_macro_id` INT NULL DEFAULT NULL AFTER `description`;
ALTER TABLE `cl_store_move` ADD INDEX(`cl_store_docs_macro_id`);
ALTER TABLE `cl_store_move` ADD FOREIGN KEY (`cl_store_docs_macro_id`) REFERENCES `cl_store_docs`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_store_move` ADD `cl_pricelist_macro_id` INT NULL DEFAULT NULL AFTER `cl_store_docs_macro_id`;
ALTER TABLE `cl_store_move` ADD INDEX(`cl_pricelist_macro_id`);
ALTER TABLE `cl_store_move` ADD FOREIGN KEY (`cl_pricelist_macro_id`) REFERENCES `cl_pricelist_macro`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_store_move` ADD `cl_store_docs_macro_in_id` INT NULL DEFAULT NULL AFTER `cl_store_docs_macro_id`;
ALTER TABLE `cl_store_move` ADD INDEX(`cl_store_docs_macro_in_id`);
ALTER TABLE `cl_store_docs` ADD `cl_invoice_arrived_id` INT NULL DEFAULT NULL AFTER `description_txt`;
ALTER TABLE `cl_store_docs` ADD INDEX(`cl_invoice_arrived_id`);
ALTER TABLE `cl_store_docs` ADD FOREIGN KEY (`cl_invoice_arrived_id`) REFERENCES `cl_invoice_arrived`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_paired_docs` ADD `cl_invoice_arrived_id` INT NULL DEFAULT NULL AFTER `cl_order_id`;
ALTER TABLE `cl_paired_docs` ADD INDEX(`cl_invoice_arrived_id`);
ALTER TABLE `cl_paired_docs` ADD FOREIGN KEY (`cl_invoice_arrived_id`) REFERENCES `cl_invoice_arrived`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_partners_event` ADD `cl_partners_branch_id` INT NULL DEFAULT NULL AFTER `selected`;
ALTER TABLE `cl_partners_event` ADD INDEX(`cl_partners_branch_id`);
ALTER TABLE `cl_partners_event` ADD FOREIGN KEY (`cl_partners_branch_id`) REFERENCES `cl_partners_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `cl_pricelist_group` ADD `is_product` TINYINT(1) NOT NULL DEFAULT '0' AFTER `cl_users_id`;
ALTER TABLE `cl_pricelist_group` ADD `is_component` TINYINT(1) NOT NULL DEFAULT '0' AFTER `is_product`;

//uploaded on production and beta 06.02.2019
ALTER TABLE `cl_store_move` ADD `cl_store_docs_in_id` INT NULL DEFAULT NULL AFTER `cl_pricelist_macro_id`;
ALTER TABLE `cl_store_move` ADD INDEX(`cl_store_docs_in_id`);
ALTER TABLE `cl_store_docs` DROP FOREIGN KEY `cl_store_docs_ibfk_8`; ALTER TABLE `cl_store_docs` ADD CONSTRAINT `cl_store_docs_ibfk_8` FOREIGN KEY (`cl_invoice_id`) REFERENCES `cl_invoice`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice` DROP FOREIGN KEY `cl_invoice_ibfk_9`; ALTER TABLE `cl_invoice` ADD CONSTRAINT `cl_invoice_ibfk_9` FOREIGN KEY (`cl_store_docs_id`) REFERENCES `cl_store_docs`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_paired_docs` DROP FOREIGN KEY `cl_paired_docs_ibfk_5`; ALTER TABLE `cl_paired_docs` ADD CONSTRAINT `cl_paired_docs_ibfk_5` FOREIGN KEY (`cl_invoice_id`) REFERENCES `cl_invoice`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `cl_paired_docs` DROP FOREIGN KEY `cl_paired_docs_ibfk_3`;
ALTER TABLE `cl_paired_docs` ADD  CONSTRAINT `cl_paired_docs_ibfk_3` FOREIGN KEY (`cl_commission_id`) REFERENCES `cl_commission`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `cl_paired_docs` DROP FOREIGN KEY `cl_paired_docs_ibfk_4`;
ALTER TABLE `cl_paired_docs` ADD  CONSTRAINT `cl_paired_docs_ibfk_4` FOREIGN KEY (`cl_offer_id`) REFERENCES `cl_offer`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `cl_paired_docs` DROP FOREIGN KEY `cl_paired_docs_ibfk_6`;
ALTER TABLE `cl_paired_docs` ADD  CONSTRAINT `cl_paired_docs_ibfk_6` FOREIGN KEY (`cl_store_docs_id`) REFERENCES `cl_store_docs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `cl_paired_docs` DROP FOREIGN KEY `cl_paired_docs_ibfk_7`;
ALTER TABLE `cl_paired_docs` ADD  CONSTRAINT `cl_paired_docs_ibfk_7` FOREIGN KEY (`cl_order_id`) REFERENCES `cl_order`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `cl_paired_docs` DROP FOREIGN KEY `cl_paired_docs_ibfk_8`;
ALTER TABLE `cl_paired_docs` ADD  CONSTRAINT `cl_paired_docs_ibfk_8` FOREIGN KEY (`cl_invoice_arrived_id`) REFERENCES `cl_invoice_arrived`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

//POZOR, zkontrolovat znaky po konverzi
ALTER TABLE `cl_pricelist` CHANGE `identification` `identification` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL, CHANGE `item_label` `item_label` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL;
ALTER TABLE `cl_pricelist` ADD FULLTEXT(`identification`);
ALTER TABLE `cl_pricelist` ADD FULLTEXT(`item_label`);
ALTER TABLE `cl_pricelist` ADD FULLTEXT(`identification`,`item_label`)
//uploaded on production and beta 21.02.2019


ALTER TABLE `cl_partners_event` ADD `cl_center_id` INT NULL DEFAULT NULL AFTER `cl_partners_category_id`;
ALTER TABLE `cl_partners_event` ADD INDEX(`cl_center_id`);
ALTER TABLE `cl_partners_event` ADD FOREIGN KEY (`cl_center_id`) REFERENCES `cl_center`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_partners_event` ADD `item_order` INT NOT NULL DEFAULT '0' AFTER `id`;
ALTER TABLE `cl_partners_book_workers` ADD `use_cl_partners_event` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_cl_delivery`;
ALTER TABLE `cl_partners_branch` ADD `use_cl_partners_event` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_cl_invoice_arrived`;
ALTER TABLE `cl_invoice_arrived` ADD `cl_partners_branch_id` INT NULL DEFAULT NULL AFTER `cl_partners_book_workers_id`;
//uploaded on production and beta 23.2.2019


ALTER TABLE `cl_invoice` ADD `cl_offer_id` INT NULL DEFAULT NULL AFTER `cl_commission_id`;
ALTER TABLE `cl_invoice` ADD INDEX(`cl_offer_id`);
ALTER TABLE `cl_invoice` DROP FOREIGN KEY `cl_invoice_ibfk_15`; ALTER TABLE `cl_invoice` ADD CONSTRAINT `cl_invoice_ibfk_15` FOREIGN KEY (`cl_offer_id`) REFERENCES `cl_offer`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice` ADD FOREIGN KEY (`cl_offer_id`) REFERENCES `cl_offer`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
//allready uploaded


ALTER TABLE `cl_commission_items` ADD `cl_invoice_id` INT NULL DEFAULT NULL AFTER `note`;
ALTER TABLE `cl_commission_items` ADD INDEX(`cl_invoice_id`);
ALTER TABLE `cl_commission_items` ADD FOREIGN KEY (`cl_invoice_id`) REFERENCES `cl_invoice`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_commission_items` DROP FOREIGN KEY `cl_commission_items_ibfk_6`; ALTER TABLE `cl_commission_items` ADD CONSTRAINT `cl_commission_items_ibfk_6` FOREIGN KEY (`cl_invoice_id`) REFERENCES `cl_invoice`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_commission_items_sel` ADD `cl_invoice_id` INT NULL DEFAULT NULL AFTER `note`;
ALTER TABLE `cl_commission_items_sel` ADD INDEX(`cl_invoice_id`);
ALTER TABLE `cl_commission_items_sel` ADD FOREIGN KEY (`cl_invoice_id`) REFERENCES `cl_invoice`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_commission_work` ADD `cl_invoice_id` INT NULL DEFAULT NULL AFTER `cl_commission_task_id`;
ALTER TABLE `cl_commission_work` ADD INDEX(`cl_invoice_id`);
ALTER TABLE `cl_commission_work` ADD FOREIGN KEY (`cl_invoice_id`) REFERENCES `cl_invoice`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_store_docs` ADD `cl_commission_id` INT NULL DEFAULT NULL AFTER `cl_invoice_arrived_id`;
ALTER TABLE `cl_store_docs` ADD INDEX(`cl_commission_id`);
ALTER TABLE `cl_store_docs` ADD FOREIGN KEY (`cl_commission_id`) REFERENCES `cl_commission`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_store_docs` ADD `cl_offer_id` INT NULL DEFAULT NULL AFTER `cl_commission_id`;
ALTER TABLE `cl_store_docs` ADD INDEX(`cl_offer_id`);
ALTER TABLE `cl_store_docs` ADD FOREIGN KEY (`cl_offer_id`) REFERENCES `cl_offer`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_offer` DROP FOREIGN KEY `cl_offer_ibfk_7`; ALTER TABLE `cl_offer` ADD CONSTRAINT `cl_offer_ibfk_7` FOREIGN KEY (`cl_invoice_id`) REFERENCES `cl_invoice`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT; ALTER TABLE `cl_offer` DROP FOREIGN KEY `cl_offer_ibfk_8`; ALTER TABLE `cl_offer` ADD CONSTRAINT `cl_offer_ibfk_8` FOREIGN KEY (`cl_store_docs_id`) REFERENCES `cl_store_docs`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_commission_items_sel` ADD `cl_store_docs_id` INT NULL DEFAULT NULL AFTER `cl_invoice_id`;
ALTER TABLE `cl_commission_items_sel` ADD INDEX(`cl_store_docs_id`);
ALTER TABLE `cl_commission_items_sel` ADD FOREIGN KEY (`cl_store_docs_id`) REFERENCES `cl_store_docs`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice` DROP FOREIGN KEY `cl_invoice_ibfk_11`; ALTER TABLE `cl_invoice` ADD CONSTRAINT `cl_invoice_ibfk_11` FOREIGN KEY (`cl_commission_id`) REFERENCES `cl_commission`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_commission` DROP FOREIGN KEY `cl_commission_ibfk_7`; ALTER TABLE `cl_commission` ADD CONSTRAINT `cl_commission_ibfk_7` FOREIGN KEY (`cl_invoice_id`) REFERENCES `cl_invoice`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

//new table cl_pricelist_bonds
ALTER TABLE `cl_invoice_items` ADD `cl_invoice_items_bond_id` INT NULL DEFAULT NULL AFTER `cl_store_move_id`;
ALTER TABLE `cl_commission_items_sel` ADD `cl_parent_bond_id` INT NULL DEFAULT NULL AFTER `cl_store_docs_id`;
ALTER TABLE `cl_invoice_items` CHANGE `cl_invoice_items_bond_id` `cl_parent_bond_id` INT(11) NULL DEFAULT NULL;
ALTER TABLE `cl_offer_items` ADD `cl_parent_bond_id` INT NULL DEFAULT NULL AFTER `description2`;
//uploaded on beta nad production 14.03.2019

//18.03.2019
ALTER TABLE `cl_offer` ADD `cl_commission_id` INT NULL DEFAULT NULL AFTER `total_sum_off`;
ALTER TABLE `cl_offer` ADD INDEX(`cl_commission_id`);
ALTER TABLE `cl_offer` ADD FOREIGN KEY (`cl_commission_id`) REFERENCES `cl_commission`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

//24.03.2019
//new table cl_delivery_note
//new table cl_delivery_note_items
//new table cl_delivery_note_items_back

ALTER TABLE `cl_files` ADD `cl_delivery_note_id` INT NULL DEFAULT NULL AFTER `cl_pricelist_image_id`;
ALTER TABLE `cl_files` ADD INDEX(`cl_delivery_note_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`cl_delivery_note_id`) REFERENCES `cl_delivery_note`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_paired_docs` ADD `cl_delivery_note_id` INT NULL DEFAULT NULL AFTER `cl_invoice_arrived_id`;
ALTER TABLE `cl_paired_docs` ADD INDEX(`cl_delivery_note_id`);
ALTER TABLE `cl_paired_docs` ADD FOREIGN KEY (`cl_delivery_note_id`) REFERENCES `cl_delivery_note`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `cl_partners_book_workers` ADD `use_cl_delivery_note` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_cl_partners_event`;
ALTER TABLE `cl_partners_book_workers` DROP `use_cl_delivery`;
ALTER TABLE `cl_store_docs` ADD `cl_delivery_note_id` INT NULL DEFAULT NULL AFTER `cl_offer_id`;
ALTER TABLE `cl_store_docs` ADD INDEX(`cl_delivery_note_id`);
ALTER TABLE `cl_store_docs` ADD FOREIGN KEY (`cl_delivery_note_id`) REFERENCES `bra`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_partners_branch` ADD `use_cl_delivery_note` TINYINT NOT NULL DEFAULT '1' AFTER `use_cl_partners_event`;
ALTER TABLE `cl_invoice` ADD `cl_delivery_note_id` INT NULL DEFAULT NULL AFTER `cl_offer_id`;
ALTER TABLE `cl_invoice` ADD INDEX(`cl_delivery_note_id`);
ALTER TABLE `cl_invoice` ADD FOREIGN KEY (`cl_delivery_note_id`) REFERENCES `cl_delivery_note`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice_items` ADD `cl_delivery_note_id` INT NULL DEFAULT NULL AFTER `cl_storage_id`;
ALTER TABLE `cl_invoice_items` ADD INDEX(`cl_delivery_note_id`);
ALTER TABLE `cl_invoice_items_back` ADD `cl_delivery_note_id` INT NULL DEFAULT NULL AFTER `cl_storage_id`;
ALTER TABLE `cl_invoice_items_back` ADD INDEX(`cl_delivery_note_id`);


//05.04.2019
ALTER TABLE `cl_pricelist` ADD `cl_partners_book_id` INT NULL DEFAULT NULL AFTER `cl_storage_id`;
ALTER TABLE `cl_pricelist` ADD INDEX(`cl_partners_book_id`);
ALTER TABLE `cl_pricelist` ADD FOREIGN KEY (`cl_partners_book_id`) REFERENCES `cl_partners_book`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_partners_book` ADD `supplier` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '1 - dodavatel' AFTER `footer_app`, ADD `customer` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '1 - odběratel' AFTER `supplier`;
ALTER TABLE `cl_order_items` ADD `cl_storage_id` INT NULL DEFAULT NULL AFTER `quantity_rcv`;
ALTER TABLE `cl_order_items` ADD INDEX(`cl_storage_id`);
ALTER TABLE `cl_order_items` ADD FOREIGN KEY (`cl_storage_id`) REFERENCES `cl_store`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_order_items` DROP FOREIGN KEY `cl_order_items_ibfk_5`; ALTER TABLE `cl_order_items` ADD CONSTRAINT `cl_order_items_ibfk_5` FOREIGN KEY (`cl_storage_id`) REFERENCES `cl_storage`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_commission_items_sel` CHANGE `cl_pricelist_id` `cl_pricelist_id` INT(11) NULL DEFAULT NULL;
//nahradit 0 za NULL v cl_commission_items_sel
UPDATE `cl_commission_items_sel` SET cl_pricelist_id = NULL WHERE cl_pricelist_id = 0
ALTER TABLE `cl_commission_items_sel` ADD FOREIGN KEY (`cl_pricelist_id`) REFERENCES `cl_pricelist`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_commission_items` CHANGE `cl_pricelist_id` `cl_pricelist_id` INT(11) NULL DEFAULT NULL;
UPDATE `cl_commission_items` SET cl_pricelist_id = NULL WHERE cl_pricelist_id = 0
ALTER TABLE `cl_commission_items` ADD FOREIGN KEY (`cl_pricelist_id`) REFERENCES `cl_pricelist`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_commission_items_sel` ADD `cl_order_id` INT NULL DEFAULT NULL AFTER `cl_parent_bond_id`;
ALTER TABLE `cl_commission_items` ADD `cl_order_id` INT NULL DEFAULT NULL AFTER `cl_invoice_id`;
ALTER TABLE `cl_commission_items` ADD INDEX(`cl_order_id`);
ALTER TABLE `cl_commission_items` ADD FOREIGN KEY (`cl_order_id`) REFERENCES `cl_order`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_commission_items_sel` ADD INDEX(`cl_order_id`);
ALTER TABLE `cl_commission_items_sel` ADD FOREIGN KEY (`cl_order_id`) REFERENCES `cl_order`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_order_items` ADD `cl_store_docs_id` INT NULL DEFAULT NULL AFTER `note_txt`;
ALTER TABLE `cl_order_items` ADD INDEX(`cl_store_docs_id`);
ALTER TABLE `cl_order_items` ADD FOREIGN KEY (`cl_store_docs_id`) REFERENCES `cl_store_docs`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_store_docs` ADD `cl_order_id` INT NULL DEFAULT NULL AFTER `cl_delivery_note_id`;
ALTER TABLE `cl_store_docs` ADD INDEX(`cl_order_id`);
ALTER TABLE `cl_store_docs` ADD FOREIGN KEY (`cl_order_id`) REFERENCES `cl_order`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_order` ADD `cl_store_docs_id_in` INT NULL DEFAULT NULL AFTER `description_txt`;
ALTER TABLE `cl_order` ADD INDEX(`cl_store_docs_id_in`);
ALTER TABLE `cl_order` ADD FOREIGN KEY (`cl_store_docs_id_in`) REFERENCES `cl_store_docs`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_status` ADD `s_pdf` TINYINT NOT NULL DEFAULT '0' AFTER `s_fin`, ADD `s_eml` TINYINT NOT NULL DEFAULT '0' AFTER `s_pdf`;
ALTER TABLE `cl_order_items` ADD `reminder` TINYINT(1) NOT NULL DEFAULT '0' AFTER `cl_store_docs_id`;
//nahrano na betu 15.04.2019
ALTER TABLE `cl_commission_items_sel` ADD `cl_store_move_id` INT NULL DEFAULT NULL AFTER `cl_order_id`;
ALTER TABLE `cl_commission_items_sel` ADD INDEX(`cl_store_move_id`);
ALTER TABLE `cl_commission_items_sel` ADD FOREIGN KEY (`cl_store_move_id`) REFERENCES `cl_store_move`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

ALTER TABLE `cl_commission_items` ADD `cl_store_move_id` INT NULL DEFAULT NULL AFTER `cl_order_id`;
ALTER TABLE `cl_commission_items` ADD INDEX(`cl_store_move_id`);
ALTER TABLE `cl_commission_items` ADD FOREIGN KEY (`cl_store_move_id`) REFERENCES `cl_store_move`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_commission_items_sel` ADD `cl_storage_id` INT NULL DEFAULT NULL AFTER `cl_store_move_id`;

ALTER TABLE `cl_commission_items` ADD `cl_store_docs_id` INT NULL DEFAULT NULL AFTER `cl_invoice_id`;
ALTER TABLE `cl_commission_items` ADD INDEX(`cl_store_docs_id`);
ALTER TABLE `cl_commission_items` ADD FOREIGN KEY (`cl_store_docs_id`) REFERENCES `cl_store_docs`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_commission_items` ADD `cl_storage_id` INT NULL DEFAULT NULL AFTER `cl_store_move_id`;

ALTER TABLE `cl_store_docs` ADD `cl_sale_id` INT NULL DEFAULT NULL AFTER `cl_order_id`;
ALTER TABLE `cl_store_docs` ADD INDEX(`cl_sale_id`);
ALTER TABLE `cl_store_docs` ADD FOREIGN KEY (`cl_sale_id`) REFERENCES `cl_sale`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_paired_docs` ADD `cl_sale_id` INT NULL DEFAULT NULL AFTER `cl_delivery_note_id`;
ALTER TABLE `cl_paired_docs` ADD INDEX(`cl_sale_id`);
ALTER TABLE `cl_paired_docs` ADD FOREIGN KEY (`cl_sale_id`) REFERENCES `cl_sale`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `cl_sale_items` ADD `cl_storage_id` INT NULL DEFAULT NULL AFTER `price_e2_vat`;
ALTER TABLE `cl_sale_items` ADD `cl_store_move_id` INT NULL DEFAULT NULL AFTER `cl_storage_id`;
ALTER TABLE `cl_sale_items` ADD INDEX(`cl_store_move_id`);
ALTER TABLE `cl_sale_items` ADD FOREIGN KEY (`cl_store_move_id`) REFERENCES `cl_store_move`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
//nahrano  12.5.2019

ALTER TABLE `cl_payment_types` ADD `payment_type` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0-transfer,1-cash,2-cash on delivery' AFTER `cl_users_id`;
ALTER TABLE `cl_partners_book` ADD `partner_code` VARCHAR(16) NOT NULL COMMENT 'code for loyalty club' AFTER `customer`;
new table cl_company_branch
new table cl_company_branch_users
ALTER TABLE `cl_sale` ADD `cl_partners_branch_id` INT NULL DEFAULT NULL AFTER `cl_payment_types_id`;
ALTER TABLE `cl_sale` ADD INDEX(`cl_partners_branch_id`);
ALTER TABLE `cl_sale` ADD FOREIGN KEY (`cl_partners_branch_id`) REFERENCES `cl_partners_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_sale` ADD `cl_company_branch_id` INT NULL DEFAULT NULL AFTER `cl_partners_branch_id`;
ALTER TABLE `cl_sale` ADD INDEX(`cl_company_branch_id`);
ALTER TABLE `cl_sale` ADD FOREIGN KEY (`cl_company_branch_id`) REFERENCES `cl_company_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_sale` ADD `cl_storage_id` INT NULL DEFAULT NULL AFTER `cl_company_branch_id`;
ALTER TABLE `cl_sale` ADD INDEX(`cl_storage_id`);
ALTER TABLE `cl_sale` ADD `sale_type` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0-sale,1-correction' AFTER `cl_partners_book_id`;
ALTER TABLE `cl_sale` ADD `cl_partners_book_workers_id` INT NULL DEFAULT NULL AFTER `cl_partners_book_id`;
ALTER TABLE `cl_sale_items` ADD FOREIGN KEY (`cl_storage_id`) REFERENCES `cl_storage`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_sale` ADD FOREIGN KEY (`cl_documents_id`) REFERENCES `cl_documents`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_sale_items` ADD `quantity_back` DECIMAL(15,4) NOT NULL DEFAULT '0' AFTER `quantity`;
ALTER TABLE `cl_sale_items` ADD `quantity_in` DECIMAL(15,4) NOT NULL DEFAULT '0' AFTER `quantity_back`;
ALTER TABLE `cl_sale_items` CHANGE `quantity` `quantity` DECIMAL(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'prodané množství', CHANGE `quantity_back` `quantity_back` DECIMAL(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'vrátit teď', CHANGE `quantity_in` `quantity_in` DECIMAL(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'celkem vráceno';
ALTER TABLE `cl_sale_items` ADD `note` TEXT NOT NULL AFTER `cl_store_move_id`;
ALTER TABLE `cl_sale` ADD `correction_cl_sale_id` INT NULL DEFAULT NULL AFTER `cl_commission_id`;
ALTER TABLE `cl_sale` ADD INDEX(`correction_cl_sale_id`);
ALTER TABLE `cl_sale` ADD FOREIGN KEY (`correction_cl_sale_id`) REFERENCES `cl_sale`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_sale` ADD `cl_store_docs_id_in` INT NULL DEFAULT NULL AFTER `cl_store_docs_id`;
ALTER TABLE `cl_sale_items` ADD `price_e2_back` DECIMAL(15,4) NOT NULL DEFAULT '0' AFTER `price_e2_vat`, ADD `price_e2_vat_back` DECIMAL(15,4) NOT NULL DEFAULT '0' AFTER `price_e2_back`;
ALTER TABLE `cl_sale_items` ADD `cl_sale_items_id` INT NULL DEFAULT NULL AFTER `quantity_in`;
new table cl_eet
ALTER TABLE `cl_sale` ADD `cl_eet_id` INT NULL DEFAULT NULL AFTER `cl_partners_book_workers_id`;
ALTER TABLE `cl_sale` ADD FOREIGN KEY (`cl_eet_id`) REFERENCES `cl_eet`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_company` ADD `eet_pfx` VARCHAR(60) NOT NULL COMMENT 'name for eet pfx file' AFTER `invoice_to_store`;
ALTER TABLE `cl_company` ADD `eet_pass` VARCHAR(32) NOT NULL COMMENT 'password for pfx' AFTER `eet_pfx`, ADD `eet_active` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0-eet neaktivní, 1 - eet aktivní' AFTER `eet_pass`, ADD `eet_test` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0-běžný režim, 1-testovací režim' AFTER `eet_active`, ADD `eet_id_provoz` VARCHAR(6) NOT NULL COMMENT 'id provozovny' AFTER `eet_test`, ADD `eet_id_pokl` VARCHAR(20) NOT NULL COMMENT 'id pokladny' AFTER `eet_id_provoz`;
ALTER TABLE `cl_company` CHANGE `eet_id_pokl` `eet_id_poklad` VARCHAR(20) CHARACTER SET utf32 COLLATE utf32_czech_ci NOT NULL COMMENT 'id pokladny';
ALTER TABLE `cl_sale` ADD `vat_active` TINYINT(1) NOT NULL AFTER `inv_title`;
ALTER TABLE `cl_invoice` ADD `vat_active` TINYINT(1) NOT NULL AFTER `inv_title`;
ALTER TABLE `cl_invoice` ADD `cl_eet_id` INT NULL DEFAULT NULL AFTER `cl_center_id`;
ALTER TABLE `cl_invoice` ADD INDEX(`cl_eet_id`);
ALTER TABLE `cl_invoice` ADD FOREIGN KEY (`cl_eet_id`) REFERENCES `cl_eet`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice` ADD `cl_company_branch_id` INT NULL DEFAULT NULL AFTER `cl_company_id`;
ALTER TABLE `cl_invoice` ADD INDEX(`cl_company_branch_id`);
ALTER TABLE `cl_invoice` ADD FOREIGN KEY (`cl_company_branch_id`) REFERENCES `cl_company_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
new table cl_cash
new table cl_cash_types
ALTER TABLE `cl_files` ADD `cl_cash_id` INT NULL DEFAULT NULL AFTER `cl_delivery_note_id`;
ALTER TABLE `cl_files` ADD INDEX(`cl_cash_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`cl_cash_id`) REFERENCES `cl_cash`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_sale` ADD `cl_cash_id` INT NULL DEFAULT NULL AFTER `discount_abs`;
ALTER TABLE `cl_sale` ADD INDEX(`cl_cash_id`);
ALTER TABLE `cl_sale` ADD FOREIGN KEY (`cl_cash_id`) REFERENCES `cl_cash`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_paired_docs` ADD `cl_cash_id` INT NULL DEFAULT NULL AFTER `cl_sale_id`;
ALTER TABLE `cl_paired_docs` ADD INDEX(`cl_cash_id`);
ALTER TABLE `cl_paired_docs` ADD FOREIGN KEY (`cl_cash_id`) REFERENCES `cl_cash`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice_payments` ADD `cl_cash_id` INT NULL DEFAULT NULL AFTER `cl_invoice_id`;
ALTER TABLE `cl_invoice_payments` ADD INDEX(`cl_cash_id`);
ALTER TABLE `cl_invoice_payments` ADD FOREIGN KEY (`cl_cash_id`) REFERENCES `cl_cash`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice_payments` ADD `cl_payment_types_id` INT NULL DEFAULT NULL AFTER `cl_currencies_id`;
ALTER TABLE `cl_invoice_payments` ADD INDEX(`cl_payment_types_id`);
ALTER TABLE `cl_invoice_payments` ADD FOREIGN KEY (`cl_payment_types_id`) REFERENCES `cl_payment_types`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice_arrived_payments` ADD `cl_cash_id` INT NULL DEFAULT NULL AFTER `cl_invoice_arrived_id`;
ALTER TABLE `cl_invoice_arrived_payments` ADD INDEX(`cl_cash_id`);
ALTER TABLE `cl_invoice_arrived_payments` ADD FOREIGN KEY (`cl_cash_id`) REFERENCES `cl_cash`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice_arrived_payments` ADD `cl_payment_types_id` INT NULL DEFAULT NULL AFTER `cl_currencies_id`;
ALTER TABLE `cl_invoice_arrived_payments` ADD INDEX(`cl_payment_types_id`);
ALTER TABLE `cl_invoice_arrived_payments` ADD FOREIGN KEY (`cl_payment_types_id`) REFERENCES `cl_payment_types`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_pricelist` ADD `weight_netto` DECIMAL(15,2) NOT NULL DEFAULT '0' AFTER `weight_unit`, ADD `weight_netto_unit` TINYINT(1) NOT NULL DEFAULT '0' AFTER `weight_netto`;
ALTER TABLE `cl_store` ADD `exp_date` DATE NULL DEFAULT NULL AFTER `batch`;
ALTER TABLE `cl_store_move` ADD `cl_countries_id` INT NULL DEFAULT NULL AFTER `cl_store_docs_in_id`;
ALTER TABLE `cl_store_move` ADD INDEX(`cl_countries_id`);
ALTER TABLE `cl_store_move` ADD FOREIGN KEY (`cl_countries_id`) REFERENCES `cl_countries`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_delivery_note_items` DROP FOREIGN KEY `cl_delivery_note_items_ibfk_5`; ALTER TABLE `cl_delivery_note_items` ADD CONSTRAINT `cl_delivery_note_items_ibfk_5` FOREIGN KEY (`cl_store_move_id`) REFERENCES `cl_store_move`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_delivery_note_items_back` DROP FOREIGN KEY `cl_delivery_note_items_back_ibfk_5`; ALTER TABLE `cl_delivery_note_items_back` ADD CONSTRAINT `cl_delivery_note_items_back_ibfk_5` FOREIGN KEY (`cl_store_move_id`) REFERENCES `cl_store_move`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice_items` DROP FOREIGN KEY `cl_invoice_items_ibfk_4`; ALTER TABLE `cl_invoice_items` ADD CONSTRAINT `cl_invoice_items_ibfk_4` FOREIGN KEY (`cl_store_move_id`) REFERENCES `cl_store_move`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice_items_back` DROP FOREIGN KEY `cl_invoice_items_back_ibfk_4`; ALTER TABLE `cl_invoice_items_back` ADD CONSTRAINT `cl_invoice_items_back_ibfk_4` FOREIGN KEY (`cl_store_move_id`) REFERENCES `cl_store_move`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_store_move` DROP FOREIGN KEY `cl_store_move_ibfk_3`; ALTER TABLE `cl_store_move` ADD CONSTRAINT `cl_store_move_ibfk_3` FOREIGN KEY (`cl_store_id`) REFERENCES `cl_store`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `cl_partners_book_workers` ADD `description_txt` TEXT NOT NULL AFTER `worker_other`;
ALTER TABLE `cl_offer_items` CHANGE `cl_pricelist_id` `cl_pricelist_id` INT(11) NULL DEFAULT NULL;
ALTER TABLE `cl_commission` ADD `description_txt2` VARCHAR(50) NOT NULL AFTER `cm_number`;
ALTER TABLE `cl_files` ADD `new_place` TINYINT(1) NOT NULL DEFAULT '0' AFTER `file_size`;
ALTER TABLE `cl_files` CHANGE `new_place` `new_place` TINYINT(1) NOT NULL DEFAULT '1';
//ALTER TABLE `cl_sale_items` ADD FOREIGN KEY (`cl_pricelist_id`) REFERENCES `cl_pricelist`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_users` ADD `companies_manager` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '1-manager has acces to settings and company switch, 0-common user' AFTER `store_manager`;
ALTER TABLE `cl_sale_shorts` ADD `cl_company_branch_id` INT NULL DEFAULT NULL AFTER `cl_users_id`;
ALTER TABLE `cl_sale_shorts` ADD INDEX(`cl_company_branch_id`);
ALTER TABLE `cl_sale_shorts` ADD FOREIGN KEY (`cl_company_branch_id`) REFERENCES `cl_company_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_paired_docs` DROP FOREIGN KEY `cl_paired_docs_ibfk_11`; ALTER TABLE `cl_paired_docs` ADD CONSTRAINT `cl_paired_docs_ibfk_11` FOREIGN KEY (`cl_cash_id`) REFERENCES `cl_cash`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `cl_cash` DROP FOREIGN KEY `cl_cash_ibfk_7`; ALTER TABLE `cl_cash` ADD CONSTRAINT `cl_cash_ibfk_7` FOREIGN KEY (`cl_sale_id`) REFERENCES `cl_sale`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_sale` DROP FOREIGN KEY `cl_sale_ibfk_12`; ALTER TABLE `cl_sale` ADD CONSTRAINT `cl_sale_ibfk_12` FOREIGN KEY (`cl_cash_id`) REFERENCES `cl_cash`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_files` ADD FOREIGN KEY (`cl_invoice_arrived_id`) REFERENCES `cl_invoice_arrived`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_center` ADD `description` VARCHAR(60) NOT NULL AFTER `name`;

ALTER TABLE `cl_invoice_types` ADD `form_use` VARCHAR(30) NOT NULL AFTER `inv_type`;
ALTER TABLE `cl_invoice` ADD `storno` TINYINT(1) NOT NULL DEFAULT '0' AFTER `description_txt`;
ALTER TABLE `cl_invoice` ADD `locked` TINYINT(1) NOT NULL DEFAULT '0' AFTER `description_txt`;
ALTER TABLE `cl_invoice_arrived` ADD `locked` TINYINT(1) NOT NULL DEFAULT '0' AFTER `price_on_commission`;
ALTER TABLE `cl_commission` ADD `locked` TINYINT(1) NOT NULL DEFAULT '0' AFTER `profit_works`;
ALTER TABLE `cl_delivery_note` ADD `locked` TINYINT(1) NOT NULL DEFAULT '0' AFTER `price_off`;
ALTER TABLE `cl_offer` ADD `locked` TINYINT(1) NOT NULL DEFAULT '0' AFTER `cl_commission_id`;
ALTER TABLE `cl_order` ADD `locked` TINYINT(1) NOT NULL DEFAULT '0' AFTER `cl_store_docs_id_in`;
//2019V166  12.06.2019 Kučinka a Techbelt
ALTER TABLE `cl_offer_items` ADD `position` VARCHAR(30) NOT NULL AFTER `item_label`;
//2019V170  14.06.2019 Beta a Public
ALTER TABLE `cl_company` ADD `offer_vat_off` TINYINT(1) NOT NULL DEFAULT '0' AFTER `eet_id_poklad`;
ALTER TABLE `cl_offer` ADD `offer_vat_off` TINYINT(1) NOT NULL DEFAULT '0' AFTER `cl_commission_id`;
//2019V170 17.06.2019 Kučinka a Techbelt
//newtable in_training_types, in_staff, in_proffession, in_lectors, in_nations, in_staff_score, in_training_staff, in_training
ALTER TABLE `cl_center` ADD `location` VARCHAR(30) NOT NULL AFTER `description`;
ALTER TABLE `cl_files` ADD `in_profession_id` INT NULL DEFAULT NULL AFTER `new_place`, ADD `in_lectors_id` INT NULL DEFAULT NULL AFTER `in_profession_id`, ADD `in_training_types_id` INT NULL DEFAULT NULL AFTER `in_lectors_id`;
ALTER TABLE `cl_files` ADD `in_staff_id` INT NULL DEFAULT NULL AFTER `in_training_types_id`;
ALTER TABLE `cl_files` ADD INDEX(`in_profession_id`);
ALTER TABLE `cl_files` ADD INDEX(`in_lectors_id`);
ALTER TABLE `cl_files` ADD INDEX(`in_training_types_id`);
ALTER TABLE `cl_files` ADD INDEX(`in_staff_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`in_lectors_id`) REFERENCES `in_lectors`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_files` ADD FOREIGN KEY (`in_profession_id`) REFERENCES `in_profession`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_files` ADD FOREIGN KEY (`in_training_types_id`) REFERENCES `in_training_types`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_files` ADD FOREIGN KEY (`in_staff_id`) REFERENCES `in_staff`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_files` ADD `in_training_id` INT NULL DEFAULT NULL AFTER `in_training_types_id`;
ALTER TABLE `cl_files` ADD INDEX(`in_training_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`in_training_id`) REFERENCES `in_training`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
//newtable in_folder
ALTER TABLE `cl_files` ADD `in_folder_id` INT NULL DEFAULT NULL AFTER `in_staff_id`;
ALTER TABLE `cl_files` ADD INDEX(`in_folder_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`in_folder_id`) REFERENCES `in_folders`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_files` ADD `description` TEXT NOT NULL AFTER `in_folder_id`;
//asi chybí u Kučinky a Techbeltu:
//memos_txt už v cl_commission asi je
//ALTER TABLE `cl_commission` ADD `memos_txt` LONGTEXT NOT NULL AFTER `description_show`;
ALTER TABLE `cl_users` ADD `company_branches` VARCHAR(200) NOT NULL COMMENT 'json with alowed cl_branch_id\'s' AFTER `quick_sums`;
ALTER TABLE `cl_invoice_arrived` ADD `cl_company_branch_id` INT NULL DEFAULT NULL AFTER `cl_company_id`;
ALTER TABLE `cl_invoice_arrived` ADD INDEX(`cl_company_branch_id`);
ALTER TABLE `cl_invoice_arrived` ADD FOREIGN KEY (`cl_company_branch_id`) REFERENCES `cl_company_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_company_branch` ADD `cl_number_series_id_cashin` INT NULL DEFAULT NULL AFTER `cl_number_series_id_correction`, ADD `cl_number_series_id_cashout` INT NULL DEFAULT NULL AFTER `cl_number_series_id_cashin`, ADD `cl_number_series_id_invoice` INT NULL DEFAULT NULL AFTER `cl_number_series_id_cashout`, ADD `cl_number_series_id_invoicearrived` INT NULL DEFAULT NULL AFTER `cl_number_series_id_invoice`;
ALTER TABLE `cl_company_branch` ADD INDEX(`cl_number_series_id_cashout`);
ALTER TABLE `cl_company_branch` ADD INDEX(`cl_number_series_id_cashin`);
ALTER TABLE `cl_company_branch` ADD INDEX(`cl_number_series_id_invoice`);
ALTER TABLE `cl_company_branch` ADD INDEX(`cl_number_series_id_invoicearrived`);
//2019V191  - Kučinka
ALTER TABLE `cl_company_branch` ADD `b_phone` VARCHAR(60) NOT NULL AFTER `b_email`, ADD `b_www` VARCHAR(60) NOT NULL AFTER `b_phone`;
ALTER TABLE `cl_company_branch` ADD `b_name` VARCHAR(60) NOT NULL AFTER `name`;
ALTER TABLE `cl_users` ADD `cl_company_branch_id` INT NULL DEFAULT NULL COMMENT 'active company branch' AFTER `company_branches`;
ALTER TABLE `cl_company_branch_users` ADD `default_branch` TINYINT(1) NOT NULL DEFAULT '0' AFTER `alow_cl_user_id`;
ALTER TABLE `cl_users` ADD INDEX(`cl_company_branch_id`);
ALTER TABLE `cl_users` ADD FOREIGN KEY (`cl_company_branch_id`) REFERENCES `cl_company_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
//2019V192  - Kučinka
ALTER TABLE `cl_sale` ADD `cash_rec` DECIMAL(14,0) NOT NULL DEFAULT '0' AFTER `cl_cash_id`;
//2019V200  - Kučinka
ALTER TABLE `cl_payment_types` ADD `eet_send` TINYINT(1) NOT NULL DEFAULT '0' AFTER `payment_type`;
//new table cl_inventory , cl_inventory_items
ALTER TABLE `cl_partners_book` ADD `min_order` DECIMAL(14,2) NOT NULL DEFAULT '0' AFTER `partner_code`;
ALTER TABLE `cl_storage` ADD `order_period` TINYINT(3) NOT NULL DEFAULT '0' AFTER `price_method`;
ALTER TABLE `cl_order` ADD `cl_storage_id` INT NULL DEFAULT NULL AFTER `locked`;
ALTER TABLE `cl_order` ADD INDEX(`cl_storage_id`);
ALTER TABLE `cl_order` ADD FOREIGN KEY (`cl_storage_id`) REFERENCES `cl_storage`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_company` ADD `order_period_last` CHAR(128) NOT NULL COMMENT 'poslední období pro které byla vytvořena objednávka dle obratů za období' AFTER `offer_vat_off`;
ALTER TABLE `cl_store` ADD `quantity_to_order` DECIMAL(15,4) NOT NULL DEFAULT '0' COMMENT 'neobjednané množství z předchozí obratové objednávky, neobjednáno z důvodu nedosažení limitu dodavatele' AFTER `st_date`;
ALTER TABLE `cl_company` CHANGE `order_period_last` `order_period_last` TEXT CHARACTER SET utf32 COLLATE utf32_czech_ci NOT NULL COMMENT 'poslední období pro které byla vytvořena objednávka dle obratů za období. Je zde uložen json s identifikací skladu a období';
ALTER TABLE `cl_storage` ADD `auto_order` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0-automatická obratová objednávka je zakázána, 1-automatická obratová objednávka je povolena' AFTER `order_period`;
ALTER TABLE `cl_storage` ADD `order_date` DATE NULL DEFAULT NULL COMMENT 'datum poslední automatické objednávky' AFTER `auto_order`, ADD `order_day` TINYINT(1)  NULL DEFAULT NULL COMMENT 'den v týdnu, ke kterému nejpozději musí být automatická objednávka vytvořena' AFTER `order_date`;
//27.07.2019
ALTER TABLE `cl_files` ADD `document_file` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '1 - document file generated to PDF' AFTER `cl_users_id`;
ALTER TABLE `cl_emailing` ADD `attachment` TEXT NOT NULL AFTER `sendTo`;
ALTER TABLE `cl_emailing` ADD `cl_order_id` INT NULL DEFAULT NULL AFTER `cl_invoice_id`;
ALTER TABLE `cl_emailing` ADD INDEX(`cl_order_id`);
ALTER TABLE `cl_emailing` ADD FOREIGN KEY (`cl_order_id`) REFERENCES `cl_order`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_inventory_items` ADD `item_order` INT NOT NULL DEFAULT '0' AFTER `id`;
//on beta.klienti.cz and kucinka

//03.08.2019
ALTER TABLE `cl_store` ADD `placement` TEXT NOT NULL AFTER `quantity_req`;
//07.08.2019
ALTER TABLE `cl_emailing_text` ADD `attach_pdf` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1 - create and insert pdf attachment of document' AFTER `email_use`, ADD `attach_csv_h` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '1 - create and insert csv attachment of document' AFTER `attach_pdf`, ADD `attach_csv_i` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '1 - create and insert csv attachment of items' AFTER `attach_csv_h`;
//08.08.2019 - Kučinka a  techbelt a beta.klienti.cz a klienti.cz

//new table in_estate_staff, in_estate, in_estate_param, in_estate_type, in_estate_type_param, in_places
ALTER TABLE `cl_files` ADD `in_estate_id` INT NULL DEFAULT NULL AFTER `in_folder_id`;
ALTER TABLE `cl_files` ADD INDEX(`in_estate_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`in_estate_id`) REFERENCES `in_estate`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_files` ADD `in_places_id` INT NULL DEFAULT NULL AFTER `in_estate_id`;
ALTER TABLE `cl_files` ADD INDEX(`in_places_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`in_places_id`) REFERENCES `in_places`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `in_estate_param` ADD `in_estate_id` INT NULL DEFAULT NULL AFTER `cl_users_id`;
ALTER TABLE `in_estate_param` ADD INDEX(`in_estate_id`);
ALTER TABLE `in_estate_param` ADD FOREIGN KEY (`in_estate_id`) REFERENCES `in_estate`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `in_estate` ADD `old_in_estate_type_id` INT NULL DEFAULT NULL AFTER `in_estate_type_id`;
//19.08.2019 - beta a ostrá
ALTER TABLE `in_places` ADD `in_places_id` INT NULL DEFAULT NULL AFTER `cl_users_id`;
ALTER TABLE `in_places` ADD FOREIGN KEY (`in_places_id`) REFERENCES `in_places`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `in_estate_type_param` CHANGE `param_name` `name` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL;
//new table in_staff_role
ALTER TABLE `in_estate_staff` ADD `in_staff_role_id` INT NULL DEFAULT NULL AFTER `in_staff_id`;
ALTER TABLE `in_estate_staff` ADD INDEX(`in_staff_role_id`);
ALTER TABLE `in_estate_staff` ADD FOREIGN KEY (`in_staff_role_id`) REFERENCES `in_staff_role`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
//new table in_estate_diary

//06.09.2019
ALTER TABLE `cl_partners_category` ADD `deactive` TINYINT(1) NOT NULL DEFAULT '0' AFTER `hour_tax_remote`;
ALTER TABLE `cl_pricelist` ADD `not_active` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0 - active, 1 - noactive' AFTER `description_txt`;

//22.09.2019
ALTER TABLE `cl_partners_book` ADD `csv_order` TEXT NOT NULL COMMENT 'definition for csv import' AFTER `min_order`;
//new table cl_csv_profiles
ALTER TABLE `cl_store_docs` ADD `cl_company_branch_id` INT NULL DEFAULT NULL AFTER `cl_storage_id`;
ALTER TABLE `cl_store_docs` ADD INDEX(`cl_company_branch_id`);
ALTER TABLE `cl_store_docs` ADD FOREIGN KEY (`cl_company_branch_id`) REFERENCES `cl_company_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
\
ALTER TABLE `cl_delivery_note` ADD `cl_company_branch_id` INT NULL DEFAULT NULL AFTER `cl_company_id`;
ALTER TABLE `cl_delivery_note` ADD INDEX(`cl_company_branch_id`);
ALTER TABLE `cl_delivery_note` ADD FOREIGN KEY (`cl_company_branch_id`) REFERENCES `cl_company_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_store_move` ADD `cl_delivery_note_items_id` INT NULL DEFAULT NULL AFTER `cl_invoice_items_back_id`;
ALTER TABLE `cl_delivery_note_items` ADD INDEX(`cl_pricelist_id`);
ALTER TABLE `cl_delivery_note_items` ADD FOREIGN KEY (`cl_pricelist_id`) REFERENCES `cl_pricelist`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_delivery_note_items_back` ADD INDEX(`cl_pricelist_id`);
ALTER TABLE `cl_delivery_note_items_back` ADD FOREIGN KEY (`cl_pricelist_id`) REFERENCES `cl_pricelist`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice_items_back` ADD INDEX(`cl_pricelist_id`);
ALTER TABLE `cl_invoice_items_back` ADD FOREIGN KEY (`cl_pricelist_id`) REFERENCES `cl_pricelist`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice_items` ADD INDEX(`cl_pricelist_id`);
ALTER TABLE `cl_invoice_items` ADD FOREIGN KEY (`cl_pricelist_id`) REFERENCES `cl_pricelist`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

//29.9.2019
ALTER TABLE `cl_csv_profiles` ADD `import_key` VARCHAR(60) NOT NULL AFTER `enclosure`;
ALTER TABLE `cl_csv_profiles` CHANGE `import_key` `import_key` VARCHAR(60) NULL DEFAULT NULL;
ALTER TABLE `cl_csv_profiles` ADD `update_keys` TEXT NOT NULL AFTER `source_order`;

//30.09.2019
ALTER TABLE `cl_order` ADD `cl_company_branch_id` INT NULL DEFAULT NULL AFTER `cl_company_id`;
ALTER TABLE `cl_order` ADD INDEX(`cl_company_branch_id`);
ALTER TABLE `cl_order` ADD FOREIGN KEY (`cl_company_branch_id`) REFERENCES `cl_company_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
UPDATE cl_order SET cl_order.cl_company_branch_id = (SELECT cl_company_branch.id FROM cl_company_branch WHERE cl_company_branch.cl_storage_id = cl_order.cl_storage_id) where cl_company_branch_id IS NULL;
ALTER TABLE `cl_order` ADD `price_e2_rcv` DECIMAL(15,2) NOT NULL DEFAULT '0' AFTER `price_e2_vat`, ADD `price_e2_vat_rcv` DECIMAL(15,2) NOT NULL DEFAULT '0' AFTER `price_e2_rcv`;
//01.10.2019
ALTER TABLE `cl_bank_accounts` ADD `cl_currencies_id` INT NULL DEFAULT NULL AFTER `show_invoice`;
ALTER TABLE `cl_bank_accounts` ADD INDEX(`cl_currencies_id`);
ALTER TABLE `cl_bank_accounts` ADD FOREIGN KEY (`cl_currencies_id`) REFERENCES `cl_currencies`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_store_docs` DROP FOREIGN KEY `cl_store_docs_ibfk_10`; ALTER TABLE `cl_store_docs` ADD CONSTRAINT `cl_store_docs_ibfk_10` FOREIGN KEY (`cl_invoice_arrived_id`) REFERENCES `cl_invoice_arrived`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

ALTER TABLE `cl_invoice_arrived` DROP FOREIGN KEY `cl_invoice_arrived_ibfk_9`; ALTER TABLE `cl_invoice_arrived` ADD CONSTRAINT `cl_invoice_arrived_ibfk_9` FOREIGN KEY (`cl_store_docs_id`) REFERENCES `cl_store_docs`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

//predchozi je vsude
//04.10.2019
ALTER TABLE `cl_csv_profiles` ADD `no_insert_set` TINYINT(1) NOT NULL DEFAULT '0' AFTER `import_key`;
ALTER TABLE `cl_csv_profiles` ADD `no_update_set` TINYINT(1) NOT NULL DEFAULT '0' AFTER `import_key`;

///erase all
delete from cl_cash;
delete from cl_commission;
delete from cl_commission_items;
delete from cl_commission_items_sel;
delete from cl_commission_task;
delete from cl_commission_work;
delete from cl_delivery_note;
delete from cl_delivery_note_items;
delete from cl_delivery_note_items_back;
delete from cl_documents;
delete from cl_eet;
delete from cl_emailing;
delete from cl_emailing_partners;
delete from cl_invoice;
delete from cl_invoice_arrived;
delete from cl_invoice_arrived_commission;
delete from cl_invoice_arrived_payments;
delete from cl_invoice_items;
delete from cl_invoice_items_back;
delete from cl_invoice_payments;
delete from cl_offer;
delete from cl_offer_items;
delete from cl_offer_task;
delete from cl_offer_work;
delete from cl_order;
delete from cl_order_items;
delete from cl_paired_docs;
delete from cl_partners_book;
delete from cl_partners_book_workers;
delete from cl_partners_branch;
delete from cl_partners_event;
delete from cl_pricelist;
delete from cl_pricelist_bonds;
delete from cl_pricelist_group;
delete from cl_pricelist_macro;
delete from cl_pricelist_partner;
delete from cl_prices;
delete from cl_sale;
delete from cl_sale_items;
delete from cl_store;
delete from cl_store_out;
delete from cl_store_move;
delete from cl_store_docs;

ALTER TABLE `cl_offer_items` ADD FOREIGN KEY (`cl_pricelist_id`) REFERENCES `cl_pricelist`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

//06.10.2019
ALTER TABLE `cl_partners_book` ADD INDEX(`company`);

ALTER TABLE `cl_company` ADD `exp_on` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0-work with exp_date off, 1-work with exp_date on' AFTER `order_period_last`;
ALTER TABLE `cl_company` ADD `batch_on` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0-work with batch disabled, 1-work with batch enabled' AFTER `exp_on`;
//07.10.2019
ALTER TABLE `cl_store_move` ADD `exp_date` DATE NULL DEFAULT NULL AFTER `cl_countries_id`, ADD `batch` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL AFTER `exp_date`;

ALTER TABLE `cl_partners_branch` ADD `b_ico` VARCHAR(15) NOT NULL AFTER `b_person`, ADD `b_dic` VARCHAR(15) NOT NULL AFTER `b_ico`;
UPDATE `cl_partners_branch` SET b_dic = (SELECT dic FROM cl_partners_book WHERE cl_partners_book.id = cl_partners_book_id) WHERE b_dic="";
UPDATE `cl_partners_branch` SET b_ico = (SELECT ico FROM cl_partners_book WHERE cl_partners_book.id = cl_partners_book_id) WHERE b_ico="";
ALTER TABLE `cl_prices` DROP FOREIGN KEY `cl_prices_ibfk_5`; ALTER TABLE `cl_prices` ADD CONSTRAINT `cl_prices_ibfk_5` FOREIGN KEY (`cl_pricelist_id`) REFERENCES `cl_pricelist`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `cl_company` ADD `order_package` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0- objednávky přepočítávají počty v balení, 1- objednávky nepřepočítávají počty v balení' AFTER `batch_on`;
ALTER TABLE `cl_invoice` ADD `correction_inv_number` VARCHAR(20) NOT NULL AFTER `storno`;

///smazání neaktivních položek z ceníku
//DELETE FROM `cl_pricelist` WHERE `not_active` = 1 AND NOT EXISTS(SELECT * FROM `cl_invoice_items` WHERE cl_invoice_items.cl_pricelist_id = cl_pricelist.id) AND NOT EXISTS(SELECT * FROM `cl_store` WHERE cl_store.cl_pricelist_id = cl_pricelist.id)

ALTER TABLE `cl_delivery_note` ADD `cl_storage_id` INT NULL DEFAULT NULL AFTER `locked`;
ALTER TABLE `cl_delivery_note` ADD INDEX(`cl_storage_id`);
ALTER TABLE `cl_delivery_note` ADD FOREIGN KEY (`cl_storage_id`) REFERENCES `cl_storage`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `cl_delivery_note` DROP FOREIGN KEY `cl_delivery_note_ibfk_3`; ALTER TABLE `cl_delivery_note` ADD CONSTRAINT `cl_delivery_note_ibfk_3` FOREIGN KEY (`cl_store_docs_id`) REFERENCES `cl_store_docs`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_store_docs` DROP FOREIGN KEY `cl_store_docs_ibfk_13`; ALTER TABLE `cl_store_docs` ADD CONSTRAINT `cl_store_docs_ibfk_13` FOREIGN KEY (`cl_delivery_note_id`) REFERENCES `cl_delivery_note`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_delivery_note` ADD `dn_title` VARCHAR(200) NOT NULL AFTER `cl_status_id`;

//10.10.2019
ALTER TABLE `cl_files` ADD `cl_sale_id` INT NULL DEFAULT NULL AFTER `cl_store_docs_id`;
ALTER TABLE `cl_files` ADD INDEX(`cl_sale_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`cl_sale_id`) REFERENCES `cl_sale`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;



ALTER TABLE `cl_invoice_items` ADD FOREIGN KEY (`cl_delivery_note_id`) REFERENCES `cl_delivery_note`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `cl_invoice_items_back` ADD FOREIGN KEY (`cl_delivery_note_id`) REFERENCES `cl_delivery_note`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;


ALTER TABLE `cl_store_out` ADD FOREIGN KEY (`cl_store_move_in_id`) REFERENCES `cl_store_move`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

//check cl_store,quantity against cl_store_move
//SELECT id,quantity, (SELECT SUM(cl_store_move.s_in-cl_store_move.s_out) FROM cl_store_move WHERE cl_store_move.cl_store_id = cl_store.id) FROM `cl_store`
//update correct quantity
//UPDATE cl_store SET quantity=(SELECT SUM(cl_store_move.s_in-cl_store_move.s_out) FROM cl_store_move WHERE cl_store_move.cl_store_id = cl_store.id)
//better way
//UPDATE cl_store SET quantity=(SELECT SUM(cl_store_move.s_end) FROM cl_store_move WHERE cl_store_move.cl_store_id = cl_store.id)
//and best approach - update cl_store and in second step cl_pricelist
//UPDATE cl_store SET quantity=(SELECT SUM(cl_store_move.s_end) FROM cl_store_move WHERE cl_store_move.cl_store_id = cl_store.id);
//UPDATE cl_pricelist SET quantity = (SELECT SUM(cl_store.quantity) FROM cl_store WHERE cl_store.cl_pricelist_id = cl_pricelist.id);


ALTER TABLE `cl_invoice` ADD `correction_base0` DECIMAL(3,2) NOT NULL DEFAULT '0' AFTER `price_base3`, ADD `correction_base1` DECIMAL(3,2) NOT NULL DEFAULT '0' AFTER `correction_base0`, ADD `correction_base2` DECIMAL(3,2) NOT NULL DEFAULT '0' AFTER `correction_base1`, ADD `correction_base3` DECIMAL(3,2) NOT NULL DEFAULT '0' AFTER `correction_base2`;
ALTER TABLE `cl_pricelist_group` ADD `is_return_package` TINYINT(1) NOT NULL DEFAULT '0' AFTER `is_component`;
//19.10.2019
ALTER TABLE `cl_company_branch` ADD `cl_number_series_id_order` INT NULL DEFAULT NULL AFTER `cl_number_series_id_invoicearrived`, ADD `cl_number_series_id_advance` INT NULL DEFAULT NULL AFTER `cl_number_series_id_order`;
ALTER TABLE `cl_company_branch` ADD `cl_number_series_id_invoicearrived_correction` INT NULL DEFAULT NULL AFTER `cl_number_series_id_invoicearrived`, ADD `cl_number_series_id_invoicearrived_advance` INT NULL DEFAULT NULL AFTER `cl_number_series_id_invoicearrived_correction`;
ALTER TABLE `cl_company` ADD `cl_storage_id_back` INT NULL DEFAULT NULL AFTER `cl_storage_id_macro`, ADD `cl_storage_id_back_sale` INT NULL DEFAULT NULL AFTER `cl_storage_id_back`;
//klienti.cz, beta.klienti.cz, perrito, kucinka, precistec

//23.10.2019
ALTER TABLE `cl_delivery_note_items` ADD `order_number` VARCHAR(30) NOT NULL AFTER `cl_invoice_id`;
ALTER TABLE `cl_prices` ADD `price_multiplier` INT NOT NULL DEFAULT '1' AFTER `cl_currencies_id`;
ALTER TABLE `cl_prices` ADD `description` TINYTEXT NOT NULL AFTER `price_multiplier`;
ALTER TABLE `cl_prices` ADD `cl_offer_id` INT NULL DEFAULT NULL AFTER `description`;
ALTER TABLE `cl_prices` ADD `cl_commission_id` INT NULL DEFAULT NULL AFTER `cl_offer_id`;
ALTER TABLE `cl_prices` ADD INDEX(`cl_offer_id`);
ALTER TABLE `cl_prices` ADD INDEX(`cl_commission_id`);
ALTER TABLE `cl_prices` ADD FOREIGN KEY (`cl_commission_id`) REFERENCES `cl_commission`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_prices` ADD FOREIGN KEY (`cl_offer_id`) REFERENCES `cl_offer`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

//POZOR!! ověřit správnost dat po převodu
///ALTER TABLE `cl_partners_book` CHANGE `company` `company` VARCHAR(60) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL;
///ALTER TABLE `cl_commission` CHANGE `cm_number` `cm_number` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL;
///ALTER TABLE `cl_partners_branch` CHANGE `b_name` `b_name` VARCHAR(60) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL, CHANGE `b_street` `b_street` VARCHAR(60) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL, CHANGE `b_city` `b_city` VARCHAR(60) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL, CHANGE `b_phone` `b_phone` VARCHAR(60) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL, CHANGE `b_email` `b_email` VARCHAR(60) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL, CHANGE `b_person` `b_person` VARCHAR(60) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL;

//new table cl_storage_places
ALTER TABLE `cl_store_move` ADD `cl_storage_places_id` INT NULL DEFAULT NULL AFTER `cl_storage_id`;
ALTER TABLE `cl_store_move` ADD INDEX(`cl_storage_places_id`);
ALTER TABLE `cl_store_move` ADD FOREIGN KEY (`cl_storage_places_id`) REFERENCES `cl_storage_places`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
// Precistec

ALTER TABLE `cl_pricelist` ADD INDEX(`order_code`);

//26.10.2019
ALTER TABLE `cl_csv_profiles` ADD `no_insert_set` TINYINT(1) NOT NULL DEFAULT '0' AFTER `import_key`;
ALTER TABLE `cl_csv_profiles` ADD `no_update_set` TINYINT(1) NOT NULL DEFAULT '0' AFTER `import_key`;

//27.10.2019 - Kučinka, Bebidos, precistec

ALTER TABLE `cl_pricelist_group` ADD `request_exp_date` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0 - datum expirace při příjmu není povinné, 1 - datum expirace při příjmu je povinné' AFTER `is_return_package`;
ALTER TABLE `cl_pricelist_group` ADD `request_batch` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0 - číslo šarže při příjmu není povinné, 1 - číslo šarže při příjmu je povinné' AFTER `request_exp_date`;
//27.10.2019 - Bebidos, Kučinka, precistec

//28.10.2019
//new table cl_transport_types
ALTER TABLE `cl_commission` ADD `cl_payment_types_id` INT NULL DEFAULT NULL AFTER `description_txt2`, ADD `cl_transport_types_id` INT NULL DEFAULT NULL AFTER `cl_payment_types_id`;
ALTER TABLE `cl_commission` ADD INDEX(`cl_payment_types_id`);
ALTER TABLE `cl_commission` ADD INDEX(`cl_transport_types_id`);
ALTER TABLE `cl_commission` ADD FOREIGN KEY (`cl_transport_types_id`) REFERENCES `cl_transport_types`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_commission` ADD FOREIGN KEY (`cl_payment_types_id`) REFERENCES `cl_payment_types`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `cl_commission_items_sel` ADD `quantity_checked` DECIMAL(15,4) NOT NULL DEFAULT '0' AFTER `quantity`;
//27.10.2019 - Kučinka, bebidos, precistec

ALTER TABLE `cl_users` ADD `progress_val` INT NOT NULL AFTER `cl_company_branch_id`, ADD `progress_max` INT NOT NULL AFTER `progress_val`;
ALTER TABLE `cl_users` ADD `progress_message` VARCHAR(60) NOT NULL AFTER `progress_max`;
//31.10.2019 - Kučinka, bebidos, precistec

ALTER TABLE `cl_sale` ADD `downloaded` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0 - not downloaded yet, 1 - downloaded allready' AFTER `cash_rec`;
UPDATE cl_sale SET downloaded = 1;
//04.11.2019 - klienti.cz, beta.klienti.cz, bebidos, kucinka, precistec

ALTER TABLE `cl_store_out` ADD `cl_store_id` INT NULL DEFAULT NULL AFTER `cl_store_move_in_id`;
UPDATE `cl_store_out` SET cl_store_id = (select cl_store_move.cl_store_id FROM cl_store_move WHERE cl_store_move.id = cl_store_out.cl_store_move_id);
ALTER TABLE `cl_store_out` ADD INDEX(`cl_store_id`);
ALTER TABLE `cl_store_out` ADD FOREIGN KEY (`cl_store_id`) REFERENCES `cl_store`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

//05.11.2019 - bebidos,klienti.cz, beta.klienti.cz, kucinka, precistec
ALTER TABLE `cl_pricelist` ADD `search_tag` VARCHAR(64) NOT NULL AFTER `not_active`;
ALTER TABLE `cl_pricelist` ADD `ean_old` VARCHAR(128) NOT NULL AFTER `search_tag`;
ALTER TABLE `cl_pricelist` ADD INDEX(`search_tag`);
ALTER TABLE `cl_pricelist` ADD INDEX(`ean_old`);
//09.11.2019 - kucinka, bebidos, precistec,klienti.cz, beta.klienti.cz,

ALTER TABLE `cl_invoice_items` ADD `is_return_package` TINYINT(1) NOT NULL DEFAULT '0' AFTER `price_e2_vat`;
ALTER TABLE `cl_invoice_items_back` ADD `is_return_package` TINYINT(1) NOT NULL DEFAULT '0' AFTER `price_e2_vat`;
ALTER TABLE `cl_invoice_items` ADD `profit` DECIMAL(15,4) NOT NULL DEFAULT '0' AFTER `discount`;
ALTER TABLE `cl_invoice_items_back` ADD `profit` DECIMAL(15,4) NOT NULL DEFAULT '0' AFTER `discount`;
ALTER TABLE `cl_invoice` ADD `price_s` DECIMAL(15,4) NOT NULL DEFAULT '0' AFTER `correction_inv_number`, ADD `profit` DECIMAL(15,4) NOT NULL DEFAULT '0' AFTER `price_s`;
ALTER TABLE `cl_invoice` ADD `profit_abs` DECIMAL(15,4) NOT NULL DEFAULT '0' AFTER `profit`;
//10.11.2019 - bebidos, precistec, kucinka,klienti.cz, beta.klienti.cz,

ALTER TABLE `cl_store_docs` ADD `cl_invoice_types_id` INT NULL DEFAULT NULL AFTER `cl_status_id`;
ALTER TABLE `cl_store_docs` ADD INDEX(`cl_invoice_types_id`);
ALTER TABLE `cl_store_docs` ADD FOREIGN KEY (`cl_invoice_types_id`) REFERENCES `cl_invoice_types`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

//12.11.2019 - precistec,bebidos, kucinka,klienti.cz, beta.klienti.cz,

ALTER TABLE `cl_store_docs` ADD `minus` TINYINT(1) NOT NULL DEFAULT '0' AFTER `cl_sale_id`;
ALTER TABLE `cl_store_move` ADD `minus` TINYINT(1) NOT NULL DEFAULT '0' AFTER `exp_date`;
//12.11.2019 - bebidos, kucinka,klienti.cz, beta.klienti.cz,

ALTER TABLE `cl_inventory_items` DROP FOREIGN KEY `cl_inventory_items_ibfk_2`; ALTER TABLE `cl_inventory_items` ADD CONSTRAINT `cl_inventory_items_ibfk_2` FOREIGN KEY (`cl_store_id`) REFERENCES `cl_store`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
//bebidos cl_store correction
//update `cl_store` set cl_storage_id = 3 WHERE `cl_storage_id` IS NULL
//17.11.2019 - bebidos, kucinka,klienti.cz, beta.klienti.cz,
ALTER TABLE `cl_eet` CHANGE `fik` `fik` VARCHAR(39) CHARACTER SET utf8 COLLATE utf8_czech_ci NULL;
//18.11.2019 - kucinka, bebidos,klienti.cz, beta.klienti.cz,

ALTER TABLE `cl_pricelist_group` ADD `order_on_docs` TINYINT(2) NULL DEFAULT NULL AFTER `request_batch`;
ALTER TABLE `cl_commission` ADD `cl_company_branch_id` INT NULL DEFAULT NULL AFTER `cl_company_id`;
ALTER TABLE `cl_offer` ADD `cl_company_branch_id` INT NULL DEFAULT NULL AFTER `cl_company_id`;
ALTER TABLE `cl_offer` ADD INDEX(`cl_company_branch_id`);
ALTER TABLE `cl_offer` ADD FOREIGN KEY (`cl_company_branch_id`) REFERENCES `cl_company_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_commission` ADD INDEX(`cl_company_branch_id`);
ALTER TABLE `cl_commission` ADD FOREIGN KEY (`cl_company_branch_id`) REFERENCES `cl_company_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
//21.11.2019 - bebidos, kucinka,klienti.cz, beta.klienti.cz,

ALTER TABLE `cl_users` ADD `authorize_pin` CHAR(6) NOT NULL AFTER `progress_message`;
//22.11.2019 - PLK, bebidos, kucinka,klienti.cz, beta.klienti.cz,
//new table cl_workplaces
ALTER TABLE `cl_inventory` ADD `active` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0-neaktivní inventura, 1-aktivní inventura (použito pro externí zařízení)' AFTER `cl_pricelist_group_id`;
//new table cl_inventory_workplaces
ALTER TABLE `in_estate_type` ADD `group_type` TINYINT(1) NOT NULL DEFAULT '0' AFTER `type_name`;
//05.12.2019 - PLK, precistec, bebidos, kucinka,klienti.cz, beta.klienti.cz,

//new table cl_reports
ALTER TABLE `cl_store_move` ADD `cl_storage_places` TEXT NOT NULL AFTER `batch`;
//remove constraint cl_storage_places from cl_store_move
//11.12.2019 - PLK, precistec, bebidos,klienti.cz, beta.klienti.cz, kucinka, techbelt (vsude drive je)

ALTER TABLE `cl_company` ADD `hd_cl_partners_book_id` INT NULL DEFAULT NULL AFTER `hd_vat`;
ALTER TABLE `cl_company` ADD `hd_anonymous` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0-příjem anonymních požadavků zakázán, 1-příjem anonymních požadavků povolen' AFTER `hd_cl_partners_book_id`;
//28.12.2019 - PLK, bebidos,klienti.cz, beta.klienti.cz, kucinka, techbelt,precistec

ALTER TABLE `cl_store_out` DROP FOREIGN KEY `cl_store_out_ibfk_4`; ALTER TABLE `cl_store_out` ADD CONSTRAINT `cl_store_out_ibfk_4` FOREIGN KEY (`cl_store_id`) REFERENCES `cl_store`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `cl_company` ADD `order_group_label` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0 - pořadí položek na dokladu podle pořadí zadání, 1 - pořadí podle pořadí skupin a pak podle názvu ' AFTER `order_package`;
ALTER TABLE `cl_users` ADD `email2` VARCHAR(256) NOT NULL COMMENT 'second email' AFTER `email`;
//01.01.2020 - PLK, bebidos,klienti.cz, beta.klienti.cz, kucinka, techbelt,precistec

//new table cl_pricelist_limits
ALTER TABLE `cl_store` ADD `cl_pricelist_limits_id` INT NULL DEFAULT NULL AFTER `cl_storage_id`;
ALTER TABLE `cl_store` ADD INDEX(`cl_pricelist_limits_id`);
ALTER TABLE `cl_store` ADD FOREIGN KEY (`cl_pricelist_limits_id`) REFERENCES `cl_pricelist_limits`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

UPDATE cl_store SET batch = NULL WHERE batch='';
//09.01.2020 - PLK, bebidos,kucinka, techbelt,precistec

ALTER TABLE `cl_company_branch` ADD `cl_center_id` INT NULL DEFAULT NULL AFTER `eet_id_poklad`;
ALTER TABLE `cl_company_branch` ADD INDEX(`cl_center_id`);
ALTER TABLE `cl_company_branch` ADD FOREIGN KEY (`cl_center_id`) REFERENCES `cl_center`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
//09.01.2019 - PLK, bebidos,kucinka, beta.klienti.cz, klienti.cz, techbelt,precistec

ALTER TABLE `cl_partners_book_workers` ADD `use_cl_delivery` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_cl_delivery_note`;
ALTER TABLE `cl_partners_book_workers` ADD `use_cl_store_docs` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_cl_delivery`;
ALTER TABLE `cl_partners_branch` ADD `use_cl_store_docs` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_cl_delivery_note`;
ALTER TABLE `cl_partners_branch` ADD `use_cl_sale` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_cl_store_docs`;
ALTER TABLE `cl_partners_book_workers` ADD `use_cl_sale` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_cl_store_docs`;
ALTER TABLE `cl_partners_book_workers` ADD `use_cl_cash` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_cl_sale`;
ALTER TABLE `cl_partners_branch` ADD `use_cl_cash` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_cl_sale`;
ALTER TABLE `cl_partners_branch` ADD `use_cl_order` TINYINT(1) NOT NULL DEFAULT '0' AFTER `use_cl_cash`;
ALTER TABLE `cl_offer` CHANGE `terms_delivery` `terms_delivery` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL, CHANGE `terms_payment` `terms_payment` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL;
ALTER TABLE `cl_company` ADD `offer_vat_def` DECIMAL(6,2) NOT NULL DEFAULT '0' AFTER `offer_vat_off`;
//12.01.2020 - PLK, bebidos,kucinka,beta.klienti.cz,techbelt, klienti.cz,precistec

ALTER TABLE `cl_order_items` CHANGE `note_txt` `note` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL;
ALTER TABLE `cl_order` ADD `cl_partners_branch_id` INT NULL DEFAULT NULL AFTER `cl_partners_book_workers_id`;
ALTER TABLE `cl_order` ADD INDEX(`cl_partners_branch_id`);
ALTER TABLE `cl_order` ADD FOREIGN KEY (`cl_partners_branch_id`) REFERENCES `cl_partners_branch`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
//15.01.2020 - PLK, bebidos,kucinka,beta.klienti.cz,klienti.cz, techbelt,precistec

ALTER TABLE `cl_order_items` CHANGE `note` `note` TEXT CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL;
//17.01.2020 - PLK, bebidos,kucinka,beta.klienti.cz,klienti.cz, precistec

ALTER TABLE `cl_messages` ADD FOREIGN KEY (`cl_company_id`) REFERENCES `cl_company`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_messages` ADD FOREIGN KEY (`cl_users_id`) REFERENCES `cl_users`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
//new table  cl_messages_main
ALTER TABLE `cl_messages` ADD `cl_messages_main_id` INT NULL DEFAULT NULL AFTER `cl_users_id`;
ALTER TABLE `cl_messages` ADD INDEX(`cl_messages_main_id`);
ALTER TABLE `cl_messages` ADD FOREIGN KEY (`cl_messages_main_id`) REFERENCES `cl_messages_main`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_messages` DROP FOREIGN KEY `cl_messages_ibfk_3`; ALTER TABLE `cl_messages` ADD CONSTRAINT `cl_messages_ibfk_3` FOREIGN KEY (`cl_messages_main_id`) REFERENCES `cl_messages_main`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `cl_currencies` ADD `rate` DECIMAL(8,5) NOT NULL DEFAULT '0' AFTER `fix_rate`;
ALTER TABLE `cl_partners_branch` ADD `use_as_main` TINYINT(1) NOT NULL DEFAULT '0' AFTER `b_dic`;
//18.01.2020 - PLK, bebidos,kucinka,beta.klienti.cz,klienti.cz,techbelt,
ALTER TABLE `cl_invoice` ADD `cm_number` VARCHAR(60) NOT NULL AFTER `description_txt`;
ALTER TABLE `cl_commission` ADD `inv_number` VARCHAR(60) NOT NULL AFTER `cm_number`;
//18.01.2020 - PLK, bebidos,kucinka,beta.klienti.cz,klienti.cz,techbelt, precistec

ALTER TABLE `cl_company` ADD `pdp_text` TEXT NOT NULL AFTER `order_group_label`;
//18.01.2020 - PLK, bebidos,kucinka,beta.klienti.cz, klienti.cz,techbelt, precistec

ALTER TABLE `cl_users_license` CHANGE `tariff_type` `tariff_type` TINYINT(4) NULL DEFAULT NULL COMMENT '1-podnikatel,2-servis,3-max';
ALTER TABLE `cl_users_license` ADD `amount_before` DECIMAL(14,2) NOT NULL DEFAULT '0' AFTER `tariff_type`;
ALTER TABLE `cl_users_license` ADD `modules` TEXT NOT NULL AFTER `tariff_type`;
ALTER TABLE `cl_users_license` CHANGE `v_symb` `v_symb` VARCHAR(12) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL;
//26.01.2020 - PLK, bebidos,kucinka, beta.klienti.cz, klienti.cz, techbelt, precistec

ALTER TABLE `cl_company_branch` ADD `cl_pricelist_group_id` INT NULL DEFAULT NULL AFTER `cl_center_id`;
ALTER TABLE `cl_company_branch` ADD INDEX(`cl_pricelist_group_id`);
ALTER TABLE `cl_company_branch` ADD FOREIGN KEY (`cl_pricelist_group_id`) REFERENCES `cl_pricelist_group`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_partners_book` CHANGE `supplier` `supplier` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1 - dodavatel', CHANGE `customer` `customer` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1 - odběratel';

//29.01.2020 - PLK, bebidos,kucinka,beta.klienti.cz, klienti.cz, techbelt, precistec
ALTER TABLE `cl_reports` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `cl_reports` ADD `active` TINYINT(1) NOT NULL DEFAULT '1' AFTER `report_type`;
ALTER TABLE `cl_reports` ADD `report_description` VARCHAR(200) NOT NULL AFTER `cl_users_id`;
ALTER TABLE `cl_invoice` ADD `export` TINYINT(1) NOT NULL DEFAULT '0' AFTER `pdp`;
ALTER TABLE `cl_invoice` ADD `cl_bank_accounts_id` INT NULL DEFAULT NULL AFTER `cl_payment_types_id`;
ALTER TABLE `cl_invoice` ADD INDEX(`cl_bank_accounts_id`);
ALTER TABLE `cl_invoice` ADD FOREIGN KEY (`cl_bank_accounts_id`) REFERENCES `cl_bank_accounts`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
//03.02.2020 - PLK, bebidos,kucinka,beta.klienti.cz, klienti.cz, techbelt, precistec

//new table cl_transport
//new table c_transport_docs
//new table c_transport_items_back
ALTER TABLE `cl_files` ADD `cl_transport_id` INT NULL DEFAULT NULL AFTER `cl_cash_id`;
ALTER TABLE `cl_files` ADD INDEX(`cl_transport_id`);
ALTER TABLE `cl_files` ADD FOREIGN KEY (`cl_transport_id`) REFERENCES `cl_transport`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cl_paired_docs` ADD `cl_transport_id` INT NULL DEFAULT NULL AFTER `cl_cash_id`;
ALTER TABLE `cl_paired_docs` ADD INDEX(`cl_transport_id`);
ALTER TABLE `cl_paired_docs` ADD FOREIGN KEY (`cl_transport_id`) REFERENCES `cl_transport`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
//03.02.2020 - PLK, bebidos,kucinka, beta.klienti.cz, klienti.cz, techbelt, precistec

ALTER TABLE `cl_tables_setting` ADD `hover_sum` TINYINT(1) NOT NULL DEFAULT '0' AFTER `edit_columns`;
UPDATE `cl_payment_types` SET eet_send = 1 WHERE payment_type = 1 ;
//12.02.2020 - PLK, bebidos,kucinka,beta.klienti.cz, klienti.cz, techbelt, precistec

ALTER TABLE `cl_pricelist` ADD `profit_per` DECIMAL(5,1) NOT NULL DEFAULT '0' AFTER `ean_old`, ADD `profit_abs` DECIMAL(15,2) NOT NULL DEFAULT '0' AFTER `profit_per`;
/*17.2.2020 - PLK, bebidos,kucinka, beta.klienti.cz, klienti.cz,techbelt, precistec*/

ALTER TABLE `cl_commission_task` ADD `cl_workplaces_id` INT NULL DEFAULT NULL AFTER `cl_users_id`;
ALTER TABLE `cl_commission_task` ADD INDEX(`cl_workplaces_id`);
ALTER TABLE `cl_commission_task` ADD FOREIGN KEY (`cl_workplaces_id`) REFERENCES `cl_workplaces`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

/*19.02.2020 - PLK, bebidos,kucinka, beta.klienti.cz, klienti.cz,techbelt, precistec*/
ALTER TABLE `cl_invoice` ADD `price_vat0` DECIMAL(15,2) NOT NULL DEFAULT '0' AFTER `correction_base3`;
ALTER TABLE `cl_inventory` ADD `cl_status_id` INT NULL DEFAULT NULL AFTER `cl_users_id`;
ALTER TABLE `cl_inventory` ADD INDEX(`cl_status_id`);
ALTER TABLE `cl_inventory` ADD FOREIGN KEY (`cl_status_id`) REFERENCES `cl_status`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;