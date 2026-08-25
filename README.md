# SuluSnippetManagerBundle
![Packagist Version](https://img.shields.io/packagist/v/perspeqtive/sulu-snippet-manager-bundle)

The **Sulu Snippet Manager Bundle** adds configurable snippet-based navigation items to the Sulu Admin interface. This allows you to organize snippets by type and permissions within the native Sulu Admin UI.

<p style="display: flex; gap: 32px; justify-content: center;">
    <a href="docs/navigation-items.png" target="_blank">
        <img src="docs/navigation-items.png" style="border-radius: 3px;" alt="Navigation items">
    </a>
    <a href="docs/rights-management.png" target="_blank">
        <img src="docs/rights-management.png" style="border-radius: 3px;" alt="Rights management">
    </a>
</p>

## 🚀 Features
- Custom navigation items for different snippet types
- Optional nested navigation items (e.g. under a main “Configuration” item)
- Independent permission handling per snippet type and snippet navigation item
- Easy custom snippet list view per snippet type
- Hide default snippet area with snippet types, where a certain permission is not given

## 🛠️ Installation
### Install the bundle via composer:

```bash
composer require perspeqtive/sulu-snippet-manager-bundle
```

### Enable the bundle

Register it in your config/bundles.php:

```php
return [
// ...
    PERSPEQTIVE\SuluSnippetManagerBundle\SuluSnippetManagerBundle::class => ['all' => true],
];
```

## 🛠️ Configuration
Create a configuration file at `config/packages/sulu_snippet_manager.yaml`. Here you define how and where your snippet navigation items appear in the Sulu Admin UI.

Example configuration:
```yaml
sulu_snippet_manager:
    navigation:
        configuration:
            navigation_title: "Configuration"
            type: "configuration"
            order: 39
            icon: "su-news"
            children:
                settings:
                    navigation_title: "Settings"
                    type: "settings"
                    order: 0
                    icon: "su-settings"
                    list_view_key: "my-custom-view"
                    tabs:
                        additional:
                            form_key: "snippet_additional_data"
                            tab_title: "app.additional_data"
                account:
                    navigation_title: "Account Settings"
                    type: "account"
                    order: 1
                    icon: "su-account"
        services:
            navigation_title: "Services"
            type: "services"
            order: 41
            icon: "su-services"
```

### Configuration keys explained:

| config item       | required | description                                                                                                                                     |
|:------------------|:--------:|:------------------------------------------------------------------------------------------------------------------------------------------------|
| navigation_title  |   yes    | Label shown in the Sulu Admin navigation                                                                                                        |
| type              |   yes    | The snippet type, when it is not a nested parent item. When it is a parent item, just use an unique identifier                                  |
| order             |   yes    | Sort order position                                                                                                                             |
| icon              |    no    | Sulu icon name (e.g. su-settings, see [icon overview](https://jsdocs.sulu.io/2.5/#!/Icon))                                                      |
| children          |    no    | Nested navigation items — parent items with children act as groups without detail views, parents without children behave like normal list views |
| snippet_list_view |    no    | The custom list view xml, where the view is configured                                                                                          |  
| tabs              |    no    | Additional form tabs shown on the snippet's edit view (see below)                                                                                |

### Additional form tabs
Third-party bundles (and your own project) can attach extra form tabs to the *core* Sulu snippet
edit view. Because this bundle builds its own view tree per snippet type, those tabs are not picked
up automatically. Instead, declare them per snippet type under `tabs:` and they are rendered on the
type's edit view — and, when secured, exposed in the permission management:

```yaml
tabs:
    additional:                                 # tab key — part of the view name and security context
        form_key: "snippet_additional_data"     # a form provided by another bundle
        tab_title: "app.additional_data"        # translation key of the tab label
        tab_order: 45                            # optional, default 45
        path: "/additional"                      # optional, default "/<key>"
        secured: true                            # optional, default true — registers an own permission
        title_visible: true                      # optional, default true
    tasks:
        type: automation                         # sulu/automation-bundle task list on the snippet
```

| tab item      | required | description                                                                                                             |
|:--------------|:--------:|:------------------------------------------------------------------------------------------------------------------------|
| type          |    no    | `form` (default) or `automation`                                                                                        |
| form_key      | for `form` | Form key of the tab's form (must be provided by some bundle; this bundle only wires the view and permission)           |
| tab_title     | for `form` | Translation key of the tab label (optional for `automation`, defaults to `sulu_automation.automation`)                 |
| tab_order     |    no    | Sort order of the tab (default `45`)                                                                                    |
| path          |    no    | Route path segment of the tab (default `/<key>`)                                                                        |
| secured       |    no    | When `true` (default), registers an EDIT security context `snippet_manager.<type>_<key>` gating the tab per role         |
| title_visible |    no    | Whether the form title is shown (default `true`, `form` only)                                                          |

#### Automation tabs
`type: automation` renders the [sulu/automation-bundle](https://github.com/sulu/SuluAutomationBundle)
task list for the snippet (scheduled publish/unpublish, etc.). It requires that bundle to be
installed; otherwise a configured automation tab throws a `LogicException` at admin build time.
The underlying task operations are additionally governed by the automation bundle's own
`sulu_automation.automation.tasks` permission.

### Permissions
Each snippet automatically receives its own permission key. These permissions are independent from the global snippet permissions in Sulu.

You can assign user roles to control access (view, add, edit, delete) to each snippet separately.

Secured `tabs` add an additional EDIT permission (`snippet_manager.<type>_<key>`) under the
*Snippet Manager* section. After adding a secured tab, re-grant the permission to the relevant roles —
new contexts default to no access.

⚠️ **Important**:
Users without the required permission won’t see the corresponding navigation item in the Sulu Admin UI.

## 👩‍🍳 Contribution

Please feel free to fork and extend existing or add new features and send a pull request with your changes! To establish a consistent code quality, please provide unit tests for all your changes and adapt the documentation.
