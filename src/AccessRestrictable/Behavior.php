<?php
namespace AccessRestrictable;

use \Yii;
use \CActiveRecordBehavior;

/**
 * This behavior allows to add a permission condition to every query so that only
 * records that the current user has access to will be returned.
 *
 * Note, that the condition is only applied if a `user` component is available
 */
class Behavior extends CActiveRecordBehavior
{
    /**
     * @var callable a callback that applies conditions for access restriction to the supplied
     * CDbCriteria. For example
     *
     *  function($criteria, $model) {
     *      $criteria->compare('user_id', Yii::app()->user->id);
     *  }
     *
     *  $model is the owner's model object as returned by CActiveRecord::model().
     */
    public $beforeAccessCheck;

    /**
     * @var bool whether to automatically apply the access restriction criteria to every query.
     * This is `true` by default. If disabled you can still use the `restricted()` scope explicitely.
     */
    public $enableRestriction = true;

    /**
     * @var bool whether to perform an unrestricted query
     */
    protected $_unrestricted = false;

    /**
     * Named scope that disables access restriction for a query.
     *
     * @return CActiveRecord the model
     */
    public function unrestricted()
    {
        $this->_unrestricted = true;
        return $this;
    }

    /**
     * Named scope that applies the access control condition to the current criteria.
     *
     * @return CActiveRecord the model
     */
    public function restricted()
    {
        if(Yii::app()->hasComponent('user') && is_callable($this->beforeAccessCheck)) {
            call_user_func($this->beforeAccessCheck, $this->owner->getDbCriteria(), $this->owner);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function beforeFind($event)
    {
        if($this->enableRestriction && !$this->_unrestricted) {
            $this->restricted();
        }
    }

    /**
     * @inheritDoc
     */
    public function beforeDelete($event)
    {
        if($this->enableRestriction && !$this->_unrestricted) {
            $this->restricted();
        }
    }
}
