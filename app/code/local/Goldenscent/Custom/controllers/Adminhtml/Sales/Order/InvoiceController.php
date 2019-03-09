<?php
/**
 * Adminhtml sales order edit controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
require_once 'Mage/Adminhtml/controllers/Sales/Order/InvoiceController.php';
class Goldenscent_Custom_Adminhtml_Sales_Order_InvoiceController extends Mage_Adminhtml_Controller_Sales_Invoice
{

    protected function _initInvoice($update = false)
    {
        $this->_title($this->__('Sales'))->_title($this->__('Invoices'));

        $invoice = false;
        $itemsToInvoice = 0;
        $invoiceId = $this->getRequest()->getParam('invoice_id');
        $orderId = $this->getRequest()->getParam('order_id');
        if ($invoiceId) {
            $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);
            if (!$invoice->getId()) {
                $this->_getSession()->addError($this->__('The invoice no longer exists.'));
                return false;
            }
        } elseif ($orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            /**
             * Check order existing
             */
            if (!$order->getId()) {
                $this->_getSession()->addError($this->__('The order no longer exists.'));
                return false;
            }
            /**
             * Check invoice create availability
             */
            if (!$order->canInvoice()) {
                $this->_getSession()->addError($this->__('The order does not allow creating an invoice.'));
                return false;
            }
            
            
            $qtys = $this->_getItemQtys();
            $allItems = array();

            $items = $order->getItemsCollection();
     
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

            $invoices = array();
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
                            $invoices[] = Mage::getModel('sales/service_order', $order)->prepareInvoice($groupItems);
                            $invoices[] = Mage::getModel('sales/service_order', $order)->prepareInvoice($groupItems2); 
                    }

                } 
            }
            else {
                $invoices[] = $invoice;
            }
            return $invoices;
            
            
            
            
