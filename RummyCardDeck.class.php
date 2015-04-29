<?php

/**
 * Class to implement the Child Gin Rummy deck
 *
 * Standard 52 cards
 *
 * handles 'stack' and 'discard pile'
 */
class RummyCardDeck {
 const heart = 1;
 const diamond = 2;
 const spades = 3;
 const clubs = 4;

 private $stack = array();
 private $discard = array(); 

 /* Create the stack with all 52 cards in order */
 public function __construct() {
   for ($j = 1; $j <= 4; $j++) {
       for ($i = 1; $i <= 13; $i++) {
           $this->stack[] = array('value' => $i, 'suit' => $j);
       }
   }
 }

 /*
  * Shuffle the stack (at least once - more if you'd like)
  */
 public function Shuffle($times = 1) {
print "Shuffling stack...\n";
   for ($i = 0; $i < $times; $i++) {
       shuffle($this->stack);
   }
 }

 /*
  * Return the card from the top of the stack
  */
 public function getTopStack() {
   $rval = array_pop($this->stack);
if ($rval == null) {
   print "ERROR CARD ON STACK IS NULL\n";
   print "COUNT: on discard: " . count($this->discard) . "\n";
}
   if (empty($this->stack)) {
      $this->stack = $this->discard;
      print "  Stack is empty now shuffling\n";
      $this->Shuffle();
      print "  there are now " . count($this->stack) . " cards on stack\n";
      $this->discard = array();
   }
   return($rval);
 }

 /*
  * Get the card at the top of the discard pile
  */
 public function getTopDiscard() {
   return(array_pop($this->discard));
 }

 /*
  * Put a card on top of the discard pile
  */
 public function putTopDiscard($card) {
   array_push($this->discard, $card);
 }

 // Debug util to print stack and discard pile
 public function printDeck() {
   print_r($this->stack);
   print_r($this->discard);
 }
}
?>
