<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;

$list_param['class'] = 'form-control edit_input';
$date_param['class'] = 'form-control edit_input bootstrap_date';
$textarea_param['class'] = 'form-control edit_input';
?>

<div id="order_div">

  <p>
  <h2>Specify transfer stores</h2>
  <br/>
  </p>
  
  <div class="row">
    <div class="col-sm-3">From store:</div>
    <div class="col-sm-3">
    <?php 
    $sql = 'SELECT store_id, name FROM '.TABLE_PREFIX.'store WHERE status = "OK" ORDER BY name';
    echo Form::sqlList($sql,$db,'from_store_id',$form['from_store_id'],$list_param) 
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">To Store:</div>
    <div class="col-sm-3">
    <?php 
    $sql = 'SELECT store_id, name FROM '.TABLE_PREFIX.'store WHERE status = "OK" ORDER BY name';
    echo Form::sqlList($sql,$db,'to_store_id',$form['to_store_id'],$list_param) 
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Transfer date:</div>
    <div class="col-sm-3">
    <?php 
    echo Form::textInput('date',$form['date'],$date_param)
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Transfer Notes:</div>
    <div class="col-sm-3">
    <?php echo Form::textAreaInput('note',$form['note'],50,5,$textarea_param); ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-6"><input type="submit" name="Submit" value="Proceed" class="btn btn-primary"></div>
  </div>  

    
  
</div>