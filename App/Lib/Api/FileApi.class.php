<?php

// namespace App\Api;

use App\Lib\Api\ApiBase;

class FileApi extends ApiBase
{

    public function post () {
        $prefix = I('post.prefix', '');

        if ($prefix) {
            $prefix = $prefix . '/';
        }

        //如果有文件上传 上传附件
        import('@.ORG.UploadFile');
        //导入上传类
        $upload = new UploadFile();
        //设置上传文件大小
        $upload->maxSize = 20000000;
        //设置附件上传目录
        $dirname = UPLOAD_PATH . $prefix . date('Ym', time()).'/'.date('d', time()).'/';
        
        $defaultinfo = unserialize(C('defaultinfo'));
        $upload->allowExts  = explode(',', $defaultinfo['allow_file_type']);// 设置附件上传类型
        
        if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
            $this->ajaxReturn(null, -1, 'ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE');
        }
        $upload->savePath = $dirname;
        if(!$upload->upload()) {// 上传错误提示错误信息
            $this->ajaxReturn(null, -1, $upload->getErrorMsg());
        }else{// 上传成功 获取上传文件信息
            $info =  $upload->getUploadFileInfo();
        }

        $m_file = M('File');
        foreach($info as &$file){
            $data['name'] = $file['name'];
            $data['file_path'] = $file['savepath'].$file['savename'];
            $data['role_id'] = $this->user()['role_id'];
            $data['size'] = $file['size'];
            $data['create_date'] = time();
            $file_id = $m_file->add($data);
            if ($file_id === false) {
                $this->ajaxReturn(null, -1, L('SAVE_FAIL'));
            }
            $file['file_id'] = $file_id;
        }
        $this->ajaxReturn($info, 0, L('ADD_ATTACHMENTS_SUCCESS'));
    }

    public function upload(){

        $m_config = M('config');
        //如果有文件上传 上传附件
        import('@.ORG.UploadFile');
        //导入上传类
        $upload = new UploadFile();
        //设置上传文件大小
        $upload->maxSize = 20000000;
        //设置附件上传目录
        $dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';

        $defaultinfo = $m_config->where('name = "defaultinfo"')->find();
        $value = unserialize($defaultinfo['value']);
        $upload->allowExts  = explode(',', $value['allow_file_type']);// 设置附件上传类型

        if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
            $this->ajaxReturn(null, -1, 'ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE');
        }
        $upload->savePath = $dirname;
        if(!$upload->upload()) {// 上传错误提示错误信息
            $this->ajaxReturn(null, -1, $upload->getErrorMsg());
        }else{// 上传成功 获取上传文件信息
            $info =  $upload->getUploadFileInfo();
        }
        $m_file = M('File');
        $r_file_module = M($_GET['r']);
        $module = $_GET['module'];
        $m_id = $_GET['id'];
        foreach($info as $value){
            $data['name'] = $value['name'];
            $data['file_path'] = $value['savepath'].$value['savename'];
            $data['role_id'] = $_SESSION['role_id'] ?? $this->user['role_id'];
            $data['size'] = $value['size'];
            $data['create_date'] = time();
            if($file_id = $m_file->add($data)){
                $temp['file_id'] = $file_id;
                $temp[$module . '_id'] = $m_id;
                if(0 >= $r_file_module->add($temp)){
                    $this->ajaxReturn(null, -1, L('ADD_FAILURE_PARTS_ACCESSORIES'));
                }
            }else{
                $this->ajaxReturn(null, -1, L('ADD_ATTACHMENTS_FAIL'));
            }
        }
        $this->ajaxReturn(null, 0, L('ADD_ATTACHMENTS_SUCCESS'));
    }

}