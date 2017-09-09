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

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var array
     */
    protected $_urls = array();

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_productModel;

    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $_categoryModel;

    /**
     * @var \Magento\Cms\Model\Page
     */
    protected $_pageModel;

    /**
     * @var \Magento\UrlRewrite\Model\UrlRewrite
     */
    protected $_urlRewriteModel;

    /**
     * @var \Zend\Log\Logger
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\Url
     */
    protected $_frontUrlModel;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\Product $productModel
     * @param \Magento\Catalog\Model\Category $categoryModel
     * @param \Magento\Cms\Model\Page $pageModel
     * @param \Magento\UrlRewrite\Model\UrlRewrite $urlRewriteModel
     * @param \Magento\Framework\Url $frontUrlModel
     */
    public function __construct(\Magento\Framework\App\Helper\Context $context,
                                \Magento\Catalog\Model\Product $productModel,
                                \Magento\Catalog\Model\Category $categoryModel,
                                \Magento\Cms\Model\Page $pageModel,
                                \Magento\UrlRewrite\Model\UrlRewrite $urlRewriteModel,
                                \Magento\Framework\Url $frontUrlModel)
    {
        parent::__construct($context);
        $this->_productModel = $productModel;
        $this->_categoryModel = $categoryModel;
        $this->_pageModel = $pageModel;
        $this->_urlRewriteModel = $urlRewriteModel;
        $this->_frontUrlModel = $frontUrlModel;
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/igorludgero_warmcache.log');
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($writer);
        $this->getUrls();
    }

    /**
     * Log a custom message.
     * @param $message
     */
    public function logMessage($message){
        $this->_logger->info($message);
    }

    /**
     * Get all urls to be cached.
     */
    private function getUrls(){

        //Add Products url
        if($this->scopeConfig->getValue('warmcache/settings/product', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            $_productCollection = $this->_productModel->getCollection()
                ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
                ->addAttributeToFilter('visibility', array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH))
                ->addAttributeToSelect(["entity_id"]);
            foreach ($_productCollection as $_product) {
                $url = $this->_frontUrlModel->getUrl("catalog/product/view", ['id' => $_product->getId()]);
                if (!in_array($url, $this->_urls)) {
                    $this->_urls[] = $url;
                }
            }
        }

        //Add category url
        if($this->scopeConfig->getValue('warmcache/settings/category', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            $_categoryCollection = $this->_categoryModel->getCollection()
                ->addAttributeToFilter('is_active', 1)
                ->addAttributeToSelect(["entity_id"]);
            foreach ($_categoryCollection as $_category) {
                $url = $this->_frontUrlModel->getUrl("catalog/category/view", ['id' => $_category->getId()]);
                if (!in_array($url, $this->_urls)) {
                    $this->_urls[] = $url;
                }
            }
        }

        //Add cms pages
        if($this->scopeConfig->getValue('warmcache/settings/cms_pages', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            $_cmsPageCollection = $this->_pageModel->getCollection()->addFieldToFilter("is_active", 1)
                ->addFieldToSelect("page_id");
            foreach ($_cmsPageCollection as $page) {
                $url = $this->_frontUrlModel->getUrl("cms/page/view", ['id' => $page->getId()]);
                if (!in_array($url, $this->_urls)) {
                    $this->_urls[] = $url;
                }
            }
        }

        //Custom urls in url rewrite.
        if($this->scopeConfig->getValue('warmcache/settings/url_rewrite', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            $_urlRewriteCollection = $this->_urlRewriteModel->getCollection()
                ->addFieldToSelect("target_path")
                ->addFieldToFilter('entity_type', array('nin' => array('cms-page', 'category', 'product')));
            foreach ($_urlRewriteCollection as $urlRewrite) {
                $newUrl = $this->_frontUrlModel->getBaseUrl() . $urlRewrite->getRequestPath();
                if (!in_array($newUrl, $this->_urls)) {
                    $this->_urls[] = $newUrl;
                }
            }
        }

    }

    /**
     * Run the crawler.
     */
    public function run()
    {
        try {
            foreach ($this->_urls as $url) {
                $this->checkUrl($url);
            }
            return true;
        }
        catch (\Exception $ex){
            $this->logMessage("Error in WarmCache: ".$ex->getMessage());
            return false;
        }
    }

    /**
     * Render the url.
     * @param $url
     */
    function checkUrl( $url )
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

        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;
    }

}