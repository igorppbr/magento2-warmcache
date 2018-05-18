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

namespace Igorludgero\WarmCache\Helper;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Url;
use Magento\Store\Model\ScopeInterface;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use Zend\Log\Logger;

class Data extends AbstractHelper
{

    /**
     * @var array
     */
    protected $urls = array();

    /**
     * @var ProductFactory
     */
    protected $productModel;

    /**
     * @var CategoryFactory
     */
    protected $categoryModel;

    /**
     * @var PageFactory
     */
    protected $pageModel;

    /**
     * @var UrlRewriteFactory
     */
    protected $urlRewriteModel;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Url
     */
    protected $frontUrlModel;

    /**
     * @var WriterInterface
     */
    protected $writerInterface;

    /**
     * Data constructor.
     * @param Context $context
     * @param ProductFactory $productModel
     * @param CategoryFactory $categoryModel
     * @param PageFactory $pageModel
     * @param UrlRewriteFactory $urlRewriteModel
     * @param Url $frontUrlModel
     * @param WriterInterface $writerInterface
     */
    public function __construct(
        Context $context,
        ProductFactory $productModel,
        CategoryFactory $categoryModel,
        PageFactory $pageModel,
        UrlRewriteFactory $urlRewriteModel,
        Url $frontUrlModel,
        WriterInterface $writerInterface
    ) {
        parent::__construct($context);
        $this->productModel = $productModel;
        $this->categoryModel = $categoryModel;
        $this->pageModel = $pageModel;
        $this->urlRewriteModel = $urlRewriteModel;
        $this->frontUrlModel = $frontUrlModel;
        $this->writerInterface = $writerInterface;
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/igorludgero_warmcache.log');
        $this->logger = new \Zend\Log\Logger();
        $this->logger->addWriter($writer);
    }

    /**
     * Log a custom message.
     * @param $message
     */
    public function logMessage($message)
    {
        $this->logger->info($message);
    }

    /**
     * Get all urls to be cached.
     */
    private function getUrls()
    {
        //Add Products url
        if ($this->scopeConfig->getValue('warmcache/settings/product', ScopeInterface::SCOPE_STORE)) {
            $_productCollection = $this->productModel->create()->getCollection()
                ->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addAttributeToFilter(
                    'visibility',
                    array(Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH)
                )
                ->addAttributeToSelect(["entity_id"]);
            foreach ($_productCollection as $_product) {
                $url = $this->frontUrlModel->getUrl("catalog/product/view", ['id' => $_product->getId()]);
                if (!in_array($url, $this->urls)) {
                    $this->urls[] = $url;
                }
            }
        }

        //Add category url
        if ($this->scopeConfig->getValue('warmcache/settings/category', ScopeInterface::SCOPE_STORE)) {
            $_categoryCollection = $this->categoryModel->create()->getCollection()
                ->addAttributeToFilter('is_active', 1)
                ->addAttributeToSelect(["entity_id"]);
            foreach ($_categoryCollection as $_category) {
                $url = $this->frontUrlModel->getUrl("catalog/category/view", ['id' => $_category->getId()]);
                if (!in_array($url, $this->urls)) {
                    $this->urls[] = $url;
                }
            }
        }

        //Add cms pages
        if ($this->scopeConfig->getValue('warmcache/settings/cms_pages', ScopeInterface::SCOPE_STORE)) {
            $_cmsPageCollection = $this->pageModel->create()->getCollection()->addFieldToFilter("is_active", 1)
                ->addFieldToSelect("page_id");
            foreach ($_cmsPageCollection as $page) {
                $url = $this->frontUrlModel->getUrl("cms/page/view", ['id' => $page->getId()]);
                if (!in_array($url, $this->urls)) {
                    $this->urls[] = $url;
                }
            }
        }

        //Custom urls in url rewrite.
        if ($this->scopeConfig->getValue('warmcache/settings/url_rewrite', ScopeInterface::SCOPE_STORE)) {
            $_urlRewriteCollection = $this->urlRewriteModel->create()
                ->getCollection()
                ->addFieldToSelect("target_path")
                ->addFieldToFilter('entity_type', array('nin' => array('cms-page', 'category', 'product')));
            foreach ($_urlRewriteCollection as $urlRewrite) {
                $newUrl = $this->frontUrlModel->getBaseUrl() . $urlRewrite->getRequestPath();
                if (!in_array($newUrl, $this->urls)) {
                    $this->urls[] = $newUrl;
                }
            }
        }
    }

    /**
     * Run the crawler.
     */
    public function run()
    {
        if ($this->isEnabled()) {
            $this->getUrls();
            if (count($this->urls) > 0) {
                try {
                    foreach ($this->urls as $url) {
                        $this->checkUrl($url);
                    }
                    return true;
                } catch (\Exception $ex) {
                    $this->logMessage("Error in WarmCache: " . $ex->getMessage());
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * Render the url.
     * @param $url
     */
    private function checkUrl($url)
    {
        $user_agent='Mozilla/4.0 (compatible;)';

        $options = array(

            CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
            CURLOPT_POST           =>false,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 4,       // stop after 10 redirects
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $err     = curl_errno($ch);
        $errmsg  = curl_error($ch);
        $header  = curl_getinfo($ch);
        curl_close($ch);

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;
    }

    /**
     * Disable warm cache enable setting
     */
    public function disableExtension()
    {
        $this->writerInterface->save("warmcache/settings/enable", 0);
    }

    /**
     * Is the extension enabled
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->scopeConfig->getValue('warmcache/settings/enable', ScopeInterface::SCOPE_STORE);
    }
}
