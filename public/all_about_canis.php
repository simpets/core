<?php
$types = ['Bases', 'Markings', 'Breeding','Customs'];
$descriptions = [
    
    
   
    'Bases' => 'There are many varieties of Bases for all Canis except the little Diamond, who has his own set of them. Most base looks are available in all the breeds, except for that. Bases are saved and display in Profiles if the base was chosen as part of a Base Custom, was Base Swapped, etc. They do not show in the Profile if they are part of the offspring appearance. Bases may be swapped out at any time for an adult Canis in the Base Swapper. When you do this, you lose ALL markings and the previous look entirely - be aware of this if you have a very unique or rare look for your Pet!',
    
    
    
    
    'Markings' => 'Markings are available in the Custom Makers only. They do not display in the Profile, but they may be inherited by offspring, and show in all sorts of ways! All Canis but the Diamond have markings.',
    
    
    
    'Breeding' => 'Breeding is always allowed between any adult Canis of the same type and the correct age - minimum level 3. There is no barrier as far as gender whatsoever for breeding, nor for age of the Pet. The offspring will be born as eggs and once they reach level 3, they are considered adults. A fast and easy way to attain that is through use of the Mature potion! Bases and Markings both may be passed down in the offspring - called Pups. ',
    
    
     'Customs' => 'Customs are a fun way to really explore all the possibilities of the various types and looks of the Canis here. Go to the Make A Custom page to see what can be created, and find out what you need to do so!
     
     
     <p> FREE one marking Custom </p>
     
     <p> Base only Custom, Token Required
     
     <p> Two marking Custom, Token Required
     
     <p> Three marking Custom, Token Required
     
     <p> Deluxe and Grand Customs, Tokens Required',
    
    
    
    
    
    
    
    
    
    
    
    
];
?>

<!DOCTYPE html>
<html>
<head>
  <title>All About Canis</title>
  

  
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