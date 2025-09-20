

Welcome to the Simpets System!

This is a base Pet Sim Framework IN BETA.

There may be errors, glitches, needed code updates, and the like. We in no way present this as a fully functional site in the sense that it is perfect and infallible.

THE FOOTER MUST RETAIN THE REFERENCE TO THE SIMPETS SYSTEM. You may add in your own in addition, or make any other mods or changes you wish to the System in any degree!

The Footer is the only thing that must retain the wording, but again you can add your own as well to that.




CHANGES:

This is all sourced from our live Site https://simpets.site. Most of the terms will refer to simbucks or simpets, etc. These all must be changed to align with your own url's and terms. Think of it like yoursite.com. It always has to be changed over, when you first start.

 In this, the error log is your friend. It will tell you exactly what needs to be changed and why. We highly advise deleting what you start with - if you have one - and letting a new one be created. 
 
 The errors will almost all be to do with changing terms. Don't forget to change in PhpMyadmin too, things such as currency in users, etc.
 
 Anything to do with buying, selling, member profiles, games, quests, etc will need the currency changed. Menu will need url changes possibly too. Sister Sites simply lists off our own sites, you will want to change that.
 
 At this time we do NOT have an Installer as such.
 
 We'd love an Installer that makes this simpler and easier, and at some point we hope to have that!
 
 


A NOTE:

Much of what you will do happens in PhpMyadmin. Many things need to be added and edited in there, at this point.

WARNING:  At last check, the admin.php section that edits users is DELETING them.

DO NOT USE unless you wish to remove that user! This is a known and very annoying bug.




1.  The Forum:

You'll need to go in the PhpMyadmin and manually insert Forums in the Forums table.

Then you can insert Threads, which should allow you to just add posts in the actual Forum. You can edit the name of your Forums in _common.php, which is found in the /forum folder. 

This is a basic, bare bones forum but it works, and people often really do not like to join anything not attached to the site.


2.  The Admin page:

admin.php and /admin contain important functions. Do NOT use edit users/members at this time.

It has not been fully tested so use this page at your own risk -  ALWAYS BACKUP. 

It's a WIP but gives you a good place to begin to have an Admin Panel.



3.  The Custom Makers, and Pet Images.


Pet images must have a transparent background to be able to accept the breeding functions as well as have backgrounds installed. Most other items are added in foreground of Pets and so this is not an issue.


4.  Breeding:

Breeding includes our special blended breed code, which perfectly blends two Pet images for a new look for the offspring. You will get one of two results, one will be an offspring very similar to one parent, or one that is a blend.

IMPORTANT:   The Pet images for the parents MUST be exactly the same in size and shape.

The default size for Pets is 200 x 200.  If you want anything different you must change this in breeding and display pages.

We include in the package two alternate breed scripts:  One that allows for any size/shape/species to interbreed, and one that contains an additional possibility for blended breed - that is half and half! 

For ALL blended breed scripts, you MUST match size, species and shape 100% or the result will not be good.



5.  

Items:

 We've left you a good number of items to work with, to see the various functions and how they work, so you can keep or delete these, and add in your own.

    Backgrounds are self explanatory. They go behind each Pet.
    
    Potions will perform various functions, and you can take a look at those in use_item.php.
    
    Tokens are not 'used' as such, but they go into your Inventory and allow you to create a custom. Once used, they may stay in Inventory but show '0'.
    
    
6.   

The Dashboard:

Here is where you get bumped to often. It shows all your current Pets and gives you a friendly greeting.
     
     
7.   

Friends

The Friends link will take you to the Friends page. You'll see Incoming and Outgoing requests as well as your current Friends.


8.   

Adventure, Games, Sparring, Battles and Quests:

These are all for earning currency (simbucks is the default, remember to change it!) and having something fun with many outcomes.

You can do a lot with these basic starters, but we think what we have is pretty fun already  :)
     
     
9.   

Sales and Buying:    

Its easy to set a Pet for sale, find that in the profile for that Pet. You can purchase a Pet too of course, and remember currency must be changed to yours.


In conclusion:


Basically -  go through and make it your own. Yes, it's a bit of a pain to have to do this, but once done you're all set. Hopefully I've remembered most to give you information on, and a good deal of the code is easy to work out. We're offering it free right now, and that's how it will stay for the time being, due to the obvious issues with installation and preparation.

We hope you are able to make good use of the Simpets System and we look forward to seeing your Branch of it some day!






 
    













