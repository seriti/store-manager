<?php

?>

<div id="transfer_div">
  
  <h1>Transfer ID = <?php echo $data['transfer_id']; ?> successfully processed!</h1>
  <h2><a href="transfer">View all transfers</a></h2>
  <h2><a href="transfer_wizard"><button class="btn btn-primary">Generate a new transfer...</button></a></h2>


  <div class="row">
    <div class="col-sm-3">TRANSFER ID:</div>
    <div class="col-sm-3"><strong><?php echo $data['transfer_id']; ?></strong></div>
  </div>
   <div class="row">
    <div class="col-sm-3">From store:</div>
    <div class="col-sm-3"><strong><?php echo $data['from_store']['name']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">To store:</div>
    <div class="col-sm-3"><strong><?php echo $data['to_store']['name']; ?></strong></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Transfer Date:</div>
    <div class="col-sm-3"><strong><?php echo $form['date']; ?></strong></div>
  </div>

  <div class="row">
    <div class="col-sm-3">Number of transfered stock items:</div>
    <div class="col-sm-3">
    <?php 
    echo '<strong>'.$data['item_count'].'</strong>';
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Total transfer weight (Kg):</div>
    <div class="col-sm-3">
    <?php 
    echo '<strong>'.number_format($data['total_kg'],2).'</strong>';
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Transfer notes:</div>
    <div class="col-sm-3">
    <?php echo nl2br($this->form['note']); ?>
    </div>
  </div>


</div>
