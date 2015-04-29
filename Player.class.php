<?php

/**
 * Player class to handle auto moves
 *
 * TODO: Add user interface menu choices so that humans and not just bots can play
 */

class Player {

 private $cards = array();
 private $bot_player = false;
 private $global_debug = false;
 private $player_num = 0;

 public function __construct($num, $bot = false) {
   $this->player_num = $num;
   $this->bot_player = $bot;
 }

 /*
  * Return the player's number
  */
 public function getPlayerNumber() {
   return($this->player_num);
 }

 /*
  * Given a deck take seven cards for this player
  */
 public function Deal(&$deck) {
   $this->cards = array();
   for ($i = 0; $i < 7; $i++) {
       $this->cards[] = $deck->getTopStack();
   }
 }

 /*
  * Show the player's cards on one line on the terminal
  */
 public function showCards() {

   foreach ($this->cards as $key => $row) {
      $value[$key] = $row['value'];
      $suit[$key] = $row['suit'];
   }
   array_multisort($suit, SORT_ASC, $value, SORT_ASC, $this->cards);

   for ($i = 0; $i < count($this->cards); $i++) {
       switch($this->cards[$i]['suit']) {
         case(1): $suit = "(hearts) "; break;
         case(2): $suit = "(diamonds) "; break;
         case(3): $suit = "(spades) "; break;
         case(4): $suit =  "(clubs) "; break;
       }
       print $this->cards[$i]['value'] . $suit . " ";
   }
   print "\n";
 }

 /*
  * Show just one card (discarded card, card taken from stack, etc)
  */
 public function print_card($card) {
   if ($card == null) {
      print "ERROR: card is null!!!!\n";
   }
   switch($card['suit']) {
      case(1): $suit = "(hearts) "; break;
      case(2): $suit = "(diamonds) "; break;
      case(3): $suit = "(spades) "; break;
      case(4): $suit =  "(clubs) "; break;
   }
   print $card['value'] . $suit . "\n";
 }

 /*
  * Return if this player is set as a bot
  */
 public function isBot() {
   return($this->bot_player);
 }

 /*
  * Method for bots to use to make their next move
  */
 public function autoMove(&$deck) {
// Test cards for unit testing
/*
    $this->cards[0]['value'] = 1;
    $this->cards[0]['suit'] = 1;

    $this->cards[1]['value'] = 2;
    $this->cards[1]['suit'] = 1;

    $this->cards[2]['value'] = 1;
    $this->cards[2]['suit'] = 2;

    $this->cards[3]['value'] = 2;
    $this->cards[3]['suit'] = 2;

    $this->cards[4]['value'] = 1;
    $this->cards[4]['suit'] = 3;

    $this->cards[5]['value'] = 2;
    $this->cards[5]['suit'] = 3;

    $this->cards[6]['value'] = 3;
    $this->cards[6]['suit'] = 3;
*/

   // Sort the hand as it is now
   foreach ($this->cards as $key => $row) {
      $value[$key] = $row['value'];
      $suit[$key] = $row['suit'];
   }
   array_multisort($suit, SORT_ASC, $value, SORT_ASC, $this->cards);

   // Figure out what we currently have before we make a decission
   $orig_cards = $this->cards;   // Save the hand because we are going to add from the discard pile

   $rval1_orig = $this->find3OfKind();
   $rval2_orig = $this->find4CardRun($rval1_orig);

   /*
    * Check if the top of discard card is worth taking
    */

   // Temporarily add discard card to hand and recheck the hand
   $keep_discard = false;
   $discard_card = $deck->getTopDiscard();
   if ($discard_card != null) {
      $this->cards[] = $discard_card;
      foreach ($this->cards as $key => $row) {
         $value[$key] = $row['value'];
         $suit[$key] = $row['suit'];
      }
      array_multisort($suit, SORT_ASC, $value, SORT_ASC, $this->cards);

      $rval1_after = $this->find3OfKind();
      $rval2_after = $this->find4CardRun($rval1_after);

      if ($rval1_orig['count'] < 3) {
         if ($rval1_after['count'] > $rval1_orig['count']) {
            print "  Taking the discard card for building the 3-of-a-kind: ";
            $this->print_card($discard_card);
            // Keep the discard card
            $keep_discard = true;
         }
      }

      if ($keep_discard == false) {
         if ($rval2_orig['count'] < 4) {
            if ($rval2_after['count'] > $rval2_orig['count']) {
               print "  Taking the discard card for the Run-of-4: ";
               $this->print_card($discard_card);
               // Keep the discard card
               $keep_discard = true;
            }
         }
      }

      if ($keep_discard == true) {
         $win_status = $this->findCardToRemove($rval1_after, $rval2_after, $deck);
      }
      else {  // Put the discard pack on top
         $deck->putTopDiscard($discard_card);
      }
   }

   /*
    * Take card from the stack since we did not keep the discard card
    */
   if ($discard_card == null || $keep_discard == false) {
      print "  Taking card from stack: ";
      $this->cards = $orig_cards;   // Reset back to the original hand (without discard card)
      $new_card = $deck->getTopStack();
      $this->print_card($new_card);
      $this->cards[] = $new_card;

      // Now sort again
      foreach ($this->cards as $key => $row) {
         $value[$key] = $row['value'];
         $suit[$key] = $row['suit'];
      }
      array_multisort($suit, SORT_ASC, $value, SORT_ASC, $this->cards);

      $rval1_after = $this->find3OfKind();
      $rval2_after = $this->find4CardRun($rval1_after);
  
      // We took this card so we have to find something to put back
      $win_status = $this->findCardToRemove($rval1_after, $rval2_after, $deck);
   }

   return($win_status);
 }

