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

    public static function getStockItem($db,$table_prefix,$stock_id) 
    {
        $table_stock = $table_prefix.'stock';
        $table_supplier = $table_prefix.'supplier';
        $table_item = $table_prefix.'item';
        $table_category = $table_prefix.'item_category';
        
        $sql = 'SELECT S.supplier_id,S.invoice_no,S.quantity_in,S.quantity_in,'.
                      'SU.name as supplier,I.name,I.units,I.units_kg_convert,C.name AS category,C.access,C.access_level '.
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
               'WHERE stock_id = "'.$db->escapeSql($stock_id).'" AND store_id = "'.$db->escapeSql($store_id).'" ';
        $record = $db->readSqlRecord($sql);
                     
        return $record;
    }

    //check if can update an order
    public static function verifyOrderItemUpdate($db,$table_prefix,$type,$item_id,$update = [],&$error)
    {
        $error = '';
        
        $item = self::get($db,$table_prefix,'order_item',$item_id,'data_id');
        if($item == 0) {
            $error .= 'Order item ID['.$item_id.'] invalid';
        } else {
            $order = self::get($db,$table_prefix,'order',$item['order_id']);
            if($order['status'] !== 'NEW') {
                $error .= 'Cannot update item as order status['.$order['status'].'] is not NEW ';
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
    public static function receiveItemUpdate($db,$table_prefix,$update_type,$data_id,$update = [],&$error)
    {
        $error = '';
        $error_tmp = '';
        
        $item = self::get($db,$table_prefix,'receive_item',$data_id,'data_id');
        if($item == 0) {
            $error .= 'Reception item ID['.$item_id.'] invalid';
        } else {
            $receive = self::get($db,$table_prefix,'receive',$item['receive_id']);
            if($receive['status'] !== 'NEW') {
                $error .= 'Cannot update item as reception status['.$receive['status'].'] is not NEW ';
            }
        }

        //reverse original quantity
        if($error === '' and ($update_type === 'UPDATE' or $update_type === 'DELETE')) {
            $quantity = $item['quantity'] * -1;
            self::updateStockReceived($db,$table_prefix,$item['item_id'],$receive['supplier_id'],$receive['invoice_no'],$quantity,$item['store_id'],$error_tmp);
            if($error_tmp !== '') {
                $error .= 'Could not reverse original quantity received. ';
                if($this->debug) $error .= $error_tmp;
            }
        }    

        //update new value if not a delete
        if($error === '' and ($update_type === 'UPDATE' or $update_type === 'INSERT')) {
            self::updateStockReceived($db,$table_prefix,$update['item_id'],$receive['supplier_id'],$receive['invoice_no'],$update['quantity'],$update['store_id'],$error_tmp);
            if($error_tmp !== '') {
                $error .= 'Could not update changed item stock. ';
                if($this->debug) $error .= $error_tmp;
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

    //NB: make quantity negative to REVERSE item delivery
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

        
        //first update-insert primary stock table
        $sql = 'SELECT stock_id,item_id,quantity_in,quantity_out  '.
               'FROM '.$table_stock.'  '.
               'WHERE stock_id = "'.$db->escapeSql($stock_id).'" ';
        $stock = $db->readSqlRecord($sql);
        //update existing stock record
        if($stock == 0) {
            $error .= 'Zero stock exists, so cannote deliver['.$quantity.'] ' ;
        } else {    
            $stock_id = $stock['stock_id'];

            if(!$reversal and $stock['quantity_in'] < abs($quantity)) {
                $error .= 'Stock quantity available['.$stock['quantity_in'].'] is less than delivery quantity['.$quantity.'] ' ;
            } else {
                $sql = 'UPDATE '.$table_stock.' SET quantity_in = quantity_in - '.$quantity.' '.
                       'WHERE stock_id = "'.$stock_id.'" ';
                $db->executeSql($sql,$error_tmp);
                if($error_tmp !== '') $error .= 'Could not update existing stock delivered levels.';
            }
        }    
        
        //now update stock store linkage
        if($error === '') {
            $sql = 'SELECT data_id,stock_id,quantity '.
                   'FROM '.$table_stock_store.' '.
                   'WHERE store_id = "'.$db->escapeSql($store_id).'" AND stock_id = "'.$db->escapeSql($stock_id).'" ';
            $stock_store = $db->readSqlRecord($sql);

            if($stock_store == 0) {
                $error .= 'Zero store stock exists, so cannote deliver['.$quantity.'] from store' ;
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
    public static function transferItemUpdate($db,$table_prefix,$update_type,$item_id,$update = [],&$error)
    {
        $error = '';
        
        $item = self::get($db,$table_prefix,'transfer_item',$item_id,'data_id');
        if($item == 0) {
            $error .= 'Transfer item ID['.$item_id.'] invalid';
        } else {
            $transfer = self::get($db,$table_prefix,'transfer',$item['transfer_id']);
            if($transfer['status'] !== 'NEW') {
                $error .= 'Cannot update item as transfer status['.$transfer['status'].'] is not NEW ';
            }
        }

        //reverse original transfer
        if($error === '' and ($update_type === 'UPDATE' or $update_type === 'DELETE')) {
            $from_store_id = $transfer['to_store_id'];
            $to_store_id = $transfer['from_store_id'];
            self::updateStockTransfered($db,$table_prefix,$from_store_id,$to_store_id,$item['stock_id'],$item['quantity'],$error_tmp);
            if($error_tmp !== '') {
                $error .= 'Could not reverse original quantity transfered. ';
                if($this->debug) $error .= $error_tmp;
            }
        }    

        //update new value if not a delete
        if($error === '' and ($update_type === 'UPDATE' or $update_type === 'INSERT')) {
            self::updateStockTransfered($db,$table_prefix,$transfer['from_store_id'],$transfer['to_store_id'],$update['stock_id'],$update['quantity'],$error_tmp);
            if($error_tmp !== '') {
                $error .= 'Could not update changed item stock. ';
                if($this->debug) $error .= $error_tmp;
            }  
        }
   

        if($error === '') return true; else return false;

        if($error === '') return true; else return false;
    }

    //update order totals after item update
    public static function updateTransfer($db,$table_prefix,$transfer_id,&$error)
    {
        $error = '';
        $output = [];
        
        $table_transfer = $table_prefix.'transfer';
        $table_transfer_item = $table_prefix.'transfer_item';
        
        $sql = 'SELECT COUNT(*) AS item_no,SUM(total_kg) AS total_kg '.
               'FROM '.$table_transfer_item.'  '.
               'WHERE transfer_id = "'.$db->escapeSql($transfer_id).'" ';
        $totals = $db->readSqlRecord($sql);
                
        $sql = 'UPDATE '.$table_transfer.'  '.
               'SET item_no = "'.$totals['item_no'].'", '.
                   'total_kg =  "'.$totals['total_kg'].'" '.
               'WHERE transfer_id = "'.$db->escapeSql($transfer_id).'" ';
        $db->executeSql($sql,$error);
        
        if($error !== '') return false; else return true;
    }

    //validates quantities and updates store stock
    public static function updateStockTransfered($db,$table_prefix,$from_store_id,$to_store_id,$stock_id,$quantity,&$error)
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
        if($stock_from['quantity'] - $quantity < 0 ) {
            $error = 'Insufficient stock['.$stock['summary'].'] in FROM store['.$stock_from['quantity'].'] for transfer amount['.$quantity.'] ';
            return false;
        }
        
        //update FROM store stock
        $sql = 'UPDATE '.$table_stock_store.' SET quantity = quantity - '.$quantity.' '.
               'WHERE data_id = "'.$stock_from['data_id'].'" ';
        $db->executeSql($sql,$error_tmp);
        if($error_tmp !== '') $error .= 'Could not update FROM store stock levels.';
        
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
        $mail_footer = $system->getDefault('SHOP_EMAIL_FOOTER','');
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
}
?>
