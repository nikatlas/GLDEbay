<?
require "lib.php";

$ebay = new ebayAccount();
echo $ebay->verifyAddItem();

/*
TODO!!!!
1st. -> Create a addItem structure .. page to page Ajax requests to get Categories - > get Country -> etc... 
2nd  -> Create Enumeration class... With print... Multiple name->values !! 
3rd  -> 
*/

/*
---Listing TODOS---
1st -> Shipping method! 
2nd -> Handling Time!
3rd -> Add product details to this listing!
4th -> Return Policy 
5th -> Pictures! 
*/

?>