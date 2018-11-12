<?php declare(strict_types=1);

namespace My\Tests;

use Lmc\Steward\ConfigProvider;
/**
 * Abstract class for custom tests, could eg. define some properties or instantiate some common components
 * using @before annotated methods.
 */
abstract class AbstractTestCase extends \Lmc\Steward\Test\AbstractTestCase
{
    /** @var string */
    public static $baseUrl;

    /** @var string */
    public static $adminUser;

    /** @var string */
    public static $adminPwd;

    /**
     * @before
     */
    public function initCredentials()
    {
        // Set base url according to environment
        switch (ConfigProvider::getInstance()->env) {
            case 'demo':
                self::$baseUrl = 'http://193.40.11.165/wordpress';
                break;
            case 'local':
                self::$baseUrl = 'http://localhost/wordpress';
                break;
            default:
                throw new \RuntimeException(sprintf('Unknown environment "%s"', ConfigProvider::getInstance()->env));
        }
    }
}