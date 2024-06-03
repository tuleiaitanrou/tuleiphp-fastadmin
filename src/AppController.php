<?php


namespace Tuleiphp\Fastadmin;

use app\exception\BadRequestHttpException;
use think\Model;
use taoser\exception\ValidateException;


class AppController{


    private ?Model  $M = null;  //挂载的模型对象

    protected string $class_root_model = "\\app\\model";
    protected string $class_root_validate = "\\app\\validate";


    protected array $exclude_validate = [];



    //v2 兼容字段
    protected string $modelName="";
    protected string $validateName="";



    public function __construct()
    {
        $this->bindValidate();
        $this->bindModel();

    }


    protected   function getMountModel():?Model
    {
        $mountModel = $this->M;
        if($mountModel == null){
            throw new BadRequestHttpException("挂载Model 不存在！");
        }
        return $mountModel;
    }

    /**
     * 自动加载model 到控制器
     */
    private function bindModel()
    {

        try{
            $defaultModelClass = $this->class_root_model; // app/model
            $modelName = $this->modelName;  //  sys.v1.UserModel
            $onMountModel = $defaultModelClass;
            if($modelName == ""){
                //  app\controller\TestingController
                $controllerStrArr = explode("\\",request()->controller);
                //TestingModel
                $modelName = str_replace("Controller","",$controllerStrArr[count($controllerStrArr) - 1])."Model";
                $onMountModel  .=   "\\".$modelName;
            }else{

                //  默认根本目录 app/model  +  sys.v1.UserModel  =>  app/model/sys/v1/UserModel
                $modelTree = explode(".",$modelName);
                foreach ($modelTree as $k=>$v){
                    $onMountModel .= "\\".$v;
                }
            }

            if(class_exists($onMountModel)){
                $this->M = new $onMountModel();
            }
        }catch (\Exception $exception){
            //抛出异常信息
            throw new BadRequestHttpException($exception->getMessage());
        }

    }

    /**
     * 自动开启验证器
     */
    private function bindValidate()
    {
        $defaultValidateClass = $this->class_root_validate;
        $validateName = $this->validateName;

        $validator = $defaultValidateClass;
        //默认验证器加载
        if($validateName == ""){
            $controllerStrArr = explode("\\",request()->controller);
            $validateName = str_replace("Controller","",$controllerStrArr[count($controllerStrArr) - 1])."Validate";
            $validator .= "\\".$validateName;
        }else{

            $validateTree = explode(".",$validateName);
            foreach ($validateTree as $k=>$v){
                $validator  .=  "\\".$v;
            }
        }

        $this->LoadValidateClass($validator);


    }



    private function LoadValidateClass($validator)
    {
        if(class_exists($validator)){

            $interScene = (new $validator())->getSceneParam();
            $scene = request()->action;  //当前方法作为场景

            //没验证场景的不验证
            foreach ($interScene as $k => $v){
                if($k === $scene)break;
            }

            //自定义不验证的函数
            if(!in_array($scene,$this->exclude_validate) && isset($interScene[$scene]) && !empty($interScene[$scene])){
                try{
                    validate($validator)->scene($scene)->check(request()->all());
                }catch (ValidateException $exception){
                    //抛出异常信息
                    throw new BadRequestHttpException($exception->getMessage());
                }
            }

            return;
        }
    }








}