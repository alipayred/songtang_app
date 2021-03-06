<?php
namespace yun\models;

use common\components\CommonFunc;
use Yii;
use yii\helpers\ArrayHelper;

class DirPermission extends \yii\db\ActiveRecord
{

    public static function getDb(){
        return Yii::$app->db_yun;
    }


    const MODE_ALLOW            = 1;   //模式 允许
    const MODE_DENY             = 2;   //模式 禁止
    const MODE_ALLOW_CN         = '允许';
    const MODE_DENY_CN          = '禁止';

    const PERMISSION_TYPE_NORMAL = 1;   //常规（不限制）
//    const PERMISSION_TYPE_ATTR_LIMIT = 2; //限制文件属性(地区,行业)和用户属性保持一致
    const PERMISSION_TYPE_ATTR_LIMIT_DISTRICT = 2; //只限制地区属性  包含了 4
    const PERMISSION_TYPE_ATTR_LIMIT_INDUSTRY = 3; //只限制行业属性  包含了 4
    const PERMISSION_TYPE_ATTR_LIMIT_DISTRICT_INDUSTRY = 4; //限制文件属性(地区,行业)和用户属性保持一致



    const OPERATION_UPLOAD      = 1;   //上传操作
    const OPERATION_DOWNLOAD    = 2;   //下载操作(预览)
    const OPERATION_COOP        = 3;   //协同操作
    //const OPERATION_DELETE      = 4;   //删除操作
    const OPERATION_UPLOAD_CN   = '上传';  //编辑 删除
    const OPERATION_DOWNLOAD_CN = '下载';  //查看
    const OPERATION_COOP_CN     = '协同';


    const TYPE_ALL              = 1;   //全体职员
    const TYPE_USER             = 2;   //单个职员
    const TYPE_WILDCARD         = 3;   //前四个的任意组合
    const TYPE_GROUP            = 7;   //权限用户组

    const TYPE_ALL_CN              = '全体职员';   //全体职员
    const TYPE_USER_CN             = '单一职员';   //单独的USER_ID
    const TYPE_WILDCARD_CN         = '通配';       //地区/行业/公司等等属性 通配
    const TYPE_GROUP_CN            = '用户组';     //用户组


    public function rules()
    {
        return [
            [['dir_id', 'permission_type','user_match_type','user_match_param_id','operation','mode'], 'integer'],
        ];
    }

    public static function getTypeItems(){
        return [
            self::TYPE_ALL => self::TYPE_ALL_CN,
            //self::TYPE_USER => self::TYPE_USER_CN,
            self::TYPE_WILDCARD => self::TYPE_WILDCARD_CN,
            self::TYPE_GROUP => self::TYPE_GROUP_CN
        ];
    }

    public static function getPermissionTypeArr(){
        return [
            self::PERMISSION_TYPE_NORMAL,
            self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT,
            self::PERMISSION_TYPE_ATTR_LIMIT_INDUSTRY,
            self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT_INDUSTRY
        ];
    }

    public static function getPermissionTypeItems(){
        return [
            self::PERMISSION_TYPE_NORMAL => '没有限制',
            self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT => '限制地区',
            self::PERMISSION_TYPE_ATTR_LIMIT_INDUSTRY => '限制行业',
            self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT_INDUSTRY => '限制地区及行业'
        ];
    }

    public static function getModeItems(){
        return [
            self::MODE_ALLOW => self::MODE_ALLOW_CN,
            self::MODE_DENY => self::MODE_DENY_CN
        ];
    }

    public static function getOperationItems(){
        return [
            self::OPERATION_UPLOAD => self::OPERATION_UPLOAD_CN,
            self::OPERATION_DOWNLOAD => self::OPERATION_DOWNLOAD_CN,
            //self::OPERATION_COOP => self::OPERATION_COOP_CN
        ];
    }

    public static function getModeName($mode){
        if($mode==self::MODE_ALLOW){
            $return = self::MODE_ALLOW_CN;
        }else if($mode==self::MODE_DENY){
            $return = self::MODE_DENY_CN;
        }else {
            $return = 'N/A';
        }
        return $return;
    }

