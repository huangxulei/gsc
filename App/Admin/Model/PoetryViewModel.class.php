<?php
namespace Admin\Model;
use Think\Model\ViewModel;

class PoetryViewModel extends ViewModel{
    public $viewFields = array(
        'poetry'=>array('poetryid','kindid','typeid','writerid','title','content'),
        'writer'=>array('writername','_on'=>'writer.writerid=poetry.writerid'),
    );
}