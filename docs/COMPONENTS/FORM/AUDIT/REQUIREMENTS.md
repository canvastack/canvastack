# Requirements Document: Form Components Audit & Fixes

## Introduction

Dokumen ini mendefinisikan requirements untuk audit dan perbaikan komprehensif terhadap Form Components di Canvastack Origin framework. Audit ini mencakup file utama Objects.php dan 7 file trait Elements (Check, DateTime, File, Radio, Select, Tab, Text). Perbaikan akan mengikuti pola sukses dari audit FormObject.php yang telah meningkatkan skor dari 3.6/10 menjadi 8.6/10 (+139%).

Tujuan utama adalah memperbaiki semua isu security, code quality, dan accessibility sambil memastikan 100% backward compatibility dan tidak ada fitur yang dihapus atau berkurang.

## Glossary

- **Form_Components**: Sistem class dan trait yang menangani rendering form HTML di Canvastack Origin framework
- **Objects_Class**: Class utama `Canvastack\Canvastack\Library\Components\Form\Objects` yang menggunakan 7 traits
- **Element_Traits**: 7 trait files (Check, DateTime, File, Radio, Select, Tab, Text) yang menyediakan fungsi form elements
- **XSS**: Cross-Site Scripting vulnerability dimana user input tidak di-escape dengan benar
- **ARIA**: Accessible Rich Internet Applications attributes untuk accessibility compliance
- **Type_Hints**: PHP type declarations untuk parameters dan return values
- **Magic_Strings**: Hardcoded string values yang seharusnya dijadikan constants
- **Backward_Compatible**: Perubahan yang tidak merusak existing code yang menggunakan library ini
- **WCAG**: Web Content Accessibility Guidelines untuk accessibility compliance
- **SafeHtml_Marker**: System untuk menandai HTML yang sudah aman dari double-encoding
- **FormConstants**: Class yang berisi konstanta untuk menggantikan magic strings
- **Escape_Helper**: Function helper untuk escape HTML output secara konsisten

## Requirements

### Requirement 1: Security - XSS Protection

**User Story:** Sebagai developer, saya ingin semua output HTML di-escape dengan benar, sehingga aplikasi terlindungi dari XSS attacks.

#### Acceptance Criteria

1. WHEN user input dirender ke HTML, THE Form_Components SHALL escape semua special characters menggunakan Escape_Helper
2. WHEN label parameter diterima di drawCheckBox(), THE Form_Components SHALL escape label sebelum rendering
3. WHEN label parameter diterima di drawRadioBox(), THE Form_Components SHALL escape label sebelum rendering
4. WHEN tab label diterima di renderTab(), THE Form_Components SHALL escape label sebelum rendering
5. WHEN file value ditampilkan di inputFile(), THE Form_Components SHALL escape filename sebelum rendering
6. WHEN attributes array berisi user data, THE Form_Components SHALL escape attribute values sebelum rendering
7. WHEN class names atau IDs di-generate dari user input, THE Form_Components SHALL escape values tersebut
8. FOR ALL functions yang menghasilkan HTML output, escape semua user-controllable data SHALL be applied

### Requirement 2: Security - Input Validation

**User Story:** Sebagai developer, saya ingin input parameters divalidasi dengan benar, sehingga invalid atau malicious input ditolak.

#### Acceptance Criteria

1. WHEN file upload diterima, THE File_Trait SHALL validate file type dan size sesuai validation rules
2. WHEN MIME type dicheck di getFileType(), THE File_Trait SHALL validate bahwa MIME type adalah format yang valid
3. WHEN upload path di-set, THE File_Trait SHALL validate bahwa path tidak mengandung directory traversal attempts
4. WHEN attributes array diterima, THE Form_Components SHALL validate bahwa tidak ada dangerous event handlers (onclick, onerror, dll)
5. IF invalid input terdeteksi, THEN THE Form_Components SHALL throw InvalidArgumentException dengan descriptive message
6. WHEN encryption digunakan untuk model names, THE Objects_Class SHALL validate encrypted data sebelum decrypt

### Requirement 3: Code Quality - Type Hints

