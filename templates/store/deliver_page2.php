<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;

$list_param['class'] = 'form-control edit_input';

$item_param['class'] = 'form-control input-sm';
$item_param['onchange'] = 'javascript:item_select()';
$item_param['xtra'] = ['0'=>'Select delivery item'];

$totals = $data['totals'];

if(!isset($data['item_count'])) $data['item_count'] = 0;
?>


<div id="order_div">
  
  <div class="row">
    <div class="col-sm-3">Client:</div>
    <div class="col-sm-3"><strong><?php echo $data['client']['name']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Client location:</div>
    <div class="col-sm-3"><strong><?php echo $data['location']['name'].'</strong><br/>'.nl2br($data['location']['address']); ?></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Delivery from store:</div>
    <div class="col-sm-3"><strong><?php echo $data['store']['name']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Delivery date:</div>
    <div class="col-sm-3"><strong><?php echo $form['date_deliver']; ?></strong></div>
  </div>

  <?php  
  //not implemented yet
  if($this->data['sale'] !== 0) {
      echo ' <div class="row">
              <div class="col-sm-3">Sales order ID['.$form['sale_id'].'] '.$data['sale']['date_create'].':</div>
              <div class="col-sm-3"><strong>Total: '.number_format($data['sale']['total'],2).' for '.$data['item_count'].' items</strong></div>
            </div>';
  }
  ?>

  <br/>
  <div class="row">
    <div class="col-sm-12">
    <h1>Deliver stock items: <a href="javascript:add_item()">[add]</a></h1>
    <?php 
    //NB: item_id refers to SS.data_id and NOT original S.item_id
    //NB: could use SS.stock_id as UNIQUE for a given store
    $sql_item = 'SELECT SS.data_id,CONCAT(C.name,": ",I.name,"(",SU.name," - ",S.invoice_no,") ",SS.quantity,I.units," available") '.
                'FROM '.TABLE_PREFIX.'stock_store AS SS JOIN '.TABLE_PREFIX.'stock AS S ON(SS.stock_id = S.stock_id) '.
                      'JOIN '.TABLE_PREFIX.'supplier AS SU ON(S.supplier_id = SU.supplier_id) '.
                      'JOIN '.TABLE_PREFIX.'item AS I ON(S.item_id = I.item_id) '.
                      'JOIN '.TABLE_PREFIX.'item_category AS C ON(I.category_id = C.category_id) '.
                'WHERE SS.store_id = "'.$this->db->escapeSql($form['store_id']).'" AND SS.quantity > 0 AND I.status <> "HIDE" '.
                'ORDER BY C.sort, I.name';


    echo '<table id="item_table" class="table  table-striped table-bordered table-hover table-condensed">'.
         '<tr><th>Item</th><th>Quantity</th><th>Price</th><th>Subtotal</th><th>Tax</th><th>Total</th><th>Delete</th></tr>';
    $i = 0;
    foreach($data['items'] as $item) {
        $i++;
        $name_item = 'item_'.$i;
        $name_amount = 'amount_'.$i;
        $name_price = 'price_'.$i;
        $name_subtotal = 'subtotal_'.$i;
        $name_tax = 'tax_'.$i;
        $name_total = 'total_'.$i;
        
        echo '<tr>'.
             '<td>'.Form::sqlList($sql_item,$this->db,$name_item,$item['id'],$item_param).'</td>'.
             '<td>'.Form::textInput($name_amount,$item['amount'],$item_param).'</td>'.
             '<td>'.Form::textInput($name_price,$item['price'],$item_param).'</td>'.
             '<td>'.Form::textInput($name_subtotal,$item['subtotal'],$item_param).'</td>'.
             '<td>'.Form::textInput($name_tax,$item['tax'],$item_param).'</td>'.
             '<td>'.Form::textInput($name_total,$item['total'],$item_param).'</td>'.
             '<td><a href="#" onclick="delete_row(this)"><img src="/images/cross.png"></a></td>'.
             '</tr>';
    }
    echo '</table>';
    
    //NB: this will contain max item count, with possible missing rows if some deleted
    echo '<INPUT TYPE="hidden" NAME="item_count" ID="item_count" VALUE="'.$data['item_count'].'">';

    ?>
    </div>
  </div>
  
  <div class="row">
    <div class="col-sm-6"><input type="submit" name="Submit" value="Proceed" class="btn btn-primary"></div>
  </div>  

</div>
<script language="javascript">

