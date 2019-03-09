<?php
class Goldenscent_Custom_Model_Observer
{
    public function savePartner(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();
        $name="partner";
        $partnerName=Mage::getModel('core/cookie')->get($name);
        $order->setPartner($partnerName);
        Mage::getModel('core/cookie')->delete($name);
    }		
}
