<?php 
return [
  'denomination' => [
    'name' => [
      'required' => '宗派名称为必填项。',
      'max' => '面额名称不得超过 :max 个字符。',
    ],
    'value' => [
      'required' => '面额为必填项。',
      'numeric' => '面值必须是数字。',
      'min' => '面值必须至少为 :min。',
      'max' => '面值不得大于 :max。',
    ],
    'type' => [
      'required' => '面额类型为必填项。',
      'in' => '所选面额类型无效。',
    ],
    'description' => [
      'max' => '面额描述不得超过 :max 个字符。',
    ],
  ],
];