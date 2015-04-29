<?php
/**
 * Main Program script to show proof of concept of Child Gin Rummy
 */

 require('RummyCardDeck.class.php');
 require('Player.class.php');

 $deck = new RummyCardDeck();
 $deck->Shuffle();

 $card = $deck->getTopStack();
 $deck->putTopDiscard($card);

 print "Starting Game:\n";
 print "==============\n\n";

 $player1 = new Player(1, true);
 $player1->Deal($deck);

 $player2 = new Player(2, true);
 $player2->Deal($deck);

 $player_arr = array();
 $player_arr[] = $player1;
 $player_arr[] = $player2;

 // Each player takes one turn in order
 while(true) {
   foreach ($player_arr as $player) {

      $num = $player->getPlayerNumber();
      print "\n\nPlayer " . $num . " is taking their turn\n";
      print "**********************************\n\n";

      print "Current hand:\n";
      $player->showCards();
      print "\n";

      if ($player->isBot()) {
         $rval = $player->autoMove($deck);
         print "\nAfter Turn:\n";
         $player->showCards();
         if ($rval == true) {
            print "\nPlayer " . $num . " has Won!\n";
            exit(0);
         }

         sleep(2);
      }
   }
 }
?>
