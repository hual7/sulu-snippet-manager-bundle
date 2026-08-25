<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluSnippetManagerBundle\Tests\Unit\Admin;

use PERSPEQTIVE\SuluSnippetManagerBundle\Admin\ConfiguredSnippetAdmin;
use PERSPEQTIVE\SuluSnippetManagerBundle\Security\PermissionTypes;
use PERSPEQTIVE\SuluSnippetManagerBundle\Tests\Assert\AssertView;
use PERSPEQTIVE\SuluSnippetManagerBundle\Tests\Mocks\MockFormToolbarBuilder;
use PERSPEQTIVE\SuluSnippetManagerBundle\Tests\Mocks\MockListToolbarBuilder;
use PERSPEQTIVE\SuluSnippetManagerBundle\Tests\Mocks\Sulu\MockLocalizationProvider;
use PERSPEQTIVE\SuluSnippetManagerBundle\Tests\Mocks\Sulu\MockSecurityChecker;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View\ActivityViewBuilderFactory;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactory;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\View\ReferenceViewBuilderFactory;
use Sulu\Content\Domain\Model\AuditableInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TaxonomyInterface;

class ConfiguredSnippetAdminTest extends TestCase
{
    private MockSecurityChecker $securityChecker;
    private ViewBuilderFactory $viewBuilderFactory;
    private MockLocalizationProvider $localizationProvider;
    private MockListToolbarBuilder $listToolbarBuilder;
    private MockFormToolbarBuilder $formToolbarBuilder;
    private ActivityViewBuilderFactory $activityViewBuilderFactory;
    private ReferenceViewBuilderFactory $referenceViewBuilderFactory;

    protected function setUp(): void
    {
        $this->securityChecker = new MockSecurityChecker();
        $this->viewBuilderFactory = new ViewBuilderFactory();
        $this->localizationProvider = new MockLocalizationProvider();
        $this->listToolbarBuilder = new MockListToolbarBuilder();
        $this->formToolbarBuilder = new MockFormToolbarBuilder();
        $this->activityViewBuilderFactory = new ActivityViewBuilderFactory($this->viewBuilderFactory, $this->securityChecker);
        $this->referenceViewBuilderFactory = new ReferenceViewBuilderFactory($this->viewBuilderFactory, $this->securityChecker);
    }

    public function testConfigureNavigationItemsWithoutPermission(): void
    {
        $this->securityChecker->hasPermission = [];
        $admin = $this->buildAdmin('testsnippet', 'My Title', 20, 'su-snippet');
        $navigationItemCollection = new NavigationItemCollection();
        $admin->configureNavigationItems($navigationItemCollection);

        self::assertCount(0, $navigationItemCollection->all());
    }

    public function testConfigureNavigationItemWithParentNavigationNotFound(): void
    {
        $admin = $this->buildAdmin('testsnippet', 'My Title', 20, 'su-snippet', 'parentNavigation');
        $navigationItemCollection = new NavigationItemCollection();
        $admin->configureNavigationItems($navigationItemCollection);

        self::assertCount(0, $navigationItemCollection->all());
    }

    public function testConfigureNavigationItemIsBuild(): void
    {
        $admin = $this->buildAdmin('testsnippet', 'My Title', 20, 'su-snippet');
        $navigationItemCollection = new NavigationItemCollection();
        $admin->configureNavigationItems($navigationItemCollection);

        $items = $navigationItemCollection->all();
        self::assertCount(1, $items);
        self::assertArrayHasKey('My Title', $items);
        $item = $items['My Title'];
        self::assertSame('My Title', $item->getName());
        self::assertSame(20, $item->getPosition());
        self::assertSame('su-snippet', $item->getIcon());
        self::assertSame('sulu_snippet_manager_testsnippet.list', $item->getView());
    }

