<?php
$types = ['Credits'];
$descriptions = [
    
    
   
    'Credits' => '<p>Site Concept and Layout:   Milly Money, Nova, Diego
    
                   <p>Site Art :                Milly Money, Nova, BAT, Renallia
                   
                   <p>Inspiration :             Ittermat  :)
    
    ',
    
    
    
    
    
    
    
    
    
];
?>

<!DOCTYPE html>
<html>
<head>
  <title>Site Credits</title>
  

  
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .type-box {
      width: 300px;
      margin: 20px;
      padding: 15px;
      border: 2px solid #ccc;
      border-radius: 10px;
      text-align: center;
      background-color: #f9f9f9;
    }
    .types-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
    }
    .type-box img {
      max-width: 150px;
      height: auto;
    }
  </style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>FAQ</h1>
  <div class="types-container">
    <?php foreach ($types as $type): ?>
      <div class="type-box">
       
        <h2><?= $type ?></h2>
        <p><?= $descriptions[$type] ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>
  <?php include 'footer.php'; ?>


</body>
</html>