**User Story:** Sebagai developer, saya ingin semua functions memiliki type hints, sehingga IDE dapat memberikan autocomplete dan type errors dapat terdeteksi.

#### Acceptance Criteria

1. THE Objects_Class SHALL menambahkan type hints untuk semua public properties
2. THE Objects_Class SHALL menambahkan type hints untuk semua method parameters
3. THE Objects_Class SHALL menambahkan return type hints untuk semua methods
4. THE Element_Traits SHALL menambahkan type hints untuk semua method parameters
5. THE Element_Traits SHALL menambahkan return type hints untuk semua methods
6. WHEN parameter bisa multiple types, THE Form_Components SHALL menggunakan union types (string|array|false)
7. WHEN parameter optional, THE Form_Components SHALL menggunakan nullable types (?string) atau default values
8. FOR ALL 8 files (Objects.php + 7 traits), type hint coverage SHALL reach 100%

### Requirement 4: Code Quality - Constants untuk Magic Strings

**User Story:** Sebagai developer, saya ingin magic strings diganti dengan constants, sehingga code lebih maintainable dan typo-resistant.

#### Acceptance Criteria

1. THE Form_Components SHALL membuat FormComponentsConstants class untuk menyimpan semua constants
2. WHEN CSS class names digunakan (btn, form-control, chosen-select, dll), THE Form_Components SHALL menggunakan constants
3. WHEN HTML attributes digunakan (class, id, role, dll), THE Form_Components SHALL menggunakan constants
4. WHEN file paths atau folder names digunakan (thumb, assets, dll), THE Form_Components SHALL menggunakan constants
5. WHEN marker strings digunakan (--[openTabHTMLForm]--, dll), THE Form_Components SHALL menggunakan constants
6. WHEN plugin names digunakan (ckeditor, tagsinput, dll), THE Form_Components SHALL menggunakan constants
7. FOR ALL magic strings yang muncul lebih dari 2 kali, constants SHALL be created

### Requirement 5: Code Quality - PHPDoc Enhancement

**User Story:** Sebagai developer, saya ingin semua functions memiliki comprehensive PHPDoc, sehingga saya memahami cara penggunaan dan security considerations.

#### Acceptance Criteria

1. THE Form_Components SHALL menambahkan @param tags dengan type dan description untuk semua parameters
2. THE Form_Components SHALL menambahkan @return tags dengan type dan description untuk semua return values
3. THE Form_Components SHALL menambahkan @throws tags untuk exceptions yang mungkin dilempar
4. WHEN function memiliki security implications, THE Form_Components SHALL menambahkan @security tag dengan warnings
5. WHEN function complex atau memiliki side effects, THE Form_Components SHALL menambahkan detailed description
6. THE Form_Components SHALL menambahkan usage examples di PHPDoc untuk public methods
7. FOR ALL deprecated patterns atau parameters, @deprecated tag SHALL be added

### Requirement 6: Code Quality - Logic Simplification

**User Story:** Sebagai developer, saya ingin complex logic disederhanakan, sehingga code lebih readable dan maintainable.

#### Acceptance Criteria

1. WHEN nested if statements lebih dari 3 levels, THE Form_Components SHALL refactor menggunakan early returns atau extract methods
2. WHEN redundant comparisons ditemukan (true === $var), THE Form_Components SHALL simplify menjadi direct checks ($var)
3. WHEN variable names tidak descriptive ($o, $s, dll), THE Form_Components SHALL rename dengan descriptive names
4. WHEN duplicate code patterns ditemukan, THE Form_Components SHALL extract ke reusable private methods
5. WHEN complex string concatenation ditemukan, THE Form_Components SHALL consider menggunakan array join atau heredoc
6. THE Form_Components SHALL reduce cyclomatic complexity untuk functions dengan complexity > 10

### Requirement 7: Accessibility - ARIA Attributes

**User Story:** Sebagai user dengan disabilities, saya ingin form elements memiliki proper ARIA attributes, sehingga screen readers dapat membaca form dengan benar.

#### Acceptance Criteria