    public static function getOperationName($oper){
        if($oper==self::OPERATION_UPLOAD){
            $return = self::OPERATION_UPLOAD_CN;
        }else if($oper==self::OPERATION_DOWNLOAD){
            $return = self::OPERATION_DOWNLOAD_CN;
        }else if($oper==self::OPERATION_COOP){
            $return = self::OPERATION_COOP_CN;
        }else {
            $return = 'N/A';
        }
        return $return;
    }

/*    public static function getModeName($mode){
        if($mode==self::MODE_ALLOW){
            $return = self::MODE_ALLOW_CN;
        }else if($mode==self::MODE_DENY){
            $return = self::MODE_DENY_CN;
        }else {
            $return = 'N/A';
        }
        return $return;
    }*/

    /*
     * 检测当前用户是否在这个范围里
     * 参数 dm : 一条dir_permission记录
     * 参数 user: 用户  默认为当前登录用户
     */
    public static function isInRange($dm_user_match_type,$dm_user_match_param_id,$user_id=false){
        $return = false;
        if($user_id===false)
            $user_id = Yii::$app->user->id;
        switch($dm_user_match_type){
            case self::TYPE_ALL:
                $return = true;
                break;
            case self::TYPE_WILDCARD:
                $userWildcard = UserWildcard::find()->where(['id'=>$dm_user_match_param_id])->one();
                foreach($userWildcard->users as $u){
                    if($u->id==$user_id)
                        $return = true;
                }
                break;
            case self::TYPE_USER:
                if($dm_user_match_param_id == $user_id)
                    $return = true;
                break;
            case self::TYPE_GROUP:
                $userGroup = UserGroup::find()->where(['id'=>$dm_user_match_param_id])->one();
                foreach($userGroup->users as $u){
                    if($u->user_id==$user_id)
                        $return = true;
                }
                break;
        }

        return $return;
    }


    public static function isInRangeByCache($dm,$user=false){
        $cache = yii::$app->cache;
        $key = 'dir-permission-is-in-range';
        $idKey = $dm->dir_id.'-'.$dm->permission_type.'-'.$dm->user_match_type.'-'.$dm->user_match_param_id.'-'.$dm->operation.'-'.$dm->mode.'-'.$user->id;
        if(isset($cache[$key]) && isset($cache[$key][$idKey])){
            $data = $cache[$key][$idKey];
        }else {
            $data = self::isInRange($dm,$user);
            if(!isset($cache[$key])){
                $arr = [$idKey => $data];
            }else{
                $arr = ArrayHelper::merge($cache[$key],[$idKey => $data]);
            }
            $cache[$key] = $arr;
        }
        return $data;
    }

