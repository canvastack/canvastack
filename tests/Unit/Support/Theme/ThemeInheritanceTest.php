<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Support\Theme\Theme;
use Canvastack\Canvastack\Support\Theme\ThemeRepository;
use PHPUnit\Framework\TestCase;

class ThemeInheritanceTest extends TestCase
{
    public function test_theme_can_have_parent(): void
    {
        $theme = new Theme(
            name: 'child',
            displayName: 'Child Theme',
            version: '1.0.0',
            author: 'Test',
            description: 'Test theme',
            config: [],
            parent: 'parent'
        );

        $this->assertTrue($theme->hasParent());
        $this->assertEquals('parent', $theme->getParent());
    }

    public function test_theme_without_parent(): void
    {
        $theme = new Theme(
            name: 'standalone',
            displayName: 'Standalone Theme',
            version: '1.0.0',
            author: 'Test',
            description: 'Test theme',
            config: []
        );

        $this->assertFalse($theme->hasParent());
        $this->assertNull($theme->getParent());
    }

    public function test_child_theme_inherits_parent_config(): void
    {
        $parent = new Theme(
            name: 'parent',
            displayName: 'Parent Theme',
            version: '1.0.0',
            author: 'Test',
            description: 'Parent theme',
            config: [
                'colors' => [
                    'primary' => '#000000',
                    'secondary' => '#111111',
                ],
                'fonts' => [
                    'sans' => 'Arial',
                ],
            ]
        );

        $child = new Theme(
            name: 'child',
            displayName: 'Child Theme',
            version: '1.0.0',
            author: 'Test',
            description: 'Child theme',
            config: [
                'colors' => [
                    'primary' => '#ff0000',
                ],
            ],
            parent: 'parent'
        );

        $child->setParentTheme($parent);

        // Child overrides primary color
        $this->assertEquals('#ff0000', $child->get('colors.primary'));

        // Child inherits secondary color from parent
        $this->assertEquals('#111111', $child->get('colors.secondary'));

        // Child inherits fonts from parent
        $this->assertEquals('Arial', $child->get('fonts.sans'));
    }

    public function test_inheritance_chain(): void
    {
        $grandparent = new Theme(
            name: 'grandparent',
            displayName: 'Grandparent',
            version: '1.0.0',
            author: 'Test',
            description: 'Test',
            config: []
        );

        $parent = new Theme(
            name: 'parent',
            displayName: 'Parent',
            version: '1.0.0',
            author: 'Test',
            description: 'Test',
            config: [],
            parent: 'grandparent'
        );
        $parent->setParentTheme($grandparent);

        $child = new Theme(
            name: 'child',
            displayName: 'Child',
            version: '1.0.0',
            author: 'Test',
            description: 'Test',
            config: [],
            parent: 'parent'
        );
        $child->setParentTheme($parent);

        $chain = $child->getInheritanceChain();

        $this->assertEquals(['grandparent', 'parent', 'child'], $chain);
    }

    public function test_repository_resolves_inheritance(): void
    {
        $repository = new ThemeRepository();

        $parent = new Theme(
            name: 'parent',
            displayName: 'Parent',
            version: '1.0.0',
            author: 'Test',
            description: 'Test',
            config: ['colors' => ['primary' => '#000000']]
        );

        $child = new Theme(
            name: 'child',
            displayName: 'Child',
            version: '1.0.0',
            author: 'Test',
            description: 'Test',
            config: ['colors' => ['secondary' => '#111111']],
            parent: 'parent'
        );

        $repository->register($parent);
        $repository->register($child);

        $repository->resolveInheritance($child);

        $this->assertNotNull($child->getParentTheme());
        $this->assertEquals('parent', $child->getParentTheme()->getName());
    }

    public function test_merged_config(): void
    {
        $parent = new Theme(
            name: 'parent',
            displayName: 'Parent',
            version: '1.0.0',
            author: 'Test',
            description: 'Test',
            config: [
                'colors' => [
                    'primary' => '#000000',
                    'secondary' => '#111111',
                ],
                'fonts' => [
                    'sans' => 'Arial',
                ],
            ]
        );

        $child = new Theme(
            name: 'child',
            displayName: 'Child',
            version: '1.0.0',
            author: 'Test',
            description: 'Test',
            config: [
                'colors' => [
                    'primary' => '#ff0000',
                ],
            ],
            parent: 'parent'
        );

        $child->setParentTheme($parent);

        $merged = $child->getMergedConfig();

        $this->assertEquals('#ff0000', $merged['colors']['primary']);
        $this->assertEquals('#111111', $merged['colors']['secondary']);
        $this->assertEquals('Arial', $merged['fonts']['sans']);
    }
}
