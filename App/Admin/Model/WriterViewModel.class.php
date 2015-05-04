<?php
namespace Admin\Model;
use Think\Model\ViewModel;

class WriterViewModel extends ViewModel{
    public $viewFields = array(
      'writer'=>array('writerid','writername','summary','dynastyid'),
    );
}