1. WHEN checkbox dirender, THE Check_Trait SHALL menambahkan aria-checked attribute
2. WHEN radio button dirender, THE Radio_Trait SHALL menambahkan aria-checked attribute
3. WHEN tab navigation dirender, THE Tab_Trait SHALL menambahkan aria-selected untuk active tab
4. WHEN tab navigation dirender, THE Tab_Trait SHALL menambahkan aria-controls untuk tab links
5. WHEN tab panels dirender, THE Tab_Trait SHALL menambahkan aria-labelledby untuk tab content
6. WHEN form elements disabled, THE Form_Components SHALL menambahkan aria-disabled="true"
7. WHEN required fields dirender, THE Form_Components SHALL menambahkan aria-required="true"
8. WHEN validation errors ada, THE Form_Components SHALL menambahkan aria-invalid="true" dan aria-describedby
9. WHEN alert messages dirender, THE Objects_Class SHALL menambahkan aria-live (assertive untuk errors, polite untuk info)
10. FOR ALL interactive elements, proper role attributes SHALL be added

### Requirement 8: Accessibility - Label Associations

**User Story:** Sebagai user dengan disabilities, saya ingin semua form inputs memiliki proper label associations, sehingga screen readers dapat mengidentifikasi input fields.

#### Acceptance Criteria

1. WHEN input field dirender, THE Form_Components SHALL ensure label memiliki for attribute yang match dengan input id
2. WHEN checkbox atau radio dirender tanpa visible label, THE Form_Components SHALL menambahkan aria-label
3. WHEN file input dirender, THE File_Trait SHALL ensure descriptive label atau aria-label tersedia
4. WHEN required symbol (*) ditambahkan, THE Form_Components SHALL include text alternative di aria-label
5. FOR ALL form inputs, proper label association SHALL exist

### Requirement 9: File Upload Security

**User Story:** Sebagai developer, saya ingin file uploads di-handle dengan secure, sehingga malicious files tidak dapat di-upload.

#### Acceptance Criteria

1. WHEN file upload diterima, THE File_Trait SHALL validate file extension terhadap whitelist
2. WHEN MIME type dicheck, THE File_Trait SHALL validate actual file content, bukan hanya extension
3. WHEN file disimpan, THE File_Trait SHALL generate random filename untuk prevent overwrite attacks
4. WHEN upload path dibuat, THE File_Trait SHALL validate path tidak keluar dari allowed directories
5. WHEN thumbnail dibuat, THE File_Trait SHALL validate image file sebelum processing
6. THE File_Trait SHALL set proper file permissions (0644) setelah upload
7. IF file validation fails, THEN THE File_Trait SHALL delete uploaded file dan throw exception

### Requirement 10: Path Traversal Protection

**User Story:** Sebagai developer, saya ingin file paths di-validate, sehingga attackers tidak dapat access files di luar allowed directories.

#### Acceptance Criteria

1. WHEN upload path di-set di setUploadPath(), THE File_Trait SHALL validate path tidak mengandung ../ atau ..\
2. WHEN asset path di-set di setAssetPath(), THE File_Trait SHALL validate path components
3. WHEN thumbnail path dibuat, THE File_Trait SHALL validate path berada dalam allowed base directory
4. THE File_Trait SHALL use realpath() untuk resolve symbolic links dan validate final path
5. IF path traversal attempt terdeteksi, THEN THE File_Trait SHALL throw SecurityException

### Requirement 11: SafeHtml Marker Integration

**User Story:** Sebagai developer, saya ingin HTML yang sudah safe tidak di-escape lagi, sehingga tidak terjadi double-encoding.

#### Acceptance Criteria

1. WHEN drawCheckBox() menghasilkan HTML, THE Check_Trait SHALL mark output dengan SafeHtml::mark()
2. WHEN drawRadioBox() menghasilkan HTML, THE Radio_Trait SHALL mark output dengan SafeHtml::mark()
3. WHEN inputFile() menghasilkan HTML, THE File_Trait SHALL mark output dengan SafeHtml::mark()
4. WHEN renderTab() menghasilkan HTML, THE Tab_Trait SHALL mark output dengan SafeHtml::mark()
5. WHEN HTML output akan digunakan di tempat lain, THE Form_Components SHALL check SafeHtml marker sebelum escape
6. THE Form_Components SHALL use SafeHtml::process() untuk handle marked content dengan benar

