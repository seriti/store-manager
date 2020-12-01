<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;

$client_param['class'] = 'form-control edit_input';
$client_param['xtra'] = ['SELECT'=>'Select client for delivery'];
//$client_param['onchange'] = 'client_change()';

$list_param['class'] = 'form-control edit_input';

$date_param['class'] = 'form-control edit_input bootstrap_date';
?>

<div id="order_div">

  <p>
  <h2>Select supplier </h2>
  <br/>
  </p>
  
  <div class="row">
    <div class="col-sm-3">Client:</div>
    <div class="col-sm-3">
    <?php 
    $sql = 'SELECT client_id, name FROM '.TABLE_PREFIX.'client WHERE status = "OK" ORDER BY name';
    echo Form::sqlList($sql,$db,'client_id',$form['client_id'],$client_param) 
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Delivery from store:</div>
    <div class="col-sm-3">
    <?php 
    $sql = 'SELECT S.store_id, CONCAT(L.name,": ",S.name) '.
           'FROM '.TABLE_PREFIX.'store AS S '.
          'JOIN '.TABLE_PREFIX.'location AS L ON(S.location_id = L.location_id) '.
           'ORDER BY L.name,S.name ';
    echo Form::sqlList($sql,$db,'store_id',$form['store_id'],$list_param) 
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Delivery date:</div>
    <div class="col-sm-3">
    <?php 
    echo Form::textInput('date_deliver',$form['date_deliver'],$date_param)
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-6"><input type="submit" name="Submit" value="Proceed" class="btn btn-primary"></div>
  </div>  

    
  
</div>