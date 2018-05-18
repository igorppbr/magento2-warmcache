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

namespace Igorludgero\WarmCache\Block\Adminhtml;

use Magento\Backend\Block\Cache as OriginalCache;

class Cache extends OriginalCache
{
    /**
     * Cache block constructor.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->buttonList->add(
            'warm_cache',
            [
                'label' => __('Run Warm Cache'),
                'onclick' => "window.location.href = '".$this->getWarmCacheUrl()."'",
                'class' => 'run-warm-cache'
            ]
        );
    }

    /**
     * Get warm cache action url.
     * @return string
     */
    private function getWarmCacheUrl()
    {
        return $this->getUrl('warmcache/WarmCache/run');
    }
}