//            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice($savedQtys);
//            if (!$invoice->getTotalQty()) {
//                Mage::throwException($this->__('Cannot create an invoice without products.'));
//            }
        }

        //Mage::register('current_invoice', $invoice);
        //return $invoice;
    }
    
    protected function _getItemQtys()
    {
        $data = $this->getRequest()->getParam('invoice');
        if (isset($data['items'])) {
            $qtys = $data['items'];
        } else {
            $qtys = array();
        }
        return $qtys;
    }
    
    public function saveAction()
    {
        $data = $this->getRequest()->getPost('invoice');
        $orderId = $this->getRequest()->getParam('order_id');

        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }

        try {
            $invoices = $this->_initInvoice();
            if(!is_array($invoices)){           
                if ($invoices) {

                    if (!empty($data['capture_case'])) {
                        $invoices->setRequestedCaptureCase($data['capture_case']);
                    }

                    if (!empty($data['comment_text'])) {
                        $invoices->addComment(
                            $data['comment_text'],
                            isset($data['comment_customer_notify']),
                            isset($data['is_visible_on_front'])
                        );
                    }

                    $invoices->register();

                    if (!empty($data['send_email'])) {
                        $invoices->setEmailSent(true);
                    }

                    $invoices->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
                    $invoices->getOrder()->setIsInProcess(true);

                    $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($invoices)
                        ->addObject($invoices->getOrder());
                    $shipment = false;
                    if (!empty($data['do_shipment']) || (int) $invoices->getOrder()->getForcedDoShipmentWithInvoice()) {
                        $shipment = $this->_prepareShipment($invoices);
                        if ($shipment) {
                            $shipment->setEmailSent($invoice->getEmailSent());
                            $transactionSave->addObject($shipment);
                        }
                    }
                    $transactionSave->save();

                    if (isset($shippingResponse) && $shippingResponse->hasErrors()) {
                        $this->_getSession()->addError($this->__('The invoice and the shipment  have been created. The shipping label cannot be created at the moment.'));
                    } elseif (!empty($data['do_shipment'])) {
                        $this->_getSession()->addSuccess($this->__('The invoice and shipment have been created.'));
                    } else {
                        $this->_getSession()->addSuccess($this->__('The invoice has been created.'));
                    }

                    // send invoice/shipment emails
                    $comment = '';
                    if (isset($data['comment_customer_notify'])) {
                        $comment = $data['comment_text'];
                    }
                    try {
                        $invoice->sendEmail(!empty($data['send_email']), $comment);
                    } catch (Exception $e) {
                        Mage::logException($e);
                        $this->_getSession()->addError($this->__('Unable to send the invoice email.'));
                    }
                    if ($shipment) {
                        try {
                            $shipment->sendEmail(!empty($data['send_email']));
                        } catch (Exception $e) {
                            Mage::logException($e);
                            $this->_getSession()->addError($this->__('Unable to send the shipment email.'));
                        }
                    }
                    Mage::getSingleton('adminhtml/session')->getCommentText(true);
                    $this->_redirect('*/sales_order/view', array('order_id' => $orderId));
                } else {
                    $this->_redirect('*/*/new', array('order_id' => $orderId));
                }
            }else{
                foreach ($invoices as $invoice){
                
                    if ($invoice) {

                        if (!empty($data['capture_case'])) {
                            $invoice->setRequestedCaptureCase($data['capture_case']);
                        }

                        if (!empty($data['comment_text'])) {
                            $invoice->addComment(
                                $data['comment_text'],
                                isset($data['comment_customer_notify']),
                                isset($data['is_visible_on_front'])
                            );
                        }

                        $invoice->register();

                        if (!empty($data['send_email'])) {
                            $invoice->setEmailSent(true);
                        }

                        $invoice->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
                        $invoice->getOrder()->setIsInProcess(true);

                        $transactionSave = Mage::getModel('core/resource_transaction')
                            ->addObject($invoice)
                            ->addObject($invoice->getOrder());
                        $shipment = false;
                        if (!empty($data['do_shipment']) || (int) $invoice->getOrder()->getForcedDoShipmentWithInvoice()) {
                            $shipment = $this->_prepareShipment($invoice);
                            if ($shipment) {
                                $shipment->setEmailSent($invoice->getEmailSent());
                                $transactionSave->addObject($shipment);
                            }
                        }
                        $transactionSave->save();

                        if (isset($shippingResponse) && $shippingResponse->hasErrors()) {
                            $this->_getSession()->addError($this->__('The invoice and the shipment  have been created. The shipping label cannot be created at the moment.'));
                        } elseif (!empty($data['do_shipment'])) {
                            $this->_getSession()->addSuccess($this->__('The invoice and shipment have been created.'));
                        } else {
                            $this->_getSession()->addSuccess($this->__('The invoice has been created.'));
                        }

                        // send invoice/shipment emails
                        $comment = '';
                        if (isset($data['comment_customer_notify'])) {
                            $comment = $data['comment_text'];
                        }
                        try {
                            $invoice->sendEmail(!empty($data['send_email']), $comment);
                        } catch (Exception $e) {
                            Mage::logException($e);
                            $this->_getSession()->addError($this->__('Unable to send the invoice email.'));
                        }
                        if ($shipment) {
                            try {
                                $shipment->sendEmail(!empty($data['send_email']));
                            } catch (Exception $e) {
                                Mage::logException($e);
                                $this->_getSession()->addError($this->__('Unable to send the shipment email.'));
                            }
                        }
                        Mage::getSingleton('adminhtml/session')->getCommentText(true);
                        $this->_redirect('*/sales_order/view', array('order_id' => $orderId));
                    } else {
                            $this->_redirect('*/*/new', array('order_id' => $orderId));
                    }
                }
                
            }                       
            
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('Unable to save the invoice.'));
            Mage::logException($e);
        }
        $this->_redirect('*/*/new', array('order_id' => $orderId));
    }

}
