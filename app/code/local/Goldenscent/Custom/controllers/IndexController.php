<?php
require_once 'Mage/Cms/controllers/IndexController.php';
class Goldenscent_Custom_IndexController extends Mage_Cms_IndexController
{
    public function indexAction($coreRoute = null)
    {
        $value = $this->getRequest()->getParam('partner');
        $name="partner";
        Mage::getModel('core/cookie')->delete($name);
        $period=86400;
        Mage::getModel('core/cookie')->set($name, $value,$period);
        $partnerName=Mage::getModel('core/cookie')->get($name);
        $pageId = Mage::getStoreConfig(Mage_Cms_Helper_Page::XML_PATH_HOME_PAGE);
        if (!Mage::helper('cms/page')->renderPage($this, $pageId)) { 
            $this->_forward('defaultIndex');
        }
    }  
}
