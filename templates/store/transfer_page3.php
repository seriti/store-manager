<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;

$list_param['class'] = 'form-control edit_input';
$text_param['class'] = 'form-control edit_input';
$textarea_param['class'] = 'form-control edit_input';

//$confirm_action = ['NONE'=>'No additional action','EMAIL'=>'Email transfer details'];
$confirm_action = ['NONE'=>'No additional actions setup'];

$button_text = 'CONFIRM transfer details';

//if($form['confirm_action'] === 'EMAIL') $button_text .= ' & EMAIL details';

?>

<div id="order_div">
  
  <div class="row">
    <div class="col-sm-3">From store:</div>
    <div class="col-sm-3"><strong><?php echo $data['from_store']['name']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">To store:</div>
    <div class="col-sm-3"><strong><?php echo $data['to_store']['name']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Date:</div>
    <div class="col-sm-3"><strong><?php echo $form['date']; ?></strong></div>
  </div>
  
  <div class="row">
    <div class="col-sm-3">Transfer items</div>
    <div class="col-sm-9">
      <?php 
      echo '<table id="item_table" class="table  table-striped table-bordered table-hover table-condensed">'.
           '<tr><th>Stock name(supplier - invoice no)</th><th>Amount</th><th>Weight Kg</th></tr>';
      $i = 0;
      foreach($data['items'] as $item) {
          echo '<tr>'.
               '<td>'.$item['name'].'</td>'.
               '<td style="text-align:right">'.$item['amount'].'</td>'.
               '<td style="text-align:right">'.number_format($item['kg'],2).'</td>'.
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
    <div class="col-sm-3">Total transfer weight Kg:</div>
    <div class="col-sm-3">
    <?php 
    echo '<strong>'.number_format($data['total_kg'],2).' KG</strong>';
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Transfer notes:</div>
    <div class="col-sm-3">
    <?php echo Form::textAreaInput('note',$form['note'],50,5,$textarea_param); ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Confirmation email:</div>
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