 /*
  * Out of a hand of 8 cards - find one to put to the discard pile
  */
 private function findCardToRemove($set1, $set2, &$deck) {

   $this->showCards();

   $card_set1 = array();  // Cards being used for 3 of a kind
   $card_set2 = array();  // Cards being used for 4 card run
   $card_set_comb = array();  // All cards being used towards winning

   // Init cards in use to false
   for ($i = 0; $i < 8; $i++) {
       $card_set1[$i] = false;
       $card_set2[$i] = false;
       $card_set_comb[$i] = false;
   }

   // Fill in arrays with active cards (ones not to discard)
   foreach ($set1['cards'] as $key => $val) {
      $card_set1[$val] = true;
      $card_set_comb[$val] = true;
   }
   foreach ($set2['cards'] as $key => $val) {
      $card_set2[$val] = true;
      $card_set_comb[$val] = true;
   }

   $discard_card = null;

// Show what cards belong to 3-of-kind and Run-of-4 for debugging purposes
if ($this->global_debug) {
   print "Card set for 3kind:\n"; print_r($card_set1);
   print "Card set for 4run:\n"; print_r($card_set2);
   print "Combined sets:\n"; print_r($card_set_comb);
}

   // If we have 4 of a kind check that the 'forth' is not also part of the 4-run
   //  and if it's not that is the card that should be discarded!
   if ($set1['count'] > 3) {

if ($this->global_debug) {
   print "WE HAVE FOUR OF A KIND!\n";
}
      $potential_discards = 0;
      for ($i = 0; $i < 8; $i++) {
          if ($card_set1[$i] == true && $card_set2[$i] == false) {
             $potential_discards += 1;
          }
      }
      if ($potential_discards == 4) {  // The forth card of the "4 of a kind" is safe to discard because it's not part of the 4-run
         for ($i = 0; $i < 8; $i++) {
             if ($card_set1[$i] == true && $card_set2[$i] == false) {
                $discard_card = $this->cards[$i];
                array_splice($this->cards, $i, 1);
                array_splice($card_set_comb, $i, 1);
             }
         }
      }
   }

   // Find a card that is not part of the 3 of a kind or 4-Run
   $discard_set = array();
   if ($discard_card == null) {
      for ($i = 0; $i < 8; $i++) {
          if ($card_set_comb[$i] == false) {
             $discard_set[] = $i;
          } 
      }
      if (!empty($discard_set)) {
         $rand_key = array_rand($discard_set);
         $discard_card = $this->cards[$discard_set[$rand_key]];
         array_splice($this->cards, $discard_set[$rand_key], 1);
         array_splice($card_set_comb, $discard_set[$rand_key], 1);
      }
   }

   // Do we have a 4 of a knind AND a run of 4 with a shared card ?
   if ($discard_card == null) {
      for ($i = 0; $i < 8; $i++) {
          if ($card_set1[$i] == true && $card_set2[$i] == true) {
             $discard_card = $this->cards[$i];
             array_splice($this->cards, $i, 1);
             array_splice($card_set_comb, $i, 1);
          }
      }
   }

   // Put the removed card on the discard pile
   if ($discard_card != null) {
      print "  Discarding card: ";
      $this->print_card($discard_card);
      $deck->putTopDiscard($discard_card);
   }
   else {
      print "ERROR:  WE DID NOT FIND A CARD TO DISCARD!!!!\n";
      print "DISCARD SET:\n";
      print_r($discard_set);
      exit(0);
   }

   /*
    * Check if we've won
    */

   // Make sure 3 of kind does not overlap run of 4
   $count1 = 0;
   for ($i = 0; $i < 7; $i++) {
       if ($card_set1[$i] == true) {
          ++$count1;
       }
   }

   $count2 = 0;
   for ($i = 0; $i < 7; $i++) {
       if ($card_set2[$i] == true) {
          ++$count2;
       }
   }

   // Needs to be 3 or more for 3-of-kind
   if ($count1 < 2) {
      return(false);
   }

   // Needs to be 4 or more for Run-of-4
   if ($count2 < 4) {
      return(false);
   }

   // Check that all cards are only allocated once
   for ($i = 0; $i < 7; $i++) {
       if ($card_set1[$i] == true && $card_set2[$i] == true) {
print "WARNING: Overlap in 3-of-Kind and Run-of-4\n";
          return(false);
       }
   }

   return(true);  // We've won
 }

