<?php
/**
 * Commerce Stock Notifier plugin for Craft CMS 3.x
 *
 * Notifies by email when a Craft Commerce site is running low on stock
 *
 * @link      https://bluemantis.com
 * @copyright Copyright (c) 2020 Bluemantis
 */

namespace bluemantis\commercestocknotifier;

use bluemantis\commercestocknotifier\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;

use craft\mail\Message;
use yii\base\Event;

/**
 * Class CommerceStockNotifier
 *
 * @author    Bluemantis
 * @package   CommerceStockNotifier
 * @since     1.0.0
 *
 */
class CommerceStockNotifier extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var CommerceStockNotifier
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * @var bool
     */
    public $hasCpSection = false;

    /**
     * @var array
     */
    protected $orderProductStockLevels = [];
    protected $lowStockVariants = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            Order::class,
            Order::EVENT_BEFORE_COMPLETE_ORDER,
            function (Event $event) {
                // @var Order $order
                $order = $event->sender;

                foreach ($order->getLineItems() as $lineItem) {
                    $variant = $lineItem->getPurchasable();

                    if (!$variant) {
                        continue;
                    }

                    if ($variant instanceof Variant && !$variant->hasUnlimitedStock && $variant->stock > $this->getSettings()->threshold) {
                        $this->orderProductStockLevels[$variant->id] = $variant->stock;
                    }
                }
            }
        );

        Event::on(
            Order::class,
            Order::EVENT_AFTER_COMPLETE_ORDER,
            function (Event $e) {
                // @var Order $order
                $order = $e->sender;

                foreach ($order->getLineItems() as $lineItem) {
                    $variant = $lineItem->getPurchasable();

                    if (!$variant->id) {
                        continue;
                    }

                    $this->lowStockVariants = [];
                    $threshold = $this->getSettings()->threshold;

                    if (isset($this->orderProductStockLevels[$variant->id])) {
                        // see if the original stock has moved from being above the threshold to
                        // below the threshold, and send an email.
                        if ($this->orderProductStockLevels[$variant->id] > $threshold && $variant->stock <= $threshold) {
                            $this->lowStockVariants[$variant->id] = $variant;
                        }
                    }

                    if (count($this->lowStockVariants) > 0) {
                        $this->sendEmail();
                    }

                }
            });

        Craft::info(
            Craft::t(
                'commerce-stock-notifier',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    private function sendEmail()
    {
        $variants = $this->lowStockVariants;

        if (empty($this->getSettings()->toEmail)) {
            return false;
        }

        // Split the email recipient string
        $recipients = explode(',',str_replace(';', ',', $this->getSettings()->toEmail));

        $body = "Hi, this is a notification that the following items stock has fallen below the threshold set to ".$this->getSettings()->threshold.":<br><br>";

        /** @var \craft\commerce\elements\Variant $variant */
        foreach ($variants as $variant)
        {
            $body .= "SKU: ".$variant->sku."<br>";
            $body .= "Description: ".$variant->getDescription()."<br>";
            $body .= "Stock Remaining: ".$variant->stock."<br>";
            $body .= "Edit Link: ".$variant->getCpEditUrl()."<br>";
        }

        $body .= "<br>";

        $settings = Craft::$app->projectConfig->get('email');
        $message = new Message();

        $message->setFrom([$settings['fromEmail'] => $settings['fromName']]);
        $message->setSubject(count($variants)." commerce products have dropped below the stock threshold.");
        $message->setHtmlBody($body);
        $message->setTextBody(str_replace('<br>', "\n", $body));

        foreach ($recipients as $recipient) {
            $message->setTo(trim($recipient));
            Craft::$app->mailer->send($message);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'commerce-stock-notifier/settings',
            [
                'settings' => $this->getSettings(),
            ]
        );
    }
}
