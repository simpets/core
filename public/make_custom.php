<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Make A Custom</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f8f2ea url('images/pawprints_bg.png') repeat;
            color: #5d4634;
            margin: 0;
            padding: 0;
        }
        .custom-container {
            max-width: 500px;
            margin: 60px auto;
            background: #fff9f3;
            border-radius: 18px;
            box-shadow: 0 2px 12px #d8cfc2;
            padding: 36px 28px 30px 28px;
        }
        h1 {
            font-size: 2.1em;
            text-align: center;
            color: #ad7a43;
            margin-bottom: 36px;
        }
        .step {
            margin: 26px 0;
            padding: 20px 18px;
            background: #fff3e0;
            border-left: 7px solid #ad7a43;
            border-radius: 14px;
        }
        .step a {
            background: #ffe6be;
            padding: 8px 16px;
            border-radius: 8px;
            color: #965a18;
            font-weight: bold;
            text-decoration: none;
            transition: background 0.15s;
            margin-left: 6px;
        }
        .step a:hover {
            background: #ffd8a6;
        }
        .marking-options {
            margin-top: 14px;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }
        .marking-btn {
            background: #fff7ed;
            border: 1px solid #edc7a3;
            color: #ad7a43;
            border-radius: 9px;
            padding: 11px 18px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.12s, border 0.12s;
            text-decoration: none;
            display: inline-block;
        }
        .marking-btn:hover {
            background: #ffe6be;
            border-color: #ad7a43;
        }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    
    <div class="custom-container">
        <h1>Make A Custom</h1>
        
        <div class="step">
            <strong>How To:</strong> 
            <p></p>Make a One Marking for Free,
            or use a <b>Token</b> </p>
            
            <b><p>Basic Customs:</b>
            
            <p>With these, you will be creating a custom 'from scratch'!
            
            <p>One Marking Custom - FREE.
            
            <p>Base Only Custom - Token Required
            
            <p>2 Marking Custom - Token Required
            <p>3 Marking Custom - Token Required
            
            
            <b><p>Special Customs:</b>  You MUST adopt a pet beforehand, and you will choose that same pet for the Customizing process.
            
            <p>Be <b>SURE</b> you choose the correct name, changes will be applied to that pet!!
            
            <p>Deluxe:  Base with 2 Markings - Token Required
            
            <p>Grand:   Base with 3 Markings - Token Required
            
            
            
            
            
            <div class="marking-options">
                <a class="marking-btn" href="custom_maker.php">1 Marking (Free)</a>
                
                
                <a class="marking-btn" href="custom_maker_2_markings.php">2 Markings (Token Required)</a>
                <a class="marking-btn" href="custom_maker_3_markings.php">3 Markings (Token Required)</a>
                
                 <a class="marking-btn" href="customizer.php"> Deluxe (Token Required)</a>
                 
                 <a class="marking-btn" href="customizer2.php"> Grand (Token Required)</a>
                 
                
                
                
            </div>
        </div>
    </div>
</body>
 <?php include 'footer.php'; ?>


</html>