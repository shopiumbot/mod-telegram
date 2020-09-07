<?php

namespace shopium\mod\telegram\models\query;


use yii\db\ActiveQuery;
use panix\engine\traits\query\DefaultQueryTrait;

class MailingQuery extends ActiveQuery
{

    use DefaultQueryTrait;


    /**
     * Default scope
     */
    public function init()
    {
        /** @var \yii\db\ActiveRecord $modelClass */
        $modelClass = $this->modelClass;
        $tableName = $modelClass::tableName();

            $this->addOrderBy(["{$tableName}.id" => SORT_DESC]);

        parent::init();
    }
}
