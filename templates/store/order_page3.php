<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;

$list_param['class'] = 'form-control edit_input';
$text_param['class'] = 'form-control edit_input';
$textarea_param['class'] = 'form-control edit_input';

$sql = 'SELECT item_id,CONCAT(name," - ",units) FROM '.TABLE_PREFIX.'item '.
       'WHERE status <> "HIDE" '.
       'ORDER BY name';
$items = $this->db->readSqlList($sql);

$confirm_action = ['NONE'=>'No additional action','EMAIL'=>'Email order to supplier'];

$button_text = 'CONFIRM order details';

if($form['confirm_action'] === 'EMAIL') $button_text .= ' & EMAIL to supplier';

if($form['supplier_email'] == '') $form['supplier_email'] = $data['supplier']['email'];
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

  <div class="row">
    <div class="col-sm-3">Order items</div>
    <div class="col-sm-9">
      <?php 
      echo '<table id="item_table" class="table  table-striped table-bordered table-hover table-condensed">'.
           '<tr><th>Item</th><th>Amount</th><th>Price</th><th>Subtotal</th><th>Tax</th><th>Total</th></tr>';
      $i = 0;
      foreach($data['items'] as $item) {
          echo '<tr>'.
               '<td>'.$items[$item['id']].'</td>'.
               '<td style="text-align:right">'.$item['amount'].'</td>'.
               '<td style="text-align:right">'.number_format($item['price'],2).'</td>'.
               '<td style="text-align:right">'.number_format($item['subtotal'],2).'</td>'.
               '<td style="text-align:right">'.number_format($item['tax'],2).'</td>'.
               '<td style="text-align:right">'.number_format($item['total'],2).'</td>'.
               '</tr>';
      }
      echo '</table>';
      
      ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Number of stock items:</div>
    <div class="col-sm-3">
    <?php 
    echo '<strong>'.$data['item_count'].'</strong>';
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Total order cost:</div>
    <div class="col-sm-3">
    <?php 
    echo '<strong>'.CURRENCY_ID.' '.number_format($data['item_total'],2).'</strong>';
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Notes for supplier:</div>
    <div class="col-sm-3">
    <?php echo Form::textAreaInput('note',$form['note'],50,5,$textarea_param); ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Supplier email:</div>
    <div class="col-sm-3">
    <?php echo Form::textInput('supplier_email',$form['supplier_email'],$text_param); ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Confirm action:</div>
    <div class="col-sm-3">
    <?php echo Form::arrayList($confirm_action,'confirm_action',$form['confirm_action'],true,$list_param); ?>
    </div>
  </div>
   


  <div class="row">
    <div class="col-sm-6"><input type="submit" name="Submit" value="<?php echo $button_text ?>" class="btn btn-primary"></div>
  </div>  

</div>
