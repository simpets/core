<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Frequently Asked Questions</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .faq-wrapper {
      background: #fff;
      max-width: 1000px;
      margin: 30px auto;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .faq-container {
      max-width: 800px;
      margin: auto;
    }
    .faq-category {
      margin-bottom: 30px;
    }
    .faq-category h2 {
      background: #e2e2e2;
      padding: 10px;
      border-left: 6px solid #6b8e23;
    }
    .faq-question {
      font-weight: bold;
      margin-top: 10px;
      cursor: pointer;
    }
    .faq-answer {
      display: none;
      margin-left: 20px;
      margin-top: 5px;
    }
  </style>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const questions = document.querySelectorAll(".faq-question");
      questions.forEach(q => {
        q.addEventListener("click", () => {
          const answer = q.nextElementSibling;
          answer.style.display = answer.style.display === "block" ? "none" : "block";
        });
      });
    });
  </script>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="faq-wrapper">
<div class="faq-container">
  <h1>Frequently Asked Questions</h1>

  <div class="faq-category">
    <h2>Getting Started</h2>
    <div class="faq-question">How do I adopt a pet?</div>
    <div class="faq-answer">Visit the 'Adopt' page from the main menu. Choose a pet type, name it, pick a gender, and it will be added to your collection.</div>

    <div class="faq-question">Do you have currency, and how do I earn it?</div>
    <div class="faq-answer">We do! All the sites have their own unique form. You earn it by boosting ( Leveling ) up pets, selling pets, or completing certain tasks and features.</div>

    
    <div class="faq-question">What do boosts and levels do?</div>
    <div class="faq-answer">They determine what a pet can do, and what they look like! You can just be patient and do slow boosting, or you can buy a potion to raise them up immediately!
    
<p>Level 1 (0–9 boosts)

<p>Level 2 (10–19)

<p>Level 3 (20–34)

<p>Level 4 (35–49)

<p>Level 5 (50+)</div>

  <div class="faq-category">
    <h2>Items & Inventory</h2>
    <div class="faq-question">How do I equip toys, and decorations?</div>
    <div class="faq-answer">Visit your Inventory and choose the item. Use it on the pet you wish to, you will need to select it on the second page, this is to ensure you have chosen the pet you really want to add it to!</div>

 <div class="faq-question">How do I add backgrounds?</div>
    <div class="faq-answer">Visit your inventory amd choose a background, then click "use on" and choose the pet.</div>


    <div class="faq-question">What do potions do?</div>
    <div class="faq-answer">Potions can change pet level, flip gender, or apply other magical effects. Use them from your inventory by selecting the pet to apply it to.</div>
    
    


    <div class="faq-question">How do I remove or reuse an item?</div>
    <div class="faq-answer">On the pet profile page, click “Remove” next to the item. It will return to your inventory and can be used again.</div>
  </div>

  <div class="faq-category">
    <h2>Shops & Economy</h2>
    <div class="faq-question">How do I buy items?</div>
    <div class="faq-answer">Click Item Market from the menu. Browse a shop and click “Buy” under the item. You’ll need enough currency to purchase.</div>

    <div class="faq-question">Where are special items sold?</div>
    <div class="faq-answer">Each shop sells different categories of items. For example, bases are in pet-specific shops, and potions are in the Potion shop.</div>

    <div class="faq-question">Can I sell my pets or items?</div>
    <div class="faq-answer">Yes. You can list your pets for sale in the Pet Market and other users can buy them using the site currency.</div>
  </div>


  <div class="faq-category">
    <h2>Breeding & Genetics</h2>
    <div class="faq-question">How does breeding work?</div>
    <div class="faq-answer">If you own two pets of the same type and both are level 3, you can breed them to create a new pet with combined traits. The young are called Pups and are presented as the beautiful eggs of that particular type!</div>

    <div class="faq-question">What do offspring inherit?</div>
    <div class="faq-answer">Offspring may visually reflect both parents. You choose the name, and the system generates a custom image merging both parent layers, or combines the looks in very interesting ways. Sometimes the offspring just looks like one of the parents.</div>

    <div class="faq-question">Why can’t I breed some pets?</div>
    <div class="faq-answer">Only pets of the same species and level 3 can breed. Make sure both are eligible and not already in a cooldown state.</div>
  </div>

  <div class="faq-category">
    <h2>Friends, Messaging & Community</h2>
    <div class="faq-question">How do I add friends?</div>
    <div class="faq-answer">Go to another user’s profile and click “Add Friend.” They must accept the request before you're connected.</div>

    <div class="faq-question">Can I block users?</div>
    <div class="faq-answer">Yes, you can block unwanted messages by visiting a user’s profile or via the messaging system.</div>

    <div class="faq-question">What are user groups?</div>
    <div class="faq-answer">Admins show stars next to their names, and have special powers like posting news and managing users. Most members are regular users with standard access.</div>
  </div>

  <div class="faq-category">
    <h2>Accounts & Profiles</h2>
    <div class="faq-question">How do I change my avatar or nickname?</div>
    <div class="faq-answer">Go to your profile page. There are options to upload a new avatar or edit your nickname there.</div>

    <div class="faq-question">What are themes and how do I change mine?</div>
    <div class="faq-answer">Themes let you personalize how your profile looks. Choose one from the profile settings, or upload your own background if allowed.</div>

    <div class="faq-question">How can I view another user’s pets?</div>
    <div class="faq-answer">Visit their profile and scroll down to see all pets they own, including links to each pet’s profile page.</div>
  </div>

</div>
</div>
</body>
 <?php include 'footer.php'; ?>


</html>