<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Ensure $pdo exists no matter who includes menu.php
if (!isset($pdo) || !$pdo) {
    $dbPath = __DIR__ . '/includes/db.php';
    if (file_exists($dbPath)) {
        require_once $dbPath; // defines $pdo
    }
}

// Optional: only after DB is ready, include tracking
$trackPath = __DIR__ . '/track_online.php';
if (file_exists($trackPath)) {
    include_once $trackPath; // this file is already hardened, but order is nice
}
?>







<nav style="background:#654321; color:white; padding:10px; text-align:center; line-height:2;">
  <div style="max-width:1000px; margin:auto;">
      
      <?php include_once 'track_online.php'; ?>
      
       <a href="index.php" style="color:white; margin:0 10px;">Home</a> |
      
      <a href="dashboard.php" style="color:white; margin:0 10px;">Dashboard</a>      |
          <a href="friends.php" style="color:white; margin:0 10px;">Friends</a>

         |
      <a href="forum/index.php" style="color:white; margin:0 10px;">Forums</a>
        
    |
      
      
    <a href="adopt.php" style="color:white; margin:0 10px;">Adopt</a> |
    
    <a href="breed.php" style="color:white; margin:0 10px;">Breed</a> |
    
    
    
    <a href="faq.php" style="color:white; margin:0 10px;">FAQ</a> |
    
    
    
    
    <a href="news.php" style="color:white; margin:0 10px;">News</a> |
    
    
    <a href="make_custom.php" style="color:white; margin:0 10px;">Make A Custom!</a>|
    
    <a href="base_swapper.php" style="color:white; margin:0 10px;">Base Swapper</a>|
    
    
    <a href="recent_pets.php" style="color:white; margin:0 10px;">Recent Pets!</a> |
    
    
    
    
          <a href="adventure.php" style="color:white; margin:0 10px;">Adventure!</a>      |


    <a href="quizgame.php" style="color:white; margin:0 10px;">Quiz Game</a>|
    
    

    <a href="pet_quest.php" style="color:white; margin:0 10px;">The Explorer Quest!</a>|
    
    

    <a href="gem_quest.php" style="color:white; margin:0 10px;">The Gem Quest!</a>|
    
    
    
 <a href="leaderboard_sparring_simpets.php" style="color:white; margin:0 10px;">Sparring Rankings</a>|

<a href="leaderboard_simpets.php" style="color:white; margin:0 10px;">Boost Board</a>|




    <a href="pet_market.php" style="color:white; margin:0 10px;">Pet Market</a>|
    
        <a href="shops.php" style="color:white; margin:0 10px;">The Market</a>| 


   
    
            <a href="sister_sites.php" style="color:white; margin:0 10px;">Sister Sites</a> |

    
        <a href="members.php" style="color:white; margin:0 10px;">Members</a> |

     <a href="online.php" style="color:white; margin:0 10px;">Online</a> |
     
     <a href="monster_battle_simbucks.php" style="color:white; margin:0 10px;">Battle</a> |


<a href="sparring_match_simpets.php" style="color:white; margin:0 10px;">Sparring</a> |

    
    
    
    
    
    
    
    
    
    
    
    
    
    
        <a href="inbox.php" style="color:white; margin:0 10px;">My Messages</a> |


        <a href="compose.php" style="color:white; margin:0 10px;">Send A Message</a> |







    <a href="my_pets.php" style="color:white; margin:0 10px;">My Pets</a> |
        <a href="profile.php" style="color:white; margin:0 10px;">My Profile</a> |

    
    <a href="inventory.php" style="color:white; margin:0 10px;">My Inventory</a>
    
 
    |
    
    <a href="logout.php" style="color:white; margin:0 10px;">Logout</a>
    
    
    
    
    <?php
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unread = $stmt->fetchColumn();
    ?>
    <a href="inbox.php" style="position:relative; padding-right:18px;">
      Inbox
      <?php if ($unread > 0): ?>
        <span style="position:absolute; top:-8px; right:-5px; background:#e54747; color:white; font-size:0.95em; padding:1px 7px; border-radius:12px;">
          <?= $unread ?>
        </span>
      <?php endif; ?>
    </a>
<?php
}
?>
    
    
    
    
    
    
    
    
    
    
    
  </div>
</nav>
