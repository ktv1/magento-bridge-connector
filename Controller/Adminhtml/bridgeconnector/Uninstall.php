<?php

namespace Api2cart\BridgeConnector\Controller\Adminhtml\bridgeconnector;

class Uninstall extends \Magento\Backend\App\Action
{

  /**
   * Index Action*
   * @return void
   */
    public function execute()
    {
        $worker = new \Api2cart\BridgeConnector\Model\Worker;

        try {
            $worker->removeBridge();
            $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(\Zend_Json::encode(['error' => null, 'result' => true]));
        } catch (\Exception $e) {
            $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(\Zend_Json::encode(['error' => $e->getMessage(), 'result' => false]));
        }
    }
}
