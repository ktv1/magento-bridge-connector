<?php

namespace Api2cart\BridgeConnector\Controller\Adminhtml\bridgeconnector;

class Index extends \Magento\Backend\App\Action
{
  /**
   * Index Action*
   * @return void
   */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
