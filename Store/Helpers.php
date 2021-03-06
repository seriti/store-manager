<?php
namespace App\Store;

use Exception;
use Seriti\Tools\Calc;
use Seriti\Tools\Calendar;
use Seriti\Tools\Csv;
use Seriti\Tools\Html;
use Seriti\Tools\Pdf;
use Seriti\Tools\Doc;
use Seriti\Tools\Date;
use Seriti\Tools\Upload;
use Seriti\Tools\DEBUG;
use Seriti\Tools\STORAGE;
use Seriti\Tools\SITE_TITLE;
use Seriti\Tools\BASE_UPLOAD;
use Seriti\Tools\UPLOAD_DOCS;
use Seriti\Tools\UPLOAD_TEMP;
use Seriti\Tools\SITE_NAME;
use Seriti\Tools\AJAX_ROUTE;

use Psr\Container\ContainerInterface;

class Helpers {
       
    //generic record get, add any exceptions you want
    public static function get($db,$table_prefix,$table,$id,$key = '') 
    {
        $table_name = $table_prefix.$table;

        if($key === '') $key = $table.'_id';    
        
        if($table === 'reserve') {
            $sql = 'SELECT * FROM '.$table_name.' WHERE '.$key.' = "'.$db->escapeSql($id).'" ';
        } else {
            $sql = 'SELECT * FROM '.$table_name.' WHERE '.$key.' = "'.$db->escapeSql($id).'" ';
        }

        $record = $db->readSqlRecord($sql);
                        
        return $record;
    } 

    public static function getDeliverItems($db,$table_prefix,$deliver_id,$format = 'ARRAY')
    {
        if($format !== 'ARRAY') {
            $output = '';
        } else {
            $output = [];    
        }
        
        $table_deliver_item = $table_prefix.'deliver_item';
        $table_stock = $table_prefix.'stock';
        $table_item = $table_prefix.'item';
        $table_category = $table_prefix.'item_category';
        
        $sql = 'SELECT D.stock_id,I.name,D.quantity,I.units,D.price,D.subtotal,D.tax,D.total,D.note  '.
               'FROM '.$table_deliver_item.' AS D '.
                     'JOIN '.$table_stock.' AS S ON(D.stock_id = S.stock_id) '.
                     'JOIN '.$table_item.' AS I ON (S.item_id = I.item_id) '.
               'WHERE deliver_id = "'.$db->escapeSql($deliver_id).'" ';

        $items = $db->readSqlArray($sql);
        if($items != 0) {
             if($format === 'HTML') {
                $output .= '<table>';
                foreach($items as $item) {
                    $output .= '<tr><td>'.$item['name'].'&nbsp;</td><td>'.$item['quantity'].$item['units'].'</td></tr>';
                }
                $output .= '</table>';
             }
        }

        return $output;
        
    }    

    public static function getDeliverConfirm($db,$table_prefix,$store_id,$allow_confirm = false)
    {
        $html = '';

        $table_store = $table_prefix.'store';
        $table_client = $table_prefix.'client';
        $table_deliver = $table_prefix.'deliver';
        //$table_item = $table_prefix.'deliver_item';
        if($store_id === 'ALL') {
            $store_name = 'ALL stores';
        } else {
            $store = Helpers::get($db,$table_prefix,'store',$store_id);
            $store_name = $store['name'];
        }
        $html .= '<h1>Confirm deliveries for: '.$store_name.'</h1>';

        $sql = 'SELECT D.deliver_id,C.name as client,D.item_no,D.total,D.note,S.name AS store '.
               'FROM '.$table_deliver.' AS D '.
               'JOIN '.$table_client.' AS C ON(D.client_id = C.client_id) '.
               'JOIN '.$table_store.' AS S ON(D.store_id = S.store_id) '.
               'WHERE D.status = "NEW" ';
        if($store_id !== 'ALL') $sql .= 'AND D.store_id = "'.$db->escapeSql($store_id).'" ';
                $sql .= 'ORDER BY S.name,D.date ';
        $deliveries = $db->readSqlArray($sql); 
        if($deliveries == 0) {
            $html .= '<h2>There are no deliveries outstanding(status = NEW).</h2>';
        } else {

            $html .= '<table class="table  table-striped table-bordered table-hover table-condensed">'.
                     '<tr><th>ID</th><th>Store</th><th>Client</th><th>Items</th><th>Total value</th><th>Delivered</th></tr>';
            foreach($deliveries as $deliver_id => $deliver) {
                if($allow_confirm) {
                    $link = '<input type="checkbox" id="D'.$deliver_id.'" onclick="deliver_update(\''.$deliver_id.'\')" >';
                } else {
                    $link = 'Not yet. You cannot update.';
                }

                $items = self::getDeliverItems($db,$table_prefix,$deliver_id,'HTML');
                
                $html .= '<tr id="R'.$deliver_id.'"><td>'.$deliver_id.'</td><td>'.$deliver['store'].'</td><td>'.$deliver['client'].'</td>'.
                             '<td>'.$items.'</td><td>'.number_format($deliver['total'],2).'</td>'.
                             '<td>'.$link.'</td>'.
                          '</tr>';
            }
            $html .= '</table>';

            $html .= '<script type="text/javascript">
                      //make first month open
                      var month = $("#collapse_1");
                      month.addClass("in");
                      
                      function deliver_update(deliver_id) {
                        var row_id = "R"+deliver_id;
                        var check_id = "D"+deliver_id;
                        var checkbox = document.getElementById(check_id);
                        //alert("Delivery"+deliver_id+" value:"+checkbox.checked);
                        
                        var param="deliver_id="+deliver_id+"&checked="+checkbox.checked;
                        var url="ajax?mode=deliver_confirm";
                        xhr(url,param,update_handler,row_id)
                         
                      }
                      function update_handler(str,row_id) {
                         var row = document.getElementById(row_id);

                         var response = JSON.parse(str);
                         if(response.errors_found) {
                           alert("ERROR:"+response.error);
                           row.style.backgroundColor = "red";
                         } else {
                           //alert(response.message); 
                           if(response.status == "DELIVERED") {
                             row.style.backgroundColor = "lime";     
                           } else {
                             row.style.backgroundColor = "white";   
                           }
                            
                         }  
                      }
                      </script>';
        } 

        return $html;     
    }

    public static function getStockItem($db,$table_prefix,$stock_id) 
    {
        $table_stock = $table_prefix.'stock';
        $table_supplier = $table_prefix.'supplier';
        $table_item = $table_prefix.'item';
        $table_category = $table_prefix.'item_category';
        
        $sql = 'SELECT S.supplier_id,S.invoice_no,S.quantity_in,S.quantity_out,'.
                      'SU.name as supplier,I.name,I.code,I.units,I.units_kg_convert,C.name AS category,C.access,C.access_level '.
               'FROM '.$table_stock.' AS S '.
                     'JOIN '.$table_supplier.' AS SU ON(S.supplier_id = SU.supplier_id) '.
                     'JOIN '.$table_item.' AS I ON (S.item_id = I.item_id) '.
                     'JOIN '.$table_category.' AS C ON (I.category_id = C.category_id) '.
                'WHERE stock_id = "'.$db->escapeSql($stock_id).'" ';

        $record = $db->readSqlRecord($sql);
        if($record != 0) {
             $record['summary'] = $record['name'].'('.$record['supplier'].' - '.$record['invoice_no'].')';
        }
                        
        return $record;
    }

    public static function getStockInStore($db,$table_prefix,$stock_id,$store_id) 
    {
        $table_stock_store = $table_prefix.'stock_store';
        
        $sql = 'SELECT SS.data_id,SS.quantity '.
               'FROM '.$table_stock_store.' AS SS '.
               'WHERE SS.stock_id = "'.$db->escapeSql($stock_id).'" AND SS.store_id = "'.$db->escapeSql($store_id).'" ';
        $record = $db->readSqlRecord($sql);
                     
        return $record;
    }

    //NB: uses stock_store data_id generally from form select lists
    public static function getStockInStoreId($db,$table_prefix,$data_id) 
    {
        $table_stock = $table_prefix.'stock';
        $table_stock_store = $table_prefix.'stock_store';
                
        $sql = 'SELECT SS.data_id,SS.store_id,SS.stock_id,SS.quantity,S.item_id '.
               'FROM '.$table_stock_store.' AS SS JOIN '.$table_stock.' AS S ON(SS.stock_id = S.stock_id)'.
               'WHERE SS.data_id = "'.$db->escapeSql($data_id).'" ';
        $record = $db->readSqlRecord($sql);
                     
        return $record;
    }

    //check if can update an order
    public static function verifyOrderItemUpdate($db,$table_prefix,$update_type,$order_id,$item_id,$update = [],&$error)
    {
        $error = '';
        
        $order = self::get($db,$table_prefix,'order',$order_id);
        if($order['status'] !== 'NEW') {
            $error .= 'Cannot update item as order status['.$order['status'].'] is not NEW ';
        }

        if($update_type !== 'INSERT') {
            $item = self::get($db,$table_prefix,'order_item',$item_id,'data_id');
            if($item == 0) {
                $error .= 'Order item ID['.$item_id.'] invalid';
            } else {
                
            }    
        }
        

        if($error === '') return true; else return false;
    }

