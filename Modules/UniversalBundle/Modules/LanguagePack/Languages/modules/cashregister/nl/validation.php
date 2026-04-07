<?php 
return [
  'denomination' => [
    'name' => [
      'required' => 'De naam van de denominatie is vereist.',
      'max' => 'De denominatienaam mag niet groter zijn dan :max tekens.',
    ],
    'value' => [
      'required' => 'De nominale waarde is vereist.',
      'numeric' => 'De nominale waarde moet een getal zijn.',
      'min' => 'De nominale waarde moet minimaal :min zijn.',
      'max' => 'De nominale waarde mag niet groter zijn dan :max.',
    ],
    'type' => [
      'required' => 'Het denominatietype is vereist.',
      'in' => 'Het geselecteerde coupuretype is ongeldig.',
    ],
    'description' => [
      'max' => 'De omschrijving van de denominatie mag niet groter zijn dan :max tekens.',
    ],
  ],
];