<?php

$js = '';

$item_id = 0;
$html_item = Form::sqlList($sql_item,$this->db,'item_id',$item_id,$item_param);

$js .= 'var item_count = '.$data['item_count'].';';

$js .= 'var html_item = \''.$html_item.'\';';

$js .= 'var tax_rate = '.TAX_RATE.';';
                       
echo $js;        

?>
var html_amount = '<input type="text" id="amount_id" name="amount_id" class="form-control" onchange="javascript:calc_total()">';
var html_price = '<input type="text" id="price_id" name="price_id" class="form-control" onchange="javascript:calc_total()">';
var html_subtotal = '<input type="text" id="subtotal_id" name="subtotal_id" class="form-control">';
var html_tax = '<input type="text" id="tax_id" name="tax_id" class="form-control">';
var html_total = '<input type="text" id="total_id" name="total_id" class="form-control">';

$(document).ready(function() {
    if(item_count == 0) {
        add_item();    
    }
    
    
});

function add_item() {
    //alert(debit_credit); 
    
    var input_item_count = document.getElementById('item_count');
    
    var html_item_select = html_item;
    var html_item_amount = html_amount;
    var html_item_price = html_price;
    var html_item_subtotal = html_subtotal;
    var html_item_tax = html_tax;
    var html_item_total = html_total;
    var html_item_delete = '<a href="#" onclick="delete_row(this)"><img src="/images/cross.png"></a>';
    
    var item_name = ''; 
    var amount_name = '';  
    
    item_count++;
    amount_name = 'amount_'+item_count;
    item_name = 'item_'+item_count;
    price_name = 'price_'+item_count;
    subtotal_name = 'subtotal_'+item_count;
    tax_name = 'tax_'+item_count;
    total_name = 'total_'+item_count;
    
    input_item_count.value = item_count;

    
    html_item_select = html_item_select.replace(/item_id/g,item_name);
    html_item_amount = html_item_amount.replace(/amount_id/g,amount_name); 
    html_item_price = html_item_price.replace(/price_id/g,price_name); 
    html_item_subtotal = html_item_subtotal.replace(/subtotal_id/g,subtotal_name); 
    html_item_tax = html_item_tax.replace(/tax_id/g,tax_name); 
    html_item_total = html_item_total.replace(/total_id/g,total_name); 
       
    
    var table = document.getElementById('item_table');
    var row = table.insertRow();
        
    row.innerHTML = '<td>'+html_item_select+'</td><td>'+html_item_amount+'</td><td>'+html_item_price+'</td>'+
                    '<td>'+html_item_subtotal+'</td><td>'+html_item_tax+'</td><td>'+html_item_total+'</td><td>'+html_item_delete+'</td>';
    
}

function delete_row(link) {
    var row = link.parentNode.parentNode;
    row.parentNode.removeChild(row);
};

function item_select() {
    var target = event.target || event.srcElement;
    var item_name = target.id;

    var price_name = item_name.replace('item','price');

    var item = document.getElementById(item_name);
    var item_id = item.value;

    //alert('selected:'+item.options[item.selectedIndex].text+' item element id:'+item_name+ ' price element name:'+price_name);

    var param = 'source=store&data_id='+item_id;
    xhr('ajax?mode=stock_item',param,show_item_price,price_name);
}

function show_item_price(str,price_id) {
    //alert(str+':'+price_id);

    if(str.substring(0,5) === 'ERROR') {
        alert(str);
    } else {  
        var item = $.parseJSON(str);

        var price = document.getElementById(price_id);
        price.value = item.price_sell;
        //force recalc for price change
        price.dispatchEvent(new Event('change'));
    }    
    
}

function calc_total() {
    var target = event.target || event.srcElement;
    
    var input_name = target.id;
    var row = input_name.split('_'). pop();

    var amount_name = 'amount_'+row;
    var price_name = 'price_'+row;
    var subtotal_name = 'subtotal_'+row;
    var tax_name = 'tax_'+row;
    var total_name = 'total_'+row;

    var amount = document.getElementById(amount_name).value;
    var price = document.getElementById(price_name).value;
    
    var subtotal  = amount * price;
    var tax = subtotal * tax_rate;
    var total = subtotal + tax;

    document.getElementById(subtotal_name).value = subtotal.toFixed(2);
    document.getElementById(tax_name).value = tax.toFixed(2);
    document.getElementById(total_name).value = total.toFixed(2);
}
</script>  