[![TYPO3 compatibility](https://img.shields.io/badge/TYPO3-13.4-ff8700?maxAge=3600&logo=typo3)](https://get.typo3.org/)
[![TYPO3 compatibility](https://img.shields.io/badge/TYPO3-14.0-ff8700?maxAge=3600&logo=typo3)](https://get.typo3.org/)


# TYPO3 extension `A11yes`


## Links

|                    | URL                                                                          |
|--------------------|------------------------------------------------------------------------------|
| **Repository:**    | https://github.com/rozumbunch/TYPO3-a11yes                                   |
| **Documentation:** | https://github.com/rozumbunch/TYPO3-a11yes                                   |
| **TER:**           | https://extensions.typo3.org/extension/a11yes                                |
| **Packagist:**     | https://packagist.org/packages/rozumbunch/a11yes                             |



**Languages:** English · [Deutsch](#deutsch)

This extension integrates the JavaScript accessibility tool **A11yes** into TYPO3.
After installation and including CSS and JavaScript via TypoScript, the tool is immediately available in the frontend – by default via an integrated, fixed button in the bottom-right corner, which can be disabled and replaced with custom buttons or menu items if needed.

Further configuration is handled via a Fluid ViewHelper and can then be adjusted in two ways:

- modern approach via **TYPO3 Site Settings / Site Settings Overview**
- classic approach via **TypoScript Constants / Constant Editor**

The active configuration source is controlled by the switch `a11yes.mode`.

## Included Files

The extension ships with a bundled version of the A11yes library. The current files are located at:

```text
Resources/Public/Contrib/a11yes.min.js
Resources/Public/Contrib/a11yes.min.css
Resources/Public/translations/de.json
Resources/Public/translations/en.json
```

A newer version of the library can be downloaded at any time from [a11yes.com](https://www.a11yes.com) and replaced in `Resources/Public/Contrib/` and `Resources/Public/translations/`.

## Frontend Behaviour

The initialization script `Resources/Public/JavaScript/a11yes.js` finds all elements with the class `a11yes-open`, reads their `data-params` attribute and starts A11yes:

```js
import { a11YesInit } from '../Contrib/a11yes.min.js';

$('.a11yes-open').each(function () {
    a11YesInit({
        openButtonClassname: 'a11yes-open',
        ...$(this).data('params')
    });
});
```

Assets are included via `Configuration/TypoScript/setup.typoscript`:

```typoscript
page {
  includeCSS {
    a11yes = EXT:a11yes/Resources/Public/Contrib/a11yes.min.css
    a11yes.media = all
  }

  includeJSFooter {
    a11yes = EXT:a11yes/Resources/Public/JavaScript/a11yes.js
    a11yes.type = module
  }
}
```

## ViewHelper and Partial

Two equivalent approaches are available for rendering the button. In addition to the integrated, fixed button in the bottom-right corner – which can be disabled via `a11yes.hideFixedButton` (Site Settings) or `plugin.tx_a11yes.behavior.showFixedButton` (TypoScript) – custom buttons or menu items can be defined in the template. Both variants below use the same configuration from Site Settings or TypoScript; the choice depends on how much control over markup and positioning is required.

### Variant 1: ViewHelper directly in the template

The ViewHelper `<rb:a11yesButton>` generates the button with the class `a11yes-open` and writes the resolved configuration as JSON into `data-params`. Configuration values are not passed as Fluid arguments, but read centrally via the `A11yesResolver`.

Register the namespace in the Fluid template:

```html
<html xmlns:rb="http://typo3.org/ns/Rozumbunch/A11yes/ViewHelpers"
      data-namespace-typo3-fluid="true">
```

Render the button with a custom icon:

```html
<div class="a11yes-trigger">
    <rb:a11yesButton>
        <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true">
            <!-- Icon SVG -->
        </svg>
    </rb:a11yesButton>
</div>
```

The content between the ViewHelper tags is rendered as the button icon. Position, spacing and additional markup are fully controlled in your own template.

### Variant 2: Include the ready-made partial

The extension provides a preconfigured partial at:

```text
Resources/Private/Partials/SiteButtonA11yes.html
```

This partial also uses `<rb:a11yesButton>` internally and positions the button fixed on the right side of the page. In the sitepackage, add the extension's partial path:

```typoscript
page.10.partialRootPaths {
    20 = EXT:a11yes/Resources/Private/Partials/
}
```

The partial can then be rendered in a layout or template:

```html
<f:render partial="SiteButtonA11yes" />
```

### Which variant when?

| Variant | Use case |
|---|---|
| **ViewHelper directly** | Custom position, custom icon, integration in header/footer or existing components |
| **Partial** | Quick integration without custom markup, standard button out of the box |

Both variants work with the same JavaScript initializer. The only requirement is that at least one element with the class `a11yes-open` and a valid `data-params` attribute is rendered in the frontend.

## Configuration Source

The source is selected via a dropdown in Site Settings:

```text
Use Site Settings
Use TypoScript constants
```

- `Use Site Settings`: Site Settings Overview
- `Use TypoScript constants`: classic TYPO3 approach via TypoScript Constants and Constant Editor

## The site set must be enabled in the site configuration:

```yaml
dependencies:
  - rozumbunch/a11yes
```

The fields then appear in the backend under:

```text
Site Management > Sites > [Site] > Settings
```

The switch is:

```yaml
a11yes.mode: siteSettings
```

## Automated rendering (without custom Fluid integration)

If you do not want to place `<rb:a11yesButton>` manually in your templates, the extension can render the default button automatically via TypoScript (`page.footerData`).

The behavior is controlled in Site Settings:

```yaml
a11yes.inclusionMode: configurable
a11yes.renderSiteButtonPartial: true
```

- `a11yes.inclusionMode = libraryOnly`: only CSS/JS assets are included, no automatic button rendering.
- `a11yes.inclusionMode = configurable`: automatic rendering can be enabled.
- `a11yes.renderSiteButtonPartial = true`: render `Resources/Private/Partials/SiteButtonA11yes.html`.
- `a11yes.renderSiteButtonPartial = false`: do not render the automatic button.

The TypoScript setup uses a condition similar to:

```typoscript
['{$a11yes.inclusionMode}' == 'configurable' && '{$a11yes.renderSiteButtonPartial}' == '1']
page.footerData.1721041300 = FLUIDTEMPLATE
page.footerData.1721041300.file = EXT:a11yes/Resources/Private/Partials/SiteButtonA11yes.html
[END]
```

This mode is useful for projects that should get a working A11yes trigger without changing existing Fluid templates.

## Classic approach: TypoScript Constants

The classic approach is maintained in `Configuration/TypoScript/constants.typoscript`.

Enable it via Site Settings:

```yaml
a11yes.mode: typoScript
```

The constants themselves no longer contain a switch. They are used when `TypoScript constants` is selected as the configuration source in Site Settings. Values still appear in the backend **Constant Editor**.

In `setup.typoscript`, these constants are mapped to `plugin.tx_a11yes.settings`. When `a11yes.mode = typoScript`, the resolver reads these mapped values.

## Translations

Translation files are located at:

```text
Resources/Public/translations/de.json
Resources/Public/translations/en.json
```

The path is configured via a pattern:

```text
../translations/{language}.json
```

`{language}` is replaced by the current TYPO3 language in the ViewHelper, e.g. `de` or `en`.

## Theme / Colour Customisation

A11yes uses CSS Custom Properties for the dialog appearance. The following variables control the colours:

| Variable | Description |
|---|---|
| `--a11yes-foreground` | Primary text colour |
| `--a11yes-secondary-foreground` | Secondary text colour |
| `--a11yes-background` | Dialog background colour |
| `--a11yes-secondary-background` | Background for input elements |
| `--a11yes-section-border` | Section border colour |
| `--a11yes-switch-background` | Switch background colour |

### Configuration via Site Settings

Theme colours are configured in the backend under **Site Management > Sites > [Site] > Settings** in the **Design** section. Each colour is available as a colour picker.

```yaml
a11yes.theme.enabled: true
a11yes.theme.foreground: '#3B1E5E'
a11yes.theme.secondaryForeground: '#6B5B8A'
a11yes.theme.background: '#F3EDFA'
a11yes.theme.secondaryBackground: '#FFFFFF'
a11yes.theme.sectionBorder: '#DCD0EC'
a11yes.theme.switchBackground: '#E5DCF0'
```

When `a11yes.theme.enabled` is active, `a11yes.js` automatically sets the values on `:root` when loading.

### Configuration via TypoScript Constants

In the Constant Editor under `plugin.tx_a11yes/theme`:

```typoscript
plugin.tx_a11yes.theme {
  enabled = 1
  foreground = #3B1E5E
  secondaryForeground = #6B5B8A
  background = #F3EDFA
  secondaryBackground = #FFFFFF
  sectionBorder = #DCD0EC
  switchBackground = #E5DCF0
}
```

Prerequisite: `a11yes.mode: typoScript` in Site Settings.

### Colour Schemes (Examples)

**Neutral (default)**

```css
:root {
    --a11yes-foreground: #292A2D;
    --a11yes-secondary-foreground: #6B6B6B;
    --a11yes-background: #EEF0F3;
    --a11yes-secondary-background: #FFF;
    --a11yes-section-border: #D8DCE1;
    --a11yes-switch-background: #E9E9EB;
}
```

**Red**

```css
:root {
    --a11yes-foreground: #7F1D1D;
    --a11yes-secondary-foreground: #8C5757;
    --a11yes-background: #FDF2F2;
    --a11yes-secondary-background: #FFFFFF;
    --a11yes-section-border: #F5C4C4;
    --a11yes-switch-background: #F4D6D6;
}
```

**Purple**

```css
:root {
    --a11yes-foreground: #3B1E5E;
    --a11yes-secondary-foreground: #6B5B8A;
    --a11yes-background: #F3EDFA;
    --a11yes-secondary-background: #FFFFFF;
    --a11yes-section-border: #DCD0EC;
    --a11yes-switch-background: #E5DCF0;
}
```

**Green**

```css
:root {
    --a11yes-foreground: #14532D;
    --a11yes-secondary-foreground: #5A7060;
    --a11yes-background: #ECFAEF;
    --a11yes-secondary-background: #FFFFFF;
    --a11yes-section-border: #C7E4D0;
    --a11yes-switch-background: #D6EBDC;
}
```

The hex values from the examples can be used directly in Site Settings or TypoScript Constants.

### Manual CSS Customisation (Sitepackage)

Alternatively, the variables can be set in the sitepackage via CSS, e.g. in a custom stylesheet:

```css
:root {
    --a11yes-foreground: #292A2D;
    --a11yes-secondary-foreground: #6B6B6B;
    --a11yes-background: #EEF0F3;
    --a11yes-secondary-background: #FFF;
    --a11yes-section-border: #D8DCE1;
    --a11yes-switch-background: #E9E9EB;
}
```

Note: When `a11yes.theme.enabled` is active, the extension configuration overrides CSS values at runtime. For pure CSS customisation in the sitepackage, keep `a11yes.theme.enabled` disabled.

The variables can also be set inline in Fluid templates or layouts:

```html
<style>
    :root {
        --a11yes-foreground: #7F1D1D;
        --a11yes-secondary-foreground: #8C5757;
        --a11yes-background: #FDF2F2;
        --a11yes-secondary-background: #FFFFFF;
        --a11yes-section-border: #F5C4C4;
        --a11yes-switch-background: #F4D6D6;
    }
</style>
```

## Notes for Developers

- The ViewHelper does not expect a direct Fluid parameter for configuration.
- Configuration is read centrally from Site Settings or TypoScript.
- The button must keep the class `a11yes-open`, as the JavaScript initializes on it.
- `fontsJson` and `linksJson` must contain valid JSON.
- After changes to `settings.definitions.yaml`, the TYPO3 cache should be cleared.

## Backend Integration

The A11yes tool is also available in the TYPO3 backend – editors can use accessibility features directly while editing content. The backend integration can be disabled via Extension Configuration if needed.

Setting in the backend under:

```text
Administration > Settings > Extension Configuration > a11yes
```

Option:

```text
backendEnabled = 1   ; A11yes active in backend (default)
backendEnabled = 0   ; Disable backend integration
```

### Known Issues

The tool is not reliably available everywhere in the backend. Affected areas include:

- some **core modules with DocHeader**
- **custom iframes**, where the DocHeader button does not work
- the **Dashboard**, where no A11yes access is currently provided

In these cases, the **toolbar button** in the top backend bar is usually still available.

---

## Deutsch

[← English version](#a11yes-typo3-integration)

# A11yes TYPO3-Integration

Diese Extension bindet das JavaScript Accessibility Tool **A11yes** in TYPO3 ein.
Nach der Installation und der Einbindung von CSS und JavaScript über TypoScript steht das Tool im Frontend sofort zur Verfügung – standardmäßig über einen integrierten, fixierten Button rechts unten, der bei Bedarf deaktiviert und durch eigene Buttons oder Menüpunkte ersetzt werden kann.

Die weitere Konfiguration erfolgt über einen Fluid ViewHelper und kann anschließend auf zwei Wegen angepasst werden:

- moderner Weg über **TYPO3 Site Settings / Site Settings Overview**
- klassischer Weg über **TypoScript Constants / Constant Editor**

Welcher Konfigurationsweg aktiv ist, wird über den Konfigurationsschalter `a11yes.mode` gesteuert.

## Enthaltene Dateien

Die Extension bringt eine gebündelte Version der A11yes-Library mit. Die aktuellen Dateien liegen unter:

```text
Resources/Public/Contrib/a11yes.min.js
Resources/Public/Contrib/a11yes.min.css
Resources/Public/translations/de.json
Resources/Public/translations/en.json
```

Eine neuere Version der Library kann jederzeit von [a11yes.com](https://www.a11yes.com) heruntergeladen und in `Resources/Public/Contrib/` sowie `Resources/Public/translations/` ersetzt werden.

## Frontend-Verhalten

Das Initialisierungs-Script `Resources/Public/JavaScript/a11yes.js` findet alle Elemente mit der Klasse `a11yes-open`, liest deren `data-params`-Attribut aus und startet A11yes:

```js
import { a11YesInit } from '../Contrib/a11yes.min.js';

$('.a11yes-open').each(function () {
    a11YesInit({
        openButtonClassname: 'a11yes-open',
        ...$(this).data('params')
    });
});
```

Die Assets werden über `Configuration/TypoScript/setup.typoscript` eingebunden:

```typoscript
page {
  includeCSS {
    a11yes = EXT:a11yes/Resources/Public/Contrib/a11yes.min.css
    a11yes.media = all
  }

  includeJSFooter {
    a11yes = EXT:a11yes/Resources/Public/JavaScript/a11yes.js
    a11yes.type = module
  }
}
```

## ViewHelper und Partial

Für die Ausgabe des Buttons stehen zwei gleichwertige Wege zur Verfügung. Zusätzlich zum integrierten, fixierten Button rechts unten – der über `a11yes.hideFixedButton` (Site Settings) bzw. `plugin.tx_a11yes.behavior.showFixedButton` (TypoScript) abgeschaltet werden kann – lassen sich eigene Buttons oder Menüpunkte im Template definieren. Beide Varianten unten nutzen dieselbe Konfiguration aus Site Settings oder TypoScript; die Wahl hängt davon ab, wie viel Kontrolle über Markup und Position gewünscht ist.

### Variante 1: ViewHelper direkt im Template

Der ViewHelper `<rb:a11yesButton>` erzeugt den Button mit der Klasse `a11yes-open` und schreibt die aufgelöste Konfiguration als JSON in `data-params`. Konfigurationswerte werden nicht als Fluid-Argumente übergeben, sondern zentral über den `A11yesResolver` gelesen.

Namespace im Fluid-Template registrieren:

```html
<html xmlns:rb="http://typo3.org/ns/Rozumbunch/A11yes/ViewHelpers"
      data-namespace-typo3-fluid="true">
```

Button mit eigenem Icon ausgeben:

```html
<div class="a11yes-trigger">
    <rb:a11yesButton>
        <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true">
            <!-- Icon-SVG -->
        </svg>
    </rb:a11yesButton>
</div>
```

Der Inhalt zwischen den ViewHelper-Tags wird als Button-Icon gerendert. Position, Abstände und zusätzliches Markup liegen vollständig im eigenen Template.

### Variante 2: Fertiges Partial einbinden

Die Extension liefert ein vorkonfiguriertes Partial mit:

```text
Resources/Private/Partials/SiteButtonA11yes.html
```

Dieses Partial nutzt intern ebenfalls `<rb:a11yesButton>` und positioniert den Button fixiert am rechten Seitenrand. Im Sitepackage wird der Partial-Pfad der Extension ergänzt:

```typoscript
page.10.partialRootPaths {
    20 = EXT:a11yes/Resources/Private/Partials/
}
```

Anschließend kann das Partial in einem Layout oder Template gerendert werden:

```html
<f:render partial="SiteButtonA11yes" />
```

### Welche Variante wann?

| Variante | Einsatz |
|---|---|
| **ViewHelper direkt** | Eigene Position, eigenes Icon, Integration in Header/Footer oder bestehende Komponenten |
| **Partial** | Schnelle Integration ohne eigenes Markup, Standard-Button out of the box |

Beide Varianten funktionieren parallel zum gleichen JavaScript-Initialisierer. Entscheidend ist nur, dass mindestens ein Element mit der Klasse `a11yes-open` und gültigem `data-params`-Attribut im Frontend ausgegeben wird.

## Konfigurationsquelle

In den Site Settings wird die Quelle über ein Dropdown entschieden:

```text
Site Settings verwenden
TypoScript-Constants verwenden
```

- `Site Settings verwenden`: Site Settings Overview
- `TypoScript-Constants verwenden`: klassischer TYPO3-Weg über TypoScript Constants und Constant Editor

## Das Set muss in der Site-Konfiguration aktiviert sein:

```yaml
dependencies:
  - rozumbunch/a11yes
```

Danach erscheinen die Felder im Backend unter:

```text
Site Management > Sites > [Site] > Settings
```

Der Umschalter ist:

```yaml
a11yes.mode: siteSettings
```

## Automatisiertes Rendering (ohne eigene Fluid-Integration)

Wenn der Button nicht manuell per `<rb:a11yesButton>` im Template gesetzt werden soll, kann die Extension den Standard-Button automatisch per TypoScript (`page.footerData`) rendern.

Gesteuert wird das in den Site Settings:

```yaml
a11yes.inclusionMode: configurable
a11yes.renderSiteButtonPartial: true
```

- `a11yes.inclusionMode = libraryOnly`: nur CSS/JS-Assets werden eingebunden, kein automatisches Button-Rendering.
- `a11yes.inclusionMode = configurable`: automatisches Rendering kann aktiviert werden.
- `a11yes.renderSiteButtonPartial = true`: rendert `Resources/Private/Partials/SiteButtonA11yes.html`.
- `a11yes.renderSiteButtonPartial = false`: rendert den automatischen Button nicht.

Im TypoScript-Setup wird dazu eine Condition wie diese verwendet:

```typoscript
['{$a11yes.inclusionMode}' == 'configurable' && '{$a11yes.renderSiteButtonPartial}' == '1']
page.footerData.1721041300 = FLUIDTEMPLATE
page.footerData.1721041300.file = EXT:a11yes/Resources/Private/Partials/SiteButtonA11yes.html
[END]
```

Dieser Modus ist sinnvoll, wenn ein funktionsfähiger A11yes-Trigger ohne Anpassungen an bestehenden Fluid-Templates benötigt wird.

## Alter Weg: TypoScript Constants

Der klassische Weg wird über `Configuration/TypoScript/constants.typoscript` gepflegt.

Aktiviert wird dieser Weg über die Site Settings:

```yaml
a11yes.mode: typoScript
```

Die Constants selbst enthalten keinen Umschalter mehr. Sie werden berücksichtigt, wenn in den Site Settings die Konfigurationsquelle `TypoScript constants` gewählt ist. Die Werte erscheinen weiterhin im Backend im **Constant Editor**.

In `setup.typoscript` werden diese Constants nach `plugin.tx_a11yes.settings` gemappt. Wenn `a11yes.mode = typoScript` ist, liest der Resolver diese gemappten Werte.

## Übersetzungen

Die Übersetzungsdateien liegen unter:

```text
Resources/Public/translations/de.json
Resources/Public/translations/en.json
```

Der Pfad wird über ein Pattern konfiguriert:

```text
../translations/{language}.json
```

`{language}` wird im ViewHelper durch die aktuelle TYPO3-Sprache ersetzt, z. B. `de` oder `en`.

## Theme / Farbanpassung

A11yes nutzt CSS Custom Properties für das Erscheinungsbild des Dialogs. Folgende Variablen steuern die Farben:

| Variable | Beschreibung |
|---|---|
| `--a11yes-foreground` | Haupttextfarbe |
| `--a11yes-secondary-foreground` | Sekundäre Textfarbe |
| `--a11yes-background` | Hintergrundfarbe des Dialogs |
| `--a11yes-secondary-background` | Hintergrund für Eingabeelemente |
| `--a11yes-section-border` | Rahmenfarbe von Abschnitten |
| `--a11yes-switch-background` | Hintergrundfarbe von Schaltern |

### Konfiguration über Site Settings

Theme-Farben werden im Backend unter **Site Management > Sites > [Site] > Settings** im Bereich **Design** konfiguriert. Jede Farbe ist als Color-Picker verfügbar.

```yaml
a11yes.theme.enabled: true
a11yes.theme.foreground: '#3B1E5E'
a11yes.theme.secondaryForeground: '#6B5B8A'
a11yes.theme.background: '#F3EDFA'
a11yes.theme.secondaryBackground: '#FFFFFF'
a11yes.theme.sectionBorder: '#DCD0EC'
a11yes.theme.switchBackground: '#E5DCF0'
```

Wenn `a11yes.theme.enabled` aktiv ist, setzt `a11yes.js` die Werte beim Laden automatisch auf `:root`.

### Konfiguration über TypoScript Constants

Im Constant Editor unter `plugin.tx_a11yes/theme`:

```typoscript
plugin.tx_a11yes.theme {
  enabled = 1
  foreground = #3B1E5E
  secondaryForeground = #6B5B8A
  background = #F3EDFA
  secondaryBackground = #FFFFFF
  sectionBorder = #DCD0EC
  switchBackground = #E5DCF0
}
```

Voraussetzung: `a11yes.mode: typoScript` in den Site Settings.

### Farbschemata (Beispiele)

**Neutral (Standard)**

```css
:root {
    --a11yes-foreground: #292A2D;
    --a11yes-secondary-foreground: #6B6B6B;
    --a11yes-background: #EEF0F3;
    --a11yes-secondary-background: #FFF;
    --a11yes-section-border: #D8DCE1;
    --a11yes-switch-background: #E9E9EB;
}
```

**Rot**

```css
:root {
    --a11yes-foreground: #7F1D1D;
    --a11yes-secondary-foreground: #8C5757;
    --a11yes-background: #FDF2F2;
    --a11yes-secondary-background: #FFFFFF;
    --a11yes-section-border: #F5C4C4;
    --a11yes-switch-background: #F4D6D6;
}
```

**Violett**

```css
:root {
    --a11yes-foreground: #3B1E5E;
    --a11yes-secondary-foreground: #6B5B8A;
    --a11yes-background: #F3EDFA;
    --a11yes-secondary-background: #FFFFFF;
    --a11yes-section-border: #DCD0EC;
    --a11yes-switch-background: #E5DCF0;
}
```

**Grün**

```css
:root {
    --a11yes-foreground: #14532D;
    --a11yes-secondary-foreground: #5A7060;
    --a11yes-background: #ECFAEF;
    --a11yes-secondary-background: #FFFFFF;
    --a11yes-section-border: #C7E4D0;
    --a11yes-switch-background: #D6EBDC;
}
```

Die Hex-Werte aus den Beispielen können direkt in Site Settings oder TypoScript Constants übernommen werden.

### Manuelle CSS-Anpassung (Sitepackage)

Alternativ können die Variablen im Sitepackage per CSS gesetzt werden, z. B. in einer eigenen Stylesheet-Datei:

```css
:root {
    --a11yes-foreground: #292A2D;
    --a11yes-secondary-foreground: #6B6B6B;
    --a11yes-background: #EEF0F3;
    --a11yes-secondary-background: #FFF;
    --a11yes-section-border: #D8DCE1;
    --a11yes-switch-background: #E9E9EB;
}
```

Hinweis: Wenn `a11yes.theme.enabled` aktiv ist, überschreibt die Extension-Konfiguration die CSS-Werte zur Laufzeit. Für reine CSS-Anpassungen im Sitepackage `a11yes.theme.enabled` deaktiviert lassen.

In Fluid-Templates oder Layouts können die Variablen auch inline gesetzt werden:

```html
<style>
    :root {
        --a11yes-foreground: #7F1D1D;
        --a11yes-secondary-foreground: #8C5757;
        --a11yes-background: #FDF2F2;
        --a11yes-secondary-background: #FFFFFF;
        --a11yes-section-border: #F5C4C4;
        --a11yes-switch-background: #F4D6D6;
    }
</style>
```

## Hinweise für Entwickler

- Der ViewHelper erwartet keinen direkten Fluid-Parameter für die Konfiguration.
- Die Konfiguration wird zentral aus Site Settings oder TypoScript gelesen.
- Der Button muss die Klasse `a11yes-open` behalten, da das JavaScript daran initialisiert.
- `fontsJson` und `linksJson` müssen gültiges JSON enthalten.
- Bei Änderungen an `settings.definitions.yaml` sollte der TYPO3-Cache geleert werden.

## Backend-Integration

Das A11yes-Tool steht auch im TYPO3-Backend zur Verfügung – Redakteure können Barrierefreiheitsfunktionen direkt beim Bearbeiten von Inhalten nutzen. Die Backend-Integration kann bei Bedarf über die Extension Configuration abgeschaltet werden.

Einstellung im Backend unter:

```text
Administration > Settings > Extension Configuration > a11yes
```

Option:

```text
backendEnabled = 1   ; A11yes im Backend aktiv (Standard)
backendEnabled = 0   ; Backend-Integration deaktivieren
```

### Bekannte Probleme

Das Tool ist im Backend nicht überall zuverlässig erreichbar. Betroffen sind unter anderem:

- einige **Core-Module mit DocHeader**
- **Custom-Iframes**, in denen der DocHeader-Button nicht greift
- das **Dashboard**, wo derzeit kein A11yes-Zugang bereitgestellt wird

In diesen Fällen bleibt der **Toolbar-Button** in der oberen Backend-Leiste in der Regel verfügbar.
