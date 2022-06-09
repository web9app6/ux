<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Integration\Doctrine;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Autocomplete\Doctrine\EntityMetadata;
use Symfony\UX\Autocomplete\Doctrine\EntityMetadataFactory;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Product;
use Symfony\UX\Autocomplete\Tests\Fixtures\Factory\ProductFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class EntityMetadataTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testGetAllPropertyNames(): void
    {
        $this->assertSame(
            ['id', 'name', 'description', 'price', 'isEnabled'],
            $this->getMetadata()->getAllPropertyNames()
        );
    }

    public function testIsAssociation(): void
    {
        $metadata = $this->getMetadata();
        $this->assertFalse($metadata->isAssociation('name'));
        $this->assertTrue($metadata->isAssociation('category'));
    }

    public function testGetIdValue(): void
    {
        $product = ProductFactory::createOne();
        $this->assertEquals($product->getId(), $this->getMetadata()->getIdValue($product->object()));
    }

    public function testGetPropertyDataType(): void
    {
        $metadata = $this->getMetadata();
        $this->assertSame(Types::STRING, $metadata->getPropertyDataType('name'));
        $this->assertEquals(ClassMetadataInfo::MANY_TO_ONE, $metadata->getPropertyDataType('category'));
    }

    public function testGetPropertyMetadata(): void
    {
        $metadata = $this->getMetadata();
        $this->assertSame([
            'fieldName' => 'name',
            'type' => 'string',
            'scale' => null,
            'length' => null,
            'unique' => false,
            'nullable' => false,
            'precision' => null,
            'columnName' => 'name',
        ], $metadata->getPropertyMetadata('name'));
    }

    public function testIsEmbeddedClassProperty(): void
    {
        // TODO
        $this->markTestIncomplete();
    }

    private function getMetadata(): EntityMetadata
    {
        /** @var EntityMetadataFactory $factory */
        $factory = self::getContainer()->get('ux.autocomplete.entity_metadata_factory');

        return $factory->create(Product::class);
    }
}