    /*
     * 检测目录是否允许执行所选操作
     * 参数 dir_id : 目录ID
     * 参数 [Array|String] permission_type :   权限类型，1常规不限制， 2 3 4 限制文件属性(地区,行业)和用户属性保持一致
     * ['and'=>array(1,2...)] 表示这些权限要都有
     * ['or'=>array(1,2,....)] 表示有其一即可
     * 1/2/3    单一数字，表示单一权限
     * 参数 operation_id : 操作类型
     * 参数 user: 用户  默认为当前登录用户
     */
    public static function isDirAllow($dir_id,$permission_type,$operation_id,$user=false,$ignoreAdmin=false){
        $isAllow = false;
        if(!$ignoreAdmin && Yii::$app->user->identity->isYunFrontendAdmin){
            $isAllow = true;
        }else{
            if($user===false)
                $user = Yii::$app->user->identity;

            $parents = CommonFunc::getByCache(Dir::className(),'getParents',[$dir_id],'yun:dir/parents');  //父目录数组 用作递归

            $act = 'and';
            if(is_array($permission_type)){
                if(isset($permission_type['or'])){
                    $act = 'or';
                    $typeArr = $permission_type['or'];
                }else{
                    $typeArr = $permission_type['and'];
                }
            }else{
                $typeArr = [$permission_type];
            }

            foreach($typeArr as $pt){
                $isAllow2 = false;
                //$ptArr = self::expandPermissionType($pt);
                $allowList = CommonFunc::getByCache(self::className(),'getList',[$dir_id,$pt,$operation_id,self::MODE_ALLOW],'yun:dir-permission/list');
                //self::getListByCache($dir_id,$pt,$operation_id,self::MODE_ALLOW);

                //$allowList = self::find()->where(['dir_id'=>$dir_id,'permission_type'=>$ptArr,'operation'=>$operation_id,'mode'=>self::MODE_ALLOW])->all();
                if(!empty($allowList)){
                    foreach($allowList as $a){
                        if(CommonFunc::getByCache(self::className(),'isInRange',[$a->user_match_type,$a->user_match_param_id,$user->id],'yun:dir-permission/is-in-range')){
                            $isAllow2 = true; //有一条允许就允许
                            break;
                        }
                    }
                }
                if($isAllow2==false && !empty($parents)){  //递归父目录
                    foreach($parents as $p){
                        $allowList = CommonFunc::getByCache(self::className(),'getList',[$p->id,$pt,$operation_id,self::MODE_ALLOW],'yun:dir-permission/list');
                        //self::getListByCache($p_id,$pt,$operation_id,self::MODE_ALLOW);
                        //$allowList = self::find()->where(['dir_id'=>$p_id,'permission_type'=>$ptArr,'operation'=>$operation_id,'mode'=>self::MODE_ALLOW])->all();
                        if(!empty($allowList)){
                            foreach($allowList as $a){
                                if(CommonFunc::getByCache(self::className(),'isInRange',[$a->user_match_type,$a->user_match_param_id,$user->id],'yun:dir-permission/is-in-range')){
                                    $isAllow2 = true; //有一条允许就允许
                                    break;
                                }
                            }
                        }
                    }
                }

                $denyList = CommonFunc::getByCache(self::className(),'getList',[$dir_id,$pt,$operation_id,self::MODE_DENY],'yun:dir-permission/list');
                //self::getListByCache($dir_id,$pt,$operation_id,self::MODE_DENY);
                //$denyList = self::find()->where(['dir_id'=>$dir_id,'permission_type'=>$ptArr,'operation'=>$operation_id,'mode'=>self::MODE_DENY])->all();
                if(!empty($denyList)){
                    foreach($denyList as $d){
                        if(CommonFunc::getByCache(self::className(),'isInRange',[$d->user_match_type,$d->user_match_param_id,$user->id],'yun:dir-permission/is-in-range')){
                            $isAllow2 = false; //有一条禁止就禁止
                            break;
                        }
                    }
                }

                if($isAllow2==true && !empty($parents)){ //递归父目录
                    foreach($parents as $p){
                        $denyList = CommonFunc::getByCache(self::className(),'getList',[$p->id,$pt,$operation_id,self::MODE_DENY],'yun:dir-permission/list');
                        //self::getListByCache($p_id,$pt,$operation_id,self::MODE_DENY);
                        //$denyList = self::find()->where(['dir_id'=>$p_id,'permission_type'=>$ptArr,'operation'=>$operation_id,'mode'=>self::MODE_DENY])->all();
                        if(!empty($denyList)){
                            foreach($denyList as $d){
                                if(CommonFunc::getByCache(self::className(),'isInRange',[$p->user_match_type,$p->user_match_param_id,$user->id],'yun:dir-permission/is-in-range')){
                                    $isAllow2 = false; //有一条禁止就禁止
                                    break;
                                }
                            }
                        }
                    }
                }
                $isAllow = $isAllow2;
                if($act == 'or' ){
                    if($isAllow2==true){
                        break;
                    }
                }elseif($act == 'and'){
                    if(($isAllow2)==false){
                        break;
                    }
                }
            }
        }
        return $isAllow;
    }

    public static function isDirAllowByCache($dir_id,$permission_type,$operation_id,$user=false,$ignoreAdmin=false){
        if(!$ignoreAdmin && Yii::$app->user->identity->isYunFrontendAdmin){
            $data = true;
        }else {
            $cache = yii::$app->cache;
            $key = 'dir-is-allow';
            $idKey = $dir_id;
            $idKey .= '-'.$permission_type;
            $idKey .= '-'.$operation_id;
            if ($user === false)
                $user = Yii::$app->user->identity;
            $idKey .= '-'.$user->id;

            if(isset($cache[$key]) && isset($cache[$key][$idKey])){
                $data = $cache[$key][$idKey];
            }else {
                $data = self::isDirAllow($dir_id,$permission_type,$operation_id,$user);
                if(!isset($cache[$key])){
                    $arr = [$idKey => $data];
                }else{
                    $arr = ArrayHelper::merge($cache[$key],[$idKey => $data]);
                }
                $cache[$key] = $arr;
            }
        }
        return $data;
    }

    public static function getList($dir_id,$permission_type,$operation_id,$mode){
        $ptArr = self::expandPermissionType($permission_type);
        return self::find()->where(['dir_id'=>$dir_id,'permission_type'=>$ptArr,'operation'=>$operation_id,'mode'=>$mode])->all();
    }

