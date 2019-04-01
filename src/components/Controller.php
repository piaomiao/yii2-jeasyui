<?php

namespace sheillendra\jeasyui\components;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Json;
use yii\web\NotFoundHttpException;
use yii\base\InvalidConfigException;

class Controller extends \yii\web\Controller {

    /**
     *
     * @var string
     */
    public $modelClass;

    /**
     *
     * @var array
     */
    public $redirectUrl;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->modelClass === null) {
            throw new InvalidConfigException('The "modelClass" property must be set.');
        }
        
        if ($this->redirectUrl === null) {
            throw new InvalidConfigException('The "redirectUrl" property must be set.');
        }
    }
    
    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'index' => ['get'],
                    'get-list' => ['get'],
                    'get-form' => ['get'],
                    'save' => ['post'],
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * 
     * @return redirect to module asset default controller
     */
    public function actionIndex() {
        return $this->redirect($this->redirectUrl);
    }

    /**
     * @return String json data for datagrid
     */
    public function actionGetList() {
        if (Yii::$app->request->isAjax) {
            echo Json::encode($this->getList());
            Yii::$app->end();
        }
    }

    /**
     * Function for actionGetListData, actionSave, actionDelete
     * @param boolean $returnWithPageRows the return contain page and rows 
     * @return array
     */
    protected function getList($returnWithPageRows = false) {
        $page = intval(Yii::$app->request->get('page', 1));
        $rows = intval(Yii::$app->request->get('rows', Yii::$app->params['defaultRows']));
        $modelClass = $this->modelClass;
        $list = $modelClass::getList($page, $rows);
        if ($returnWithPageRows) {
            return ['page' => $page, 'rows' => $rows, 'list' => $list];
        } else {
            return $list;
        }
    }
    
    /**
     * Get form, type request is GET
     * @return String Html form
     */
    public function actionGetForm() {
        $model = $this->getModel();
        if (Yii::$app->request->isAjax) {
            echo $this->renderAjax('_form', ['model' => $model]);
            Yii::$app->end();
        }
    }

    /**
     * Save data from type form, type request is POST
     * @return string json contain list will implement to datagrid
     */
    public function actionSave() {
        $this->save();
    }
    
    /**
     * 
     */
    public function save(){
        $post = Yii::$app->request->post();
        $reflector = new \ReflectionClass($this->modelClass);
        $model = $this->getModel($post[$reflector->getShortName()]['id']);
        if ($model->load($post)) {
            $result = [];
            if ($model->save(true)) {
                $result = $this->getList(true);
                $result['success'] = true;
            } else {
                $result['success'] = false;
                $result['error'] = $model->getErrors();
            }
            echo Json::encode($result);
            Yii::$app->end();
        }
    }

    /**
     * @param array $ids checked row of datagrid
     * @return json will implement to datagrid
     */
    public function actionDelete() {
        $result = [];
        $oneMoreSuccess = false;
        $arrPost = Yii::$app->request->post('ids');
        foreach ($arrPost as $id) {
            $model = $this->findModel($id);
            if (!$model->delete()) {
                break;
            }
            $oneMoreSuccess = true;
        }

        if ($oneMoreSuccess) {
            $result = $this->getList(true);
            $result['success'] = true;
        } else {
            $result['success'] = false;
            $result['error'] = $model->getErrors();
        }

        echo Json::encode($result);
        Yii::$app->end();
    }

    /**
     * 
     * @param type $id
     * @return object model new if id is null or model find result if id is not null
     */
    protected function getModel($id = null) {
        if ($id) {
            return $this->findModel($id);
        }
        $modelClass = $this->modelClass;
        return new $modelClass();
    }
    
    /**
     * 
     * @param int $id 
     * @return object model find result
     * @throws NotFoundHttpException
     */
    protected function findModel($id) {
        $modelClass = $this->modelClass;
        if (($model = $modelClass::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
