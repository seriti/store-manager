<?php
namespace App\Store;

use Seriti\Tools\SetupModuleData;

class SetupData extends SetupModuledata
{

    public function setupSql()
    {
        $this->tables = ['item','item_category','store','location','supplier','order','order_item','receive','receive_item',
                         'transfer','transfer_item','client','deliver','deliver_item','stock','stock_store','file','user_extend'];

        $this->addCreateSql('item',
                            'CREATE TABLE `TABLE_NAME` (
                                `item_id` int(11) NOT NULL AUTO_INCREMENT,
                                `category_id` int(11) NOT NULL,
                                `name` varchar(64) NOT NULL,
                                `code` varchar(64) NOT NULL,
                                `units` varchar(16) NOT NULL,
                                `units_kg_convert` decimal(12,2) NOT NULL,
                                `price_buy` decimal(12,2) NOT NULL,
                                `price_sell` decimal(12,2) NOT NULL,
                                `tax_free` tinyint(1) NOT NULL,
                                `note` text NOT NULL,
                                `status` varchar(64) NOT NULL,
                                PRIMARY KEY (`item_id`),
                                UNIQUE KEY `idx_str_item1` (`code`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('item_category',
                            'CREATE TABLE `TABLE_NAME` (
                              `category_id` INT NOT NULL AUTO_INCREMENT,
                              `name` VARCHAR(250) NOT NULL,
                              `access` varchar(64) NOT NULL,
                              `access_level` int(11) NOT NULL,
                              `sort` INT NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`category_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');


        $this->addCreateSql('store',
                            'CREATE TABLE `TABLE_NAME` (
                                `store_id` int(11) NOT NULL AUTO_INCREMENT,
                                `location_id` int(11) NOT NULL,
                                `name` varchar(64) NOT NULL,
                                `note` text NOT NULL,
                                `access` varchar(64) NOT NULL,
                                `access_level` int(11) NOT NULL,
                                `status` varchar(64) NOT NULL,
                                PRIMARY KEY (`store_id`) 
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('location',
                            'CREATE TABLE `TABLE_NAME` (
                                `location_id` int(11) NOT NULL AUTO_INCREMENT,
                                `name` varchar(64) NOT NULL,
                                `status` varchar(64) NOT NULL,
                                PRIMARY KEY (`location_id`) 
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('supplier',
                            'CREATE TABLE `TABLE_NAME` (
                                `supplier_id` int(11) NOT NULL AUTO_INCREMENT,
                                `name` varchar(64) NOT NULL,
                                `contact` varchar(64) NOT NULL,
                                `email` varchar(250) NOT NULL,
                                `address` text NOT NULL,
                                `note` text NOT NULL,
                                `status` varchar(64) NOT NULL,
                                PRIMARY KEY (`supplier_id`) 
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('order',
                            'CREATE TABLE `TABLE_NAME` (
                                `order_id` int(11) NOT NULL AUTO_INCREMENT,
                                `location_id` int(11) NOT NULL,
                                `supplier_id` int(11) NOT NULL,
                                `date_create` date NOT NULL,
                                `date_receive` date NOT NULL,
                                `item_no` int(11) NOT NULL,
                                `subtotal` decimal(12,2) NOT NULL,
                                `tax` decimal(12,2) NOT NULL,
                                `total` decimal(12,2) NOT NULL,
                                `note` text NOT NULL,
                                `status` varchar(64) NOT NULL,
                                PRIMARY KEY (`order_id`)
                          ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('order_item',
                            'CREATE TABLE `TABLE_NAME` (
                                `data_id` int(11) NOT NULL AUTO_INCREMENT,
                                `order_id` int(11) NOT NULL,
                                `item_id` int(11) NOT NULL,
                                `quantity` decimal(12,2) NOT NULL,
                                `price` decimal(12,2) NOT NULL,
                                `subtotal` decimal(12,2) NOT NULL,
                                `tax` decimal(12,2) NOT NULL,
                                `total` decimal(12,2) NOT NULL,
                                PRIMARY KEY (`data_id`),
                                UNIQUE KEY `idx_order_item1` (`order_id`,`item_id`)
                          ) ENGINE=InnoDB DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('receive',
                            'CREATE TABLE `TABLE_NAME` (
                                `receive_id` int(11) NOT NULL AUTO_INCREMENT,
                                `supplier_id` int(11) NOT NULL,
                                `order_id` int(11) NOT NULL,
                                `date` date NOT NULL,
                                `invoice_no` varchar(64) NOT NULL,
                                `location_id` int(11) NOT NULL,
                                `item_no` int(11) NOT NULL,
                                `subtotal` decimal(12,2) NOT NULL,
                                `tax` decimal(12,2) NOT NULL,
                                `total` decimal(12,2) NOT NULL,
                                `note` text NOT NULL,
                                `status` varchar(64) NOT NULL,
                                PRIMARY KEY (`receive_id`),
                                UNIQUE KEY `idx_receive1` (`supplier_id`,`invoice_no`,`date`)
                          ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('receive_item',
                            'CREATE TABLE `TABLE_NAME` (
                                `data_id` int(11) NOT NULL AUTO_INCREMENT,
                                `receive_id` int(11) NOT NULL,
                                `store_id` int(11) NOT NULL,
                                `item_id` int(11) NOT NULL,
                                `quantity` decimal(12,2) NOT NULL,
                                `price` decimal(12,2) NOT NULL,
                                `subtotal` decimal(12,2) NOT NULL,
                                `tax` decimal(12,2) NOT NULL,
                                `total` decimal(12,2) NOT NULL,
                                PRIMARY KEY (`data_id`),
                                UNIQUE KEY `idx_receive_item1` (`receive_id`,`store_id`,`item_id`)
                          ) ENGINE=InnoDB DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('client',
                            'CREATE TABLE `TABLE_NAME` (
                                `client_id` int(11) NOT NULL AUTO_INCREMENT,
                                `name` varchar(64) NOT NULL,
                                `account_code` varchar(64) NOT NULL,
                                `email` varchar(250) NOT NULL,
                                `address` text NOT NULL,
                                `note` text NOT NULL,
                                `status` varchar(16) NOT NULL,
                                PRIMARY KEY (`client_id`) 
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('deliver',
                            'CREATE TABLE `TABLE_NAME` (
                                `deliver_id` int(11) NOT NULL AUTO_INCREMENT,
                                `date` date NOT NULL,
                                `client_id` int(11) NOT NULL,
                                `store_id` int(11) NOT NULL,
                                `item_no` int(11) NOT NULL,
                                `subtotal` decimal(12,2) NOT NULL,
                                `tax` decimal(12,2) NOT NULL,
                                `total` decimal(12,2) NOT NULL,
                                `note` text NOT NULL,
                                `status` varchar(64) NOT NULL,
                                PRIMARY KEY (`deliver_id`)
                          ) ENGINE=InnoDB DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('deliver_item',
                            'CREATE TABLE `TABLE_NAME` (
                                `data_id` int(11) NOT NULL AUTO_INCREMENT,
                                `deliver_id` int(11) NOT NULL,
                                `stock_id` int(11) NOT NULL,
                                `quantity` decimal(12,2) NOT NULL,
                                `price` decimal(12,2) NOT NULL,
                                `subtotal` decimal(12,2) NOT NULL,
                                `tax` decimal(12,2) NOT NULL,
                                `total` decimal(12,2) NOT NULL,
                                `note` text NOT NULL,
                                `status` varchar(64) NOT NULL,
                                PRIMARY KEY (`data_id`),
                                UNIQUE KEY `idx_deliver_item1` (`deliver_id`,`stock_id`)
                          ) ENGINE=InnoDB DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('transfer',
                            'CREATE TABLE `TABLE_NAME` (
                                `transfer_id` int(11) NOT NULL AUTO_INCREMENT,
                                `date` date NOT NULL,
                                `from_store_id` int(11) NOT NULL,
                                `to_store_id` int(11) NOT NULL,
                                `item_no` int(11) NOT NULL,
                                `total_kg` decimal(12,2) NOT NULL,
                                `note` text NOT NULL,
                                `status` varchar(64) NOT NULL,
                                PRIMARY KEY (`transfer_id`)
                          ) ENGINE=InnoDB DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('transfer_item',
                            'CREATE TABLE `TABLE_NAME` (
                                `data_id` int(11) NOT NULL AUTO_INCREMENT,
                                `transfer_id` int(11) NOT NULL,
                                `stock_id` int(11) NOT NULL,
                                `quantity` decimal(12,2) NOT NULL,
                                `total_kg` decimal(12,2) NOT NULL,
                                `note` text NOT NULL,
                                `status` varchar(64) NOT NULL,
                                PRIMARY KEY (`data_id`),
                                UNIQUE KEY `idx_transfer_item1` (`transfer_id`,`stock_id`)
                          ) ENGINE=InnoDB DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('stock',
                            'CREATE TABLE `TABLE_NAME` (
                                `stock_id` int(11) NOT NULL AUTO_INCREMENT,
                                `item_id` int(11) NOT NULL,
                                `supplier_id` int(11) NOT NULL,
                                `invoice_no` varchar(64) NOT NULL,
                                `quantity_in` decimal(12,2) NOT NULL,
                                `quantity_out` decimal(12,2) NOT NULL,
                                PRIMARY KEY (`stock_id`),
                                UNIQUE KEY `idx_stock1` (`item_id`,`supplier_id`,`invoice_no`)
                          ) ENGINE=InnoDB DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('stock_store',
                            'CREATE TABLE `TABLE_NAME` (
                                `data_id` int(11) NOT NULL AUTO_INCREMENT,
                                `store_id` int(11) NOT NULL,
                                `stock_id` int(11) NOT NULL,
                                `quantity` decimal(12,2) NOT NULL,
                                PRIMARY KEY (`data_id`),
                                UNIQUE KEY `idx_stock_store1` (`store_id`,`stock_id`)
                          ) ENGINE=InnoDB DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('file',
                            'CREATE TABLE `TABLE_NAME` (
                              `file_id` int(10) unsigned NOT NULL,
                              `title` varchar(255) NOT NULL,
                              `file_name` varchar(255) NOT NULL,
                              `file_name_orig` varchar(255) NOT NULL,
                              `file_text` longtext NOT NULL,
                              `file_date` date NOT NULL DEFAULT \'0000-00-00\',
                              `location_id` varchar(64) NOT NULL,
                              `location_rank` int(11) NOT NULL,
                              `key_words` text NOT NULL,
                              `description` text NOT NULL,
                              `file_size` int(11) NOT NULL,
                              `encrypted` tinyint(1) NOT NULL,
                              `file_name_tn` varchar(255) NOT NULL,
                              `file_ext` varchar(16) NOT NULL,
                              `file_type` varchar(16) NOT NULL,
                              PRIMARY KEY (`file_id`),
                              FULLTEXT KEY `search_idx` (`key_words`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');  

        $this->addCreateSql('user_extend',
                            'CREATE TABLE `TABLE_NAME` (
                              `extend_id` INT NOT NULL AUTO_INCREMENT,
                              `user_id` INT NOT NULL,
                              `store_id` INT NOT NULL,
                              `cell` varchar(64) NOT NULL,
                              `tel` varchar(64) NOT NULL,
                              `email_alt` varchar(255) NOT NULL,
                              `address` TEXT NOT NULL,
                              PRIMARY KEY (`extend_id`),
                              UNIQUE KEY `idx_store_user1` (`user_id`)
                            ) ENGINE = MyISAM DEFAULT CHARSET=utf8');


        //initialisation
        $this->addInitialSql('INSERT INTO `TABLE_PREFIXclient` (name,email,status) '.
                             'VALUES("My first client","client@wherever.com","OK")');

        $this->addInitialSql('INSERT INTO `TABLE_PREFIXlocation` (name,status) '.
                             'VALUES("My default location","OK")');

        $this->addInitialSql('INSERT INTO `TABLE_PREFIXstore` (location_id,name,access,access_level,status) '.
                             'VALUES(1,"My first store","ADMIN",2,"OK"),(1,"My secure store","GOD",1,"OK")');

        $this->addInitialSql('INSERT INTO `TABLE_PREFIXitem_category` (name,access,access_level,sort,status) '.
                             'VALUES("Item default category","ADMIN",2,10,"OK"),("Item secure category","GOD",1,20,"OK")');

        $this->addInitialSql('INSERT INTO `TABLE_PREFIXitem` (category_id,name,code,units,units_kg_convert,status) '.
                             'VALUES(1,"My first stock item","MFSI","Kg",1,"OK"),(2,"My secure stock item","Litre",1,"OK")'); 

        $this->addInitialSql('INSERT INTO `TABLE_PREFIXsupplier` (name,status) '.
                             'VALUES("My first supplier","OK")');       

        //updates use time stamp in ['YYYY-MM-DD HH:MM'] format, must be unique and sequential
        //$this->addUpdateSql('YYYY-MM-DD HH:MM','Update TABLE_PREFIX--- SET --- "X"');
    }
 
}


  
?>
