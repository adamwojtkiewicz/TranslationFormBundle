<?php

/*
 * This file is part of the TranslationFormBundle package.
 *
 * (c) David ALLIX <http://a2lix.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace A2lix\TranslationFormBundle\Tests\Locale;

use A2lix\TranslationFormBundle\Locale\DefaultProvider;
use PHPUnit\Framework\TestCase;

/**
 * @author Gonzalo Vilaseca <gvilaseca@reiss.co.uk>
 */
class DefaultProviderTest extends TestCase
{
    protected $provider;
    protected $locales;
    protected $defaultLocale;
    protected $requiredLocales;

    protected function setUp(): void
    {
        $this->locales = ['es', 'en', 'pt'];
        $this->defaultLocale = 'en';
        $this->requiredLocales = ['es', 'en'];

        $this->provider = new DefaultProvider($this->locales, $this->defaultLocale, $this->requiredLocales);
    }

    public function testDefaultLocaleIsInLocales(): void
    {
        $classname = 'A2lix\TranslationFormBundle\Locale\DefaultProvider';

        $reflectedClass = new \ReflectionClass($classname);
        $provider = $reflectedClass->newInstanceWithoutConstructor();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Default locale `de` not found within the configured locales `[es,en]`');

        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke($provider, ['es', 'en'], 'de', []);
    }

    public function testRequiredLocaleAreInLocales(): void
    {
        $classname = 'A2lix\TranslationFormBundle\Locale\DefaultProvider';

        $reflectedClass = new \ReflectionClass($classname);
        $provider = $reflectedClass->newInstanceWithoutConstructor();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Required locales should be contained in locales');

        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke($provider, ['es', 'en'], 'en', ['en', 'pt']);
    }

    public function testGetLocales(): void
    {
        $expected = $this->provider->getLocales();
        $locales = $this->locales;

        $this->assertSame(array_diff($expected, $locales), array_diff($locales, $expected));
    }

    public function testGetDefaultLocale(): void
    {
        $expected = $this->provider->getDefaultLocale();

        $this->assertEquals($this->defaultLocale, $expected);
    }

    public function testGetRequiredLocales(): void
    {
        $expected = $this->provider->getRequiredLocales();
        $requiredLocales = $this->requiredLocales;

        $this->assertSame(array_diff($expected, $requiredLocales), array_diff($requiredLocales, $expected));
    }
}
