# Canvatility (Static Facade)

Central static facade exposing utility methods for internal packages and apps.

## Methods
- elementValue(string $html, string $tag, string $attr, bool $asHTML=true): ?string
- assetBasePath(): string
- checkStringPath(string $path, bool $existCheck=false): ?string

## Notes
- Implementations live under Html/, Assets/, Url/.
- Keep behaviors identical to legacy helpers.
- Avoid adding cross-domain coupling; keep modules cohesive.