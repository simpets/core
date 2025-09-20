<?php
$types = ['TOS'];
$descriptions = [
    
    
   
    'TOS' => '<p>Terms of Service & Privacy Policy
<p>Account Creation and Usage
<p>In accordance with US, EU, and other laws, users of Simpets must be at least 13 years of age. Additionally, users may not have more than two accounts on the each site. Accounts MAY be bought, sold, or transfered to other users for in game currency.

ALL rules apply to Simpets and the Simpets Forum, unless stated explicitly to be different on a Site.

<p> SIte Information

<p> Most Site Information and Updates will be posted to the Forum. Therefore it is very advised to make an account there!


<p>Intellectual Property

<p>All artwork, text, and other  assets of the website are the property of Simpets and are referenced or created by those noted in the credits page.

<p>By uploading any content to the website, you affirm that you have the right to upload this content and you grant Pet-Sim.online and all its sites a non-exclusive license to display this content.

<p>User Content<br>


<p>The following content is NOT allowed on Simpets:

<p> Strong profanity or inappropriate names for pets, ie, blatantly offensive or filthy. Use Common Sense. </p>

<p>Real Life Hate speech or discrimination of any kind including but not limited to racism, sexism, homophobia, or transphobia

<p>Real Life Harassment or aggression towards other users of the site, or

<p>Real Life AND RP:  Mature topics such as sexual content, violence, obscene language, and drug use

<p>DO NOT POST:

<p>Personally identifying information about yourself or other people; including but not limited to full legal name, date of birth, or any geographic information that is more specific than a country, state, province, or region

<p>Any material that is illegal such as piracy or actionable threats

<p>Posting such content will lead to the offending content being removed, and may lead to loss of account privileges or termination.
    
                
    ',
    
    
    ];
?>

<!DOCTYPE html>
<html>
<head>
  <title>Terms Of Service</title>
  

  
  <link rel="stylesheet" href="assets/styles.css">
  <style>
  
    .type-box {
      width: 700px;
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
  <h1>TOS</h1>
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