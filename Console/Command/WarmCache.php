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

use Igorludgero\WarmCache\Helper\Data;
use Magento\Framework\App\State;
use Magento\Framework\App\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WarmCache extends Command
{
    /**
     * @var State
     */
    protected $appState;

    /**
     * WarmCache constructor.
     * @param State $appState
     */
    public function __construct(
        State $appState
    ) {
        $this->appState = $appState;
        parent::__construct('igorludgero:warmcache');
    }

    /**
     * Configure cli command.
     */
    protected function configure()
    {
        $this->setName('igorludgero:warmcache')
            ->setDescription('Run the warm cache and cache all available pages in the store.');
    }

    /**
     * Execute cli command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this|int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->appState->setAreaCode('adminhtml');
        /**
         * @var $helper Data
         */
        $helper = ObjectManager::getInstance()->create(Data::class);
        if ($helper->run()) {
            $helper->logMessage("Warm cache process finished.");
            $output->writeln('Warm cache process finished.');
        } else {
            $output->writeln('Was not possible to run the command, please try again later. '.
            'Check if the extension is enabled on admin and if you enabled at least one warm cache type.');
        }
        return $this;
    }
}
