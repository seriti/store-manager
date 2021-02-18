<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;

$supplier_param['class'] = 'form-control edit_input';
$supplier_param['xtra'] = ['SELECT'=>'Select supplier for linked orders'];
$supplier_param['onchange'] = 'supplier_change()';

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
    echo Form::sqlList($sql,$db,'supplier_id',$form['supplier_id'],$supplier_param) 
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Purchase order ID:</div>
    <div class="col-sm-3">
      <?php 
      if($form['supplier_id'] == 0) {
        $sql = 'SELECT O.order_id, CONCAT(S.name," order ID[",O.order_id,"]: ",O.date_create) '.
               'FROM '.TABLE_PREFIX.'order AS O JOIN '.TABLE_PREFIX.'supplier AS S ON(O.supplier_id = S.supplier_id) '.
               'WHERE O.status = "NEW" ORDER BY S.name, O.date_create, O.order_id';
      } else {
        $sql = 'SELECT order_id, CONCAT("Order ID[",order_id,"]: ",date_create) FROM '.TABLE_PREFIX.'order '.
               'WHERE supplier_id= "'.$db->escapeSql($form['supplier_id']).'" AND status = "NEW" ORDER BY date_create, order_id';
      }
      echo Form::sqlList($sql,$db,'order_id',$form['order_id'],$list_param)
      ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><?php echo MODULE_STORE['labels']['invoice_no'];?>:</div>
    <div class="col-sm-3">
    <?php 
    echo Form::textInput('invoice_no',$form['invoice_no'],$list_param)
    ?>
    </div>
  </div>

  

  <div class="row">
    <div class="col-sm-3">Reception location:</div>
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

<script language="javascript">


function supplier_change() {
    var form = document.getElementById('wizard_form');
    var supplier_id = form.supplier_id.value;
    
    var param = 'supplier_id='+supplier_id;
    xhr('ajax?mode=supplier_orders',param,show_order_list,order_id);
      
} 

function show_order_list(str,supplier_id) {
    //alert('Result'+str);
    if(str.substring(0,5) === 'ERROR') {
        alert(str);
    } else {  
        var orders = $.parseJSON(str);
        var sel = '';

        var list = document.getElementById('order_id');
        //use jquery to reset order select list
        $('#order_id option').remove();
        $.each(orders, function(i,item){
            // Create and append the new options into the select list
            //if(i == portfolio_id) sel = 'SELECTED'; else sel = '';
            $('#order_id').append('<option value='+i+' '+sel+'>'+item+'</option>');
        });
    }    
}



</script>  