<?php

/**
 * Warm Cache
 *
 * Provide a warm cache extension.
 *
 * @package Igorludgero\WarmCache
 * @author Igor Ludgero Miura <igor@igorludgero.com>
 * @copyright Copyright (c) 2017 Igor Ludgero Miura (https://www.igorludgero.com/)
 * @license https://opensource.org/licenses/OSL-3.0.php Open Software License 3.0
 */

namespace Igorludgero\WarmCache\Controller\Adminhtml\WarmCache;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Igorludgero\WarmCache\Helper\Data;

class Run extends Action
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * Run constructor.
     * @param Action\Context $context
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        Data $helper
    ) {
        parent::__construct($context);
        $this->_helper = $helper;
    }

    /**
     * Start the WarmCache.
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $this->_helper->run();
        $this->messageManager->addSuccessMessage(__("Warm cache ran successfully!"));
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Igorludgero_Warmcache::settings');
    }
}