### Requirement 12: Backward Compatibility

**User Story:** Sebagai developer yang sudah menggunakan library ini, saya ingin semua existing code tetap berfungsi, sehingga tidak perlu refactor aplikasi.

#### Acceptance Criteria

1. THE Form_Components SHALL maintain semua existing public method signatures
2. THE Form_Components SHALL maintain semua existing parameter orders
3. THE Form_Components SHALL maintain semua existing default values
4. THE Form_Components SHALL maintain semua existing return value formats
5. WHEN new parameters ditambahkan, THE Form_Components SHALL make them optional dengan default values
6. WHEN internal logic diubah, THE Form_Components SHALL ensure output HTML tetap sama (kecuali security fixes)
7. THE Form_Components SHALL maintain semua existing public properties
8. FOR ALL changes, backward compatibility SHALL be 100%

### Requirement 13: Error Handling Enhancement

**User Story:** Sebagai developer, saya ingin errors di-handle dengan proper, sehingga saya dapat debug issues dengan mudah.

#### Acceptance Criteria

1. WHEN invalid parameters diterima, THE Form_Components SHALL throw InvalidArgumentException dengan descriptive message
2. WHEN file upload fails, THE File_Trait SHALL throw FileUploadException dengan error details
3. WHEN path validation fails, THE File_Trait SHALL throw SecurityException dengan attempted path
4. WHEN encryption/decryption fails, THE Objects_Class SHALL throw EncryptionException dengan context
5. THE Form_Components SHALL log security-related errors untuk monitoring
6. WHEN exceptions dilempar, THE Form_Components SHALL include relevant context data
7. THE Form_Components SHALL avoid silent failures dan always provide feedback

### Requirement 14: Code Organization

**User Story:** Sebagai developer, saya ingin code terorganisir dengan baik, sehingga mudah di-maintain dan di-extend.

#### Acceptance Criteria

1. WHEN private methods terlalu panjang (>50 lines), THE Form_Components SHALL extract ke smaller methods
2. WHEN logic bisa direuse, THE Form_Components SHALL extract ke shared private methods
3. THE Form_Components SHALL group related methods together
4. THE Form_Components SHALL order methods logically (public first, then protected, then private)
5. THE Form_Components SHALL add clear section comments untuk method groups
6. WHEN trait methods depend on Objects_Class methods, THE Form_Components SHALL document dependencies clearly

### Requirement 15: Testing Support

**User Story:** Sebagai developer, saya ingin code mudah di-test, sehingga saya dapat write unit tests dengan confidence.

#### Acceptance Criteria

1. THE Form_Components SHALL extract complex logic dari rendering methods untuk testability
2. THE Form_Components SHALL make validation logic accessible untuk testing
3. THE Form_Components SHALL avoid hard dependencies pada global functions dimana possible
4. WHEN random values di-generate, THE Form_Components SHALL allow seeding untuk reproducible tests
5. THE Form_Components SHALL document test scenarios di PHPDoc

### Requirement 16: Parser untuk Tab Rendering

**User Story:** Sebagai developer, saya ingin tab rendering logic robust, sehingga complex tab structures dapat di-handle dengan benar.

#### Acceptance Criteria

1. WHEN tab markers diparse di renderTab(), THE Tab_Trait SHALL validate marker format
2. WHEN nested tabs ditemukan, THE Tab_Trait SHALL handle atau throw clear error
3. WHEN tab content kosong, THE Tab_Trait SHALL handle gracefully
4. WHEN invalid tab structure ditemukan, THE Tab_Trait SHALL throw descriptive exception
5. THE Tab_Trait SHALL include round-trip property test: render → parse → render should produce equivalent output

### Requirement 17: File Upload Round-Trip Property

**User Story:** Sebagai developer, saya ingin file upload dan retrieval konsisten, sehingga uploaded files dapat diakses dengan benar.

