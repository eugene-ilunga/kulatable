<?php 
return [
  'denomination' => [
    'name' => [
      'required' => 'Le nom de la dénomination est obligatoire.',
      'max' => 'Le nom de la dénomination ne peut pas dépasser :max caractères.',
    ],
    'value' => [
      'required' => 'La valeur nominale est obligatoire.',
      'numeric' => 'La valeur de la dénomination doit être un nombre.',
      'min' => 'La valeur de la dénomination doit être d\'au moins :min.',
      'max' => 'La valeur nominale ne peut pas être supérieure à :max.',
    ],
    'type' => [
      'required' => 'Le type de dénomination est obligatoire.',
      'in' => 'Le type de dénomination sélectionné n\'est pas valide.',
    ],
    'description' => [
      'max' => 'La description de la dénomination ne peut pas dépasser :max caractères.',
    ],
  ],
];