    //update order totals after item update
    public static function updateOrder($db,$table_prefix,$order_id,&$error)
    {
        $error = '';
        $output = [];
        
        $table_order = $table_prefix.'order';
        $table_order_item = $table_prefix.'order_item';
        
        $sql = 'SELECT COUNT(*) AS item_no,SUM(subtotal) AS subtotal,SUM(tax) AS tax,SUM(total) AS total '.
               'FROM '.$table_order_item.'  '.
               'WHERE order_id = "'.$db->escapeSql($order_id).'" ';
        $totals = $db->readSqlRecord($sql);
                
        $sql = 'UPDATE '.$table_order.'  '.
               'SET item_no = "'.$totals['item_no'].'", '.
                   'subtotal = "'.$totals['subtotal'].'", '.
                   'tax = "'.$totals['tax'].'", '.
                   'total =  "'.$totals['total'].'" '.
               'WHERE order_id = "'.$db->escapeSql($order_id).'" ';
        $db->executeSql($sql,$error);
        
        if($error !== '') return false; else return true;
    }

    //check if can update a reception, and process required stock updates 
    public static function receiveItemUpdate($db,$table_prefix,$update_type,$receive_id,$data_id,$update = [],&$error)
    {
        $error = '';
        $error_tmp = '';

        $receive = self::get($db,$table_prefix,'receive',$receive_id);
        if($receive['status'] !== 'NEW') {
            $error .= 'Cannot update item as reception status['.$receive['status'].'] is not NEW ';
        }
    
        //reverse original quantity
        if($error === '' and ($update_type === 'UPDATE' or $update_type === 'DELETE')) {
            $item = self::get($db,$table_prefix,'receive_item',$data_id,'data_id');
            $quantity = $item['quantity'] * -1;
            
            self::updateStockReceived($db,$table_prefix,$item['item_id'],$receive['supplier_id'],$receive['invoice_no'],$quantity,$item['store_id'],$error_tmp);
            if($error_tmp !== '') {
                $error .= 'Could not reverse original quantity received. ';
                if(DEBUG) $error .= $error_tmp;
            }
        }    

        //update new value if not a delete
        if($error === '' and ($update_type === 'UPDATE' or $update_type === 'INSERT')) {
            self::updateStockReceived($db,$table_prefix,$update['item_id'],$receive['supplier_id'],$receive['invoice_no'],$update['quantity'],$update['store_id'],$error_tmp);
            if($error_tmp !== '') {
                $error .= 'Could not update changed item stock. ';
                if(DEBUG) $error .= $error_tmp;
            }  
        }
   

        if($error === '') return true; else return false;
    }

    //update reception totals after item update
    public static function updateReceive($db,$table_prefix,$receive_id,&$error)
    {
        $error = '';
        $output = [];
        
        $table_receive = $table_prefix.'receive';
        $table_receive_item = $table_prefix.'receive_item';
        
        $sql = 'SELECT COUNT(*) AS item_no,SUM(subtotal) AS subtotal,SUM(tax) AS tax,SUM(total) AS total '.
               'FROM '.$table_receive_item.'  '.
               'WHERE receive_id = "'.$db->escapeSql($receive_id).'" ';
        $totals = $db->readSqlRecord($sql);
                
        $sql = 'UPDATE '.$table_receive.'  '.
               'SET item_no = "'.$totals['item_no'].'", '.
                   'subtotal = "'.$totals['subtotal'].'", '.
                   'tax = "'.$totals['tax'].'", '.
                   'total =  "'.$totals['total'].'" '.
               'WHERE receive_id = "'.$db->escapeSql($receive_id).'" ';
        $db->executeSql($sql,$error);
        
        if($error !== '') return false; else return true;
    }

    //NB: make quantity negative to REVERSE item reception
    public static function updateStockReceived($db,$table_prefix,$item_id,$supplier_id,$invoice_no,$quantity,$store_id,&$error)
    {
        $error = '';
        $output = [];

        $table_stock = $table_prefix.'stock';
        $table_stock_store = $table_prefix.'stock_store';

        if($quantity == 0) {
            $error = 'Stock reception quantity cannot be ZERO';
            return false;
        }

        //NB: negative quantity is so can process reversals of stock received
        if($quantity < 0) $reversal = true; else $reversal = false;

        
        //first update-insert primary stock table
        $sql = 'SELECT stock_id,quantity_in,quantity_out  '.
               'FROM '.$table_stock.'  '.
               'WHERE item_id = "'.$db->escapeSql($item_id).'" AND supplier_id = "'.$db->escapeSql($supplier_id).'" AND invoice_no = "'.$db->escapeSql($invoice_no).'" ';
        $stock = $db->readSqlRecord($sql);
        //update existing stock record
        if($stock != 0) {
            $stock_id = $stock['stock_id'];

            if($reversal and $stock['quantity_in'] < abs($quantity)) {
                $error .= 'Stock quantity received['.$stock['quantity_in'].'] cannot be less than reversal quantity['.$quantity.'] ' ;
            } else {
                $sql = 'UPDATE '.$table_stock.' SET quantity_in = quantity_in + '.$quantity.' '.
                       'WHERE stock_id = "'.$stock_id.'" ';
                $db->executeSql($sql,$error_tmp);
                if($error_tmp !== '') $error .= 'Could not update existing stock received levels.';
            }
        } else {
            if($reversal) {
                $error .= 'Zero stock exists, so cannote reverse quantity['.$quantity.'] ' ;
            } else {
                $stock = [];
                $stock['item_id'] = $item_id;
                $stock['supplier_id'] = $supplier_id;
                $stock['invoice_no'] = $invoice_no;
                $stock['quantity_in'] = $quantity;
                $stock['quantity_out'] = '0.00';

                $stock_id = $db->insertRecord($table_stock,$stock,$error_tmp);
                if($error_tmp !== '') $error .= 'Could not create new stock item.';
            }  
        }    
        
        //now update stock store linkage
        if($error === '') {
            $sql = 'SELECT data_id,stock_id,quantity '.
                   'FROM '.$table_stock_store.' '.
                   'WHERE store_id = "'.$db->escapeSql($store_id).'" AND stock_id = "'.$db->escapeSql($stock_id).'" ';
            $stock_store = $db->readSqlRecord($sql);

            if($stock_store != 0) {
                if($reversal and $stock_store['quantity'] < abs($quantity)) {
                   $error .= 'Stock quantity available in store['.$stock_store['quantity'].'] cannot be less than reversal quantity['.$quantity.'] ' ;
                } else {
                    $sql = 'UPDATE '.$table_stock_store.' SET quantity = quantity + '.$quantity.' '.
                           'WHERE data_id = "'.$stock_store['data_id'].'" ';
                    $db->executeSql($sql,$error_tmp);
                    if($error_tmp !== '') $error .= 'Could not update existing stock levels.';
                }
            } else {
                if($reversal) {
                    $error .= 'Zero stock exists in store, so cannote reverse quantity['.$quantity.'] ' ;
                } else {
                    $stock_store = [];
                    $stock_store['store_id'] = $store_id;
                    $stock_store['stock_id'] = $stock_id;
                    $stock_store['quantity'] = $quantity;
                    
                    $stock_id = $db->insertRecord($table_stock_store,$stock_store,$error_tmp);
                    if($error_tmp !== '') $error .= 'Could not create new store stock item.'.$error_tmp;
                }

            }
        }
        
        if($error !== '') return false; else return true;
    }

    //check if can update a reception, and process required stock updates 
    public static function deliverItemUpdate($db,$table_prefix,$update_type,$deliver_id,$data_id,$update = [],&$error)
    {
        $error = '';
        $error_tmp = '';
        
        $deliver = self::get($db,$table_prefix,'deliver',$deliver_id);
        if($deliver['status'] !== 'NEW') {
            $error .= 'Cannot update item as deliver status['.$deliver['status'].'] is not NEW ';
        }
                
        //reverse original quantity
        if($error === '' and ($update_type === 'UPDATE' or $update_type === 'DELETE')) {
            $item = self::get($db,$table_prefix,'deliver_item',$data_id,'data_id');
            $quantity = $item['quantity'] * -1;

            self::updateStockDelivered($db,$table_prefix,$deliver['store_id'],$item['stock_id'],$quantity,$error_tmp);
            if($error_tmp !== '') {
                $error .= 'Could not reverse original quantity received. ';
                if(DEBUG) $error .= $error_tmp;
            }
        }    

        //update new value if not a delete
        if($error === '' and ($update_type === 'UPDATE' or $update_type === 'INSERT')) {
            self::updateStockDelivered($db,$table_prefix,$deliver['store_id'],$update['stock_id'],$update['quantity'],$error_tmp);
            if($error_tmp !== '') {
                $error .= 'Could not update changed item stock. ';
                if(DEBUG) $error .= $error_tmp;
            }  
        }
   

        if($error === '') return true; else return false;
    }

    //update delivery totals after item update
    public static function updateDeliver($db,$table_prefix,$receive_id,&$error)
    {
        $error = '';
        $output = [];
        
        $table_deliver = $table_prefix.'deliver';
        $table_item = $table_prefix.'deliver_item';
        
        $sql = 'SELECT COUNT(*) AS item_no,SUM(subtotal) AS subtotal,SUM(tax) AS tax,SUM(total) AS total '.
               'FROM '.$table_item.'  '.
               'WHERE deliver_id = "'.$db->escapeSql($deliver_id).'" ';
        $totals = $db->readSqlRecord($sql);
                
        $sql = 'UPDATE '.$table_deliver.'  '.
               'SET item_no = "'.$totals['item_no'].'", '.
                   'subtotal = "'.$totals['subtotal'].'", '.
                   'tax = "'.$totals['tax'].'", '.
                   'total =  "'.$totals['total'].'" '.
               'WHERE deliver_id = "'.$db->escapeSql($deliver_id).'" ';
        $db->executeSql($sql,$error);
        
        if($error !== '') return false; else return true;
    }

