<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;

$client_param['class'] = 'form-control edit_input';
$client_param['xtra'] = ['SELECT'=>'Select client for delivery'];
$client_param['onchange'] = 'client_change()';

$list_param['class'] = 'form-control edit_input';
$text_param['class'] = 'form-control edit_input';

$date_param['class'] = 'form-control edit_input bootstrap_date';
?>

<div id="order_div">

  <p>
  <h2>Select client location & store</h2>
  <br/>
  </p>
  
  <div class="row">
    <div class="col-sm-3">Client:</div>
    <div class="col-sm-3">
    <?php 
    
    //$sql = 'SELECT client_id, CONCAT(name,":",SUBSTRING(address,1,LOCATE("\n",address))) FROM '.TABLE_PREFIX.'client WHERE status = "OK" ORDER BY name';
    $sql = 'SELECT client_id, name FROM '.TABLE_PREFIX.'client WHERE status = "OK" ORDER BY name';
    echo Form::sqlList($sql,$db,'client_id',$form['client_id'],$client_param) 
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Client location:</div>
    <div class="col-sm-3">
    <?php 
    if($form['client_id'] == 0) {
      $sql = 'SELECT 0,"Unknown, Select client."';
    } else {
      $sql = 'SELECT location_id,name FROM '.TABLE_PREFIX.'client_location '.
             'WHERE client_id= "'.$db->escapeSql($form['client_id']).'" AND status <> "HIDE" ORDER BY sort';
    }
    echo Form::sqlList($sql,$db,'client_location_id',$form['client_location_id'],$list_param) 
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Client Order No.:</div>
    <div class="col-sm-3">
    <?php 
    echo Form::textInput('client_order_no',$form['client_order_no'],$text_param)
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

<script language="javascript">


function client_change() {
    var form = document.getElementById('wizard_form');
    var client_id = form.client_id.value;
    
    var param = 'client_id='+client_id;
    var div_id = ''; //not used
    xhr('ajax?mode=client_locations',param,show_location_list,div_id);
      
} 

function show_location_list(str,div_id) {
    //alert('Result'+str);
    if(str.substring(0,5) === 'ERROR') {
        alert(str);
    } else {  
        var locations = $.parseJSON(str);
        var sel = '';

        //var list = document.getElementById('client_location_id');
        //use jquery to reset order select list
        $('#client_location_id option').remove();
        $.each(locations, function(i,item){
            // Create and append the new options into the select list
            $('#client_location_id').append('<option value='+i+' '+sel+'>'+item+'</option>');
        });
    }    
}



</script>  