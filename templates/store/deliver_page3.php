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

$confirm_action = ['NONE'=>'No additional action','EMAIL'=>'Email details to client'];

$button_text = 'CONFIRM delivery details';

if($form['confirm_action'] === 'EMAIL') $button_text .= ' & EMAIL to client';

if($form['confirm_email'] == '') $form['confirm_email'] = $data['supplier']['email'];

?>

<div id="order_div">
  
  <div class="row">
    <div class="col-sm-3">Client:</div>
    <div class="col-sm-3"><strong><?php echo $data['client']['name']; ?></strong></div>
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

  <div class="row">
    <div class="col-sm-3">Reception items</div>
    <div class="col-sm-9">
      <?php 
      
      echo '<table id="item_table" class="table  table-striped table-bordered table-hover table-condensed">'.
           '<tr><th>Item</th><th>Amount</th><th>Price</th><th>Subtotal</th><th>Tax</th><th>Total</th></tr>';
      
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
    <div class="col-sm-3">Number of delivery items:</div>
    <div class="col-sm-3">
    <?php 
    echo '<strong>'.$data['item_count'].'</strong>';
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Total delivery cost:</div>
    <div class="col-sm-3">
    <?php 
    echo '<strong>'.CURRENCY_ID.' '.number_format($data['item_total'],2).'</strong>';
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Notes for delivery:</div>
    <div class="col-sm-3">
    <?php echo Form::textAreaInput('note',$form['note'],50,5,$textarea_param); ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Confirmation email address:</div>
    <div class="col-sm-3">
    <?php echo Form::textInput('confirm_email',$form['confirm_email'],$text_param); ?>
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