    //NB: make quantity negative to REVERSE item delivery, $data_id 
    public static function updateStockDelivered($db,$table_prefix,$store_id,$stock_id,$quantity,&$error)
    {
        $error = '';
        $output = [];

        $table_stock = $table_prefix.'stock';
        $table_stock_store = $table_prefix.'stock_store';

        if($quantity == 0) {
            $error = 'Stock reception quantity cannot be ZERO';
            return false;
        }

        //NB: negative quantity is so can process reversals of stock delivered
        if($quantity < 0) $reversal = true; else $reversal = false;

        //first update primary stock table
        $sql = 'SELECT stock_id,item_id,quantity_in,quantity_out  '.
               'FROM '.$table_stock.'  '.
               'WHERE stock_id = "'.$db->escapeSql($stock_id).'" ';
        $stock = $db->readSqlRecord($sql);
        if($stock == 0) {
            $error .= 'Stock ID['.$stock_id.'] does not exist, so cannote deliver['.$quantity.'] ' ;
        } else { 
            $available = $stock['quantity_in'] - $stock['quantity_out'];  
            if(!$reversal and $available < abs($quantity)) {
                $error .= 'Stock quantity available['.$available.'] is less than delivery quantity['.$quantity.'] ' ;
            } else {
                $sql = 'UPDATE '.$table_stock.' SET quantity_out = quantity_out + '.$quantity.' '.
                       'WHERE stock_id = "'.$stock['stock_id'].'" ';
                $db->executeSql($sql,$error_tmp);
                if($error_tmp !== '') $error .= 'Could not update existing stock delivered levels.';
            }
        }
      
        //now update store stock table
        if($error === '') {
            $sql = 'SELECT data_id,stock_id,store_id,quantity '.
                   'FROM '.$table_stock_store.' '.
                   'WHERE store_id = "'.$db->escapeSql($store_id).'" AND stock_id = "'.$db->escapeSql($stock_id).'" ';
            $stock_store = $db->readSqlRecord($sql);
            if($stock_store == 0) {
                $error .= 'Zero store['.$store_id.'] stock['.$stock_id.'] exists, so cannote deliver['.$quantity.'] from store' ;
            } else {    
                if(!$reversal and $stock_store['quantity'] < abs($quantity)) {
                   $error .= 'Stock quantity available in store['.$stock_store['quantity'].'] cannot be less than delivery quantity['.$quantity.'] ' ;
                } else {
                    $sql = 'UPDATE '.$table_stock_store.' SET quantity = quantity - '.$quantity.' '.
                           'WHERE data_id = "'.$stock_store['data_id'].'" ';
                    $db->executeSql($sql,$error_tmp);
                    if($error_tmp !== '') $error .= 'Could not update existing stock store levels.';
                }
            } 
        }        

        
        if($error !== '') return false; else return true;
    }

    //check if can update a transfer
    public static function transferItemUpdate($db,$table_prefix,$update_type,$transfer_id,$item_id,$update = [],&$error)
    {
        $error = '';
        
        $transfer = self::get($db,$table_prefix,'transfer',$transfer_id);
        if($transfer['status'] !== 'NEW') {
            $error .= 'Cannot update item as transfer status['.$transfer['status'].'] is not NEW ';
        }
        
        //reverse original transfer 
        if($error === '' and ($update_type === 'UPDATE' or $update_type === 'DELETE')) {
            $item = self::get($db,$table_prefix,'transfer_item',$item_id,'data_id');

            //add back original amount to FROM store
            self::updateStockInStore($db,$table_prefix,$transfer['from_store_id'],$item['stock_id'],$item['quantity'],$error_tmp);
            if($error_tmp !== '') {
                $error .= 'Could not reverse original quantity transfered FROM Store. ';
                if(DEBUG) $error .= $error_tmp;
            }

            //if confirmed need to reverse original amount from TO store as well
            if($transfer['status'] === 'CONFIRMED') {
                $quantity = abs($item['quantity']) * -1;
                self::updateStockInStore($db,$table_prefix,$transfer['to_store_id'],$item['stock_id'],$quantity,$error_tmp);
                if($error_tmp !== '') {
                    $error .= 'Could not reverse original quantity transfered TO Store. ';
                    if(DEBUG) $error .= $error_tmp;
                }
            }
            
            /*
            $from_store_id = $transfer['to_store_id'];
            $to_store_id = $transfer['from_store_id'];
            self::updateStockTransfered($db,$table_prefix,'TO',$from_store_id,$to_store_id,$item['stock_id'],$item['quantity'],$error_tmp);
            if($error_tmp !== '') {
                $error .= 'Could not reverse original quantity transfered TO Store. ';
                if(DEBUG) $error .= $error_tmp;
            }
            */
        }    

        //update new value if not a delete
        if($error === '' and ($update_type === 'UPDATE' or $update_type === 'INSERT')) {
            
            $quantity_to = abs($update['quantity']);
            $quantity_from = $quantity_to * -1;

            self::updateStockInStore($db,$table_prefix,$transfer['from_store_id'],$update['stock_id'],$quantity_from,$error_tmp);
            if($error_tmp !== '') {
                $error .= 'Could not remove quantity transfered FROM Store. ';
                if(DEBUG) $error .= $error_tmp;
            }

            //if confirmed need to update TO store as well
            if($transfer['status'] === 'CONFIRMED') {
                self::updateStockInStore($db,$table_prefix,$transfer['to_store_id'],$update['stock_id'],$quantity_to,$error_tmp);
                if($error_tmp !== '') {
                    $error .= 'Could not add quantity transfered TO Store. ';
                    if(DEBUG) $error .= $error_tmp;
                }
            }

            /*
            self::updateStockTransfered($db,$table_prefix,'FROM',$transfer['from_store_id'],$transfer['to_store_id'],$update['stock_id'],$update['quantity'],$error_tmp);
            if($error_tmp !== '') {
                $error .= 'Could not update changed item stock FROM store. ';
                if(DEBUG) $error .= $error_tmp;
            } 
            */ 
        }
   

        if($error === '') return true; else return false;
    }



    //update transfer totals after item update
    public static function updateTransfer($db,$table_prefix,$update,$transfer_id,&$error)
    {
        $error = '';
        $output = [];
        
        $table_transfer = $table_prefix.'transfer';
        $table_transfer_item = $table_prefix.'transfer_item';

        $transfer = self::get($db,$table_prefix,'transfer',$transfer_id);
        
        if($update === 'TOTALS') {
            $sql = 'SELECT COUNT(*) AS item_no,SUM(total_kg) AS total_kg '.
                   'FROM '.$table_transfer_item.'  '.
                   'WHERE transfer_id = "'.$db->escapeSql($transfer_id).'" ';
            $totals = $db->readSqlRecord($sql);
                    
            $sql = 'UPDATE '.$table_transfer.'  '.
                   'SET item_no = "'.$totals['item_no'].'", '.
                       'total_kg =  "'.$totals['total_kg'].'" '.
                   'WHERE transfer_id = "'.$db->escapeSql($transfer_id).'" ';
            $db->executeSql($sql,$error);    
        }

        if($update === 'CONFIRM') {
            if($transfer['status'] !== 'NEW') {
                $error .= 'Cannot CONFIRM a transfer unless status = NEW';
            } else {
                $db->executeSql('START TRANSACTION',$error_tmp);
                if($error_tmp !== '') {
                    $error .= 'Could not START Transfer confirm transaction';
                } else {        
                    $sql = 'SELECT data_id,stock_id,quantity,total_kg,status '.
                           'FROM '.$table_transfer_item.' WHERE transfer_id = "'.$db->escapeSql($transfer_id).'" ';
                    $items = $db->readSqlarray($sql);
                    foreach($items as $data_id => $item) {

                        Helpers::updateStockInStore($db,$table_prefix,$transfer['to_store_id'],$item['stock_id'],$item['quantity'],$error_tmp);
                        if($error_tmp !== '') {
                            $error .= 'We could not update TO Store amounts for stock ID['.$item['stock_id'].'] ';
                            if(DEBUG) $error .= $error_tmp;
                        } else {
                            $sql = 'UPDATE '.$table_transfer_item.' SET status = "CONFIRMED" '.
                                   'WHERE data_id = "'.$db->escapeSql($data_id).'" ';
                            $db->executeSql($sql,$error_tmp);
                            if($error_tmp !== '') $error .= 'We could not CONFIRM transfer ID['.$transfer_id.'] ';
                        }

                        /*
                        Helpers::updateStockTransfered($db,$table_prefix,'TO',$transfer['from_store_id'],$transfer['to_store_id'],$item['stock_id'],$item['quantity'],$error_tmp);
                        if($error_tmp !== '') {
                            $error .= 'We could not update TO Store amounts for stock ID['.$item['stock_id'].'] ';
                        } else {
                            $sql = 'UPDATE '.$table_transfer_item.' SET status = "CONFIRMED" '.
                                   'WHERE data_id = "'.$db->escapeSql($data_id).'" ';
                            $db->executeSql($sql,$error_tmp);
                            if($error_tmp !== '') $error .= 'We could not CONFIRM transfer ID['.$transfer_id.'] ';
                        }
                        */
                    }
                }    

                if($error === '') {
                    $sql = 'UPDATE '.$table_transfer.' SET status = "CONFIRMED" '.
                           'WHERE transfer_id = "'.$db->escapeSql($transfer_id).'" ';
                    $db->executeSql($sql,$error_tmp);
                    if($error_tmp !== '') $error .= 'We could not CONFIRM transfer ID['.$transfer_id.']';
                }

                if($error !== '') {
                    $db->executeSql('ROLLBACK',$error_tmp);
                    if($error_tmp !== '') $error .= 'Could not ROLLBACK transfer confirm transaction';
                } else {
                    $db->executeSql('COMMIT',$error_tmp);
                    if($error_tmp !== '') $error .= 'Could not COMMIT transfer confirm transaction';
                }
            }
        }
        
        
        if($error !== '') return false; else return true;
    }