 /*
  * Check the current hand for the most cards possible to make up the 3-of-kind set
  */
 private function find3OfKind() {

   $rval = array();

   $max_count = 0;
   $max_val = 0;

   for ($j = 1; $j <= 13; $j++) {
       $count = 0;
       for ($i = 0; $i < count($this->cards); $i++) {
           if ($this->cards[$i]['value'] == $j) {
              ++$count;
           }
       }
       if ($count > $max_count) {
          $max_count = $count;
          $max_val = $j;
       }
   }

   // Return data to show what cards are being used for 3-of-kind
   $rval['count'] = $max_count;
   $rval['value'] = $max_val;

   $rval['cards'] = array();
   for ($j = 0; $j < count($this->cards); $j++) {
       if ($this->cards[$j]['value'] == $rval['value']) {
          $rval['cards'][] = $j;
       }
   }

   return($rval);
 }

 /*
  * Check current hand for the most cards possible to make up the Run-of-4
  */
 private function find4CardRun($set) {
   $rval = array();

   $max_count = 1;
   $end_run = 0;
   $max_suit = 0;

   for ($k = 1; $k <= 4; $k++) {   // Go through the hand one time for each suit

       $card_set = array();
       $count = 1;

       // Build a set of cars with the same suit
       for ($j = 0; $j < count($this->cards); $j++) {
           if ($this->cards[$j]['suit'] == $k) {
              $card_set[] = $this->cards[$j]['value'];
           }
       }

       // Find the longest run
       for ($i = 1; $i < count($card_set); $i++) {
           if ($card_set[$i] == ($card_set[$i - 1] + 1)) {
              $count++;
           }
           else {
              if ($count > $max_count) {
                 $max_count = $count;
                 $end_run = $card_set[$i - 1];
                 $max_suit = $k;
              }
              $count = 1;
           }
       }
       if ($count > $max_count) {
          $max_count = $count;
          $end_run = $card_set[$i - 1];
          $max_suit = $k;
       }
   }

   // Return data showing what card details are being used for Run-of-4
   $rval['cards'] = array();
   $rval['count'] = 0;
   $rval['suit'] = 0;
   $rval['end_run'] = 0;

   if ($max_count > 1) {
      $rval['count'] = $max_count;
      $rval['suit'] = $max_suit;
      $rval['end_run'] = $end_run;

      for ($i = 0; $i < count($this->cards); $i++) {
          if ($this->cards[$i]['suit'] == $max_suit && $this->cards[$i]['value'] == $end_run) {
             for ($j = ($i - $max_count + 1); $j <= $i; $j++) {
                 $rval['cards'][] = $j;
             }
          }
      }
   }

   return($rval);
 }
}
?>
