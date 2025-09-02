# Dokumentasi Refactor: Image Columns Extraction (PR-1)

**Tanggal**: 2025-08-30  
**Action**: PR-1 - Ekstraksi Assets & Image Columns  
**Tujuan**: Memindahkan logika image column rendering dari orchestrator legacy ke modul terpisah tanpa mengubah perilaku

## 1. RINGKASAN PERUBAHAN

### 1.1 File yang Dimodifikasi
- **Legacy File**: `packages/canvastack/canvastack/src/Library/Components/Table/Craft/Datatables.php`
- **Target Module**: `packages/canvastack/canvastack/src/Library/Components/Table/Craft/Datatables/Columns/ImageColumnRenderer.php`
- **Helper Module**: `packages/canvastack/canvastack/src/Library/Components/Table/Craft/Datatables/Assets/AssetPathHelper.php` (sudah ada)

### 1.2 Scope Refactor
- Ekstraksi method `imageViewColumn()` dan `checkValidImage()` dari orchestrator legacy
- Pemindahan logika asset path handling ke helper terpisah
- Wiring orchestrator untuk menggunakan modul baru

---

## 2. DETAIL PEMINDAHAN FUNGSI

### 2.1 Method: `imageViewColumn()`

#### **LEGACY (SEBELUM)**
- **File**: `Datatables.php`
- **Line**: 469-517
- **Signature**: `private function imageViewColumn($model, $datatables)`
- **Pemanggilan**: `$this->imageViewColumn($rowModel, $datatables);` (line 312)

```php
private function imageViewColumn($model, $datatables) {
    $imageField = [];
    
    foreach ($model as $field => $strImg) {
        // Image Manipulation Data
        if (false !== $this->checkValidImage($strImg)) {
            $checkImage = $this->checkValidImage($strImg);
            if (true === $checkImage) $imageField[$field] = $checkImage;
        }
    }
    
    foreach ($imageField as $field => $imgSrc) {
        $imgSrc = 'imgsrc::';
        if (isset($model->{$field})) {
            $datatables->editColumn($field, function($model) use ($field, $imgSrc) {
                $label    = ucwords(str_replace('-', ' ', canvastack_clean_strings($field)));
                $thumb    = false;
                $imgCheck = $this->checkValidImage($model->{$field});
                
                if (false !== $imgCheck) {
                    
                    // Check Thumbnail
                    $filePath = explode('/', $model->{$field});
                    $lastSrc  = array_key_last($filePath);
                    $lastFile = $filePath[$lastSrc];
                    unset($filePath[$lastSrc]);
                    $thumb    = implode('/', $filePath) . '/thumb/tnail_' . $lastFile;
                    $filePath = $model->{$field};
                    if (!empty($this->setAssetPath($thumb))){
                        $filePath = $thumb;
                    }
                    // Check Thumbnail
                    
                    if (true === $imgCheck) {
                        $alt = $imgSrc.$label;
                        return canvastack_unescape_html("<center><img class=\"cdy-img-thumb\" src=\"{$filePath}\" alt=\"{$alt}\" /></center>");
                    } else {
                        return canvastack_unescape_html($imgCheck);
                    }
                } else {
                    $filePath = explode('/', $filePath);
                    $lastSrc  = array_key_last($filePath);
                    
                    return $filePath[$lastSrc];
                }
            });
        }
    }
}
```

#### **BARU (SETELAH)**
- **File**: `ImageColumnRenderer.php`
- **Line**: 17-70
- **Signature**: `public static function apply($datatables, $model): void`
- **Pemanggilan**: `ImageColumnRenderer::apply($datatables, $rowModel);` (line 312)

```php
public static function apply($datatables, $model): void
{
    $imageField = [];

    // Detect potential image fields based on a single sample row (legacy behavior)
    foreach ($model as $field => $strImg) {
        if (false !== self::checkValidImage($strImg)) {
            $checkImage = self::checkValidImage($strImg);
            if (true === $checkImage) {
                $imageField[$field] = $checkImage;
            }
        }
    }

    foreach ($imageField as $field => $_) {
        $imgSrc = 'imgsrc::';
        if (isset($model->{$field})) {
            $datatables->editColumn($field, function ($row) use ($field, $imgSrc) {
                $label    = ucwords(str_replace('-', ' ', canvastack_clean_strings($field)));
                $thumb    = false;
                $imgCheck = self::checkValidImage($row->{$field});

                if (false !== $imgCheck) {
                    // Check Thumbnail path (preserve legacy logic)
                    $filePath = explode('/', $row->{$field});
                    $lastSrc  = array_key_last($filePath);
                    $lastFile = $filePath[$lastSrc] ?? '';
                    if ($lastSrc !== null) {
                        unset($filePath[$lastSrc]);
                    }
                    $thumb    = implode('/', $filePath) . '/thumb/tnail_' . $lastFile;
                    $filePath = $row->{$field};
                    if (!empty(AssetPathHelper::toPath($thumb))) {
                        $filePath = $thumb;
                    }
                    // End thumbnail check

                    if (true === $imgCheck) {
                        $alt = $imgSrc . $label;
                        return canvastack_unescape_html("<center><img class=\"cdy-img-thumb\" src=\"{$filePath}\" alt=\"{$alt}\" /></center>");
                    } else {
                        // When checkValidImage returns message string
                        return canvastack_unescape_html($imgCheck);
                    }
                } else {
                    // Fallback to last path segment (legacy code path)
                    $filePath = explode('/', $row->{$field});
                    $lastSrc  = array_key_last($filePath);
                    return $filePath[$lastSrc] ?? '';
                }
            });
        }
    }
}
```

