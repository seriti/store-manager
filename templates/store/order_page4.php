<?php

?>

<div id="order_div">
  
  <h1>Order ID = <?php echo $data['order_id']; ?> successfully processed!</h1>
  <h2><a href="order">View all orders</a></h2>
  <h2><a href="order_wizard"><button class="btn btn-primary">Generate a new order...</button></a></h2>


  <div class="row">
    <div class="col-sm-3">ORDER ID:</div>
    <div class="col-sm-3"><strong><?php echo $data['order_id']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Supplier:</div>
    <div class="col-sm-3"><strong><?php echo $data['supplier']['name']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Supplier email:</div>
    <div class="col-sm-3"><strong><?php echo $data['supplier']['email']; ?></strong></div>
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
    <?php echo nl2br($this->form['note']); ?>
    </div>
  </div>


</div>
