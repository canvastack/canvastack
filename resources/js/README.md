# CanvaStack JavaScript Components

Modern, reusable JavaScript components for CanvaStack CMS.

## 📦 Components

### Filter Components

Located in `components/filter/`:

#### FilterModal
Main Alpine.js component for filter modal.

**Usage**:
```javascript
import { createFilterModal } from '@canvastack/components/filter';

Alpine.data('filterModal', () => createFilterModal({
    filters: [...],
    activeFilters: {...},
    tableName: 'users',
    connection: 'mysql',
    config: {...}
}));
```

#### FilterCache
Caching component with TTL support.

**Usage**:
```javascript
import { FilterCache } from '@canvastack/components/filter';

const cache = new FilterCache(300000, 100); // 5 min TTL, max 100 entries

cache.set('key', data);
const cached = cache.get('key');
```

#### FilterCascade
Bidirectional cascade logic for dependent filters.

**Usage**:
```javascript
import { FilterCascade } from '@canvastack/components/filter';

const cascade = new FilterCascade(filters, config, fetchOptions);

await cascade.execute(filter, value, filterValues);
```

#### FilterFlatpickr
Flatpickr integration for date filters.

**Usage**:
```javascript
import { FilterFlatpickr } from '@canvastack/components/filter';

const flatpickr = new FilterFlatpickr();

flatpickr.initAll(filters, filterValues, onChangeCallback);
```

## 🛠️ Utilities

Located in `utils/`:

#### debounce
Debounce function calls.

**Usage**:
```javascript
import { debounce } from '@canvastack/utils';

const debouncedFn = debounce(() => {
    console.log('Called after 300ms');
}, 300);
```

#### fetchWithCsrf
Fetch wrapper with automatic CSRF token.

**Usage**:
```javascript
import { fetchWithCsrf } from '@canvastack/utils';

const data = await fetchWithCsrf('/api/endpoint', {
    method: 'POST',
    body: { key: 'value' }
});
```

## 📖 Documentation

- [Refactoring Plan](../../../../../FILTER-MODAL-REFACTORING-PLAN.md)
- [Refactoring Summary](../../../../../FILTER-MODAL-REFACTORING-SUMMARY.md)
- [Phase 3 Complete](../../../../../FILTER-MODAL-PHASE-3-COMPLETE.md)
- [Quick Start Guide](../../../../../FILTER-MODAL-QUICK-START.md)

## 🧪 Testing

Run tests:
```bash
npm test
```

Run tests with coverage:
```bash
npm run test:coverage
```

Run tests with UI:
```bash
npm run test:ui
```

## 🚀 Development

Start dev server:
```bash
npm run dev
```

Build for production:
```bash
npm run build
```

## 📁 Structure

```
resources/js/
├── app.js                      # Entry point
├── components/
│   └── filter/
│       ├── FilterModal.js      # Main component (500+ lines)
│       ├── FilterCache.js      # Caching (150 lines)
│       ├── FilterCascade.js    # Cascade (350 lines)
│       ├── FilterFlatpickr.js  # Date picker (280 lines)
│       └── index.js            # Exports
└── utils/
    ├── debounce.js             # Debounce (30 lines)
    ├── fetch.js                # Fetch wrapper (60 lines)
    └── index.js                # Exports
```

## 🎯 Benefits

1. **Modular**: Each component has single responsibility
2. **Reusable**: Can be imported and used anywhere
3. **Testable**: Unit tests for each component
4. **Performant**: Minified, cached, tree-shaken
5. **Maintainable**: Clear structure, well-documented

## 📊 Performance

**Before** (Inline JavaScript):
- 2,261 lines in one file
- ~80KB unminified
- No caching

**After** (External JavaScript):
- 870 lines across 5 files
- ~25KB minified
- ~10KB gzipped
- Browser caching (1 year)

**Result**: ~70% file size reduction!

## 🔗 Links

- [Vite Configuration](../../vite.config.js)
- [Vitest Configuration](../../vitest.config.js)
- [Package.json](../../package.json)
- [Test Setup](../../tests/js/setup.js)

---

**Version**: 1.0.0  
**Last Updated**: 2026-03-10  
**Author**: CanvaStack Team
