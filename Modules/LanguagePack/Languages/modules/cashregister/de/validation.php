<?php 
return [
  'denomination' => [
    'name' => [
      'required' => 'Der Nennwert ist erforderlich.',
      'max' => 'Der Nennwertname darf nicht länger als :max Zeichen sein.',
    ],
    'value' => [
      'required' => 'Der Nennwert ist erforderlich.',
      'numeric' => 'Der Nennwert muss eine Zahl sein.',
      'min' => 'Der Nennwert muss mindestens :min betragen.',
      'max' => 'Der Nennwert darf nicht größer als :max sein.',
    ],
    'type' => [
      'required' => 'Der Nennwerttyp ist erforderlich.',
      'in' => 'Der ausgewählte Nennwerttyp ist ungültig.',
    ],
    'description' => [
      'max' => 'Die Nennwertbeschreibung darf nicht länger als :max Zeichen sein.',
    ],
  ],
];