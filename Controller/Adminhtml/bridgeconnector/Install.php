<?php

namespace KTV\BridgeConnector\Controller\Adminhtml\bridgeconnector;

class Install extends \Magento\Backend\App\Action
{

  /**
   * Index Action*
   * @return void
   */
    public function execute()
    {
        $worker = new \KTV\BridgeConnector\Model\Worker;

        try {
            $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(\Zend_Json::encode(['error' => null, 'result' => $worker->installBridge()]));
        } catch (\Exception $e) {
            $worker->removeBridge();
            $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(\Zend_Json::encode(['error' => $e->getMessage(), 'result' => false]));
        }
    }
}
