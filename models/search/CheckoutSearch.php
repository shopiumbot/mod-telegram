<?php

namespace shopium\mod\telegram\models\search;

use panix\engine\data\ActiveDataProvider;
use shopium\mod\telegram\models\Checkout;

/**
 * Class CheckoutSearch
 * @property integer $id
 * @property string $name
 * @package shopium\mod\telegram\models\search
 */
class CheckoutSearch extends Checkout {

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id','user_id'], 'integer'],
            [['currency','total_amount'], 'safe'],
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
        $query = Checkout::find();
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
        $query->andFilterWhere(['like', 'currency', $this->currency]);
        $query->andFilterWhere(['like', 'total_amount', $this->total_amount]);

        return $dataProvider;
    }

}
