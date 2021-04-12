<?php

?>

<div id="order_div">
  
  <h1>Delivery ID = <?php echo $data['deliver_id']; ?> successfully processed!</h1>
  <h2><a href="deliver">View all stock deliveries</a></h2>
  <h2><a href="deliver_wizard"><button class="btn btn-primary">Deliver more stock...</button></a></h2>


  <div class="row">
    <div class="col-sm-3">DELIVERY ID:</div>
    <div class="col-sm-3"><strong><?php echo $data['deliver_id']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">For Client:</div>
    <div class="col-sm-3"><strong><?php echo $data['client']['name']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Client location:</div>
    <div class="col-sm-3"><strong><?php echo $data['location']['name'].'</strong><br/>'.nl2br($data['location']['address']); ?></div>
  </div>
  <div class="row">
    <div class="col-sm-3">From store:</div>
    <div class="col-sm-3"><strong><?php echo $data['store']['name']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Delivery date:</div>
    <div class="col-sm-3"><strong><?php echo $form['date_deliver']; ?></strong></div>
  </div>

  <div class="row">
    <div class="col-sm-3">Number of stock items delivered:</div>
    <div class="col-sm-3">
    <?php 
    echo '<strong>'.$data['item_count'].'</strong>';
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Total delivery value:</div>
    <div class="col-sm-3">
    <?php 
    echo '<strong>'.CURRENCY_ID.' '.number_format($data['item_total'],2).'</strong>';
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Notes for delivery:</div>
    <div class="col-sm-3">
    <?php echo nl2br($this->form['note']); ?>
    </div>
  </div>


</div>