#### Acceptance Criteria

1. WHEN file di-upload via fileUpload(), THE File_Trait SHALL return correct asset path
2. WHEN asset path di-generate, THE File_Trait SHALL ensure path dapat diakses via HTTP
3. WHEN thumbnail dibuat, THE File_Trait SHALL return correct thumbnail path
4. THE File_Trait SHALL ensure uploaded file path dan returned path konsisten
5. FOR ALL uploaded files, path yang dikembalikan SHALL be accessible dan valid

### Requirement 18: Validation Attributes Propagation

**User Story:** Sebagai developer, saya ingin validation rules dari controller otomatis diterapkan ke form elements, sehingga client-side validation konsisten dengan server-side.

#### Acceptance Criteria

1. WHEN validation rules di-set via setValidations(), THE Objects_Class SHALL parse rules dengan benar
2. WHEN required rule ditemukan, THE Objects_Class SHALL add required attribute ke input
3. WHEN validation attributes di-propagate, THE Objects_Class SHALL merge dengan existing attributes
4. THE Objects_Class SHALL handle validation rules untuk nested fields (checkbox arrays, dll)
5. WHEN checkValidationAttributes() dipanggil, THE Objects_Class SHALL return merged attributes dengan benar

### Requirement 19: Model Binding Security

**User Story:** Sebagai developer, saya ingin model binding secure, sehingga sensitive data tidak exposed dan tampering prevented.

#### Acceptance Criteria

1. WHEN model name di-encrypt di model(), THE Objects_Class SHALL use secure encryption
2. WHEN encrypted model name di-generate, THE Objects_Class SHALL include integrity check
3. THE Objects_Class SHALL validate encrypted model name sebelum use
4. WHEN model data di-bind, THE Objects_Class SHALL respect model's hidden attributes
5. THE Objects_Class SHALL prevent mass assignment vulnerabilities

### Requirement 20: Sync (Ajax Relational Fields) Security

**User Story:** Sebagai developer, saya ingin ajax relational fields secure, sehingga SQL injection dan data exposure prevented.

#### Acceptance Criteria

1. WHEN query di-encrypt di sync(), THE Objects_Class SHALL validate query tidak mengandung dangerous SQL
2. WHEN encrypted data dikirim ke client, THE Objects_Class SHALL ensure data integrity
3. THE Objects_Class SHALL validate source dan target field names
4. WHEN ajax request diterima, THE Objects_Class SHALL validate encrypted parameters
5. THE Objects_Class SHALL sanitize query results sebelum return ke client

## Catatan Implementasi

### Prioritas Fase

Implementasi akan dibagi menjadi fase seperti audit FormObject.php:

1. **Phase 1: Security Fixes** (Requirements 1, 2, 9, 10, 19, 20)
   - XSS protection
   - Input validation
   - File upload security
   - Path traversal protection
   - Model binding security
   - Ajax security

2. **Phase 2: Code Quality** (Requirements 3, 4, 5, 6, 14)
   - Type hints
   - Constants
   - PHPDoc
   - Logic simplification
   - Code organization

3. **Phase 3: Accessibility** (Requirements 7, 8)
   - ARIA attributes
   - Label associations

4. **Phase 4: Integration & Testing** (Requirements 11, 15, 16, 17, 18)
   - SafeHtml marker
   - Testing support
   - Parser properties
   - Round-trip properties
   - Validation propagation

5. **Phase 5: Verification** (Requirements 12, 13)
   - Backward compatibility testing
   - Error handling verification

### Success Metrics

Target improvement mengikuti pola FormObject.php:
- Security Score: 1/10 → 9/10
- Code Quality: 4/10 → 9/10
- Maintainability: 3/10 → 9/10
- Accessibility: 2/10 → 8/10
- Overall: 3.6/10 → 8.6/10 (+139%)

### Backward Compatibility Guarantee

Semua perubahan HARUS:
- Tidak mengubah public API
- Tidak menghapus fitur existing
- Tidak mengubah default behavior (kecuali security fixes)
- Tidak break existing code yang menggunakan library ini
