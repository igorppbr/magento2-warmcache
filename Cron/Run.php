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

use Igorludgero\WarmCache\Helper\Data;

class Run
{

    /**
     * @var Data
     */
    protected $helper;

    /**
     * Run constructor.
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Run the warm cache process.
     * @return $this
     */
    public function execute()
    {
        $this->helper->run();
        return $this;
    }
}