    //validates quantities and updates store stock, $update_store = FROM or TO or BOTH
    //NOT used anywhere, REPLACED BY using updateStockInStore(), makes for massive confusion as it is, maybe repurpose, probably remove entirely
    public static function updateStockTransfered($db,$table_prefix,$update_store,$from_store_id,$to_store_id,$stock_id,$quantity,&$error)
    {
        $error = '';
        $output = [];

        $quantity = abs($quantity);

        $table_stock = $table_prefix.'stock';
        $table_stock_store = $table_prefix.'stock_store';

        $stock = self::getStockItem($db,$table_prefix,$stock_id); 

        $stock_from = self::getStockInStore($db,$table_prefix,$stock_id,$from_store_id);  
        $stock_to = self::getStockInStore($db,$table_prefix,$stock_id,$to_store_id);  

        //check stock in FROM store still available
        if($update_store === 'FROM' or $update_store === 'BOTH') {
            if($stock_from['quantity'] - $quantity < 0 ) {
                $error = 'Insufficient stock['.$stock['summary'].'] in FROM store['.$stock_from['quantity'].'] for transfer amount['.$quantity.'] ';
                return false;
            }

            //update FROM store stock
            $sql = 'UPDATE '.$table_stock_store.' SET quantity = quantity - '.$quantity.' '.
                   'WHERE data_id = "'.$stock_from['data_id'].'" ';
            $db->executeSql($sql,$error_tmp);
            if($error_tmp !== '') $error .= 'Could not update FROM store stock levels.';
        }  
        
        
        if($update_store === 'TO' or $update_store === 'BOTH') {
            //update TO store stock
            if($stock_to != 0) {
                $sql = 'UPDATE '.$table_stock_store.' SET quantity = quantity + '.$quantity.' '.
                       'WHERE data_id = "'.$stock_to['data_id'].'" ';
                $db->executeSql($sql,$error_tmp);
                if($error_tmp !== '') $error .= 'Could not update TO store stock levels.';
            } else {
                $stock_store = [];
                $stock_store['store_id'] = $to_store_id;
                $stock_store['stock_id'] = $stock_id;
                $stock_store['quantity'] = $quantity;
                
                $data_id = $db->insertRecord($table_stock_store,$stock_store,$error_tmp);
                if($error_tmp !== '') $error .= 'Could not create TO store stock.';
            }
        }        
                
        if($error !== '') return false; else return true;
    }

    public static function updateStockInStore($db,$table_prefix,$store_id,$stock_id,$quantity,&$error)
    {
        $error = '';

        $table_stock_store = $table_prefix.'stock_store';

        $store = self::get($db,$table_prefix,'store',$store_id); 

        $stock = self::getStockItem($db,$table_prefix,$stock_id); 
        if($stock == 0) $error .= 'Invalid stock ID['.$stock_id.']';
        
        $stock_store = self::getStockInStore($db,$table_prefix,$stock_id,$store_id); 
        
        //check sufficient stock if $quantity negative 
        if($quantity < 0) {
            if($stock_store == 0) {
                $error = 'NO Stock['.$stock['summary'].'] in store['.$store['name'].'] to REMOVE amount['.abs($quantity).'] ';
            } elseif($stock_store['quantity'] + $quantity < 0 ) {
                $error = 'Stock['.$stock['summary'].'] in store['.$store['name'].'] amount['.$stock_store['quantity'].'] insufficient to REMOVE amount['.abs($quantity).'] ';
            }    
        }

        if($error !== '') return false;
           
        if($stock_store != 0) {
            $sql = 'UPDATE '.$table_stock_store.' SET quantity = quantity + '.$quantity.' '.
                   'WHERE data_id = "'.$stock_store['data_id'].'" ';
            $db->executeSql($sql,$error_tmp);
            if($error_tmp !== '') $error .= 'Could not update store['.$store['name'].'] stock['.$stock['summary'].'] quantity.';
        } else {
            $stock_store = [];
            $stock_store['store_id'] = $store_id;
            $stock_store['stock_id'] = $stock_id;
            $stock_store['quantity'] = $quantity;
            
            $data_id = $db->insertRecord($table_stock_store,$stock_store,$error_tmp);
            if($error_tmp !== '') $error .= 'Could not create store stock.';
        }
                
                
        if($error !== '') return false; else return true;
    }

    
    public static function getOrderDetails($db,$table_prefix,$order_id,&$error)
    {
        $error = '';
        $output = [];
        
        $table_order = $table_prefix.'order';
        $table_supplier = $table_prefix.'supplier';
        $table_order_item = $table_prefix.'order_item';
        $table_item = $table_prefix.'item';
        $table_location = $table_prefix.'location';
        
        $sql = 'SELECT O.order_id,O.date_create,O.date_receive,O.subtotal,O.tax,O.total,O.status,'.
                      'O.location_id,O.note,O.item_no,L.name AS location,'.
                      'O.supplier_id,S.name AS supplier,S.email AS supplier_email,S.contact AS supplier_contact '.
               'FROM '.$table_order.' AS O '.
                     'LEFT JOIN '.$table_location.' AS L ON(O.location_id = L.location_id) '.
                     'LEFT JOIN '.$table_supplier.' AS S ON(O.supplier_id = S.supplier_id) '.
               'WHERE O.order_id = "'.$db->escapeSql($order_id).'" ';
        $order = $db->readSqlRecord($sql);
        if($order === 0) {
            $error .= 'Invalid Order ID['.$order_id.']. ';
        } else {
            $output['order'] = $order;
        }

        $sql = 'SELECT I.data_id,I.item_id,IT.name,I.price,I.quantity,I.subtotal,I.tax,I.total '.
               'FROM '.$table_order_item.' AS I LEFT JOIN '.$table_item.' AS IT ON(I.item_id = IT.item_id) '.
               'WHERE I.order_id = "'.$db->escapeSql($order_id).'" ';
        $items = $db->readSqlArray($sql);
        if($items === 0) {
            $error .= 'No items for Order ID['.$order_id.']. ';
        } else {
            $output['items'] = $items;
        }
        
        if($error !== '') return false; else return $output;
    }

    public static function getDeliverDetails($db,$table_prefix,$deliver_id,$param = [],&$error)
    {
        $error = '';
        $output = [];
        
        if(!isset($param['get'])) $param['get'] = 'ALL';

        $table_deliver = $table_prefix.'deliver';
        $table_client = $table_prefix.'client';
        $table_client_location = $table_prefix.'client_location';
        $table_store = $table_prefix.'store';

        $table_deliver_item = $table_prefix.'deliver_item';
        $table_stock = $table_prefix.'stock';
        $table_item = $table_prefix.'item';
        
        if($param['get'] === 'ALL' or $param['get'] === 'BASIC') {
            $sql = 'SELECT D.deliver_id,D.date,D.item_no,D.subtotal,D.tax,D.total,D.note,D.transport_paid,D.status,'.
                          'D.store_id,S.name AS store,'.
                          'D.client_id,D.client_order_no,C.name AS client,C.contact,C.account_code,C.email,C.cell,C.tel,C.email,C.address, '.
                          'D.client_location_id,L.name AS client_location,L.address AS location_address, '.
                          'L.contact AS location_contact,L.cell AS location_cell,L.tel AS location_tel,L.email AS location_email '.
                   'FROM '.$table_deliver.' AS D '.
                         'JOIN '.$table_store.' AS S ON(D.store_id = S.store_id) '.
                         'JOIN '.$table_client.' AS C ON(D.client_id = C.client_id) '.
                         'LEFT JOIN '.$table_client_location.' AS L ON(D.client_location_id = L.location_id) '.
                   'WHERE D.deliver_id = "'.$db->escapeSql($deliver_id).'" ';
            $deliver = $db->readSqlRecord($sql);
            if($deliver === 0) {
                $error .= 'Invalid Deliver ID['.$order_id.']. ';
            } else {
                $output['deliver'] = $deliver;
            }    
        }
                
        if($param['get'] === 'ALL' or $param['get'] === 'ITEMS') {
            $sql = 'SELECT D.stock_id,I.name,D.quantity,I.units,D.price,D.subtotal,D.tax,D.total,D.note  '.
                   'FROM '.$table_deliver_item.' AS D '.
                         'JOIN '.$table_stock.' AS S ON(D.stock_id = S.stock_id) '.
                         'JOIN '.$table_item.' AS I ON (S.item_id = I.item_id) '.
                   'WHERE deliver_id = "'.$db->escapeSql($deliver_id).'" ';
            $items = $db->readSqlArray($sql);
            if($items === 0) {
                $error .= 'No items for Deliver ID['.$deliver_id.']. ';
            } else {
                $output['items'] = $items;
            }
        }
        
        if($error !== '') return false; else return $output;
    }    

