<?php
require_once 'Mage/Adminhtml/controllers/Sales/Order/ShipmentController.php';
class Goldenscent_Custom_Adminhtml_Sales_Order_ShipmentController extends Mage_Adminhtml_Sales_Order_ShipmentController
{

    public function saveAction()
    {
        $isNeedCreateLabel = isset($data['create_shipping_label']) && $data['create_shipping_label'];

        if ($isNeedCreateLabel) return parent::saveAction();

        $data = $this->getRequest()->getPost('shipment');
        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }

        try {
            $shipment = $this->_initShipment();
            if (!$shipment) {
                $this->_forward('noRoute');
                return;
            }

            $comment = '';
            if (!empty($data['comment_text'])) {
                $shipment->addComment(
                    $data['comment_text'],
                    isset($data['comment_customer_notify']),
                    isset($data['is_visible_on_front'])
                );
                if (isset($data['comment_customer_notify'])) {
                    $comment = $data['comment_text'];
                }
            }

            $shipmentCreatedMessage = $this->__('The shipment has been created.');
            $labelCreatedMessage = $this->__('The shipping label has been created.');

            $shipments = $this->_initShipmentGroups($shipment);

            foreach ($shipments as $shipment) {
                $shipment->register();
                if (!empty($data['send_email'])) {
                    $shipment->setEmailSent(true);
                }
                $shipment->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));

                $this->_saveShipment($shipment);
                $shipment->sendEmail(!empty($data['send_email']), $comment);

                $this->_getSession()->addSuccess($isNeedCreateLabel ? $shipmentCreatedMessage . ' ' . $labelCreatedMessage
                    : $shipmentCreatedMessage);
                Mage::getSingleton('adminhtml/session')->getCommentText(true);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('Cannot save shipment.'));
            $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));
        }
        $this->_redirect('*/sales_order/view', array('order_id' => $shipment->getOrderId()));
    }


    protected function _initShipmentGroups(Mage_Sales_Model_Order_Shipment $shipment)
    {        
            $qtys = $this->_getItemQtys();
            $allItems = array();
            $order = $shipment->getOrder();
            $items = $shipment->getOrder()->getItemsCollection();
            foreach ($qtys as $itemId => $qty) {
                while ($qty--) $allItems[] = $itemId;
            }

            $groups = array();
            $i = 0;

            while (count($allItems) > 0) {
                $i++;
                $groups[$i] = array(
                    'items' => array(),
                );
                foreach ($allItems as $index => $itemId) {
                    echo $itemId; echo "<br/>";
                }

                if (count($groups[$i]['items']) == 0) {
                    $groups[$i]['items'][] = $itemId;
                    unset($allItems[$index]);
                }
            }

            $shipments = array();
            if($order->getPartner()!=""){
                if (count($groups) > 1) {

                    $ceilvalue = (count($groups)/2);
                    $half1 = ceil($ceilvalue);
                    $half2 = count($groups)-$half1;
                    if($half1>0 && $half2 >0)
                    {
                                foreach ($groups as $data) {
                                $groupItems = array();
                                $groupItems2 = array();
                                foreach ($data['items'] as $itemId) {
                                        $groupItems[$itemId] = $half1;
                                        $groupItems2[$itemId] = $half2;
                                }
                            }
                            $shipments[] = Mage::getModel('sales/service_order', $order)->prepareShipment($groupItems);
                            $shipments[] = Mage::getModel('sales/service_order', $order)->prepareShipment($groupItems2); 
                    }

                } 
            }
            
            else {
                $shipments[] = $shipment;
            }
            return $shipments;

    }
}