<?php
namespace Api2cart\BridgeConnector\Block\Adminhtml;

class BridgeConnector extends \Magento\Backend\Block\Template
{
    protected $_buttonClass;
    protected $_buttonText;
    protected $_storeKey;

    function _prepareLayout()
    {
    }

    public function _construct()
    {
        parent::_construct();

        $worker = new \Api2cart\BridgeConnector\Model\Worker;
        $this->_storeKey = $worker->readStoreKey();

        if ($worker->getBridgeStatus() === 3) {
            $this->_buttonClass = 'btn-disconnect';
            $this->_buttonText = 'Uninstall connector';
        } else {
            $this->_buttonClass = 'btn-connect';
            $this->_buttonText = 'Install Connector';
        }
    }

    public function getStoreKey()
    {
        return $this->_storeKey;
    }

    public function getButtonText()
    {
        return $this->_buttonText;
    }

    public function getButtonClass()
    {
        return $this->_buttonClass;
    }

    public function getRouteUrl($route)
    {
        /** @var \Magento\Framework\App\ObjectManager $om */
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Backend\Model\UrlInterface $filesystem */
        $filesystem = $om->get('\Magento\Backend\Model\UrlInterface');

        return $filesystem->getRouteUrl($route);
    }
}