    public static function sendOrderConfirmation($db,$table_prefix,ContainerInterface $container,$order_id,$subject,$message,$param,&$error)
    {
        $error = '';

        $html = '';
        $error = '';
        $error_tmp = '';

        if(!isset($param['cc_admin'])) $param['cc_admin'] = false;
        if(!isset($param['email'])) $param['email'] = '';


        $system = $container['system'];
        $mail = $container['mail'];

        //setup email parameters
        $mail_footer = $system->getDefault('STORE_EMAIL_FOOTER','');
        $mail_param = [];
        $mail_param['format'] = 'html';
        if($param['cc_admin']) $mail_param['bcc'] = MAIL_FROM;
       
        $data = self::getOrderDetails($db,$table_prefix,$order_id,$error_tmp);
        if($data === false or $error_tmp !== '') {
            $error .= 'Could not get Order details: '.$error_tmp;
        } 

        if($error === '') {
            $mail_from = ''; //will use default MAIL_FROM
            if($param['email'] !== '') {
                $mail_to = $param['email'];   
            } else {
                $mail_to = $data['order']['supplier_email'];    
            }
            
            $mail_subject = SITE_NAME.' Order ID['.$order_id.'] ';

            if($subject !== '') $mail_subject .= ': '.$subject;
            
            $mail_body = '<h1>Hi there '.$data['order']['supplier_contact'].'</h1>';
            
            if($message !== '') $mail_body .= '<h3>'.$message.'</h3>';
            
            $mail_body .= '<h3>Order ID['.$order_id.'] details:</h3>'.
                          'Created on: <b>'.Date::formatDate($data['order']['date_create']).'</b><br/>'.
                          'Deliver on: <b>'.Date::formatDate($data['order']['date_receive']).'</b><br/>'.
                          'Deliver to: <b>'.$data['order']['location'].'</b><br/><br/>'.
                          'Subtotal: <b>'.$data['order']['subtotal'].'</b><br/>'.
                          'Tax: <b>'.$data['order']['tax'].'</b><br/>'.
                          'Total: <b>'.$data['order']['total'].'</b><br/>';

            //do not want bootstrap class default
            $html_param = ['class'=>''];
            $items = [];
            foreach($data['items'] as $item) {
                //IT.name,I.price,I.quantity,I.subtotal,I.tax,I.total
                $items[] = ['Name'=>$item['name'],
                            'Quantity'=>$item['quantity'],
                            'Price'=>$item['price'],
                            'Subtotal'=>$item['subtotal'],
                            'Tax'=>$item['tax'],
                            'Total'=>$item['total']] ;
            }
            $mail_body .= '<h3>Order items:</h3>'.Html::arrayDumpHtml($items,$html_param);

                            
            $mail_body .= '<br/><br/>'.$mail_footer;
            
            $mail->sendEmail($mail_from,$mail_to,$mail_subject,$mail_body,$error_tmp,$mail_param);
            if($error_tmp != '') { 
                $error .= 'Error sending Order details to email['. $mail_to.']:'.$error_tmp; 
            }
        }

        if($error === '') return true; else return false;
    }

    public static function sendDeliverConfirmation($db,$table_prefix,ContainerInterface $container,$deliver_id,$subject,$message,$param,&$error)
    {
        $error = '';
        $html = '';
        $error_tmp = '';

        if(!isset($param['cc_admin'])) $param['cc_admin'] = false;
        if(!isset($param['email'])) $param['email'] = '';


        $system = $container['system'];
        $mail = $container['mail'];

        //setup email parameters
        $mail_footer = $system->getDefault('STORE_EMAIL_FOOTER','');
        $mail_param = [];
        $mail_param['format'] = 'html';
        if($param['cc_admin']) $mail_param['bcc'] = MAIL_FROM;

        $detail_param = [];
       
        $data = self::getDeliverDetails($db,$table_prefix,$deliver_id,$detail_param,$error_tmp);
        if($data === false or $error_tmp !== '') {
            $error .= 'Could not get Delivery details: '.$error_tmp;
        } 

        if($error === '') {
            $mail_from = ''; //will use default MAIL_FROM
            if($param['email'] !== '') {
                $mail_to = $param['email'];   
            } else {
                $mail_to = $data['deliver']['email'];    
            }
            
            $mail_subject = SITE_NAME.' Delivery ID['.$deliver_id.'] ';

            if($subject !== '') $mail_subject .= ': '.$subject;
            
            $mail_body = '<h1>Hi there '.$data['deliver']['contact'].'</h1>';
            
            if($message !== '') $mail_body .= '<h3>'.$message.'</h3>';
            
            $mail_body .= '<h3>Deliver ID['.$deliver_id.'] details:</h3>'.
                          'Created on: <b>'.Date::formatDate($data['deliver']['date']).'</b><br/>'.
                          'Deliver to: <b>'.$data['deliver']['client'].'</b><br/><br/>'.
                          'Subtotal: <b>'.$data['deliver']['subtotal'].'</b><br/>'.
                          'Tax: <b>'.$data['deliver']['tax'].'</b><br/>'.
                          'Total: <b>'.$data['deliver']['total'].'</b><br/>';

            //do not want bootstrap class default
            $html_param = ['class'=>''];
            $items = [];
            foreach($data['items'] as $item) {
                //IT.name,I.price,I.quantity,I.subtotal,I.tax,I.total
                $items[] = ['Name'=>$item['name'],
                            'Quantity'=>$item['quantity'],
                            'Price'=>$item['price'],
                            'Subtotal'=>$item['subtotal'],
                            'Tax'=>$item['tax'],
                            'Total'=>$item['total']] ;
            }
            $mail_body .= '<h3>Delivery items:</h3>'.Html::arrayDumpHtml($items,$html_param);
                            
            $mail_body .= '<br/><br/>'.$mail_footer;
            
            $mail->sendEmail($mail_from,$mail_to,$mail_subject,$mail_body,$error_tmp,$mail_param);
            if($error_tmp != '') { 
                $error .= 'Error sending Order details to email['. $mail_to.']:'.$error_tmp; 
            }
        }

        if($error === '') return true; else return false;
    }

    //sends delivery docs for multiple deliveries
    public static function sendDeliverDocs($db,$table_prefix,ContainerInterface $container,$deliveries = [],$email,$subject,$message,$param,&$error)
    {
        $error = '';
        $html = '';
        $error_tmp = '';

        if(!isset($param['cc_admin'])) $param['cc_admin'] = false;
        if(!isset($param['zip'])) $param['zip'] = false;
        
        if($email === '') $error .= 'No email address specified. ';

        $system = $container['system'];
        $mail = $container['mail'];

        //setup email parameters
        $mail_footer = $system->getDefault('STORE_EMAIL_FOOTER','');
        $mail_param = [];
        $mail_param['format'] = 'html';
        if($param['cc_admin']) $mail_param['bcc'] = MAIL_FROM;

        if(count($deliveries) === 0) $error .= 'No deliveries specified for emailing documents. ';

        $deliver_param = ['get'=>'BASIC'];
        $deliver_details = [];
        foreach($deliveries as $deliver_id) {
            $deliver = self::getDeliverDetails($db,$table_prefix,$deliver_id,$deliver_param,$error_tmp);
            if($error_tmp !== '') {
                $error .= 'Could not get Delivery ID['.$deliver_id.'] details. ';
            } else {
                $deliver_details[$deliver_id] = $deliver;
            }
        }  

        if($error !== '') return false;

        //get all files related to invoice
        $attach = array();
        $attach_file = array();
        $attach_msg = '';

        //NB: only using for download, all files associated with invoice will be attached
        $docs = new Upload($db,$container,$table_prefix.'file');
        $docs->setup(['location'=>'DEL','interface'=>'download']);
        foreach($deliveries as $deliver_id) {
            $sql = 'SELECT file_id,file_name_orig FROM '.$table_prefix.'file '.
                   'WHERE location_id ="DEL'.$deliver_id.'" ORDER BY file_id ';
            $deliver_files = $db->readSqlList($sql);
            if($deliver_files != 0) {
                foreach($deliver_files as $file_id => $file_name) {
                    $attach_file['name'] = $file_name;
                    $attach_file['path'] = $docs->fileDownload($file_id,'FILE'); 
                    if(substr($attach_file['path'],0,5) !== 'Error' and file_exists($attach_file['path'])) {
                        $attach[] = $attach_file;
                        $attach_msg .= $deliver_details[$deliver_id]['deliver']['client'].': '.$file_name.'<br/>';
                    } else {
                        $error .= 'Error fetching files for delivery ID['.$deliver_id.']!'; 
                    }   
                }   
            }
        }

        //configure and send email
        if($error == '') {
            $mail_from = ''; //will use default MAIL_FROM
            $mail_to = $email;
            
            $mail_subject = SITE_NAME.' multiple delivery documents ';

            if($subject !== '') $mail_subject .= ': '.$subject;
            
            $mail_body = '<h1>Hi there.</h1>';
            
            if($message !== '') $mail_body .= '<h3>'.$message.'</h3>';
                        
            $mail_body .= 'All documents attached to this email: <br/>'.$attach_msg.'<br/>';
            
            $mail_body .= $mail_footer.'<br/>';
                        
            $mail_param['attach'] = $attach;

            $mail->sendEmail($mail_from,$mail_to,$mail_subject,$mail_body,$error_tmp,$mail_param);
            if($error_tmp != '') { 
                $error .= 'Error sending delivery douments to email['. $mail_to.']:'.$error_tmp; 
            }       
        }  
            
        if($error == '') return true; else return false;  
    }

