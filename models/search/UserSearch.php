<?php

namespace shopium\mod\telegram\models\search;

use panix\engine\data\ActiveDataProvider;
use shopium\mod\telegram\models\User;

/**
 * Class UserSearch
 * @property integer $id
 * @property string $name
 * @package panix\mod\cart\models\search
 */
class UserSearch extends User {

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id','is_bot'], 'integer'],
            [['first_name','last_name','username'], 'safe'],
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
        $query = User::find();
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
        $query->andFilterWhere(['like', 'first_name', $this->first_name]);
        $query->andFilterWhere(['like', 'last_name', $this->last_name]);
        $query->andFilterWhere(['like', 'username', $this->username]);

        return $dataProvider;
    }

}
