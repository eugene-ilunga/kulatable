<?php 
return [
  'denomination' => [
    'name' => [
      'required' => 'El nombre de la denominación es obligatorio.',
      'max' => 'El nombre de la denominación no podrá tener más de :max caracteres.',
    ],
    'value' => [
      'required' => 'Se requiere el valor de la denominación.',
      'numeric' => 'El valor de la denominación debe ser un número.',
      'min' => 'El valor de la denominación debe ser al menos :min.',
      'max' => 'El valor de la denominación no podrá ser mayor a :max.',
    ],
    'type' => [
      'required' => 'El tipo de denominación es obligatorio.',
      'in' => 'El tipo de denominación seleccionado no es válido.',
    ],
    'description' => [
      'max' => 'La descripción de la denominación no podrá tener más de :max caracteres.',
    ],
  ],
];