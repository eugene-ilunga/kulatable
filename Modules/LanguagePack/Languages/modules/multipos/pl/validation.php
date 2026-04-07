<?php 
return [
  'multiPOS' => [
    'name' => [
      'required' => 'Wymagana jest nazwa wielu pozycji.',
      'max' => 'Nazwa wielopoz. nie może być dłuższa niż :max znaków.',
    ],
    'type' => [
      'required' => 'Wymagany jest typ wielopozycyjny.',
      'in' => 'Wybrany typ wielu pozycji jest nieprawidłowy.',
    ],
  ],
];