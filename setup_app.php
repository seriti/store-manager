<?php
/*
NB: This is not stand alone code and is intended to be used within "seriti/slim3-skeleton" framework
The code snippet below is for use within an existing src/setup_app.php file within this framework
add the below code snippet to the end of existing "src/setup_app.php" file.
This tells the framework about module: name, sub-memnu route list and title, database table prefix.
*/

$container['config']->set('module','store',['name'=>'Store manager',
                                              'route_root'=>'admin/store/',
                                              'route_list'=>['dashboard'=>'Dashboard','order'=>'Orders','receive'=>'Reception',
                                                             'transfer'=>'Transfers','deliver'=>'Deliveries',
                                                             'stock'=>'Stock','setup_dashboard'=>'Setup','report'=>'Reports'],
                                              'labels'=>['invoice_no'=>'Invoice/batch No.'],
                                              'table_prefix'=>'str_'
                                             ]);

