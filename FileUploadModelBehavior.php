<?php
/**
 * Created by PhpStorm.
 * User: Kurraz
 */

namespace kurraz\yii2_file_upload_model_behavior;


use yii\base\Behavior;
use yii\base\ModelEvent;
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

    /**
     * @param ModelEvent $event
     * @return bool
     */
    public function onBeforeSave($event)
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
                    if(!$this->owner->{$this->uploadedFileDbProp}) $this->owner->addError($this->uploadedFileDbProp,'Can\t upload file');
                }
                else
                {
                    $this->owner->addError($this->uploadedFileDbProp,$this->owner->getErrors($this->uploadedFileProp)[0]);
                }
            }else
            {
                $this->owner->{$this->uploadedFileDbProp} = $this->owner->getOldAttribute($this->uploadedFileDbProp);
            }
        }

        if($this->owner->hasErrors())
            $event->isValid = false;
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