    public static function getListByCache($dir_id,$permission_type,$operation_id,$mode){
        $cache = yii::$app->cache;
        $key = 'dir-permission-list';
        $idKey = $dir_id.'-'.$permission_type.'-'.$operation_id.'-'.$mode;
        if(isset($cache[$key]) && isset($cache[$key][$idKey])){
            $data = $cache[$key][$idKey];
        }else {
            $data = self::getList($dir_id,$permission_type,$operation_id,$mode);
            if(!isset($cache[$key])){
                $arr = [$idKey => $data];
            }else{
                $arr = ArrayHelper::merge($cache[$key],[$idKey => $data]);
            }
            $cache[$key] = $arr;
        }
        return $data;
    }


/*    public static function isDirAllowByCache($dir_id,$permission_type,$operation_id,$user=false,$ignoreAdmin=false){
        $cache = yii::$app->cache;
        $key = 'dir-full-route';
        $idKey = $dir_id.'_'.
        if(isset($cache[$key]) && isset($cache[$key][$id])){
            $data = $cache[$key][$id];
        }else {
            $data = self::getFullRoute($id);
            if(!isset($cache[$key])){
                $arr = [$id => $data];
            }else{
                $arr = ArrayHelper::merge($cache[$key],[$id => $data]);
            }
            $cache[$key] = $arr;
        }
        return $data;
    }*/

    private static function expandPermissionType($type){
        $arr = [$type];
        if(in_array($type,[self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT,self::PERMISSION_TYPE_ATTR_LIMIT_INDUSTRY])){
            $arr[] = self::PERMISSION_TYPE_NORMAL;
        }elseif(in_array($type,[self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT_INDUSTRY])){
            $arr[] = self::PERMISSION_TYPE_NORMAL;
            $arr[] = self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT;
            $arr[] = self::PERMISSION_TYPE_ATTR_LIMIT_INDUSTRY;
        }
        return $arr;
    }

    /*
     * 获取目录拥有的最高权限
     */
    public static function getTopPermission($dir_id,$operation_id,$user=false,$ignoreAdmin=false){
        $permission = false;
        if(!$ignoreAdmin && Yii::$app->user->identity->isYunFrontendAdmin){
            $permission = self::PERMISSION_TYPE_NORMAL;
        }else{
            $pTypeNormal = self::isDirAllow($dir_id,self::PERMISSION_TYPE_NORMAL,$operation_id,$user,true);

            if($pTypeNormal){
                $permission = self::PERMISSION_TYPE_NORMAL;
            }else {
                $pTypeDistrict = self::isDirAllow($dir_id, self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT, $operation_id, $user, true);

                $pTypeIndustry = self::isDirAllow($dir_id, self::PERMISSION_TYPE_ATTR_LIMIT_INDUSTRY, $operation_id, $user, true);

                if($pTypeDistrict || $pTypeIndustry){
                    if($pTypeDistrict){
                        $permission = self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT;
                    }
                    if($pTypeIndustry){
                        $permission = $permission!=false?array_merge([self::PERMISSION_TYPE_ATTR_LIMIT_INDUSTRY],[$permission]):self::PERMISSION_TYPE_ATTR_LIMIT_INDUSTRY;
                    }
                }else{
                    $pTypeDistrictIndustry = self::isDirAllow($dir_id,self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT_INDUSTRY,$operation_id,$user,true);
                    if($pTypeDistrictIndustry){
                        $permission = self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT_INDUSTRY;
                    }
                }
            }
        }
        return $permission;
    }