    public static function createDeliverPdf($db,ContainerInterface $container,$deliver_id,&$doc_name,&$error)
    {
        $error = '';
        $pdf_dir = BASE_UPLOAD.UPLOAD_DOCS;
        //for custom settings like signature
        $upload_dir = BASE_UPLOAD.UPLOAD_DOCS;

        $table_items = TABLE_PREFIX.'deliver_item';
        $system = $container->system;

        $show_price = DELIVER_NOTE_PRICE;
        $show_contact = DELIVER_NOTE_CONTACT;

        $business_address = $system->getDefault('STORE_ADDRESS','No address setup');
        $business_contact = $system->getDefault('STORE_CONTACT','No contacts details');
        $footer = $system->getDefault('STORE_DELIVER_FOOTER','No footer setup');
        
        $deliver = self::get($db,TABLE_PREFIX,'deliver',$deliver_id);
        $deliver['discount'] = '0.00';  //NB: no discount supported for delivery notes

        $client = self::get($db,TABLE_PREFIX,'client',$deliver['client_id']);

        if($deliver['client_location_id'] == 0) {
            $location['address'] = $client['address'];
            $location['contact'] = $client['contact'];
            $location['cell'] = $client['cell'];
            $location['tel'] = $client['tel'];
        } else {
            $location = self::get($db,TABLE_PREFIX,'client_location',$deliver['client_location_id'],'location_id');
        }

        if($show_contact) {
            $contact = [];
            if($location['contact'] != '') $contact[] = $location['contact'];
            if($location['cell'] != '') $contact[] = $location['cell'];
            if($location['tel'] != '') $contact[] = $location['tel'];
            $contact_str = implode(',',$contact);
        } else {
            $contact_str = '';
        }

        
        $sql = 'SELECT data_id,stock_id,quantity,price,subtotal,tax,total '.
               'FROM '.$table_items.' WHERE deliver_id = "'.$db->escapeSql($deliver_id).'" '.
               'ORDER BY total DESC ';
        $items = $db->readSqlArray($sql); 

     
        //doc_no must be unique
        $doc_no = 'DN'.$deliver_id;
        $pdf_name = 'DN-'.$deliver_id.'.pdf';
        $doc_name = $pdf_name;
                
        $pdf = new DeliverPdf('Portrait','mm','A4');
        $pdf->AliasNbPages();
            
        $pdf->setupLayout(['db'=>$db]);

        //NB: override PDF defaults
        //NB: h1_title only relevant to header
        //$pdf->h1_title = array(33,33,33,'B',10,'',5,10,'L','YES',33,33,33,'B',12,20,180); //NO date
        //$pdf->bg_image = array('images/logo.jpeg',5,140,50,20,'YES'); //NB: YES flag turns off logo image display
        $pdf->page_margin = array(115,10,10,50);//top,left,right,bottom!!
        //$pdf->text = array(33,33,33,'',8);
        $pdf->SetMargins($pdf->page_margin[1],$pdf->page_margin[0],$pdf->page_margin[2]);

        //assign deliver HEADER data 
        //NB: is only used if no logo image set.
        $pdf->addTextElement('business_title',SITE_NAME);

        //check custom pdf setup for presence of a logo and use that if set.
        if(!isset($pdf->bg_image[5]) or $pdf->bg_image[5] !== 'YES') {
            $logo_path = $pdf_dir.'PDF_logo1.png';
            //width = 0 allows to scale proportionally
            $pdf->addLogo(['display'=>true,'path'=>$logo_path,'top'=>12,'left'=>12,'width'=>0,'height'=>14,'margin'=>16]);
        } 
        
        $pdf->addTextElement('doc_name','Delivery Note');
        $pdf->addTextElement('doc_date',$deliver['date']);
        $pdf->addTextElement('doc_no',$doc_no);
       
        $pdf->addTextBlock('business_address',$business_address);
        $pdf->addTextBlock('business_contact',$business_contact);

        $pdf->addTextBlock('client_detail',$client['name']."\r\n".$client['address']);
        $pdf->addTextBlock('client_deliver','Deliver to: '.$contact_str."\r\n".$location['address']);

        $pdf->addTextElement('acc_no',$client['account_code']);
        $pdf->addTextElement('acc_ref',$deliver['client_order_no']);
        $pdf->addTextElement('acc_tax_exempt','N');
        $pdf->addTextElement('acc_tax_ref','');
        $pdf->addTextElement('acc_sales_code','');

        //assign deliver FOOTER data 
        $pdf->addTextBlock('total_info',$footer); //can be anything but normaly banking data
        if($show_price) {
            $pdf->addTextElement('total_sub',number_format($deliver['subtotal'],2));
            $pdf->addTextElement('total_discount',number_format($deliver['discount'],2));
            $pdf->addTextElement('total_ex_tax',number_format(($deliver['subtotal'] - $deliver['discount']),2));
            $pdf->addTextElement('total_tax',number_format($deliver['tax'],2));
            $pdf->addTextElement('total',number_format($deliver['total'],2));
        }
        
        //NB footer must be set before this
        $pdf->AddPage();

        $row_h = 5;

        //$pdf->SetY(120);
        //$pdf->Ln($row_h);
        $frame_y = $pdf->getY();
        
        if(count($items) != 0) {
            
            $arr = [];
            $r = 0;
            $arr[0][$r] = 'Code';
            $arr[1][$r] = 'Description';
            $arr[2][$r] = 'Quantity';
            $arr[3][$r] = 'Price';
            $arr[4][$r] = 'Disc%';
            $arr[5][$r] = 'Tax';
            $arr[6][$r] = 'Total';
            
            foreach($items as $item) {

                $stock_item = Self::getStockItem($db,TABLE_PREFIX,$item['stock_id']); 

                if(!$show_price) {
                    $item['price'] = '';
                    $item['tax'] = '';
                    $item['total'] = '';
                }

                $r++;
                $arr[0][$r] = $stock_item['code'];
                $arr[1][$r] = $stock_item['name'];
                $arr[2][$r] = number_format($item['quantity'],0).$stock_item['units'];
                $arr[3][$r] = $item['price'];
                $arr[4][$r] = '';//$item['discount'];
                $arr[5][$r] = $item['tax'];
                $arr[6][$r] = $item['total'];
            }
                         
            $pdf->changeFont('TEXT');
            //item_id,item_code,item_desc,quantity,units,unit_price,discount,tax,total
            $col_width = array(20,75,20,20,20,20,25);
            $col_type = array('','','','DBL2','DBL2','DBL2','DBL2');
            $table_options['resize_cols'] = true;
            $table_options['format_header'] = ['line_width'=>0.1,'fill'=>'#FFFFFF','line_color'=>'#000000'];
            $table_options['format_text'] = ['line_width'=>0.1]; //['line_width'=>-1];
            $table_options['header_align'] = 'L';
            $pdf->arrayDrawTable($arr,$row_h,$col_width,$col_type,'L',$table_options);
        }
        
        if($deliver['notes'] != '') {
            $pdf->MultiCell(0,$row_h,$deliver['notes'],0,'L',0); 
            $pdf->Ln($row_h);
        }

        $pdf->changeFont('H2');
        $pdf->SetLineWidth(.1);
        $pdf->SetDrawColor(0,0,0);
        $pos_x = 10;
        $pos_y = $frame_y;
        $width = 190;
        $height = $pdf->GetY() - $frame_y;
        $pdf->Rect($pos_x,$pos_y,$width,$height,'D');
                
        //finally create pdf file
        $file_path = $pdf_dir.$pdf_name;
        $pdf->Output($file_path,'F'); 

        if($error === '') {
            //comment out and then can view pdf in storage/docs without uploading to amazon etc
            self::saveDeliverPdf($db,$container->s3,$deliver_id,$doc_name,$error);  
        }    
                
        if($error == '') return true; else return false ;
    }

    public static function saveDeliverPdf($db,$s3,$deliver_id,$doc_name,&$error) {
        $error_tmp = '';
        $error = '';
     
        $pdf_dir = BASE_UPLOAD.UPLOAD_DOCS; 
        
        $location_id = 'DEL'.$deliver_id;
        $file_id = Calc::getFileId($db);
        $file_name = $file_id.'.pdf';
        $pdf_path_old = $pdf_dir.$doc_name;
        $pdf_path_new = $pdf_dir.$file_name;
        //rename doc to new guaranteed non-clashing name
        if(!rename($pdf_path_old,$pdf_path_new)) {
            $error .= 'Could not rename delivery note pdf!<br/>'; 
        } 
                
        //create file records and upload to amazon if required
        if($error == '') {    
            $file = array();
            $file['file_id'] = $file_id; 
            $file['file_name'] = $file_name;
            $file['file_name_orig'] = $doc_name;
            $file['file_ext'] = 'pdf';
            $file['file_date'] = date('Y-m-d');
            $file['location_id'] = $location_id;
            $file['encrypted'] = false;
            $file['file_size'] = filesize($pdf_path_new); 
            
            if(STORAGE === 'amazon') {
                $s3->putFile($file['file_name'],$pdf_path_new,$error_tmp); 
                if($error_tmp !== '') $error.='Could NOT upload files to Amazon S3 storage!<br/>';
            } 
            
            if($error == '') {
                $db->insertRecord(TABLE_PREFIX.'file',$file,$error_tmp);
                if($error_tmp != '') $error .= 'ERROR creating delivery note file record: '.$error_tmp.'<br/>';
            }   
        }   
               
        
        if($error == '') return $deliver_id; else return false;
    }

