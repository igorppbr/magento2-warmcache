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

namespace Igorludgero\WarmCache\Console\Command;

class WarmCache extends \Symfony\Component\Console\Command\Command
{

    /**
     * @var \Igorludgero\WarmCache\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $_appState;

    /**
     * WarmCache constructor.
     * @param \Igorludgero\WarmCache\Helper\Data $helper
     */
    public function __construct(\Magento\Framework\App\State $appState,
                                \Igorludgero\WarmCache\Helper\Data $helper)
    {
        $this->_helper = $helper;
        $this->_appState = $appState;
        parent::__construct('igorludgero:warmcache');
    }

    /**
     * Configure cli command.
     */
    protected function configure()
    {
        $this->setName('igorludgero:warmcache')->setDescription('Run the warm cache and cache all available pages in the store.');
    }

    /**
     * Execute cli command.
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return $this
     */
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->_appState->setAreaCode('adminhtml');
        if($this->_helper->run()){
            $this->_helper->logMessage("Warm cache process finished.");
            $output->writeln('Warm cache process finished.');
        }
        else{
            $output->writeln('Was not possible to run the command, please try again later.');
        }
        return $this;
    }

}