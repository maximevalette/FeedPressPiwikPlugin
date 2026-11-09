<?php
/**
 * FeedPress Matomo Plugin
 *
 * @link https://feedpress.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FeedPress\Widgets;

use Piwik\Widget\WidgetConfig;

/**
 * Dashboard widget. Rendered by Controller::subscribersWidget(), which is
 * the action derived from this class name.
 */
class SubscribersWidget extends \Piwik\Widget\Widget
{
    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('General_Visitors');
        $config->setName('FeedPress_WidgetName');
        $config->setOrder(99);
    }
}
