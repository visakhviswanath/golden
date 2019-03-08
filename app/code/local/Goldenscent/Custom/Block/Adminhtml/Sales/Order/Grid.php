<?php

class Goldenscent_Custom_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{

    protected function _prepareCollection() {
        //$collection = parent::_prepareCollection()->getCollection();
        //$collection->addFieldToSelect('partner');
        //return $this;
        
            //$collection = Mage::getResourceModel($this->_getCollectionClass());
            //$collection->addFieldToSelect('partner');
    //$this->setCollection($collection);
    //return parent::_prepareCollection();
    
    $collection = Mage::getResourceModel($this->_getCollectionClass());
    $collection->addFieldToSelect('partner');
    $this->setCollection($collection);
    return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('partner', array(
            'header'=> Mage::helper('sales')->__('Partner Name'),
            'width' => '80px',
            'type'  => 'text',
            'index' => 'partner',
        ));

        return parent::_prepareColumns();
    }
}