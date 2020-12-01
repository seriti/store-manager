<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;

$list_param['class'] = 'form-control edit_input';
$date_param['class'] = 'form-control edit_input bootstrap_date';
?>

<div id="order_div">

  <p>
  <h2>Select supplier </h2>
  <br/>
  </p>
  
  <div class="row">
    <div class="col-sm-3">Supplier:</div>
    <div class="col-sm-3">
    <?php 
    $sql = 'SELECT supplier_id, name FROM '.TABLE_PREFIX.'supplier WHERE status = "OK" ORDER BY name';
    echo Form::sqlList($sql,$db,'supplier_id',$form['supplier_id'],$list_param) 
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Deliver to:</div>
    <div class="col-sm-3">
    <?php 
    $sql = 'SELECT location_id, name FROM '.TABLE_PREFIX.'location WHERE status = "OK" ORDER BY name';
    echo Form::sqlList($sql,$db,'location_id',$form['location_id'],$list_param) 
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Reception date:</div>
    <div class="col-sm-3">
    <?php 
    echo Form::textInput('date_receive',$form['date_receive'],$date_param)
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-6"><input type="submit" name="Submit" value="Proceed" class="btn btn-primary"></div>
  </div>  

    
  
</div>