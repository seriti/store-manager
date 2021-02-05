<?php  
/*
NB: This is not stand alone code and is intended to be used within "seriti/slim3-skeleton" framework
The code snippet below is for use within an existing src/routes.php file within this framework
copy the "/asset" group into the existing "/admin" group within existing "src/routes.php" file 
*/

$app->group('/admin', function () {

    $this->group('/store', function () {
        $this->post('/ajax', \App\Store\Ajax::class);
        $this->any('/client', App\Store\ClientController::class);
        $this->any('/client_file', App\Store\ClientFileController::class);
        $this->any('/client_location', App\Store\ClientLocationController::class);
        $this->any('/deliver', App\Store\DeliverController::class);
        $this->any('/deliver_file', App\Store\DeliverFileController::class);
        $this->any('/deliver_item', App\Store\DeliverItemController::class);
        $this->any('/deliver_wizard', \App\Store\DeliverWizardController::class);
        $this->any('/deliver_confirm', \App\Store\DeliverConfirmController::class);
        $this->any('/item', \App\Store\ItemController::class);
        $this->any('/item_category', \App\Store\ItemCategoryController::class);
        $this->any('/location', \App\Store\LocationController::class);
        $this->any('/order', App\Store\OrderController::class);
        $this->any('/order_file', App\Store\OrderFileController::class);
        $this->any('/order_item', App\Store\OrderItemController::class);
        $this->any('/order_wizard', \App\Store\OrderWizardController::class);
        $this->any('/receive', App\Store\ReceiveController::class);
        $this->any('/receive_file', App\Store\ReceiveFileController::class);
        $this->any('/receive_item', App\Store\ReceiveItemController::class);
        $this->any('/receive_wizard', \App\Store\ReceiveWizardController::class);
        $this->any('/report', App\Store\ReportController::class);
        $this->any('/stock', App\Store\StockController::class);
        $this->any('/setup', \App\Store\SetupController::class);
        $this->any('/store', App\Store\StoreController::class);
        $this->any('/supplier', App\Store\SupplierController::class);
        $this->any('/supplier_file', App\Store\SupplierFileController::class);
        $this->any('/transfer', App\Store\TransferController::class);
        $this->any('/transfer_item', App\Store\TransferItemController::class);
        $this->any('/transfer_wizard', App\Store\TransferWizardController::class);
        $this->any('/user_extend', \App\Store\UserExtendController::class);

        $this->any('/dashboard', \App\Store\DashboardController::class);
        $this->any('/setup_dashboard', \App\Store\SetupDashboardController::class);
        $this->get('/setup_data', \App\Store\SetupDataController::class);
    })->add(\App\Store\Config::class);
    
})->add(\App\User\ConfigAdmin::class);



