<?php

namespace shopium\mod\telegram\models\search;

use panix\engine\data\ActiveDataProvider;
use shopium\mod\telegram\models\Chat;

/**
 * Class ChatSearch
 * @property integer $id
 * @property string $name
 * @package shopium\mod\telegram\models\search
 */
class ChatSearch extends Chat {

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id'], 'integer'],
            [['title','type','username'], 'safe'],
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
        $query = Chat::find();
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
        $query->andFilterWhere(['like', 'title', $this->title]);
        $query->andFilterWhere(['like', 'type', $this->type]);
        $query->andFilterWhere(['like', 'username', $this->username]);

        return $dataProvider;
    }

}
