<?php
class Goldenscent_Custom_Model_Observer
{

			public function savePartner(Varien_Event_Observer $observer)
			{
                            
                                                       $event = $observer->getEvent();
                            $order = $event->getOrder();
        //$fieldVal = Mage::app()->getFrontController()->getRequest()->getParams();
        $order->setPartner("visakh");
				//Mage::dispatchEvent('admin_session_user_login_success', array('user'=>$user));
				//$user = $observer->getEvent()->getUser();
				//$user->doSomething();
			}
		
}