    /*
     * 检测文件是否允许执行所选操作
     * 参数 dir_id : 目录ID
     * 参数 file_id : 文件ID  (dir_id 和 file_id 应该已经检验过 存在且 状态正常 此处不再做检测）
     * 参数 operation_id : 操作类型
     * 参数 user: 用户  默认为当前登录用户
     * 参数 ignoreAdmin: 是否忽略当前用户是管理员  默认是false(前台用）   后台用true
     */
    public static function isFileAllow($dir_id,$file_id,$operation_id,$user=false,$ignoreAdmin=false){
        $isAllow = false;
        if(!$ignoreAdmin && Yii::$app->user->identity->isYunFrontendAdmin){
            $isAllow = true;
        }else{
            if(self::isDirAllow($dir_id,self::PERMISSION_TYPE_NORMAL,$operation_id,$user,$ignoreAdmin)){
                $isAllow = true;
            }elseif(self::isDirAllow($dir_id,self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT,$operation_id,$user,$ignoreAdmin)){
                $isAllow = self::isFileAttributeAccorded($file_id,self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT,$user);
            }elseif(self::isDirAllow($dir_id,self::PERMISSION_TYPE_ATTR_LIMIT_INDUSTRY,$operation_id,$user,$ignoreAdmin)){
                $isAllow = self::isFileAttributeAccorded($file_id,self::PERMISSION_TYPE_ATTR_LIMIT_INDUSTRY,$user);
            }elseif(self::isDirAllow($dir_id,self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT_INDUSTRY,$operation_id,$user,$ignoreAdmin)){
                $isAllow = self::isFileAttributeAccorded($file_id,self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT_INDUSTRY,$user);
            }

            //查看目录的attr_limit  如果是0 则需要isDirAllow 的permissionType 为0  如果是1 则isDirAllow的perssiomType 为 0或1 都可以
//            $dir = Dir::find()->where(['id'=>$dir_id])->one();
//            $attr_limit = $dir->attr_limit;
//            $isDirAllow = false;
//            if($attr_limit == 0){
//                if(self::isDirAllow($dir_id,0,$operation_id,$user,$ignoreAdmin)){
//                    $isDirAllow = true;
//                }
//            }elseif($attr_limit == 1){
//                if(self::isDirAllow($dir_id,0,$operation_id,$user,$ignoreAdmin) || self::isDirAllow($dir_id,1,$operation_id,$user,$ignoreAdmin)){
//                    $isDirAllow = true;
//                }
//            }
//            if($isDirAllow){
//                $isAllow = self::isFileAttributeAccorded($file_id,$user);
//            }
        }
        return $isAllow;
    }


    /*
     * 检测文件的属性是否与属性限制条件相一致
     * 参数 file_id : 文件ID
     * 参数 user: 用户  默认为当前登录用户
     */
    public static function isFileAttributeAccorded($file_id,$attr_type,$user=false){
        $return = false;
        if($user===false)
            $user = Yii::$app->user->identity;



        if($attr_type==self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT || $attr_type==self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT_INDUSTRY){
            $districtReturn = false;
            $fileAttr = FileAttribute::find()->where(['file_id'=>$file_id,'attr_type'=>Attribute::TYPE_DISTRICT])->one();
            if($fileAttr){
                $attr = $fileAttr->attr_id;
                if($attr==Attribute::DISTRICT_DEFAULT || $attr==$user->district_id){
                    $districtReturn = true;
                }
            }
            $return = $districtReturn;

        }
        if($attr_type==self::PERMISSION_TYPE_ATTR_LIMIT_INDUSTRY || ($return && $attr_type==self::PERMISSION_TYPE_ATTR_LIMIT_DISTRICT_INDUSTRY)) {
            $industryReturn = false;
            $fileAttr = FileAttribute::find()->where(['file_id' => $file_id, 'attr_type' => Attribute::TYPE_INDUSTRY])->one();
            if ($fileAttr) {
                $attr = $fileAttr->attr_id;
                if ($attr == Attribute::INDUSTRY_DEFAULT || $attr == $user->industry_id) {
                    $industryReturn = true;
                }
            }
            $return = $industryReturn;
        }


        return $return;
    }

    /*
     * 获取 权限列表
     */
    public static function getPmList($p_id=0){
        //$ids = DirFunc::getChildrens($p_id);
        $arr = [];
        $list = DirPermission::find()->all();
        foreach($list as $l){
            $arr[$l->dir_id][] = $l->attributes;
        }
        return $arr;
    }


//    public static function hasPermissionType($dir_id,$operation_id,$user){
//        $isNormal = self::isDirAllow($dir_id,DirPermission::PERMISSION_TYPE_NORMAL,$operation_id,$user);
//        if($isNormal){
//            return DirPermission::PERMISSION_TYPE_NORMAL;
//        }else{
//            $isAttrLimit = self::isDirAllow($dir_id,DirPermission::PERMISSION_TYPE_ATTR_LIMIT,$operation_id,$user);
//            if($isAttrLimit){
//                return DirPermission::PERMISSION_TYPE_ATTR_LIMIT;
//            }else{
//                return false;
//            }
//        }
//
//    }

}