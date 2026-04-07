<?php 
return [
  'multiPOS' => [
    'name' => [
      'required' => 'Απαιτείται το όνομα multi pos.',
      'max' => 'Το όνομα multi pos δεν μπορεί να είναι μεγαλύτερο από χαρακτήρες :max.',
    ],
    'type' => [
      'required' => 'Απαιτείται ο τύπος multi pos.',
      'in' => 'Ο επιλεγμένος τύπος multi pos δεν είναι έγκυρος.',
    ],
  ],
];