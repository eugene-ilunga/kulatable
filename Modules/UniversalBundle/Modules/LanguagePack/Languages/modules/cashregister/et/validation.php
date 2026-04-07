<?php 
return [
  'denomination' => [
    'name' => [
      'required' => 'Nimiväärtuse nimi on kohustuslik.',
      'max' => 'Nimiväärtus ei tohi olla suurem kui :max tähemärki.',
    ],
    'value' => [
      'required' => 'Nimiväärtus on nõutav.',
      'numeric' => 'Nimiväärtus peab olema arv.',
      'min' => 'Nimiväärtus peab olema vähemalt :min.',
      'max' => 'Nimiväärtus ei tohi olla suurem kui :max.',
    ],
    'type' => [
      'required' => 'Nimiväärtuse tüüp on nõutav.',
      'in' => 'Valitud nimiväärtuse tüüp on kehtetu.',
    ],
    'description' => [
      'max' => 'Nimiväärtuse kirjeldus ei tohi olla suurem kui :max tähemärki.',
    ],
  ],
];