    public static function stockReport($db,$scope,$store_id,$options = [],&$error)
    {
        $error = '';

        if(!isset($options['format'])) $options['format'] = 'HTML'; 
        $options['format'] = strtoupper($options['format']);

        $table_prefix = TABLE_PREFIX;

        $table_store = $table_prefix.'store';
        $table_stock = $table_prefix.'stock';
        $table_stock_store = $table_prefix.'stock_store';

        $table_deliver = $table_prefix.'deliver';
        $table_deliver_item = $table_prefix.'deliver_item';

        $table_transfer = $table_prefix.'transfer';
        $table_transfer_item = $table_prefix.'transfer_item';

        $table_item = $table_prefix.'item';
        $table_category = $table_prefix.'item_category';


        if($store_id != 'ALL') {
            $store = self::get($db,$table_prefix,'store',$store_id);
        } else {
            $store['name'] = 'All stores';
        }

        $base_doc_name = 'stock_report_summary_'.str_replace(' ','_',$store['name']);
        $page_title = 'Stock summary '.$store['name'];
        
        //list of stock items
        $sql = 'SELECT I.item_id,I.name,I.code,I.units,I.units_kg_convert,I.category_id, C.name AS category '.
               'FROM '.$table_item.' AS I JOIN '.$table_category.' AS C ON (I.category_id = C.category_id) '.
               'ORDER BY I.item_id ';
        $items = $db->readSqlArray($sql);

        //get current stock
        $sql = 'SELECT SS.stock_id,SS.quantity,SS.store_id,S.name AS store,'.
                      'ST.item_id,ST.supplier_id,ST.invoice_no,ST.quantity_in,ST.quantity_out '.
               'FROM '.$table_stock_store.' AS SS '.
                     'JOIN '.$table_store.' AS S ON(SS.store_id = S.store_id) '.
                     'JOIN '.$table_stock.' AS ST ON(SS.stock_id = ST.stock_id) '.
                'WHERE SS.quantity > 0 ';
        if($store_id != 'ALL') $sql .= 'AND SS.store_id = "'.$db->escapeSql($store_id).'" ';
        $stock = $db->readSqlArray($sql,false);

        //get deliveries not completed
        $sql = 'SELECT DI.stock_id,DI.quantity,D.store_id,S.name AS store, '.
                      'ST.item_id,ST.supplier_id,ST.invoice_no,ST.quantity_in,ST.quantity_out '.
               'FROM '.$table_deliver_item.' AS DI '.
                     'JOIN '.$table_deliver.' AS D ON(DI.deliver_id = D.deliver_id) '.
                     'JOIN '.$table_store.' AS S ON(D.store_id = S.store_id) '.
                     'JOIN '.$table_stock.' AS ST ON(DI.stock_id = ST.stock_id) '.
               'WHERE D.status = "NEW" ';
        if($store_id !== 'ALL') $sql .= 'AND D.store_id = "'.$db->escapeSql($store_id).'" ';
        $delivery = $db->readSqlArray($sql,false);

        //get transfer TO store stock not confirmed yet
        $sql = 'SELECT TI.stock_id,TI.quantity,T.to_store_id AS store_id,S.name AS store, '.
                      'ST.item_id,ST.supplier_id,ST.invoice_no,ST.quantity_in,ST.quantity_out '.
               'FROM '.$table_transfer_item.' AS TI '.
                     'JOIN '.$table_transfer.' AS T ON(TI.transfer_id = T.transfer_id) '.
                     'JOIN '.$table_store.' AS S ON(T.to_store_id = S.store_id) '.
                     'JOIN '.$table_stock.' AS ST ON(TI.stock_id = ST.stock_id) '.
               'WHERE T.status = "NEW" ';
        if($store_id !== 'ALL') $sql .= 'AND T.to_store_id = "'.$db->escapeSql($store_id).'" ';
        $transfer = $db->readSqlArray($sql,false);

        if($stock == 0 and $delivery == 0 and $transfer == 0) $error .= 'NO stock found in stores, awaiting delivery confirmation or transfer confirmation';
        
        if($error !== '') return false;

        $data = [];
        $stock_store = [];
        $stock_deliver = [];
        $stock_transfer = [];
        $stock_items = [];
        $r = 0;
        if($scope === 'SUMMARY') {
            $col_width = [30,50,10,30,30];
            $col_type = ['','','','DBL0','DBL0'];

            $data[0][$r] = 'Item code';
            $data[1][$r] = 'Item Name';
            $data[2][$r] = 'Units';
            $data[3][$r] = 'Store quantity';
            $data[4][$r] = 'Delivery quantity';
            $data[5][$r] = 'Transfer quantity'; 
            $r++; 

            if($stock != 0) {
                foreach($stock AS $item) {
                    //simple list of report items
                    if(!in_array($item['item_id'],$stock_items)) $stock_items[] = $item['item_id'];
                    //sum all existing stock
                    if(!isset($stock_store[$item['item_id']])) $stock_store[$item['item_id']] = 0;
                    $stock_store[$item['item_id']] += $item['quantity'];
                }
            }

            if($delivery != 0) {
                foreach($delivery AS $item) {
                    if(!in_array($item['item_id'],$stock_items)) $stock_items[] = $item['item_id'];
                    if(!isset($stock_deliver[$item['item_id']])) $stock_deliver[$item['item_id']] = 0;
                    $stock_deliver[$item['item_id']] += $item['quantity'];
                }
            }

            if($transfer != 0) {
                foreach($transfer AS $item) {
                    if(!in_array($item['item_id'],$stock_items)) $stock_items[] = $item['item_id'];
                    if(!isset($stock_transfer[$item['item_id']])) $stock_transfer[$item['item_id']] = 0;
                    $stock_transfer[$item['item_id']] += $item['quantity'];
                }
            }

            foreach($stock_items AS $item_id) {
                $data[0][$r] = $items[$item_id]['code'];
                $data[1][$r] = $items[$item_id]['name'];
                $data[2][$r] = $items[$item_id]['units'];
                $data[3][$r] = $stock_store[$item_id];
                $data[4][$r] = $stock_deliver[$item_id];
                $data[5][$r] = $stock_transfer[$item_id];
                $r++;
            }   
        }
        

        if($options['format'] === 'PDF') {
            $pdf_name=$base_doc_name.date('Y-m-d').'.pdf';
            $doc_name=$pdf_name;
            
            //$logo=array($img_dir.'logo_new.jpg',5,140,60,22); 
            
            $pdf=new Pdf('Portrait','mm','A4');
            $pdf->AliasNbPages();
              
            $pdf->setupLayout(['db'=>$db]);
            //change setup system setting if there is one
            $pdf->page_title = $page_title;
            //$pdf->bg_image=$logo; 
            //$pdf->SetLineWidth(0.1);
            
            //$pdf->footer_text='footer';
    
            //NB footer must be set before this
            $pdf->AddPage();
            $pdf->changeFont('TEXT');
            $pdf_options=array();
            $pdf_options['font_size']=6;
            $row_h = 6;

            $pdf->arrayDrawTable($data,$row_h,$col_width,$col_type,'C',$pdf_options);

            //$pdf->mysqlDrawTable($result,$row_h,$col_width,$col_type,'L',$options);
                        
            //$file_path=$pdf_dir.$pdf_name;
            //$pdf->Output($file_path,'F');  
    
            //finally create pdf file to browser
            $pdf->Output($pdf_name,'D');    
            exit;
            
        }
        if($options['format'] === 'CSV') {
            
            $csv_data = '';
            $doc_name = $base_doc_name.'_on_'.date('Y-m-d').'.csv';
            $csv_data = Csv::arrayDumpCsv($data); 
            
            Doc::outputDoc($csv_data,$doc_name,'DOWNLOAD','csv');
            exit;
            
        }
        
        if($options['format']==='HTML') {
            $html = '<h1>'.$page_title.'</h1>';  
            $html_options = [];
            $html_options['col_type'] = $col_type; 
            $html.=Html::arrayDumpHtml2($data,$html_options); 
          
            $html.='<br/>';
                  
            return $html;
        }

    }

