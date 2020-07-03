<?php

namespace shopium\mod\telegram\models\search;

use panix\engine\data\ActiveDataProvider;
use shopium\mod\telegram\models\StartSource;

/**
 * Class ChatSearch
 * @property integer $id
 * @property string $name
 * @package shopium\mod\telegram\models\search
 */
class StartSourceSearch extends StartSource {

    public $test;
    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id','user_id','test'], 'integer'],
            [['created_at','source'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios() {
        // bypass scenarios() implementation in the parent class
        return \yii\base\Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params) {
        $query = StartSource::find();
        $query->addSelect(['*','COUNT(DISTINCT(user_id)) as usersCount']);
        $query->groupBy('source');
        $query->orderBy(['created_at'=>SORT_DESC]);
      // echo $query->createCommand()->rawSql;die;
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere(['id' => $this->id]);
        $query->andFilterWhere(['user_id'=>$this->user_id]);
        $query->andFilterWhere(['source', 'source', $this->source]);


        return $dataProvider;
    }

}
