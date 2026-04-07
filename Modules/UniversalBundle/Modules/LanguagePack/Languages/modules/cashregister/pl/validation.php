<?php 
return [
  'denomination' => [
    'name' => [
      'required' => 'Nazwa nominału jest wymagana.',
      'max' => 'Nazwa nominału nie może być większa niż :max znaków.',
    ],
    'value' => [
      'required' => 'Wymagana jest wartość nominału.',
      'numeric' => 'Wartość nominału musi być liczbą.',
      'min' => 'Wartość nominału musi wynosić co najmniej :min.',
      'max' => 'Nominał nie może być większy niż :max.',
    ],
    'type' => [
      'required' => 'Typ nominału jest wymagany.',
      'in' => 'Wybrany typ nominału jest nieprawidłowy.',
    ],
    'description' => [
      'max' => 'Opis nominału nie może być większy niż :max znaków.',
    ],
  ],
];