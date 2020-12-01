<?php

?>

<div id="order_div">
  
  <h1>Reception ID = <?php echo $data['receive_id']; ?> successfully processed!</h1>
  <h2><a href="receive">View all stock receptions</a></h2>
  <h2><a href="receive_wizard"><button class="btn btn-primary">Receive more stock...</button></a></h2>


  <div class="row">
    <div class="col-sm-3">RECEPTION ID:</div>
    <div class="col-sm-3"><strong><?php echo $data['receive_id']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">From Supplier:</div>
    <div class="col-sm-3"><strong><?php echo $data['supplier']['name']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Supplier invoice:</div>
    <div class="col-sm-3"><strong><?php echo $form['invoice_no']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Reception location:</div>
    <div class="col-sm-3"><strong><?php echo $data['location']['name']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Received on:</div>
    <div class="col-sm-3"><strong><?php echo $form['date_receive']; ?></strong></div>
  </div>  

  <div class="row">
    <div class="col-sm-3">Number of stock items received:</div>
    <div class="col-sm-3">
    <?php 
    echo '<strong>'.$data['item_count'].'</strong>';
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Total reception value:</div>
    <div class="col-sm-3">
    <?php 
    echo '<strong>'.CURRENCY_ID.' '.number_format($data['item_total'],2).'</strong>';
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Notes for reception:</div>
    <div class="col-sm-3">
    <?php echo nl2br($this->form['note']); ?>
    </div>
  </div>


</div>
