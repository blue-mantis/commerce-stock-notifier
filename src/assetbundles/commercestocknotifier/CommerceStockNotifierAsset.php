<?php
/**
 * Commerce Stock Notifier plugin for Craft CMS 3.x
 *
 * Notifies by email when a Craft Commerce site is running low on stock
 *
 * @link      https://bluemantis.com
 * @copyright Copyright (c) 2020 Bluemantis
 */

namespace bluemantis\commercestocknotifier\assetbundles\commercestocknotifier;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Bluemantis
 * @package   CommerceStockNotifier
 * @since     1.0.0
 */
class CommerceStockNotifierAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@bluemantis/commercestocknotifier/assetbundles/commercestocknotifier/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/CommerceStockNotifier.js',
        ];

        $this->css = [
            'css/CommerceStockNotifier.css',
        ];

        parent::init();
    }
}
