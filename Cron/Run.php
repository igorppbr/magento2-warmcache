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

namespace Igorludgero\WarmCache\Cron;

class Run
{

    /**
     * @var \Igorludgero\WarmCache\Helper\Data
     */
    protected $_helper;

    /**
     * Run constructor.
     * @param \Igorludgero\WarmCache\Helper\Data $helper
     */
    public function __construct(\Igorludgero\WarmCache\Helper\Data $helper)
    {
        $this->_helper = $helper;
    }

    /**
     * Run the warm cache process.
     * @return $this
     */
    public function execute()
    {

        $this->_helper->run();
        return $this;
    }

}