<?php
/**
 * Commerce Stock Notifier plugin for Craft CMS 3.x
 *
 * Notifies by email when a Craft Commerce site is running low on stock
 *
 * @link      https://bluemantis.com
 * @copyright Copyright (c) 2020 Bluemantis
 */

namespace bluemantis\commercestocknotifier\models;

use bluemantis\commercestocknotifier\CommerceStockNotifier;

use Craft;
use craft\base\Model;

/**
 * @author    Bluemantis
 * @package   CommerceStockNotifier
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $toEmail = '';

    public $threshold = 2;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['threshold', 'number'],
        ];
    }
}