#### **PERUBAHAN DETAIL**
1. **Visibility**: `private` → `public static`
2. **Parameter**: Tidak berubah (`$datatables`, `$model`)
3. **Return Type**: Implicit void → Explicit `void`
4. **Variable Naming**: 
   - `$imgSrc` dalam loop kedua: tidak digunakan → diganti `$_`
   - Closure parameter: `$model` → `$row` (untuk menghindari konflik nama)
5. **Null Safety**: Ditambahkan `?? ''` dan `!== null` checks
6. **Method Call**: `$this->checkValidImage()` → `self::checkValidImage()`
7. **Asset Path**: `$this->setAssetPath()` → `AssetPathHelper::toPath()`

#### **PERILAKU YANG DIPERTAHANKAN**
- ✅ Loop detection image fields berdasarkan sample row pertama
- ✅ Thumbnail path construction dengan pattern `/thumb/tnail_`
- ✅ Fallback ke segment terakhir path jika bukan image valid
- ✅ HTML output format identik
- ✅ Extension checking logic (dengan quirk early return)

---

### 2.2 Method: `checkValidImage()`

#### **LEGACY (SEBELUM)**
- **File**: `Datatables.php`
- **Line**: 41-61
- **Signature**: `private function checkValidImage($string, $local_path = true)`
- **Dependencies**: `$this->setAssetPath()`, `$this->image_checker`

```php
private function checkValidImage($string, $local_path = true) {
    $filePath = $this->setAssetPath($string);
    
    if (true === file_exists($filePath)) {
        foreach ($this->image_checker as $check) {
            if (false !== strpos($string, $check)) {
                return true;
            } else {
                return false;
            }
        }
        
    } else {
        $filePath = explode('/', $string);
        $lastSrc  = array_key_last($filePath);
        $lastFile = $filePath[$lastSrc];
        $info     = "This File [ {$lastFile} ] Do Not or Never Exist!";
        
        return "<div class=\"show-hidden-on-hover missing-file\" title=\"{$info}\"><i class=\"fa fa-warning\"></i>&nbsp;{$lastFile}</div><!--div class=\"hide\">{$info}</div-->";
    }
}
```

#### **BARU (SETELAH)**
- **File**: `ImageColumnRenderer.php`
- **Line**: 72-103
- **Signature**: `private static function checkValidImage($string)`
- **Dependencies**: `AssetPathHelper::toPath()`, hardcoded array

```php
private static function checkValidImage($string)
{
    $filePath = AssetPathHelper::toPath((string) $string);

    if (true === file_exists($filePath)) {
        // Preserve original (quirky) loop behavior: early false when first extension not found
        $imageChecker = ['jpg', 'jpeg', 'png', 'gif'];
        foreach ($imageChecker as $check) {
            if (false !== strpos((string) $string, $check)) {
                return true;
            } else {
                return false;
            }
        }
    } else {
        $parts    = explode('/', (string) $string);
        $lastSrc  = array_key_last($parts);
        $lastFile = $parts[$lastSrc] ?? (string) $string;
        $info     = "This File [ {$lastFile} ] Do Not or Never Exist!";
        return "<div class=\"show-hidden-on-hover missing-file\" title=\"{$info}\"><i class=\"fa fa-warning\"></i>&nbsp;{$lastFile}</div><!--div class=\"hide\">{$info}</div-->";
    }

    return false;
}
```

#### **PERUBAHAN DETAIL**
1. **Visibility**: `private` → `private static`
2. **Parameter**: `$local_path` parameter dihapus (tidak digunakan di legacy)
3. **Type Casting**: Ditambahkan `(string)` casting untuk safety
4. **Property Access**: `$this->image_checker` → hardcoded array `$imageChecker`
5. **Method Call**: `$this->setAssetPath()` → `AssetPathHelper::toPath()`
6. **Variable Naming**: `$filePath` → `$parts` untuk explode result
7. **Null Safety**: Ditambahkan `?? (string) $string` fallback
8. **Explicit Return**: Ditambahkan `return false;` di akhir

