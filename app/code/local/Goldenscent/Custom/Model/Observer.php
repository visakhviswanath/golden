<?php
class Goldenscent_Custom_Model_Observer
{
  public function savePartner(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();
        $order->setPartner("ifconfig");

    }		
}
