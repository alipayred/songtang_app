<?php
    use oa\modules\admin\components\AdminFunc;
    //手动引入bootstrap.js
    //**由于有可能没有调用任何bootstrap组件   **使用Asset依赖注册不会重复引入js文件
    yii\bootstrap\BootstrapPluginAsset::register($this);
?>
<div class="side-nav">
    <div class="side-head">
        <?=Yii::$app->name?> 后台管理
    </div>
    <div class="side-head2">
        欢迎光临，<?=Yii::$app->user->identity->name?>
    </div>
    <ul class="nav nav-pills nav-stacked">
        <li class="menu-single <?=$this->context->id=='default'?'active':''?>">
            <a href="<?=AdminFunc::adminUrl('/')?>">
                <span class="menu-icon glyphicon glyphicon-home"></span>
                仪表盘
            </a>
        </li>
        <li class="menu-single <?=$this->context->id=='news'?'active':''?>">
            <a href="<?=AdminFunc::adminUrl('news')?>">
                <span class="menu-icon glyphicon glyphicon-th-large"></span>
                首页新闻
            </a>
        </li>
        <li class="menu-single <?=$this->context->id=='recruitment'?'active':''?>">
            <a href="<?=AdminFunc::adminUrl('recruitment')?>">
                <span class="menu-icon glyphicon glyphicon-user"></span>
                招聘信息
            </a>
        </li>
        <li class="menu-single <?=$this->context->id=='dir'?'active':''?>">
            <a href="<?=AdminFunc::adminUrl('dir')?>">
                <span class="menu-icon glyphicon glyphicon-list"></span>
                板块目录
            </a>
        </li>

        <li class="menu-list <?=$this->context->id=='permission'?'nav-active':''?>">
            <a href="javascript:void(0);" class="<?=$this->context->id=='permission'?'':'collapsed'?>">
                <span class="menu-icon glyphicon glyphicon-hdd"></span>
                目录权限
                <span class="sub-menu-collapsed glyphicon glyphicon-plus"></span>
                <span class="sub-menu-collapsed-in glyphicon glyphicon-minus"></span>
            </a>

            <ul class="sub-menu-list collapse <?=$this->context->id=='permission'?'in':''?>" id="system-collapse">
                <li class="<?=$this->context->id=='permission' && substr($this->context->action->id,0,4)=='user' && substr($this->context->action->id,0,10)!='user-group'?'active':''?>">
                    <a href="<?=AdminFunc::adminUrl('permission/user')?>">
                        用户
                    </a>
                </li>
                <li class="<?=$this->context->id=='permission' && substr($this->context->action->id,0,10)=='user-group'?'active':''?>">
                    <a href="<?=AdminFunc::adminUrl('permission/user-group')?>">
                        用户组
                    </a>
                </li>
                <li class="<?=$this->context->id=='permission' && $this->context->action->id=='check'?'active':''?>">
                    <a href="<?=AdminFunc::adminUrl('permission/check')?>">
                        权限检验
                    </a>
                </li>
            </ul>
        </li>
        <li class="menu-single <?=$this->context->id=='cache'?'active':''?>">
            <a href="<?=AdminFunc::adminUrl('cache')?>">
                <span class="menu-icon glyphicon glyphicon-list"></span>
                缓存管理
            </a>
        </li>
        <li class="menu-single">
            <a href="<?=Yii::$app->params['logoutUrl']?>">
                <span class="menu-icon glyphicon glyphicon-log-out"></span>
                退出
            </a>
        </li>
    </ul>
    <?=$this->render('../../../../../ucenter/views/layouts/app_entry',['current'=>'yunBackendAdmin'])?>
</div>