    public function testConfigureNavigationItemIsBuildUnderParent(): void
    {
        $admin = $this->buildAdmin('testsnippet', 'My Title', 20, 'su-snippet', 'parentNavigation');
        $navigationItemCollection = new NavigationItemCollection();
        $navigationItemCollection->add(new NavigationItem('parentNavigation'));
        $admin->configureNavigationItems($navigationItemCollection);

        $parentItems = $navigationItemCollection->all();
        self::assertCount(1, $parentItems);
        self::assertArrayHasKey('parentNavigation', $parentItems);
        $items = $parentItems['parentNavigation']->getChildren();
        self::assertCount(1, $items);
        $item = $items[0];
        self::assertSame('My Title', $item->getName());
        self::assertSame(20, $item->getPosition());
        self::assertSame('su-snippet', $item->getIcon());
        self::assertSame('sulu_snippet_manager_testsnippet.list', $item->getView());
    }

    public function testConfigureViewCollection(): void
    {
        $admin = $this->buildAdmin('testsnippet', 'My Title', 20, 'su-snippet', 'parentNavigation', 'my_list_view');
        $navigationItemCollection = new NavigationItemCollection();
        $navigationItemCollection->add(new NavigationItem('parentNavigation'));
        $viewCollection = new ViewCollection();
        $admin->configureViews($viewCollection);

        $views = $viewCollection->all();

        self::assertCount(10, $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.edit', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.edit.details', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.add', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.add.details', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.list', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.excerpt', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.settings', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.insights.activity', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.insights.reference', $views);

        $editView = $views['sulu_snippet_manager_testsnippet.edit'];
        AssertView::assertResourceView([
            'name' => 'sulu_snippet_manager_testsnippet.edit',
            'path' => '/testsnippet-snippets/:locale/:id',
            'routerAttributesToBackView' => ['locale'],
            'backView' => 'sulu_snippet_manager_testsnippet.list',
            'locales' => ['de', 'en'],
        ], $editView->getView());

        $addView = $views['sulu_snippet_manager_testsnippet.add'];
        AssertView::assertResourceView([
            'name' => 'sulu_snippet_manager_testsnippet.add',
            'path' => '/testsnippet-snippets/:locale/add',
            'backView' => 'sulu_snippet_manager_testsnippet.list',
            'locales' => ['de', 'en'],
        ], $addView->getView());

        $listView = $views['sulu_snippet_manager_testsnippet.list'];
        AssertView::assertListView([
            'name' => 'sulu_snippet_manager_testsnippet.list',
            'path' => '/testsnippet-snippets/:locale',
            'resourceKey' => 'snippets',
            'listKey' => 'my_list_view',
            'title' => 'Testsnippet Administration',
            'routerAttributesToListRequest' => ['locale'],
            'editView' => 'sulu_snippet_manager_testsnippet.edit',
            'addView' => 'sulu_snippet_manager_testsnippet.add',
            'toolbarActions' => ['save', 'delete'],
            'locales' => ['de', 'en'],
            'requestParameters' => ['types' => 'testsnippet', 'templateKeys' => 'testsnippet'],
        ], $listView->getView());

        $editFormView = $views['sulu_snippet_manager_testsnippet.edit.details'];
        AssertView::assertFormView([
            'name' => 'sulu_snippet_manager_testsnippet.edit.details',
            'path' => '/details',
            'resourceKey' => 'snippets',
            'formKey' => 'snippets',
            'editView' => 'sulu_snippet_manager_testsnippet.edit',
            'toolbarActions' => ['save', 'delete'],
            'parent' => 'sulu_snippet_manager_testsnippet.edit',
        ], $editFormView->getView());

        $addFormView = $views['sulu_snippet_manager_testsnippet.add.details'];
        AssertView::assertFormView([
            'name' => 'sulu_snippet_manager_testsnippet.add.details',
            'path' => '/details',
            'resourceKey' => 'snippets',
            'formKey' => 'snippets',
            'editView' => 'sulu_snippet_manager_testsnippet.edit',
            'toolbarActions' => ['save', 'delete'],
            'metadataRequestParameters' => ['overwriteDefaultType' => 'testsnippet'],
            'parent' => 'sulu_snippet_manager_testsnippet.add',
        ], $addFormView->getView());
    }

    public function testConfigureViewCollectionHasNoInsights(): void
    {
        $this->securityChecker->hasPermission = ['snippet_manager.testsnippet' => [
            PermissionTypes::VIEW => true,
            PermissionTypes::EDIT => true,
            PermissionTypes::ADD => true,
        ],
            'snippet_manager.testsnippet_excerpt' => [PermissionTypes::EDIT => true],
            'snippet_manager.testsnippet_settings' => [PermissionTypes::EDIT => true],
        ];
        $admin = $this->buildAdmin('testsnippet', 'My Title', 20, 'su-snippet', 'parentNavigation');
        $navigationItemCollection = new NavigationItemCollection();
        $navigationItemCollection->add(new NavigationItem('parentNavigation'));
        $viewCollection = new ViewCollection();
        $admin->configureViews($viewCollection);

        $views = $viewCollection->all();

        self::assertCount(7, $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.edit', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.edit.details', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.add', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.add.details', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.list', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.excerpt', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.settings', $views);
    }

    public function testConfigureViewCollectionHasNoSettings(): void
    {
        $this->securityChecker->hasPermission = ['snippet_manager.testsnippet' => [
            PermissionTypes::VIEW => true,
            PermissionTypes::EDIT => true,
            PermissionTypes::ADD => true,
        ],
            'sulu.references.references' => ['*' => true],
            'sulu.activities.activities' => ['*' => true],
            'snippet_manager.testsnippet_excerpt' => [PermissionTypes::EDIT => true],
            'snippet_manager.testsnippet_insights' => [PermissionTypes::EDIT => true],
        ];
        $admin = $this->buildAdmin('testsnippet', 'My Title', 20, 'su-snippet', 'parentNavigation');
        $navigationItemCollection = new NavigationItemCollection();
        $navigationItemCollection->add(new NavigationItem('parentNavigation'));
        $viewCollection = new ViewCollection();
        $admin->configureViews($viewCollection);

        $views = $viewCollection->all();

        self::assertCount(9, $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.edit', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.edit.details', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.add', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.add.details', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.list', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.excerpt', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.insights', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.insights.activity', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.insights.reference', $views);
    }

    public function testConfigureViewCollectionHasNoTaxonomies(): void
    {
        $this->securityChecker->hasPermission = ['snippet_manager.testsnippet' => [
            PermissionTypes::VIEW => true,
            PermissionTypes::EDIT => true,
            PermissionTypes::ADD => true,
        ],
            'snippet_manager.testsnippet_insights' => [PermissionTypes::EDIT => true],
            'snippet_manager.testsnippet_settings' => [PermissionTypes::EDIT => true],
            'sulu.references.references' => ['*' => true],
            'sulu.activities.activities' => ['*' => true],
        ];
        $admin = $this->buildAdmin('testsnippet', 'My Title', 20, 'su-snippet', 'parentNavigation');
        $navigationItemCollection = new NavigationItemCollection();
        $navigationItemCollection->add(new NavigationItem('parentNavigation'));
        $viewCollection = new ViewCollection();
        $admin->configureViews($viewCollection);

        $views = $viewCollection->all();

        self::assertCount(9, $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.edit', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.edit.details', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.add', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.add.details', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.list', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.insights', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.settings', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.insights.activity', $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.insights.reference', $views);
    }

    public function testConfigureViewCollectionWithoutPermission(): void
    {
        $this->securityChecker->hasPermission = [];
        $admin = $this->buildAdmin('testsnippet', 'My Title', 20, 'su-snippet', 'parentNavigation');
        $navigationItemCollection = new NavigationItemCollection();
        $navigationItemCollection->add(new NavigationItem('parentNavigation'));
        $viewCollection = new ViewCollection();
        $admin->configureViews($viewCollection);

        $views = $viewCollection->all();
        self::assertCount(0, $views);
    }

    public function testConfigureViewCollectionWithSecuredTab(): void
    {
        $admin = $this->buildAdmin('testsnippet', 'My Title', 20, 'su-snippet', 'parentNavigation', 'snippets', [
            'additional' => [
                'form_key' => 'snippet_additional_data',
                'tab_title' => 'app.additional_data',
                'tab_order' => 45,
                'path' => null,
                'secured' => true,
                'title_visible' => true,
            ],
        ]);
        $viewCollection = new ViewCollection();
        $admin->configureViews($viewCollection);

        $views = $viewCollection->all();

        self::assertCount(11, $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.edit.additional', $views);

        $tabView = $views['sulu_snippet_manager_testsnippet.edit.additional']->getView();
        AssertView::assertFormView([
            'name' => 'sulu_snippet_manager_testsnippet.edit.additional',
            'path' => '/additional',
            'resourceKey' => 'snippets',
            'toolbarActions' => ['save', 'delete'],
            'parent' => 'sulu_snippet_manager_testsnippet.edit',
        ], $tabView);
        self::assertSame('snippet_additional_data', $tabView->getOption('formKey'));
        self::assertSame('app.additional_data', $tabView->getOption('tabTitle'));
        self::assertSame(45, $tabView->getOption('tabOrder'));
        self::assertTrue($tabView->getOption('titleVisible'));
    }

    public function testConfigureViewCollectionUsesCustomPath(): void
    {
        $admin = $this->buildAdmin('testsnippet', 'My Title', 20, 'su-snippet', 'parentNavigation', 'snippets', [
            'additional' => [
                'form_key' => 'snippet_additional_data',
                'tab_title' => 'app.additional_data',
                'path' => '/custom-path',
            ],
        ]);
        $viewCollection = new ViewCollection();
        $admin->configureViews($viewCollection);

        $tabView = $viewCollection->all()['sulu_snippet_manager_testsnippet.edit.additional']->getView();
        self::assertSame('/custom-path', $tabView->getPath());
    }

    public function testConfigureViewCollectionHidesSecuredTabWithoutPermission(): void
    {
        $this->securityChecker->hasPermission = [
            'snippet_manager.testsnippet' => [
                PermissionTypes::VIEW => true,
                PermissionTypes::EDIT => true,
                PermissionTypes::ADD => true,
            ],
            'snippet_manager.testsnippet_excerpt' => [PermissionTypes::EDIT => true],
            'snippet_manager.testsnippet_settings' => [PermissionTypes::EDIT => true],
            'snippet_manager.testsnippet_insights' => [PermissionTypes::EDIT => true],
            'sulu.references.references' => ['*' => true],
            'sulu.activities.activities' => ['*' => true],
            // no permission for "snippet_manager.testsnippet_additional"
        ];
        $admin = $this->buildAdmin('testsnippet', 'My Title', 20, 'su-snippet', 'parentNavigation', 'snippets', [
            'additional' => [
                'form_key' => 'snippet_additional_data',
                'tab_title' => 'app.additional_data',
                'secured' => true,
            ],
        ]);
        $viewCollection = new ViewCollection();
        $admin->configureViews($viewCollection);

        $views = $viewCollection->all();

        self::assertCount(10, $views);
        self::assertArrayNotHasKey('sulu_snippet_manager_testsnippet.edit.additional', $views);
    }

    public function testConfigureViewCollectionShowsUnsecuredTabWithoutTabPermission(): void
    {
        $this->securityChecker->hasPermission = [
            'snippet_manager.testsnippet' => [
                PermissionTypes::VIEW => true,
                PermissionTypes::EDIT => true,
                PermissionTypes::ADD => true,
            ],
            'snippet_manager.testsnippet_excerpt' => [PermissionTypes::EDIT => true],
            'snippet_manager.testsnippet_settings' => [PermissionTypes::EDIT => true],
            'snippet_manager.testsnippet_insights' => [PermissionTypes::EDIT => true],
            'sulu.references.references' => ['*' => true],
            'sulu.activities.activities' => ['*' => true],
        ];
        $admin = $this->buildAdmin('testsnippet', 'My Title', 20, 'su-snippet', 'parentNavigation', 'snippets', [
            'public' => [
                'form_key' => 'snippet_public_data',
                'tab_title' => 'app.public_data',
                'secured' => false,
            ],
        ]);
        $viewCollection = new ViewCollection();
        $admin->configureViews($viewCollection);

        $views = $viewCollection->all();

        self::assertCount(11, $views);
        self::assertArrayHasKey('sulu_snippet_manager_testsnippet.edit.public', $views);
    }

    public function testGetSecurityContextWithSecuredTabs(): void
    {
        $admin = $this->buildAdmin('testsnippet', 'My Title', 20, 'su-snippet', 'parentNavigation', 'snippets', [
            'additional' => [
                'form_key' => 'snippet_additional_data',
                'tab_title' => 'app.additional_data',
                'secured' => true,
            ],
            'public' => [
                'form_key' => 'snippet_public_data',
                'tab_title' => 'app.public_data',
                'secured' => false,
            ],
        ]);

        $contexts = $admin->getSecurityContexts()['Sulu']['Snippet Manager'];

        self::assertArrayHasKey('snippet_manager.testsnippet_additional', $contexts);
        self::assertSame([PermissionTypes::EDIT], $contexts['snippet_manager.testsnippet_additional']);
        self::assertArrayNotHasKey('snippet_manager.testsnippet_public', $contexts);
    }

    public function testGetSecurityContext(): void
    {
        $expected = [
            'Sulu' => [
                'Snippet Manager' => [
                    'snippet_manager.testsnippet' => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                    'snippet_manager.testsnippet_excerpt' => [
                        PermissionTypes::EDIT,
                    ],
                    'snippet_manager.testsnippet_settings' => [
                        PermissionTypes::EDIT,
                    ],
                    'snippet_manager.testsnippet_insights' => [
                        PermissionTypes::EDIT,
                    ],
                    'snippet_manager.testsnippet_default_snippets' => [
                        PermissionTypes::EDIT,
                    ],
                ],
            ],
        ];

        $this->securityChecker->hasPermission = [];
        $admin = $this->buildAdmin('testsnippet', 'My Title', 20, 'su-snippet', 'parentNavigation');
        $context = $admin->getSecurityContexts();

        self::assertSame($expected, $context);
    }

    /**
     * @param array<string, array{form_key: string, tab_title: string, tab_order?: int, path?: string|null, secured?: bool, title_visible?: bool}> $tabs
     */
    private function buildAdmin(
        string $snippetType,
        string $navigationTitle,
        int $position = 10,
        string $icon = 'su-icon',
        ?string $parentNavigation = null,
        string $listViewKey = 'snippets',
        array $tabs = [],
    ): ConfiguredSnippetAdmin {
        return new ConfiguredSnippetAdmin(
            $this->viewBuilderFactory,
            $this->securityChecker,
            $this->localizationProvider,
            $this->formToolbarBuilder,
            $this->listToolbarBuilder,
            $this->activityViewBuilderFactory,
            $this->referenceViewBuilderFactory,
            [
                'excerpt-form1' => ['instanceOf' => TaxonomyInterface::class],
                'excerpt-form2' => ['instanceOf' => ExcerptInterface::class],
            ],
            [
                'settings-form1' => ['instanceOf' => AuditableInterface::class],
                'settings-form2' => ['instanceOf' => ShadowInterface::class],
            ],
            $snippetType,
            $navigationTitle,
            $listViewKey,
            $position,
            $icon,
            $parentNavigation,
            $tabs,
        );
    }
}
