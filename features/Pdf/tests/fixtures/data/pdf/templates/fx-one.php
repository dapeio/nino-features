<?php return array (
  'name' => 'Fixture one',
  'tag' => 'offer',
  'blocks' => 
  array (
    0 => 
    array (
      'type' => 'html',
      'block' => 'fx-text',
    ),
    1 => 
    array (
      'type' => 'html',
      'block' => 'fx-text',
    ),
    2 => 
    array (
      'type' => 'input',
      'name' => 'note',
      'label' => 'Note',
      'multiline' => true,
      'required' => false,
    ),
    3 => 
    array (
      'type' => 'pagebreak',
    ),
    4 => 
    array (
      'type' => 'html',
      'html' => '<p>[[name]] from [[/nowhere/text]]</p>',
    ),
    5 => 
    array (
      'type' => 'slot',
      'name' => 'rows',
    ),
    6 => 
    array (
      'type' => 'signature',
      'name' => 'seller',
      'party' => 'owner',
      'label' => 'The seller',
      'page' => 'last',
      'x' => 20.0,
      'y' => 230.0,
      'w' => 60.0,
      'h' => 20.0,
      'line' => true,
    ),
  ),
);