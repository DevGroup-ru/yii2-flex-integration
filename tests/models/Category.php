<?php

namespace DevGroup\FlexIntegration\Tests\models;

use Yii;
use yii\db\ActiveRecord;

class Category extends ActiveRecord
{
    public function rules()
    {
        return [
            ['name', 'required'],
        ];
    }

    public static function tableName()
    {
        return '{{%category}}';
    }
}
