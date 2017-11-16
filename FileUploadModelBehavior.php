<?php
/**
 * Created by PhpStorm.
 * User: Kurraz
 */

namespace kurraz\yii2_file_upload_model_behavior;


use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use Yii;

class FileUploadModelBehavior extends Behavior
{
    public $uploadedFileProp;
    public $uploadedFileDeleteProp;
    public $uploadedFileDbProp;

    public function attach($owner)
    {
        parent::attach($owner);

        $this->owner->on(ActiveRecord::EVENT_BEFORE_INSERT, array($this, 'onBeforeSave'));
        $this->owner->on(ActiveRecord::EVENT_BEFORE_UPDATE, array($this, 'onBeforeSave'));
    }

    public function onBeforeSave()
    {
        if($this->owner->{$this->uploadedFileDeleteProp})
        {
            $this->delete_uploaded();
        }else
        {
            $this->owner->{$this->uploadedFileProp} = UploadedFile::getInstance($this->owner, $this->uploadedFileDbProp);

            if($this->owner->{$this->uploadedFileProp})
            {
                if($this->owner->validate())
                {
                    $this->delete_uploaded();
                    $this->owner->{$this->uploadedFileDbProp} = $this->upload();
                    if(!$this->owner->{$this->uploadedFileDbProp}) $this->owner->addError('image','Can\t upload file');
                }
                else
                {
                    $this->owner->addError($this->owner->{$this->uploadedFileDbProp},$this->owner->getErrors($this->owner->{$this->uploadedFileProp})[0]);
                }
            }else
            {
                $this->image = $this->owner->getOldAttribute($this->owner->{$this->uploadedFileDbProp});
            }
        }

        if($this->owner->hasErrors()) return false;

        return true;
    }

    private function upload()
    {
        if($this->owner->validate())
        {
            $filename = md5(microtime(true)) . '.' . $this->owner->{$this->uploadedFileProp}->extension;

            $this->owner->{$this->uploadedFileProp}->saveAs( Yii::getAlias('@upload/') . $filename);
            return $filename;
        }
        return false;
    }

    private function delete_uploaded()
    {
        @unlink(Yii::getAlias('@upload/') . $this->owner->getOldAttribute($this->owner->{$this->uploadedFileDbProp}));
        $this->owner->{$this->uploadedFileDbProp} = '';
    }
}