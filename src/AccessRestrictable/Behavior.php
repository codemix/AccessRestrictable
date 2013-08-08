<?php
namespace AccessRestrictable;

use \Yii;
use \CActiveRecordBehavior;

/**
 * This behavior adds permission checks to every read and write operation.
 *
 * For read operations it will restrict the query results to allowed records only.
 * Write operations will fail for users without permission.
 */
class Behavior extends CActiveRecordBehavior
{
    /**
     * @var bool whether to automatically restrict read and write operations.
     * This is `true` by default. If disabled you can still use the `readable()`
     * and `writeable()` methods to restrict read and write operations respectively.
     */
    public $enableRestriction = true;

    /**
     * @var bool whether to perform an unrestricted read operation
     */
    protected $_unrestricted = false;

    /**
     * @var bool whether to restrict write operations when `enableRestriction` is `false`
     */
    protected $_writeable = false;

    /**
     * Named scope that disables access restriction for a read operation. For use when
     * `enableRestriction` is `true`.
     *
     * @return \CActiveRecord the model
     */
    public function unrestricted()
    {
        $this->_unrestricted = true;
        return $this->owner;
    }

    /**
     * Named scope that applies the access control condition to the current criteria.
     * For use when `enableRestriction` is `false`.
     *
     * @return \CActiveRecord the model
     */
    public function readable()
    {
        if(method_exists($this->owner, 'beforeRead')) {
            $value      = $this->owner->beforeRead();
            $criteria   = $this->owner->getDbCriteria();
            if($value===false) {
                $criteria->addCondition('0');
            } elseif(($value instanceof \CDbCriteria) || is_array($value)) {
                $criteria->mergeWith($value);
            }
        }
        return $this->owner;
    }

    /**
     * Disable access restriction for write operations. For use when `enableRestriction` is `true`.
     *
     * @return \CActiveRecord the record to save
     */
    public function force()
    {
        $this->_unrestricted = true;
        return $this->owner;
    }

    /**
     * Enable write permission check. For use when `enableRestriction` is `false`.
     *
     * @return \CActiveRecord the record to save
     */
    public function writeable()
    {
        $this->_writeable = true;
        return $this->owner;
    }

    /**
     * @inheritDoc
     */
    public function beforeFind($event)
    {
        if($this->enableRestriction && !$this->_unrestricted) {
            $this->readable();
        }
    }

    /**
     * @inheritDoc
     */
    public function beforeCount($event)
    {
        if($this->enableRestriction && !$this->_unrestricted) {
            $this->readable();
        }
    }

    /**
     * @param CEvent $event the event object
     */
    public function beforeSave($event)
    {
        if($this->_writeable || $this->enableRestriction && !$this->_unrestricted) {
            $this->applyWriteRestriction($event);
        }
    }

    /**
     * @param CEvent $event the event object
     */
    public function beforeDelete($event)
    {
        if($this->_writeable || $this->enableRestriction && !$this->_unrestricted) {
            $this->applyWriteRestriction($event);
        }
    }

    /**
     * @param CEvent $event the event object
     */
    protected function applyWriteRestriction($event)
    {
        if(method_exists($this->owner, 'beforeRead')) {
            $event->isValid = $this->owner->beforeWrite();
        }
    }
}
