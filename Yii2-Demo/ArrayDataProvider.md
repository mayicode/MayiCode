#ArrayDataProvider
#数据非数据库分页

控制器代码
```PHP
public function actionArrayDataProvider(){
		$data = [
			['id' => 1, 'name' => 'name 1','date'=>'2333222'],
    		['id' => 2, 'name' => 'name 2'],
    		['id' => 100, 'name' => 'name 100'],
		];

		$provider = new ArrayDataProvider([
			'allModels'	=> $data,
			'pagination' => [
				'pageSize' => 2,
			],
			'sort' =>[
				'attributes' => ['id'],
			],
			'key' => function($model){
				return md5($model['id']);
			}
		]);
		$pages = new Pagination(['totalCount' => count($data),'pageSize'=>2]);
//		$rows = $provider->getModels();
		return $this->render('array-data-provider',['provider'=>$provider,'pages'=>$pages]);
	}
  ```
  
前端代码
```PHP
	echo LinkPager::widget([
		'pagination' => $pages
	]);

	echo GridView::widget([
		'dataProvider'=>$provider,
		'columns' => ['id','name',
			[
				'class' => 'yii\grid\CheckboxColumn',
				'name'	=> 'test',
				'checkboxOptions' => function ($model, $key, $index, $column) {
					return ['value' => $model['name']];
				}
			]
		],
	]);
```
