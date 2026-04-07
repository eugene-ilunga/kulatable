<?php 
return [
  'denomination' => [
    'name' => [
      'required' => 'Denumirea este obligatorie.',
      'max' => 'Denumirea nu poate fi mai mare de :max caractere.',
    ],
    'value' => [
      'required' => 'Valoarea nominală este necesară.',
      'numeric' => 'Valoarea nominală trebuie să fie un număr.',
      'min' => 'Valoarea nominală trebuie să fie de cel puțin :min.',
      'max' => 'Valoarea nominală nu poate fi mai mare de :max.',
    ],
    'type' => [
      'required' => 'Tipul de denumire este obligatoriu.',
      'in' => 'Tipul de denumire selectat este nevalid.',
    ],
    'description' => [
      'max' => 'Descrierea denumirii nu poate fi mai mare de :max caractere.',
    ],
  ],
];