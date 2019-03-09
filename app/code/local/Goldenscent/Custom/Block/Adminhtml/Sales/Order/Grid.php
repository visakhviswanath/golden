<?php

class Goldenscent_Custom_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{
    
    protected function _prepareCollection() {
        $collection = parent::_prepareCollection()->getCollection();
        $collection->addFieldToSelect('partner');    
        return $this;
    }
      public function setCollection($collection)
    {
             $collection->join(['order' => 'sales/order'], 'main_table.entity_id=order.entity_id',['partner' => 'order.partner']);

        parent::setCollection($collection);
    }

    protected function _prepareColumns()
    {

        
        $this->addColumn('partner', array(
            'header'    => $this->__('Partner Name'),
            'width'     => '75px',
            'index'     => 'partner',
            'filter_index' => 'order.partner',
        ));

        $this->addColumnsOrder('partner','entity_id');
        return parent::_prepareColumns();    
    }
}