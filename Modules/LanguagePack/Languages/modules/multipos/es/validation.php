<?php 
return [
  'multiPOS' => [
    'name' => [
      'required' => 'El nombre de la posición múltiple es obligatorio.',
      'max' => 'El nombre de la posición múltiple no puede tener más de :max caracteres.',
    ],
    'type' => [
      'required' => 'Se requiere el tipo de posición múltiple.',
      'in' => 'El tipo de posición múltiple seleccionado no es válido.',
    ],
  ],
];