    public static function stockReportAllStores($db,$scope,$options = [],&$error)
    {
        $error = '';

        if(!isset($options['format'])) $options['format'] = 'HTML'; 
        $options['format'] = strtoupper($options['format']);

        $table_prefix = TABLE_PREFIX;

        $table_store = $table_prefix.'store';
        $table_stock = $table_prefix.'stock';
        $table_stock_store = $table_prefix.'stock_store';

        $table_deliver = $table_prefix.'deliver';
        $table_deliver_item = $table_prefix.'deliver_item';

        $table_transfer = $table_prefix.'transfer';
        $table_transfer_item = $table_prefix.'transfer_item';

        $table_item = $table_prefix.'item';
        $table_category = $table_prefix.'item_category';


        if($store_id != 'ALL') {
            $store = self::get($db,$table_prefix,'store',$store_id);
        } else {
            $store['name'] = 'All stores';
        }

        $base_doc_name = 'stock_report_summary_'.str_replace(' ','_',$store['name']);
        $page_title = 'Stock summary '.$store['name'];
        
        //list of stock items
        $sql = 'SELECT I.item_id,I.name,I.code,I.units,I.units_kg_convert,I.category_id, C.name AS category '.
               'FROM '.$table_item.' AS I JOIN '.$table_category.' AS C ON (I.category_id = C.category_id) '.
               'ORDER BY I.item_id ';
        $items = $db->readSqlArray($sql);

        //Simple list of stores
        $sql = 'SELECT store_id,name '.
               'FROM '.$table_store.' ORDER BY name';
        $stores = $db->readSqlList($sql);


        //get current stock
        $sql = 'SELECT SS.stock_id,SS.quantity,SS.store_id,S.name AS store,'.
                      'ST.item_id,ST.supplier_id,ST.invoice_no,ST.quantity_in,ST.quantity_out '.
               'FROM '.$table_stock_store.' AS SS '.
                     'JOIN '.$table_store.' AS S ON(SS.store_id = S.store_id) '.
                     'JOIN '.$table_stock.' AS ST ON(SS.stock_id = ST.stock_id) '.
                'WHERE SS.quantity > 0 '.
                'ORDER BY ST.item_id,S.name ';
        //if($store_id != 'ALL') $sql .= 'AND SS.store_id = "'.$db->escapeSql($store_id).'" ';
        $stock = $db->readSqlArray($sql,false);

        //get deliveries not completed
        $sql = 'SELECT DI.stock_id,DI.quantity,D.store_id,S.name AS store, '.
                      'ST.item_id,ST.supplier_id,ST.invoice_no,ST.quantity_in,ST.quantity_out '.
               'FROM '.$table_deliver_item.' AS DI '.
                     'JOIN '.$table_deliver.' AS D ON(DI.deliver_id = D.deliver_id) '.
                     'JOIN '.$table_store.' AS S ON(D.store_id = S.store_id) '.
                     'JOIN '.$table_stock.' AS ST ON(DI.stock_id = ST.stock_id) '.
               'WHERE D.status = "NEW" ';
        //if($store_id !== 'ALL') $sql .= 'AND D.store_id = "'.$db->escapeSql($store_id).'" ';
        $delivery = $db->readSqlArray($sql,false);

        //get transfer TO store stock not confirmed yet
        $sql = 'SELECT TI.stock_id,TI.quantity,T.to_store_id AS store_id,S.name AS store, '.
                      'ST.item_id,ST.supplier_id,ST.invoice_no,ST.quantity_in,ST.quantity_out '.
               'FROM '.$table_transfer_item.' AS TI '.
                     'JOIN '.$table_transfer.' AS T ON(TI.transfer_id = T.transfer_id) '.
                     'JOIN '.$table_store.' AS S ON(T.to_store_id = S.store_id) '.
                     'JOIN '.$table_stock.' AS ST ON(TI.stock_id = ST.stock_id) '.
               'WHERE T.status = "NEW" ';
        //if($store_id !== 'ALL') $sql .= 'AND T.to_store_id = "'.$db->escapeSql($store_id).'" ';
        $transfer = $db->readSqlArray($sql,false);

        if($stock == 0 and $delivery == 0 and $transfer == 0) $error .= 'NO stock found in stores, awaiting delivery confirmation or transfer confirmation';
        
        if($error !== '') return false;

        $data = [];
        $stock_store = [];
        $stock_deliver = [];
        $stock_transfer = [];
        $stock_items = [];
        $r = 0;
        if($scope === 'SUMMARY') {
            $col_width = [30,30,50,10,30,30];
            $col_type = ['','','','','DBL0','DBL0'];

            $data[0][$r] = 'Item code';
            $data[1][$r] = 'Item Name';
            $data[2][$r] = 'Units';
            $data[3][$r] = 'Store';
            $data[4][$r] = 'Quantity';
            $data[5][$r] = 'Delivery quantity'; 
            $data[6][$r] = 'Transfer quantity'; 
            $r++; 

            if($stock != 0) {
                foreach($stock AS $item) {
                    //simple list of report items
                    $key = $item['store_id'].':'.$item['item_id'];
                    if(!in_array($key,$stock_items)) $stock_items[$key] = ['store_id'=>$item['store_id'],'item_id'=>$item['item_id']];
                    //sum all existing stock
                    if(!isset($stock_store[$key])) $stock_store[$key] = 0;
                    $stock_store[$key] += $item['quantity'];
                }
            }

            if($delivery != 0) {
                foreach($delivery AS $item) {
                    $key = $item['store_id'].':'.$item['item_id'];
                    if(!in_array($key,$stock_items)) $stock_items[$key] = ['store_id'=>$item['store_id'],'item_id'=>$item['item_id']];
                    if(!isset($stock_deliver[$key])) $stock_deliver[$key] = 0;
                    $stock_deliver[$key] += $item['quantity'];
                }
            }

            if($transfer != 0) {
                foreach($transfer AS $item) {
                    $key = $item['store_id'].':'.$item['item_id'];
                    if(!in_array($key,$stock_items)) $stock_items[$key] = ['store_id'=>$item['store_id'],'item_id'=>$item['item_id']];
                    if(!isset($stock_transfer[$key])) $stock_transfer[$key] = 0;
                    $stock_transfer[$key] += $item['quantity'];
                }
            }

            $item_id_prev = '';
            foreach($stock_items AS $key=>$item) {
                if($item['item_id'] !== $item_id_prev) {
                    if($item_id_prev !== '' and $total_no > 1) {
                        $data[0][$r] = '';
                        $data[1][$r] = '';
                        $data[2][$r] = '';
                        $data[3][$r] = 'Total';
                        $data[4][$r] = $total_store;
                        $data[5][$r] = $total_deliver;
                        $data[6][$r] = $total_transfer;
                        $r++; 
                    }

                    $total_store = 0;
                    $total_deliver = 0;
                    $total_transfer = 0;
                    $total_no = 0; 
                
                    $code = $items[$item['item_id']]['code'];
                    $name = $items[$item['item_id']]['name'];
                    $units = $items[$item['item_id']]['units'];
                } else {
                    $code = '...';
                    $name = '...';
                    $units = '...';
                }

                $total_store += $stock_store[$key];
                $total_deliver += $stock_deliver[$key];
                $total_transfer += $stock_transfer[$key];
                $total_no++;

                $data[0][$r] = $code;
                $data[1][$r] = $name;
                $data[2][$r] = $units;
                $data[3][$r] = $stores[$item['store_id']];
                $data[4][$r] = $stock_store[$key];
                $data[5][$r] = $stock_deliver[$key];
                $data[6][$r] = $stock_transfer[$key];
                $r++;

                $item_id_prev = $item['item_id'];
            }

            //final totals if necessary
            if($total_no > 1) {
                $data[0][$r] = '';
                $data[1][$r] = '';
                $data[2][$r] = '';
                $data[3][$r] = 'Total';
                $data[4][$r] = $total_store;
                $data[5][$r] = $total_deliver;
                $data[6][$r] = $total_transfer;
            }   
        }
        

        if($options['format'] === 'PDF') {
            $pdf_name=$base_doc_name.date('Y-m-d').'.pdf';
            $doc_name=$pdf_name;
            
            //$logo=array($img_dir.'logo_new.jpg',5,140,60,22); 
            
            $pdf=new Pdf('Portrait','mm','A4');
            $pdf->AliasNbPages();
              
            $pdf->setupLayout(['db'=>$db]);
            //change setup system setting if there is one
            $pdf->page_title = $page_title;
            //$pdf->bg_image=$logo; 
            //$pdf->SetLineWidth(0.1);
            
            //$pdf->footer_text='footer';
    
            //NB footer must be set before this
            $pdf->AddPage();
            $pdf->changeFont('TEXT');
            $pdf_options=array();
            $pdf_options['font_size']=6;
            $row_h = 6;

            $pdf->arrayDrawTable($data,$row_h,$col_width,$col_type,'C',$pdf_options);

            //$pdf->mysqlDrawTable($result,$row_h,$col_width,$col_type,'L',$options);
                        
            //$file_path=$pdf_dir.$pdf_name;
            //$pdf->Output($file_path,'F');  
    
            //finally create pdf file to browser
            $pdf->Output($pdf_name,'D');    
            exit;
            
        }
        if($options['format'] === 'CSV') {
            
            $csv_data = '';
            $doc_name = $base_doc_name.'_on_'.date('Y-m-d').'.csv';
            $csv_data = Csv::arrayDumpCsv($data); 
            
            Doc::outputDoc($csv_data,$doc_name,'DOWNLOAD','csv');
            exit;
            
        }
        
        if($options['format']==='HTML') {
            $html = '<h1>'.$page_title.'</h1>';  
            $html_options = [];
            $html_options['col_type'] = $col_type; 
            $html.=Html::arrayDumpHtml2($data,$html_options); 
          
            $html.='<br/>';
                  
            return $html;
        }

    }


}
?>
