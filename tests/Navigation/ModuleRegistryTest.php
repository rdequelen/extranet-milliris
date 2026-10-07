<?php

declare(strict_types=1);

namespace App\Tests\Navigation;

use App\Navigation\ModuleFamily;
use App\Navigation\ModuleRegistry;
use PHPUnit\Framework\TestCase;

final class ModuleRegistryTest extends TestCase
{
    public function testCodesAreUnique(): void
    {
        $codes = array_map(
            static fn ($module) => $module->code,
            (new ModuleRegistry())->all(),
        );

        self::assertSame($codes, array_unique($codes));
    }

    public function testFindReturnsNullOnUnknownCode(): void
    {
        self::assertNull((new ModuleRegistry())->find('inconnu'));
    }

    public function testEveryDeclaredFamilyCarriesAtLeastOneModule(): void
    {
        $registry = new ModuleRegistry();

        foreach ($registry->families() as $family) {
            self::assertNotEmpty($registry->byFamily($family));
        }
    }

    /**
     * La valeur de chaque famille sert de suffixe de classe CSS
     * (`.socle-family--<valeur>`), generee depuis la carte $milliris-familles
     * de assets/styles/_brand.scss. Si ce test casse, la carte SCSS et l'enum
     * ont diverge et la couleur de branche disparait silencieusement.
     */
    public function testFamilyValuesMatchTheScssColourMap(): void
    {
        $scss = file_get_contents(__DIR__.'/../../assets/styles/_brand.scss');
        self::assertIsString($scss);

        foreach (ModuleFamily::cases() as $family) {
            self::assertStringContainsString(
                sprintf("'%s':", $family->value),
                $scss,
                sprintf('La famille « %s » n\'a pas de couleur dans _brand.scss.', $family->value),
            );
        }
    }
}
