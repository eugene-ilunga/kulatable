<?php 
return [
  'denomination' => [
    'name' => [
      'required' => 'Il nome della denominazione è obbligatorio.',
      'max' => 'Il nome della denominazione non può contenere più di :max caratteri.',
    ],
    'value' => [
      'required' => 'Il valore nominale è obbligatorio.',
      'numeric' => 'Il valore della denominazione deve essere un numero.',
      'min' => 'Il valore nominale deve essere almeno :min.',
      'max' => 'Il valore nominale non può essere maggiore di :max.',
    ],
    'type' => [
      'required' => 'Il tipo di denominazione è obbligatorio.',
      'in' => 'Il tipo di denominazione selezionato non è valido.',
    ],
    'description' => [
      'max' => 'La descrizione della denominazione non può contenere più di :max caratteri.',
    ],
  ],
];