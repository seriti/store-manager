<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;

$list_param['class'] = 'form-control edit_input';

$totals = $data['totals'];

if(!isset($data['item_count'])) $data['item_count'] = 0;
?>

<div id="order_div">
  
  <div class="row">
    <div class="col-sm-3">Supplier:</div>
    <div class="col-sm-3"><strong><?php echo $data['supplier']['name']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Deliver to:</div>
    <div class="col-sm-3"><strong><?php echo $data['location']['name']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Deliver on:</div>
    <div class="col-sm-3"><strong><?php echo $form['date_receive']; ?></strong></div>
  </div>


  <br/>
  <div class="row">
    <div class="col-sm-12">
    <h1>Order items: <a href="javascript:add_item()">[add]</a></h1>
    <?php 
    $item_param = [];
    $item_param['class'] = 'form-control';

    $sql_item = 'SELECT I.item_id,CONCAT(C.name,": ",I.name," - ",I.units) '.
                'FROM '.TABLE_PREFIX.'item AS I JOIN '.TABLE_PREFIX.'item_category AS C ON(I.category_id = C.category_id) '.
                'WHERE I.status <> "HIDE" '.
                'ORDER BY C.sort, I.name';

    echo '<table id="item_table" class="table  table-striped table-bordered table-hover table-condensed">'.
         '<tr><th>Item</th><th>Quantity</th><th>Price</th><th>Delete</th></tr>';
    $i = 0;
    foreach($data['items'] as $item) {
        $i++;
        $name_item = 'item_'.$i;
        $name_amount = 'amount_'.$i;
        $name_price = 'price_'.$i;
        
        echo '<tr>'.
             '<td>'.Form::sqlList($sql_item,$this->db,$name_item,$item['id'],$item_param).'</td>'.
             '<td>'.Form::textInput($name_amount,$item['amount'],$item_param).'</td>'.
             '<td>'.Form::textInput($name_price,$item['price'],$item_param).'</td>'.
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
                       
echo $js;        

?>
var html_amount = '<input type="text" id="amount_id" name="amount_id" class="form-control">';
var html_price = '<input type="text" id="price_id" name="price_id" class="form-control">';

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
    var html_item_delete = '<a href="#" onclick="delete_row(this)"><img src="/images/cross.png"></a>';
    
    var item_name = ''; 
    var amount_name = '';  
    
    item_count++;
    amount_name = 'amount_'+item_count;
    item_name = 'item_'+item_count;
    price_name = 'price_'+item_count;
    
    input_item_count.value = item_count;

    
    html_item_select = html_item_select.replace(/item_id/g,item_name);
    html_item_amount = html_item_amount.replace(/amount_id/g,amount_name); 
    html_item_price = html_item_price.replace(/price_id/g,price_name); 
       
    
    var table = document.getElementById('item_table');
    var row = table.insertRow();
        
    row.innerHTML = '<td>'+html_item_select+'</td><td>'+html_item_amount+'</td><td>'+html_item_price+'</td><td>'+html_item_delete+'</td>';
    
}

function delete_row(link) {
    var row = link.parentNode.parentNode;
    row.parentNode.removeChild(row);
};


</script>  