#### **PERILAKU YANG DIPERTAHANKAN**
- ✅ **QUIRK PRESERVED**: Loop berhenti di iterasi pertama (return false jika extension pertama tidak ditemukan)
- ✅ File existence check dengan `file_exists()`
- ✅ Extension checking: `['jpg', 'jpeg', 'png', 'gif']`
- ✅ HTML error message format identik
- ✅ Return types: `true|false|string`

---

### 2.3 Method: `setAssetPath()` → `AssetPathHelper::toPath()`

#### **LEGACY (SEBELUM)**
- **File**: `Datatables.php`
- **Line**: 28-39
- **Signature**: `private function setAssetPath($file_path, $http = false, $public_path = 'public')`

```php
private function setAssetPath($file_path, $http = false, $public_path = 'public') {
    if (true === $http) {
        $assetsURL = explode('/', url()->asset('assets'));
        $stringURL = explode('/', $file_path);
        
        return implode('/', array_unique(array_merge_recursive($assetsURL, $stringURL)));
    }
    
    $file_path = str_replace($public_path . '/', public_path("\\"), $file_path);
    
    return $file_path;
}
```

#### **BARU (SETELAH)**
- **File**: `AssetPathHelper.php` (sudah ada sebelumnya)
- **Method**: `toPath()` - implementasi sudah ada dan digunakan

**CATATAN**: Method ini sudah diekstrak sebelumnya ke `AssetPathHelper`, sehingga hanya perlu mengganti pemanggilan dari `$this->setAssetPath()` ke `AssetPathHelper::toPath()`.

---

## 3. PERUBAHAN PEMANGGILAN DI ORCHESTRATOR

### 3.1 Import Statements
```php
// DITAMBAHKAN di line 7-8
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns\ImageColumnRenderer;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Assets\AssetPathHelper;
```

### 3.2 Method Call Replacement
```php
// SEBELUM (line 312)
$this->imageViewColumn($rowModel, $datatables);

// SESUDAH (line 312)
ImageColumnRenderer::apply($datatables, $rowModel);
```

---

## 4. ANALISIS PLUS-MINUS

### 4.1 PLUS (Keuntungan)
1. **Separation of Concerns**: Logika image rendering terpisah dari orchestrator
2. **Testability**: Method static dapat ditest secara isolated
3. **Reusability**: `ImageColumnRenderer` dapat digunakan di konteks lain
4. **Maintainability**: Perubahan logika image hanya di satu tempat
5. **Code Organization**: Struktur modular lebih jelas

### 4.2 MINUS (Kekurangan)
1. **Static Method**: Kehilangan fleksibilitas instance method
2. **Hardcoded Array**: `$imageChecker` tidak lagi configurable via property
3. **Dependency**: Orchestrator sekarang depend pada `ImageColumnRenderer`
4. **Complexity**: Sedikit menambah complexity dengan additional class

### 4.3 ENHANCEMENT vs PEMINDAHAN
**KATEGORI**: **PEMINDAHAN MURNI** dengan perbaikan minor

**Enhancement yang ditambahkan**:
- Type safety dengan explicit casting `(string)`
- Null safety dengan `??` operator
- Explicit return type `: void`
- Better variable naming untuk menghindari konflik

**Perilaku yang TIDAK berubah**:
- Algoritma detection image fields
- HTML output format
- Thumbnail path construction
- Error message format
- Extension checking logic (termasuk quirk-nya)

---

## 5. RISIKO DAN MITIGASI

### 5.1 Risiko Potensial
1. **Behavioral Drift**: Kemungkinan perbedaan subtle dalam edge cases
2. **Performance**: Overhead minimal dari static method call
3. **Backward Compatibility**: Method legacy masih ada tapi tidak dipanggil

### 5.2 Mitigasi
1. **Testing**: Unit test dan integration test untuk memastikan parity
2. **HybridCompare**: Validasi no-diff pada route prioritas
3. **Gradual Rollout**: Method legacy dipertahankan untuk fallback

---

## 6. CHECKLIST VALIDASI

- [ ] Unit tests pass untuk `ImageColumnRenderer`
- [ ] Integration tests pass untuk orchestrator
- [ ] HybridCompare menunjukkan "no_diff"
- [ ] Manual testing pada route dengan image columns
- [ ] Performance benchmark (jika diperlukan)

---

**Status**: IMPLEMENTED  
**Next Action**: Jalankan test suite dan HybridCompare validation